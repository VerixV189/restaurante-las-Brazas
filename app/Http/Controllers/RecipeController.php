<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Dish;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(): Response
    {
        $recipes = Recipe::with(['dish.category', 'ingredients.ingredient.supplier'])
            ->latest()
            ->get();

        // Platillos que aún no tienen receta para el selector de creación
        $dishes = Dish::with('category')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes,
            'dishes' => $dishes,
            'ingredients' => $ingredients,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dish_id' => 'required|exists:dishes,id|unique:recipes,dish_id',
            'instructions' => 'nullable|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity_required' => 'required|numeric|min:0.001',
            'ingredients.*.unit_of_measure' => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($validated) {
            $recipe = Recipe::create([
                'dish_id' => $validated['dish_id'],
                'instructions' => $validated['instructions'] ?? null,
            ]);

            foreach ($validated['ingredients'] as $item) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity_required' => $item['quantity_required'],
                    'unit_of_measure' => $item['unit_of_measure'],
                ]);
            }
        });

        return redirect()->back()->with('success', 'Receta técnica creada exitosamente.');
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'instructions' => 'nullable|string',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity_required' => 'required|numeric|min:0.001',
            'ingredients.*.unit_of_measure' => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($recipe, $validated) {
            $recipe->update([
                'instructions' => $validated['instructions'] ?? null,
            ]);

            // Recrear los ingredientes de la receta
            $recipe->ingredients()->delete();

            foreach ($validated['ingredients'] as $item) {
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity_required' => $item['quantity_required'],
                    'unit_of_measure' => $item['unit_of_measure'],
                ]);
            }
        });

        return redirect()->back()->with('success', 'Receta técnica actualizada exitosamente.');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return redirect()->back()->with('success', 'Receta eliminada exitosamente.');
    }
}
