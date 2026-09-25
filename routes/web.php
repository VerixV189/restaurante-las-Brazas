<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\InventoryMovementController;

// ─────────────────────────────────────────────────────────────────────────────
// Rutas Públicas (sin autenticación)
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/reserve', [HomeController::class, 'reserve'])->name('public.reserve');
Route::get('/terms', [AuthController::class, 'showTerms'])->name('terms');
Route::get('/terminos-y-condiciones', [AuthController::class, 'showTerms']);
Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación (solo para usuarios NO autenticados)
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─────────────────────────────────────────────────────────────────────────────
// Panel de Administración (requiere autenticación)
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard - accesible por todos los roles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Menú, Platillos y Fichas Técnicas / Recetas (Administrador, Chef) ──────
    Route::middleware('role:Administrador,Chef')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('dishes', DishController::class)->except(['create', 'edit', 'show']);
        Route::resource('recipes', RecipeController::class)->except(['create', 'edit', 'show']);
    });

    // ── Pantalla de Cocina / KDS (Administrador, Chef) ──────────────────────
    Route::middleware('role:Administrador,Chef')->group(function () {
        Route::get('/kitchen', [\App\Http\Controllers\KitchenController::class, 'index'])->name('kitchen.index');
        Route::post('/kitchen/items/{item}/status', [\App\Http\Controllers\KitchenController::class, 'updateItemStatus'])->name('kitchen.items.status');
        Route::post('/kitchen/orders/{order}/advance', [\App\Http\Controllers\KitchenController::class, 'advanceOrderStatus'])->name('kitchen.orders.advance');
    });

    // ── Inventario, Insumos, Mermas & Predicciones (Administrador, Chef) ────
    Route::middleware('role:Administrador,Chef')->group(function () {
        Route::resource('ingredients', IngredientController::class)->except(['create', 'edit', 'show']);
        Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])->name('inventory-movements.index');
        Route::post('/inventory-movements', [InventoryMovementController::class, 'store'])->name('inventory-movements.store');
        Route::get('/predictions', [\App\Http\Controllers\PredictionController::class, 'index'])->name('predictions.index');
        Route::post('/predictions/batch-calculate', [\App\Http\Controllers\PredictionController::class, 'calculateBatch'])->name('predictions.calculate');
        Route::post('/predictions/demand', [\App\Http\Controllers\PredictionController::class, 'predictDemand'])->name('predictions.demand');
        Route::post('/predictions/export-pdf', [\App\Http\Controllers\PredictionController::class, 'exportPdf'])->name('predictions.export.pdf');
    });

    // ── Zonas, Mesas y Gestor de Planos IA (Administrador, Recepcionista) ──
    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::resource('areas', AreaController::class)->except(['create', 'edit', 'show']);
        Route::resource('tables', TableController::class)->only(['store', 'update', 'destroy']);
        Route::get('/floor-plans', [\App\Http\Controllers\FloorPlanController::class, 'index'])->name('floor-plans.index');
        Route::post('/floor-plans/detect', [\App\Http\Controllers\FloorPlanController::class, 'detect'])->name('floor-plans.detect');
        Route::post('/floor-plans/optimize', [\App\Http\Controllers\FloorPlanController::class, 'optimize'])->name('floor-plans.optimize');
        Route::post('/floor-plans/sync-tables', [\App\Http\Controllers\FloorPlanController::class, 'syncTables'])->name('floor-plans.sync');
    });

    // ── Proveedores (Administrador) ───────────────────────────────────────────
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['create', 'edit', 'show']);
    });

    // ── Directorio de Clientes (Administrador, Recepcionista) ─────────────────
    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::resource('customers', CustomerController::class)->except(['create', 'edit', 'show']);
    });

    // ── Punto de Venta (POS) y Comandas (Administrador, Mesero) ─────────────
    Route::middleware('role:Administrador,Mesero')->group(function () {
        Route::get('/pos', [\App\Http\Controllers\PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/orders', [\App\Http\Controllers\PosController::class, 'storeOrder'])->name('pos.orders.store');
        Route::post('/pos/orders/{order}/items', [\App\Http\Controllers\PosController::class, 'addItem'])->name('pos.orders.addItem');
        Route::post('/pos/orders/{order}/close', [\App\Http\Controllers\PosController::class, 'closeOrder'])->name('pos.orders.close');
        Route::post('/pos/orders/{order}/cancel', [\App\Http\Controllers\PosController::class, 'cancelOrder'])->name('pos.orders.cancel');
        Route::get('/pos/orders/{order}/ticket', [\App\Http\Controllers\PosController::class, 'ticketPdf'])->name('pos.orders.ticket');
    });

    // ── Reservas (Administrador, Recepcionista, Mesero) ───────────────────────
    Route::middleware('role:Administrador,Recepcionista,Mesero')->group(function () {
        Route::resource('reservations', ReservationController::class)->except(['create', 'edit', 'show']);
    });

    // ── Usuarios & Roles (solo Administrador) ─────────────────────────────────
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });

    // ── Reportes y Analytics (solo Administrador) ───────────────────────────
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::post('/reports/ai-query', [\App\Http\Controllers\AiReportController::class, 'query'])->name('reports.ai.query');
    });

    // ── Logs de Auditoría (solo Administrador) ────────────────────────────────
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

});
