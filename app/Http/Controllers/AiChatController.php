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
            'message'           => 'required|string|min:1|max:1000',
            'history'           => 'nullable|array|max:10',
            'history.*.role'    => 'nullable|in:user,assistant',
            'history.*.content' => 'nullable|string|max:1500',
        ], [
            'message.required' => 'El mensaje no puede estar vacío.',
            'message.max'      => 'El mensaje no puede exceder los 1000 caracteres.',
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
            'message'           => 'required|string|min:1|max:800',
            'portal'            => 'nullable|in:restaurant,corporate',
            'history'           => 'nullable|array|max:10',
            'history.*.role'    => 'nullable|in:user,assistant',
            'history.*.content' => 'nullable|string|max:1500',
        ], [
            'message.required' => 'Por favor escribe tu consulta.',
            'message.max'      => 'El mensaje no puede exceder los 800 caracteres.',
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
            'engine'            => $this->aiChatService->getLastEngine(),
            'timestamp'         => now()->format('H:i'),
        ]);
    }

    /**
     * Recibe audio grabado desde el micrófono y lo transcribe a texto con IA (Gemini / Whisper).
     * Usado por todos los chatbots (Landings, Sidebar Staff/Cliente, Portal Cliente y Reportes IA).
     */
    public function transcribeAudio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audio'     => 'required|file|max:5120',
            'mime_type' => 'nullable|string|max:120',
            'context'   => 'nullable|in:restaurant,corporate,portal,reports,staff,general',
        ], [
            'audio.required' => 'No se recibió ningún archivo de audio.',
            'audio.file'     => 'El audio enviado no es un archivo válido.',
            'audio.max'      => 'El audio excede el límite de 5 MB. Graba un mensaje más breve.',
        ]);

        $file = $request->file('audio');
        $binary = (string) $file->get();
        $mimeType = (string) ($validated['mime_type'] ?? $file->getClientMimeType() ?? $file->getMimeType() ?? 'audio/webm');
        $context = (string) ($validated['context'] ?? 'general');

        $result = $this->aiChatService->transcribeAudio($binary, $mimeType, $context);

        if (!$result['success']) {
            $status = match ($result['code']) {
                'too_short', 'silence' => 422,
                'no_provider'          => 503,
                default                => 502,
            };

            return response()->json([
                'success' => false,
                'code'    => $result['code'],
                'message' => $result['error'],
            ], $status);
        }

        return response()->json([
            'success'    => true,
            'transcript' => $result['transcript'],
            'engine'     => $result['engine'],
        ]);
    }
}
