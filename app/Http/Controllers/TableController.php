<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'table_number' => 'required|string|max:50|unique:tables,table_number',
            'capacity' => 'required|integer|min:1|max:50',
            'status' => 'required|in:available,occupied,reserved,maintenance',
        ]);

        Table::create($validated);

        return redirect()->back()->with('success', 'Mesa creada exitosamente.');
    }

    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'table_number' => 'required|string|max:50|unique:tables,table_number,' . $table->id,
            'capacity' => 'required|integer|min:1|max:50',
            'status' => 'required|in:available,occupied,reserved,maintenance',
        ]);

        $table->update($validated);

        return redirect()->back()->with('success', 'Mesa actualizada exitosamente.');
    }

    public function destroy(Table $table)
    {
        $table->delete();

        return redirect()->back()->with('success', 'Mesa eliminada exitosamente.');
    }
}
