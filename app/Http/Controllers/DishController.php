<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DishController extends Controller
{
    public function index(): Response
    {
        $dishes = Dish::with('category')->latest()->get();
        $categories = Category::where('is_active', true)->get();

        return Inertia::render('Dishes/Index', [
            'dishes' => $dishes,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'prep_time_minutes' => 'required|integer|min:1',
            'is_available' => 'boolean',
            'image_url' => 'nullable|string',
        ]);

        Dish::create($validated);

        return redirect()->back()->with('success', 'Platillo registrado correctamente.');
    }

    public function update(Request $request, Dish $dish)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'prep_time_minutes' => 'required|integer|min:1',
            'is_available' => 'boolean',
            'image_url' => 'nullable|string',
        ]);

        $dish->update($validated);

        return redirect()->back()->with('success', 'Platillo actualizado correctamente.');
    }

    public function destroy(Dish $dish)
    {
        $dish->delete();

        return redirect()->back()->with('success', 'Platillo eliminado correctamente.');
    }
}
