<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /** Obtiene (o crea) el Customer vinculado al usuario autenticado por email. */
    public static function customerFor(Request $request): Customer
    {
        $user = $request->user();
        return Customer::firstOrCreate(
            ['email' => $user->email],
            ['name' => $user->name, 'phone' => $user->phone]
        );
    }

    public function addresses(Request $request): JsonResponse
    {
        $list = self::customerFor($request)->addresses()->orderByDesc('is_default')->get();
        return response()->json(['success' => true, 'data' => $list]);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:50',
            'street' => 'required|string|max:255',
            'number' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);
        $customer = self::customerFor($request);
        if (! empty($data['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }
        $address = $customer->addresses()->create($data);
        return response()->json(['success' => true, 'data' => $address], 201);
    }

    public function updateAddress(Request $request, int $id): JsonResponse
    {
        $address = self::customerFor($request)->addresses()->findOrFail($id);
        $data = $request->validate([
            'label' => 'sometimes|string|max:50',
            'street' => 'sometimes|string|max:255',
            'number' => 'nullable|string|max:20',
            'floor' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);
        if (! empty($data['is_default'])) {
            $address->customer->addresses()->update(['is_default' => false]);
        }
        $address->update($data);
        return response()->json(['success' => true, 'data' => $address]);
    }

    public function destroyAddress(Request $request, int $id): JsonResponse
    {
        self::customerFor($request)->addresses()->findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Dirección eliminada.']);
    }
}

