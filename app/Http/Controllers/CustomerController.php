<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        $customers = Customer::withCount(['reservations', 'orders'])
            ->with(['reservations' => function ($query) {
                $query->with('table.area')->latest('reservation_date')->take(5);
            }])
            ->latest()
            ->get();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'preferences_notes' => 'nullable|string',
        ]);

        Customer::create($validated);

        return redirect()->back()->with('success', 'Cliente registrado exitosamente.');
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'preferences_notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->back()->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->back()->with('success', 'Cliente eliminado exitosamente.');
    }
}
