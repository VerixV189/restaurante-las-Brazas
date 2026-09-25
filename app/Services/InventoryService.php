<?php

namespace App\Services;

use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Descuenta automáticamente los ingredientes de la receta de un platillo vendido o servido.
     *
     * @param Dish $dish
     * @param int|float $quantity Cantidad de platillos servidos
     * @param int|null $userId ID del usuario que registra la acción (mesero o chef)
     * @param string|null $reason Motivo o referencia de la comanda
     * @return array Resumen de insumos descontados
     */
    public function deductForDish(Dish $dish, int|float $quantity = 1, ?int $userId = null, ?string $reason = null): array
    {
        // Cargar la receta con sus ingredientes
        $dish->loadMissing('recipe.ingredients.ingredient');

        if (!$dish->recipe || $dish->recipe->ingredients->isEmpty()) {
            return [
                'status' => 'no_recipe',
                'message' => "El platillo {$dish->name} no tiene ficha técnica o receta configurada.",
                'deductions' => []
            ];
        }

        $deductions = [];

        DB::transaction(function () use ($dish, $quantity, $userId, $reason, &$deductions) {
            foreach ($dish->recipe->ingredients as $recipeItem) {
                $ingredient = $recipeItem->ingredient;
                if (!$ingredient) continue;

                $amountToDeduct = (float) $recipeItem->quantity_required * (float) $quantity;
                $newStock = max(0, (float) $ingredient->current_stock - $amountToDeduct);

                // Actualizar stock en la base de datos
                $ingredient->update(['current_stock' => $newStock]);

                // Registrar en el historial de movimientos de inventario
                $movement = InventoryMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'user_id' => $userId,
                    'movement_type' => 'out',
                    'quantity' => $amountToDeduct,
                    'reason' => $reason ?: "Consumo por preparación de {$quantity}x {$dish->name}",
                ]);

                $deductions[] = [
                    'ingredient_id' => $ingredient->id,
                    'ingredient_name' => $ingredient->name,
                    'deducted' => $amountToDeduct,
                    'unit' => $recipeItem->unit_of_measure,
                    'remaining_stock' => $newStock,
                    'is_low_stock' => $newStock <= (float) $ingredient->minimum_stock,
                ];

                // Si el stock cae por debajo del mínimo, registramos alerta en el log
                if ($newStock <= (float) $ingredient->minimum_stock) {
                    Log::warning("⚠️ ALERTA DE STOCK BAJO: El insumo '{$ingredient->name}' tiene {$newStock} {$ingredient->unit_of_measure} (Mínimo: {$ingredient->minimum_stock}).");
                }
            }
        });

        return [
            'status' => 'success',
            'message' => "Se descontaron {$dish->recipe->ingredients->count()} insumos correctamente.",
            'deductions' => $deductions,
        ];
    }

    /**
     * Registra un movimiento manual de inventario (Entrada de compra, Merma/Desperdicio, Ajuste).
     *
     * @param int $ingredientId
     * @param string $type in|out|adjustment|waste
     * @param float $quantity
     * @param string|null $reason
     * @param int|null $userId
     * @return InventoryMovement
     */
    public function registerManualMovement(
        int $ingredientId,
        string $type,
        float $quantity,
        ?string $reason = null,
        ?int $userId = null
    ): InventoryMovement {
        return DB::transaction(function () use ($ingredientId, $type, $quantity, $reason, $userId) {
            $ingredient = Ingredient::findOrFail($ingredientId);
            $currentStock = (float) $ingredient->current_stock;
            $finalQty = $quantity;

            switch ($type) {
                case 'in':
                    $newStock = $currentStock + $quantity;
                    break;

                case 'out':
                case 'waste':
                    $newStock = max(0, $currentStock - $quantity);
                    break;

                case 'adjustment':
                    // En ajuste, la cantidad especificada es el nuevo stock real físico
                    $newStock = max(0, $quantity);
                    $finalQty = abs($newStock - $currentStock);
                    break;

                default:
                    throw new \InvalidArgumentException("Tipo de movimiento inválido: {$type}");
            }

            $ingredient->update(['current_stock' => $newStock]);

            return InventoryMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => $userId,
                'movement_type' => $type,
                'quantity' => $finalQty,
                'reason' => $reason ?: match ($type) {
                    'in' => 'Entrada / Compra de insumo',
                    'out' => 'Salida manual',
                    'waste' => 'Merma / Caducidad / Desperdicio',
                    'adjustment' => "Ajuste físico de stock a {$newStock} {$ingredient->unit_of_measure}",
                },
            ]);
        });
    }

    /**
     * Verifica si hay suficiente stock para preparar un platillo.
     *
     * @param Dish $dish
     * @param int $portions
     * @return bool
     */
    public function canPrepareDish(Dish $dish, int $portions = 1): bool
    {
        $dish->loadMissing('recipe.ingredients.ingredient');

        if (!$dish->recipe || $dish->recipe->ingredients->isEmpty()) {
            return true;
        }

        foreach ($dish->recipe->ingredients as $item) {
            $ingredient = $item->ingredient;
            if (!$ingredient) return false;

            $needed = (float) $item->quantity_required * $portions;
            if ((float) $ingredient->current_stock < $needed) {
                return false;
            }
        }

        return true;
    }
}
