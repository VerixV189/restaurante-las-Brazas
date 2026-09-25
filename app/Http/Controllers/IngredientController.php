<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Supplier;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IngredientController extends Controller
{
    public function index(): Response
    {
        $ingredients = Ingredient::with('supplier')->latest()->get();
        $suppliers = Supplier::all();

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $ingredient = Ingredient::create($validated);

        if ($ingredient->current_stock > 0) {
            InventoryMovement::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => Auth::id(),
                'movement_type' => 'in',
                'quantity' => $ingredient->current_stock,
                'reason' => 'Stock inicial de registro',
            ]);
        }

        return redirect()->back()->with('success', 'Insumo creado correctamente.');
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $ingredient->update($validated);

        return redirect()->back()->with('success', 'Insumo actualizado correctamente.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()->back()->with('success', 'Insumo eliminado correctamente.');
    }
}
