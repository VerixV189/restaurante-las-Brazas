<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationApiController extends Controller
{
    private const DURATION_HOURS = 2;
    private const MIN_CANCEL_HOURS = 2;
    private const TOLERANCE_MESSAGE = 'Tu mesa se mantendrá por 15 minutos de tolerancia desde la hora reservada.';

    /** Áreas (zonas) disponibles con su aforo total. */
    public function areas(): JsonResponse
    {
        $areas = Area::withSum('tables as capacity', 'capacity')->get(['id', 'name', 'description']);
        return response()->json(['success' => true, 'data' => $areas]);
    }

    private function remainingCapacity(int $areaId, \Carbon\Carbon $when): int
    {
        $capacity = (int) Area::findOrFail($areaId)->tables()->where('status', '!=', 'maintenance')->sum('capacity');
        $booked = (int) Reservation::where('area_id', $areaId)
            ->whereIn('status', ['pending', 'confirmed', 'seated'])
            ->whereBetween('reservation_date', [
                $when->copy()->subHours(self::DURATION_HOURS)->addMinute(),
                $when->copy()->addHours(self::DURATION_HOURS)->subMinute(),
            ])->sum('guest_count');
        return max(0, $capacity - $booked);
    }

    /** Aforo disponible por área para una fecha y hora. */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d H:i', 'guests' => 'nullable|integer|min:1']);
        $when = \Carbon\Carbon::parse($data['date']);
        $areas = Area::all(['id', 'name'])->map(function ($a) use ($when, $data) {
            $left = $this->remainingCapacity($a->id, $when);
            return ['area_id' => $a->id, 'name' => $a->name, 'seats_left' => $left,
                'available' => $left >= ($data['guests'] ?? 1)];
        });
        return response()->json(['success' => true, 'data' => $areas]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reservation_date' => 'required|date_format:Y-m-d H:i|after:now',
            'adults' => 'required|integer|min:1|max:30',
            'children' => 'nullable|integer|min:0|max:20',
            'area_id' => 'required|exists:areas,id',
            'special_requests' => 'nullable|string|max:500',
        ]);
        $when = \Carbon\Carbon::parse($data['reservation_date']);
        $guests = $data['adults'] + ($data['children'] ?? 0);

        if ($this->remainingCapacity($data['area_id'], $when) < $guests) {
            return response()->json(['success' => false, 'message' => 'No hay aforo disponible en esa zona y horario.'], 422);
        }

        $reservation = Reservation::create([
            'customer_id' => CustomerController::customerFor($request)->id,
            'area_id' => $data['area_id'],
            'reservation_date' => $when,
            'guest_count' => $guests,
            'children_count' => $data['children'] ?? 0,
            'status' => 'pending',
            'special_requests' => $data['special_requests'] ?? null,
        ]);

        return response()->json([
            'success' => true, 'message' => 'Reserva registrada. ' . self::TOLERANCE_MESSAGE,
            'data' => $reservation->load('table:id,table_number'),
        ], 201);
    }

    /** Reservas activas e historial. */
    public function index(Request $request): JsonResponse
    {
        $list = CustomerController::customerFor($request)->reservations()->latest('reservation_date')->get();
        return response()->json(['success' => true, 'data' => [
            'active' => $list->whereIn('status', ['pending', 'confirmed', 'seated'])->values(),
            'history' => $list->whereIn('status', ['cancelled', 'completed'])->values(),
        ]]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $r = CustomerController::customerFor($request)->reservations()->findOrFail($id);
        abort_unless(in_array($r->status, ['pending', 'confirmed']), 422, 'Esta reserva no se puede cancelar.');
        abort_if($r->reservation_date->lt(now()->addHours(self::MIN_CANCEL_HOURS)), 422,
            'Solo se puede cancelar con al menos ' . self::MIN_CANCEL_HOURS . ' horas de anticipación.');
        $r->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Reserva cancelada.']);
    }
}

