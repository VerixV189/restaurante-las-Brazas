<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Reservation;
use App\Models\User;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_dishes' => Dish::count(),
            'available_dishes' => Dish::where('is_available', true)->count(),
            'total_ingredients' => Ingredient::count(),
            'low_stock_ingredients' => Ingredient::whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'pending_reservations' => Reservation::where('status', 'pending')->count(),
            'today_reservations' => Reservation::whereDate('reservation_date', today())->count(),
            'total_users' => User::count(),
        ];

        $lowStockList = Ingredient::whereColumn('current_stock', '<=', 'minimum_stock')
            ->with('supplier')
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'lowStockList' => $lowStockList,
            'recentActivities' => $recentActivities,
        ]);
    }
}
