<?php

namespace Tests\Feature;

use App\Mail\ConditionsMail;
use App\Mail\PrivacyPolicyMail;
use App\Mail\TermsMail;
use App\Mail\VerifyAccountMail;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GoogleMobileOAuthTest extends TestCase
{
    use RefreshDatabase;

    private function configureGoogleOAuth(): void
    {
        config([
            'services.google.client_id'     => 'mobile-test-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'mobile-test-client-secret',
            'services.google.redirect'      => 'https://lasbrazas.ramio.tech/auth/google/callback',
        ]);
    }

    public function test_mobile_google_redirect_uses_mobile_state_prefix_and_works_even_when_chrome_is_logged_in(): void
    {
        $this->configureGoogleOAuth();

        $clientRole = Role::firstOrCreate(['name' => 'Cliente']);
        $loggedInWebUser = User::factory()->create(['role_id' => $clientRole->id]);

        // En flujo web normal, usuario autenticado sigue yendo a /dashboard
        $webRes = $this->actingAs($loggedInWebUser)->get('/auth/google/redirect');
        $webRes->assertRedirect('/dashboard');

        // En flujo móvil (?source=mobile), NO es bloqueado por middleware guest ni redirigido a /dashboard
        $mobileRes = $this->actingAs($loggedInWebUser)->get('/auth/google/redirect?source=mobile');
        $mobileRes->assertStatus(302);

        $location = (string) $mobileRes->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', $location);
        $this->assertStringContainsString('redirect_uri=' . urlencode('https://lasbrazas.ramio.tech/auth/google/callback'), $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $queryParams);
        $this->assertStringStartsWith('mobile_', $queryParams['state'] ?? '');
    }

    public function test_mobile_google_callback_creates_new_client_sanctum_token_sends_three_emails_and_returns_android_intent_html(): void
    {
        $this->configureGoogleOAuth();
        Mail::fake();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-mobile-token-999',
                'token_type'   => 'Bearer',
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub'            => '777888999',
                'name'           => 'Sofía Flutter',
                'email'          => 'sofia.flutter@example.com',
                'email_verified' => true,
            ], 200),
        ]);

        // 1. Iniciar flujo móvil
        $redirect = $this->get('/auth/google/redirect?source=mobile');
        $redirect->assertStatus(302);
        parse_str((string) parse_url((string) $redirect->headers->get('Location'), PHP_URL_QUERY), $q);
        $state = $q['state'];
        $this->assertStringStartsWith('mobile_', $state);

        // 2. Simular callback SIN cookie de sesión (probando respaldo en caché de Chrome Android)
        $callback = $this->withSession([])
            ->get('/auth/google/callback?code=flutter-code-123&state=' . urlencode($state));

        $callback->assertStatus(200);
        $html = $callback->getContent();

        $this->assertStringContainsString('intent://auth/callback?', $html);
        $this->assertStringContainsString('#Intent;scheme=lasbrazas;package=com.example.restaurante_movil;end', $html);
        $this->assertStringContainsString('lasbrazas://auth/callback?', $html);
        $this->assertStringContainsString('Abrir App Las Brazas', $html);

        // Extraer el deep link del botón y validar todos los query parameters exigidos por Flutter
        preg_match('/href="(lasbrazas:\/\/auth\/callback\?[^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'Deep link href no encontrado en la vista HTML');

        $deepLink = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
        parse_str((string) parse_url($deepLink, PHP_URL_QUERY), $cbParams);

        $this->assertNotEmpty($cbParams['token'] ?? null);
        $this->assertSame('Sofía Flutter', $cbParams['name'] ?? null);
        $this->assertSame('sofia.flutter@example.com', $cbParams['email'] ?? null);
        $this->assertSame('Cliente', $cbParams['role'] ?? null);
        $this->assertSame('1', $cbParams['is_new'] ?? null);
        $this->assertNotEmpty($cbParams['message'] ?? null);

        // Verificar usuario, ficha Customer y correos enviados (3 informativos, 0 de verificación)
        $created = User::where('email', 'sofia.flutter@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame((string) $created->id, $cbParams['id'] ?? null);
        $this->assertTrue((bool) $created->is_active);
        $this->assertNotNull($created->email_verified_at);
        $this->assertNotNull(Customer::where('email', 'sofia.flutter@example.com')->first());

        Mail::assertSent(TermsMail::class);
        Mail::assertSent(ConditionsMail::class);
        Mail::assertSent(PrivacyPolicyMail::class);
        Mail::assertNotSent(VerifyAccountMail::class);

        // Verificar que el plainTextToken de Sanctum funciona realmente contra la API móvil /api/v1/auth/me
        $meRes = $this->withHeader('Authorization', 'Bearer ' . $cbParams['token'])
            ->getJson('/api/v1/auth/me');
        $meRes->assertOk();
    }

    public function test_mobile_google_callback_existing_client_returns_is_new_zero_and_valid_token(): void
    {
        $this->configureGoogleOAuth();
        Mail::fake();

        $clientRole = Role::firstOrCreate(['name' => 'Cliente']);
        $existing = User::factory()->create([
            'role_id'   => $clientRole->id,
            'name'      => 'Diego Recurrente',
            'email'     => 'diego.recurrente@example.com',
            'is_active' => true,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok-exist'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'name'  => 'Diego Recurrente',
                'email' => 'diego.recurrente@example.com',
            ], 200),
        ]);

        $redirect = $this->get('/auth/google/redirect?source=mobile');
        parse_str((string) parse_url((string) $redirect->headers->get('Location'), PHP_URL_QUERY), $q);
        $state = $q['state'];

        $callback = $this->get('/auth/google/callback?code=code-existing&state=' . urlencode($state));
        $callback->assertStatus(200);

        preg_match('/href="(lasbrazas:\/\/auth\/callback\?[^"]+)"/', $callback->getContent(), $matches);
        parse_str((string) parse_url(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), PHP_URL_QUERY), $cbParams);

        $this->assertSame('0', $cbParams['is_new'] ?? null);
        $this->assertSame((string) $existing->id, $cbParams['id'] ?? null);
        $this->assertNotEmpty($cbParams['token'] ?? null);
        Mail::assertNothingSent();
    }

    public function test_mobile_google_callback_blocks_staff_and_handles_cancel_or_expired_state(): void
    {
        $this->configureGoogleOAuth();

        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        User::factory()->create([
            'role_id' => $adminRole->id,
            'email'   => 'admin.brazas@example.com',
        ]);

        // 1. Cancelación en Google retorna HTML móvil con ?error=...
        $cancelRes = $this->get('/auth/google/callback?error=access_denied&state=mobile_fake123');
        $cancelRes->assertStatus(200);
        $this->assertStringContainsString('lasbrazas://auth/callback?error=', $cancelRes->getContent());
        $this->assertStringContainsString('Abrir App Las Brazas', $cancelRes->getContent());

        // 2. State expirado retorna HTML móvil con ?error=...
        $expiredRes = $this->get('/auth/google/callback?code=xyz&state=mobile_expired_state');
        $expiredRes->assertStatus(200);
        $this->assertStringContainsString('lasbrazas://auth/callback?error=', $expiredRes->getContent());

        // 3. Cuenta Staff/Administrador bloqueada en móvil retorna HTML móvil con ?error=...
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok-admin'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'name'  => 'Admin',
                'email' => 'admin.brazas@example.com',
            ], 200),
        ]);

        $redirect = $this->get('/auth/google/redirect?source=mobile');
        parse_str((string) parse_url((string) $redirect->headers->get('Location'), PHP_URL_QUERY), $q);

        $staffRes = $this->get('/auth/google/callback?code=code-admin&state=' . urlencode($q['state']));
        $staffRes->assertStatus(200);
        $this->assertStringContainsString('lasbrazas://auth/callback?error=', $staffRes->getContent());
        $this->assertStringContainsString('equipo operativo', $staffRes->getContent());
    }
}
