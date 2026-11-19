<?php

namespace App\Http\Controllers;

use App\Services\AiChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        protected AiChatService $aiChatService
    ) {
    }

    /**
     * Procesa una consulta enviada al chat de IA y retorna la respuesta generada.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
            'history' => 'nullable|array',
        ], [
            'message.required' => 'El mensaje no puede estar vacío.',
            'message.max' => 'El mensaje no puede exceder los 1000 caracteres.',
        ]);

        $user = $request->user();
        $message = $validated['message'];
        $history = $validated['history'] ?? [];

        $reply = $this->aiChatService->generateReply($user, $message, $history);
        $interactive = $this->aiChatService->extractInteractivePayload($user, $message, $reply);

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'suggested_items' => $interactive['suggested_items'],
            'suggested_coupons' => $interactive['suggested_coupons'],
            'created_reservation' => $interactive['created_reservation'],
            'timestamp' => now()->format('H:i'),
        ]);
    }

    /**
     * Endpoint público para el Agente Inteligente en los Landings (/ y /empresa).
     */
    public function sendPublicMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
            'portal'  => 'nullable|in:restaurant,corporate',
            'history' => 'nullable|array',
        ], [
            'message.required' => 'Por favor escribe tu consulta.',
            'message.max'      => 'El mensaje no puede exceder los 1000 caracteres.',
        ]);

        $portal  = $validated['portal'] ?? 'restaurant';
        $message = $validated['message'];
        $history = $validated['history'] ?? [];

        $reply   = $this->aiChatService->generatePublicReply($message, $history, $portal);
        $actions = $this->aiChatService->extractPublicActions($message, $reply, $portal);

        return response()->json([
            'success'           => true,
            'reply'             => $reply,
            'suggested_actions' => $actions,
            'timestamp'         => now()->format('H:i'),
        ]);
    }
}
