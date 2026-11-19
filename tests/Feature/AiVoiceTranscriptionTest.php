<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiVoiceTranscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_audio_transcription_sends_inline_audio_to_gemini_with_api_key_header(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key-12345',
            'services.gemini.model'   => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => '¿Cuáles son los planes SaaS de BrazasTech y sus precios en Bolivianos?'],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $fakeAudio = UploadedFile::fake()->createWithContent(
            'voz.webm',
            str_repeat('WEBM_AUDIO_BYTES_', 40)
        );

        $response = $this->post('/ai/public-transcribe', [
            'audio'     => $fakeAudio,
            'mime_type' => 'audio/webm;codecs=opus',
            'context'   => 'corporate',
        ]);

        $response->assertOk()
            ->assertJson([
                'success'    => true,
                'transcript' => '¿Cuáles son los planes SaaS de BrazasTech y sus precios en Bolivianos?',
                'engine'     => 'gemini-gemini-3.8-flash',
            ]);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $inline = $body['contents'][0]['parts'][0]['inlineData'] ?? [];

            return $request->hasHeader('x-goog-api-key', 'AQ.test-key-12345')
                && ($inline['mimeType'] ?? null) === 'audio/webm'
                && !empty($inline['data'])
                && ($body['generationConfig']['thinkingConfig']['thinkingLevel'] ?? null) === 'LOW';
        });
    }

    public function test_public_audio_transcription_returns_422_on_silence(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key-12345',
            'services.gemini.model'   => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => '[SILENCIO]'],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $fakeAudio = UploadedFile::fake()->createWithContent(
            'voz.ogg',
            str_repeat('OGG_SILENCE_BYTES', 30)
        );

        $response = $this->post('/ai/public-transcribe', [
            'audio'     => $fakeAudio,
            'mime_type' => 'audio/ogg;codecs=opus',
            'context'   => 'restaurant',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code'    => 'silence',
            ]);
    }

    public function test_public_audio_transcription_rejects_too_short_payload(): void
    {
        $tinyAudio = UploadedFile::fake()->createWithContent('voz.webm', 'tiny');

        $response = $this->post('/ai/public-transcribe', [
            'audio' => $tinyAudio,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code'    => 'too_short',
            ]);
    }

    public function test_authenticated_transcribe_endpoint_requires_login_and_works_for_authenticated_user(): void
    {
        $fakeAudio = UploadedFile::fake()->createWithContent(
            'consulta.webm',
            str_repeat('AUDIO_REPORT_BYTES', 30)
        );

        // Invitado bloqueado en /ai/transcribe
        $guestRes = $this->postJson('/ai/transcribe', [
            'audio'   => $fakeAudio,
            'context' => 'reports',
        ]);
        $guestRes->assertStatus(401);

        // Usuario autenticado transcribe correctamente
        $role = Role::firstOrCreate(['name' => 'Administrador']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        config([
            'services.gemini.api_key' => 'AQ.test-key-12345',
            'services.gemini.model'   => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Ventas de Arrachera esta semana en gráfico de barras'],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $authRes = $this->actingAs($user)->postJson('/ai/transcribe', [
            'audio'     => $fakeAudio,
            'mime_type' => 'audio/webm',
            'context'   => 'reports',
        ]);

        $authRes->assertOk()
            ->assertJson([
                'success'    => true,
                'transcript' => 'Ventas de Arrachera esta semana en gráfico de barras',
            ]);
    }

    public function test_safari_mp4_audio_retries_mime_fallback_cleanly(): void
    {
        config([
            'services.gemini.api_key' => 'AQ.test-key-12345',
            'services.gemini.model'   => 'gemini-3.8-flash',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent' => Http::sequence()
                ->push(['error' => ['code' => 400, 'message' => 'Unsupported MIME type audio/m4a']], 400)
                ->push([
                    'candidates' => [[
                        'content' => [
                            'parts' => [
                                ['text' => 'Reserva una mesa para cuatro personas mañana a las ocho'],
                            ],
                        ],
                    ]],
                ], 200),
        ]);

        $safariAudio = UploadedFile::fake()->createWithContent(
            'voz.m4a',
            str_repeat('MP4_AUDIO_SAFARI_', 35)
        );

        $response = $this->post('/ai/public-transcribe', [
            'audio'     => $safariAudio,
            'mime_type' => 'audio/mp4',
            'context'   => 'restaurant',
        ]);

        $response->assertOk()
            ->assertJson([
                'success'    => true,
                'transcript' => 'Reserva una mesa para cuatro personas mañana a las ocho',
            ]);
    }
}
