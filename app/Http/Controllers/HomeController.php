<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Table;
use App\Models\Customer;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $categories = Category::where('is_active', true)->get();
        $dishes = Dish::with('category')->where('is_available', true)->get();
        $tables = Table::with('area')->where('status', 'available')->get();

        return Inertia::render('Landing', [
            'categories' => $categories,
            'dishes' => $dishes,
            'tables' => $tables,
        ]);
    }

    public function reserve(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'reservation_date' => 'required|date',
            'guest_count' => 'required|integer|min:1|max:20',
            'table_id' => 'nullable|exists:tables,id',
            'special_requests' => 'nullable|string',
        ]);

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'] ?? null,
            ]
        );

        Reservation::create([
            'customer_id' => $customer->id,
            'table_id' => $validated['table_id'] ?? null,
            'reservation_date' => $validated['reservation_date'],
            'guest_count' => $validated['guest_count'],
            'status' => 'pending',
            'special_requests' => $validated['special_requests'] ?? null,
        ]);

        return redirect()->back()->with('success', '¡Tu reserva ha sido enviada con éxito! Nos comunicaremos para confirmar.');
    }
}
