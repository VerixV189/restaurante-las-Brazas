<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión desde la aplicación móvil.
     * Retorna el token Bearer de Laravel Sanctum y los datos del usuario.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son incorrectas.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta se encuentra desactivada. Contacta al restaurante.',
            ], 403);
        }

        $deviceName = $request->device_name ?? 'mobile_device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => '¡Bienvenido de nuevo, ' . $user->name . '!',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role ? $user->role->name : 'Cliente',
            ],
        ]);
    }

    /**
     * Registrar un nuevo cliente desde la aplicación móvil.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:50',
            'device_name' => 'nullable|string',
        ]);

        // Rol Cliente para usuarios que se registran desde la app
        $clientRole = Role::firstOrCreate(
            ['name' => 'Cliente'],
            ['description' => 'Comensal registrado desde la app móvil o web']
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role_id' => $clientRole->id,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        // Sincronizar o crear en la libreta de Clientes
        Customer::firstOrCreate(
            ['email' => $user->email],
            [
                'name' => $user->name,
                'phone' => $user->phone,
                'preferences_notes' => 'Registrado vía App Móvil Flutter',
            ]
        );

        $deviceName = $request->device_name ?? 'mobile_device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada exitosamente en Las Brazas.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => 'Cliente',
            ],
        ], 201);
    }

    /**
     * Obtener el perfil del usuario autenticado actual.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role ? $user->role->name : 'Cliente',
            ],
        ]);
    }

    /**
     * Cerrar sesión y revocar el token actual del dispositivo móvil.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar el token con el que se autenticó esta petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
