<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    /**
     * Obtener el catálogo de categorías activas para el menú móvil.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->withCount(['dishes' => function ($q) {
                $q->where('is_available', true);
            }])
            ->get(['id', 'name', 'slug', 'description', 'icon']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Obtener los platillos disponibles, con soporte para filtrado por categoría o búsqueda.
     */
    public function dishes(Request $request): JsonResponse
    {
        $query = Dish::with('category:id,name,slug')
            ->where('is_available', true);

        // Filtro por categoría (ID o slug)
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // Búsqueda por nombre o descripción
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }

        $dishes = $query->orderBy('name')->get();

        // Formatear la URL de la imagen para que Flutter pueda mostrarla directamente
        $formatted = $dishes->map(function ($dish) {
            $imageUrl = $dish->image_url;

            if (empty($imageUrl)) {
                if (stripos($dish->name, 'arrachera') !== false) {
                    $imageUrl = url('/images/arrachera.png');
                } else {
                    $imageUrl = 'https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=600&auto=format&fit=crop';
                }
            } elseif (! str_starts_with($imageUrl, 'http')) {
                $imageUrl = url($imageUrl);
            }

            // Opciones de personalización gastronómica para asador (RF-02)
            $isMeat = stripos($dish->name, 'corte') !== false 
                   || stripos($dish->name, 'arrachera') !== false
                   || stripos($dish->name, 'bife') !== false
                   || stripos($dish->name, 'parrillada') !== false
                   || (optional($dish->category)->slug === 'cortes-y-parrilladas');

            return [
                'id' => $dish->id,
                'category_id' => $dish->category_id,
                'category_name' => optional($dish->category)->name,
                'name' => $dish->name,
                'description' => $dish->description,
                'price' => (float) $dish->price,
                'image_url' => $imageUrl,
                'prep_time_minutes' => $dish->prep_time_minutes,
                'is_available' => (bool) $dish->is_available,
                'is_meat' => $isMeat,
                'meat_cooking_terms' => $isMeat ? [
                    'Vuelta y vuelta (Sellado)',
                    'Término Medio (A punto)',
                    'Tres Cuartos (Jugoso)',
                    'Bien Cocido'
                ] : [],
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $formatted->count(),
            'data' => $formatted,
        ]);
    }

    /**
     * Obtener el detalle individual de un platillo.
     */
    public function show(int $id): JsonResponse
    {
        $dish = Dish::with(['category', 'recipe.recipeIngredients.ingredient'])->find($id);

        if (! $dish) {
            return response()->json([
                'success' => false,
                'message' => 'Platillo no encontrado.',
            ], 404);
        }

        $imageUrl = $dish->image_url;
        if (empty($imageUrl)) {
            $imageUrl = url('/images/arrachera.png');
        } elseif (! str_starts_with($imageUrl, 'http')) {
            $imageUrl = url($imageUrl);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $dish->id,
                'category' => $dish->category,
                'name' => $dish->name,
                'description' => $dish->description,
                'price' => (float) $dish->price,
                'image_url' => $imageUrl,
                'prep_time_minutes' => $dish->prep_time_minutes,
                'is_available' => (bool) $dish->is_available,
            ],
        ]);
    }
}
