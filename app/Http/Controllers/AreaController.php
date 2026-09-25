<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function index(): Response
    {
        $areas = Area::with(['tables.reservations' => function ($query) {
            $query->whereDate('reservation_date', today());
        }])
        ->withCount('tables')
        ->latest()
        ->get();

        $tables = Table::with('area')->orderBy('table_number')->get();

        return Inertia::render('Areas/Index', [
            'areas' => $areas,
            'tables' => $tables,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name',
            'description' => 'nullable|string',
        ]);

        Area::create($validated);

        return redirect()->back()->with('success', 'Zona/Área creada exitosamente.');
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,' . $area->id,
            'description' => 'nullable|string',
        ]);

        $area->update($validated);

        return redirect()->back()->with('success', 'Zona/Área actualizada exitosamente.');
    }

    public function destroy(Area $area)
    {
        if ($area->tables()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar un área que contiene mesas asignadas.');
        }

        $area->delete();

        return redirect()->back()->with('success', 'Zona/Área eliminada exitosamente.');
    }
}
