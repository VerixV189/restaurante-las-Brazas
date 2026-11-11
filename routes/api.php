<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReservationApiController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\AiChatController;

/*
|--------------------------------------------------------------------------
| API Routes - Restaurante Las Brazas
|--------------------------------------------------------------------------
|
| Rutas REST API consumidas por la aplicación móvil en Flutter.
| Todas las rutas dentro de este archivo tienen el prefijo "/api".
|
*/

Route::prefix('v1')->group(function () {

    // Verificación de conectividad para la app móvil
    Route::get('/health', function () {
        return response()->json([
            'status' => 'online',
            'app' => 'Restaurante Las Brazas API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // ── Rutas Públicas (sin token) ───────────────────────────────────────────
    // Autenticación móvil
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.auth.register');

    // Catálogo y Menú Digital
    Route::get('/categories', [MenuController::class, 'categories'])->name('api.menu.categories');
    Route::get('/dishes', [MenuController::class, 'dishes'])->name('api.menu.dishes');
    Route::get('/dishes/{id}', [MenuController::class, 'show'])->name('api.menu.dishDetail');

    // ── Rutas Protegidas (requieren token Bearer de Laravel Sanctum) ──────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

        // Direcciones
        Route::get('/customer/addresses', [CustomerController::class, 'addresses']);
        Route::post('/customer/addresses', [CustomerController::class, 'storeAddress']);
        Route::put('/customer/addresses/{id}', [CustomerController::class, 'updateAddress']);
        Route::delete('/customer/addresses/{id}', [CustomerController::class, 'destroyAddress']);

        // Pedidos
        Route::post('/orders/quote', [OrderController::class, 'quote']);
        Route::post('/coupons/validate', [OrderController::class, 'validateCoupon']);
        Route::get('/orders/history', [OrderController::class, 'history']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/pay', [OrderController::class, 'pay']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'invoice']);
        Route::post('/orders/{id}/reorder', [OrderController::class, 'reorder']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // Reservas
        Route::get('/areas', [ReservationApiController::class, 'areas']);
        Route::get('/reservations/availability', [ReservationApiController::class, 'availability']);
        Route::get('/reservations', [ReservationApiController::class, 'index']);
        Route::post('/reservations', [ReservationApiController::class, 'store']);
        Route::post('/reservations/{id}/cancel', [ReservationApiController::class, 'cancel']);

        // Repartidor
        Route::get('/delivery/pending', [DeliveryController::class, 'pending']);
        Route::get('/delivery/mine', [DeliveryController::class, 'mine']);
        Route::post('/delivery/{id}/take', [DeliveryController::class, 'take']);
        Route::post('/delivery/{id}/pickup', [DeliveryController::class, 'pickup']);
        Route::post('/delivery/{id}/confirm', [DeliveryController::class, 'confirm']);

        // Asistente IA Móvil (RF-27)
        Route::post('/ai/chat', [AiChatController::class, 'sendMessage'])->name('api.ai.chat');
    });

});
