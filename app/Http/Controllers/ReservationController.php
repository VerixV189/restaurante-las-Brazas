<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(): Response
    {
        $reservations = Reservation::with(['customer', 'table.area', 'user'])->latest()->get();
        $customers = Customer::all();
        $tables = Table::with('area')->get();

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'customers' => $customers,
            'tables' => $tables,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'table_id' => 'nullable|exists:tables,id',
            'reservation_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'status' => 'required|in:pending,confirmed,seated,cancelled,completed',
            'special_requests' => 'nullable|string',
        ]);

        $customer = Customer::firstOrCreate(
            ['name' => $validated['customer_name']],
            [
                'phone' => $validated['customer_phone'] ?? null,
                'email' => $validated['customer_email'] ?? null,
            ]
        );

        Reservation::create([
            'customer_id' => $customer->id,
            'table_id' => $validated['table_id'] ?? null,
            'user_id' => Auth::id(),
            'reservation_date' => $validated['reservation_date'],
            'guest_count' => $validated['guest_count'],
            'status' => $validated['status'],
            'special_requests' => $validated['special_requests'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Reserva registrada exitosamente.');
    }

    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'table_id' => 'nullable|exists:tables,id',
            'reservation_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'status' => 'required|in:pending,confirmed,seated,cancelled,completed',
            'special_requests' => 'nullable|string',
        ]);

        $reservation->update($validated);

        return redirect()->back()->with('success', 'Reserva actualizada correctamente.');
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return redirect()->back()->with('success', 'Reserva eliminada correctamente.');
    }
}
