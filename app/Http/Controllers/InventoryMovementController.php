<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Ingredient;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryMovementController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(): Response
    {
        $movements = InventoryMovement::with(['ingredient.supplier', 'user'])
            ->latest()
            ->get();

        $ingredients = Ingredient::orderBy('name')->get();

        $summary = [
            'total_movements' => InventoryMovement::count(),
            'total_in' => InventoryMovement::where('movement_type', 'in')->count(),
            'total_out' => InventoryMovement::where('movement_type', 'out')->count(),
            'total_waste' => InventoryMovement::where('movement_type', 'waste')->count(),
            'total_adjustment' => InventoryMovement::where('movement_type', 'adjustment')->count(),
        ];

        return Inertia::render('InventoryMovements/Index', [
            'movements' => $movements,
            'ingredients' => $ingredients,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'movement_type' => 'required|in:in,out,adjustment,waste',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->inventoryService->registerManualMovement(
            ingredientId: (int) $validated['ingredient_id'],
            type: $validated['movement_type'],
            quantity: (float) $validated['quantity'],
            reason: $validated['reason'] ?? null,
            userId: Auth::id()
        );

        return redirect()->back()->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
