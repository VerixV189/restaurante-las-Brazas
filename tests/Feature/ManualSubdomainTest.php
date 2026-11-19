<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualSubdomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_route_renders_interactive_manual_page(): void
    {
        $response = $this->get('/manual');

        $response->assertStatus(200);
        $response->assertSee('Manual\/Index', false);
        $response->assertSee('Manual_Usuario_Completo_Las_Brazas.pdf', false);
    }

    public function test_manual_prefix_subdomain_routes_root_to_interactive_manual(): void
    {
        $response = $this->get('http://manual.lasbrazas.bo/');

        $response->assertStatus(200);
        $response->assertSee('Manual\/Index', false);
        $response->assertSee('"isSubdomain":true', false);
        $response->assertSee('"pdfUrl":"\/pdf"', false);
    }

    public function test_explicit_configured_manual_domain_routes_root_to_manual(): void
    {
        config(['manual.domain' => 'guia.brazastech.bo']);

        $response = $this->get('http://guia.brazastech.bo/');

        $response->assertStatus(200);
        $response->assertSee('Manual\/Index', false);
        $response->assertSee('"isSubdomain":true', false);
    }

    public function test_main_domain_still_renders_landing_page(): void
    {
        $response = $this->get('http://lasbrazas.bo/');

        $response->assertStatus(200);
        $response->assertSee('Landing', false);
    }

    public function test_manual_pdf_endpoints_serve_valid_pdf_inline_and_download(): void
    {
        $inlineResponse = $this->get('/manual/pdf');
        $inlineResponse->assertStatus(200);
        $inlineResponse->assertHeader('Content-Type', 'application/pdf');
        $filePath = $inlineResponse->baseResponse->getFile()->getPathname();
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($filePath, false, null, 0, 16));

        $subdomainShortResponse = $this->get('http://manual.lasbrazas.bo/pdf');
        $subdomainShortResponse->assertStatus(200);
        $subdomainShortResponse->assertHeader('Content-Type', 'application/pdf');

        $downloadResponse = $this->get('/manual/pdf?download=1');
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment; filename=Manual_Usuario_Completo_Las_Brazas.pdf',
            str_replace('"', '', (string) $downloadResponse->headers->get('Content-Disposition'))
        );
    }

    public function test_pwa_assets_and_logout_cache_purge_headers_are_configured(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $swContent = (string) file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('lasbrazas-static-v2', $swContent);
        $this->assertStringContainsString('lasbrazas-session-v1', $swContent);
        $this->assertStringContainsString('CLEAR_AUTH_CACHE', $swContent);
        $this->assertStringContainsString("url.pathname.startsWith('/auth/')", $swContent);

        $role = \App\Models\Role::create([
            'name' => 'Administrador',
            'description' => 'Admin role',
        ]);
        $user = \App\Models\User::create([
            'role_id' => $role->id,
            'name' => 'Admin Test',
            'email' => 'admin.pwa@lasbrazas.bo',
            'password' => bcrypt('password123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $logoutResponse = $this->actingAs($user)->post('/logout');
        $logoutResponse->assertRedirect('/');
        $logoutResponse->assertHeader('Clear-Site-Data', '"cache"');
        $this->assertGuest();
    }

    public function test_google_oauth_registration_creates_verified_client_and_sends_only_two_emails_without_verification_mail(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Http::fake([
            'https://oauth2.googleapis.com/token' => \Illuminate\Support\Facades\Http::response([
                'access_token' => 'fake-google-access-token-123',
                'token_type'   => 'Bearer',
                'expires_in'   => 3599,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => \Illuminate\Support\Facades\Http::response([
                'sub'            => '109876543210',
                'name'           => 'Carlos Comensal Google',
                'given_name'     => 'Carlos',
                'email'          => 'carlos.google@example.com',
                'email_verified' => true,
            ], 200),
        ]);

        config([
            'services.google.client_id'     => 'test-google-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect'      => 'https://lasbrazas.ramio.tech/auth/google/callback',
        ]);

        $redirectResponse = $this->get('/auth/google/redirect');
        $redirectResponse->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com/o/oauth2/v2/auth', (string) $redirectResponse->headers->get('Location'));

        $state = session('google_oauth_state');
        $this->assertNotEmpty($state);

        $callbackResponse = $this->withSession(['google_oauth_state' => $state])
            ->get('/auth/google/callback?code=fake-auth-code-xyz&state=' . $state);

        $callbackResponse->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $createdUser = \App\Models\User::where('email', 'carlos.google@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue((bool) $createdUser->is_active);
        $this->assertNotNull($createdUser->email_verified_at);
        $this->assertSame('Cliente', $createdUser->role?->name);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TermsMail::class, function ($mail) {
            return $mail->hasTo('carlos.google@example.com');
        });
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ConditionsMail::class, function ($mail) {
            return $mail->hasTo('carlos.google@example.com');
        });
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PrivacyPolicyMail::class, function ($mail) {
            return $mail->hasTo('carlos.google@example.com');
        });
        \Illuminate\Support\Facades\Mail::assertNotSent(\App\Mail\VerifyAccountMail::class);
    }

    public function test_privacy_policy_routes_render_dedicated_privacy_view(): void
    {
        $response = $this->get('/privacy');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Privacy'));

        $spanishAliasResponse = $this->get('/politica-de-privacidad');
        $spanishAliasResponse->assertOk();
        $spanishAliasResponse->assertInertia(fn ($page) => $page->component('Privacy'));
    }

    public function test_manual_registration_requires_both_terms_and_privacy_checkboxes_and_sends_four_emails(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        // Falla si solo marca términos sin política de privacidad
        $failResponse = $this->from('/register')->post('/register', [
            'name'                  => 'Ana Prueba',
            'email'                 => 'ana.prueba@example.com',
            'phone'                 => '+591 700-11111',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
            'terms'                 => true,
            'privacy'               => false,
        ]);
        $failResponse->assertRedirect('/register');
        $failResponse->assertSessionHasErrors(['privacy']);
        $this->assertNull(\App\Models\User::where('email', 'ana.prueba@example.com')->first());

        // Pasa cuando marca ambos checkboxes y envía los 4 correos
        $okResponse = $this->from('/register')->post('/register', [
            'name'                  => 'Ana Prueba',
            'email'                 => 'ana.prueba@example.com',
            'phone'                 => '+591 700-11111',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
            'terms'                 => true,
            'privacy'               => true,
        ]);
        $okResponse->assertRedirect('/login');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TermsMail::class);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ConditionsMail::class);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PrivacyPolicyMail::class);
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\VerifyAccountMail::class);
    }

    public function test_contact_form_validates_and_sends_email_to_configured_recipient(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['mail.contact_to' => 'restaurante.lasbrazas@gmail.com']);

        $response = $this->from('/')->post('/contact', [
            'name'    => 'Valeria Hurtado',
            'email'   => 'valeria.cliente@gmail.com',
            'phone'   => '+591 700-98765',
            'subject' => 'Reserva Grupal o Evento Corporativo',
            'message' => 'Hola, quisiera cotizar una cena corporativa para 20 personas este viernes.',
            'website' => '',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ContactMessageMail::class, function ($mail) {
            return $mail->hasTo('restaurante.lasbrazas@gmail.com')
                && ($mail->contactData['email'] ?? null) === 'valeria.cliente@gmail.com'
                && ($mail->contactData['subject'] ?? null) === 'Reserva Grupal o Evento Corporativo';
        });
    }

    public function test_corporate_landing_and_saas_quote_email_to_gmail(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['mail.contact_to' => 'erikaguilar189@gmail.com']);

        $pageResponse = $this->get('/empresa');
        $pageResponse->assertOk();
        $pageResponse->assertInertia(fn ($page) => $page->component('CorporateLanding'));

        $quoteResponse = $this->from('/empresa')->post('/empresa/cotizacion', [
            'restaurant_name' => 'Parrilla El Gaucho S.R.L.',
            'contact_name'    => 'Ing. Roberto Céspedes',
            'contact_phone'   => '+591 700-55555',
            'contact_email'   => 'gerencia@elgaucho.bo',
            'selected_plan'   => 'Plan Steakhouse Pro (Bs. 690 / mes - Mensual)',
            'cloud_provider'  => 'Servidor Cloud Dedicado + Alta Disponibilidad',
            'branches_count'  => 2,
            'message'         => 'Necesitamos 2 sucursales con KDS y App móvil.',
            'website'         => '',
        ]);

        $quoteResponse->assertRedirect('/empresa');
        $quoteResponse->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SaasQuoteMail::class, function ($mail) {
            return $mail->hasTo('erikaguilar189@gmail.com')
                && ($mail->quoteData['restaurant_name'] ?? null) === 'Parrilla El Gaucho S.R.L.'
                && ($mail->quoteData['contact_email'] ?? null) === 'gerencia@elgaucho.bo';
        });
    }

    public function test_strict_dual_login_separates_clients_and_staff_completely(): void
    {
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'Administrador'], ['description' => 'Admin']);
        $clientRole = \App\Models\Role::firstOrCreate(['name' => 'Cliente'], ['description' => 'Cliente']);

        $admin = \App\Models\User::create([
            'role_id'           => $adminRole->id,
            'name'              => 'Admin Staff',
            'email'             => 'staff.admin@lasbrazas.com',
            'password'          => bcrypt('password123'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $client = \App\Models\User::create([
            'role_id'           => $clientRole->id,
            'name'              => 'Cliente Portal',
            'email'             => 'cliente.portal@gmail.com',
            'password'          => bcrypt('password123'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // 1. Vista /staff/login carga correctamente
        $this->get('/staff/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/StaffLogin'));

        // 2. Administrador bloqueado en /login (Portal de Comensales)
        $this->from('/login')->post('/login', [
            'email'    => $admin->email,
            'password' => 'password123',
        ])->assertRedirect('/login')->assertSessionHasErrors(['email']);
        $this->assertGuest();

        // 3. Cliente bloqueado en /staff/login (Portal de Personal)
        $this->from('/staff/login')->post('/staff/login', [
            'email'    => $client->email,
            'password' => 'password123',
        ])->assertRedirect('/staff/login')->assertSessionHasErrors(['email']);
        $this->assertGuest();

        // 4. Cliente entra correctamente por /login
        $this->post('/login', [
            'email'    => $client->email,
            'password' => 'password123',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($client);
        $this->post('/logout');

        // 5. Administrador entra correctamente por /staff/login
        $this->post('/staff/login', [
            'email'    => $admin->email,
            'password' => 'password123',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout');

        // 6. Administrador bloqueado también al intentar Google OAuth en portal clientes
        \Illuminate\Support\Facades\Http::fake([
            'https://oauth2.googleapis.com/token' => \Illuminate\Support\Facades\Http::response([
                'access_token' => 'fake-admin-token',
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => \Illuminate\Support\Facades\Http::response([
                'email' => $admin->email,
                'name'  => $admin->name,
            ], 200),
        ]);
        config([
            'services.google.client_id'     => 'test-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect'      => 'https://lasbrazas.ramio.tech/auth/google/callback',
        ]);

        $this->withSession(['google_oauth_state' => 'state-admin-check'])
            ->get('/auth/google/callback?code=abc&state=state-admin-check')
            ->assertRedirect('/staff/login')
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_client_can_create_reservation_and_reload_portal_without_500_error(): void
    {
        $clientRole = \App\Models\Role::firstOrCreate(['name' => 'Cliente'], ['description' => 'Cliente']);
        $client = \App\Models\User::create([
            'role_id'           => $clientRole->id,
            'name'              => 'Julio Montano',
            'email'             => 'julio.reserva@gmail.com',
            'password'          => bcrypt('password123'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $area = \App\Models\Area::create([
            'name'        => 'Terraza Jardín',
            'description' => 'Zona exterior',
            'is_active'   => true,
        ]);

        \App\Models\Table::create([
            'area_id'      => $area->id,
            'table_number' => 'T-01',
            'capacity'     => 6,
            'status'       => 'available',
        ]);

        $storeResp = $this->actingAs($client)->postJson('/client/reservations', [
            'reservation_date' => now()->addDays(2)->setTime(20, 0)->toIso8601String(),
            'adults'           => 4,
            'children'         => 1,
            'area_id'          => $area->id,
            'special_requests' => 'Mesa cerca de la parrilla',
        ]);

        $storeResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.area_name', 'Terraza Jardín');

        // Recargar /dashboard con la reserva ya creada no debe arrojar 500
        $dashboardResp = $this->actingAs($client)->get('/dashboard');
        $dashboardResp->assertOk();
        $dashboardResp->assertInertia(fn ($page) => $page
            ->component('Client/Portal')
            ->has('reservations', 1)
        );

        // Si el cliente ya logueado entra a /login, redirige limpiamente sin 500
        $this->actingAs($client)->get('/login')->assertStatus(302);
    }

    public function test_public_ai_agent_answers_mission_location_terms_privacy_and_saas_plans(): void
    {
        // Forzar motor de conocimiento local para verificar precisión determinística sin depender de red
        config(['services.gemini.api_key' => null]);

        $missionResp = $this->postJson('/ai/public-chat', [
            'message' => '¿Cuál es la misión y visión de la empresa?',
            'portal'  => 'corporate',
        ]);
        $missionResp->assertOk()
            ->assertJsonPath('success', true);
        $this->assertStringContainsString('Misión', (string) $missionResp->json('reply'));
        $this->assertStringContainsString('Visión', (string) $missionResp->json('reply'));

        $locationResp = $this->postJson('/ai/public-chat', [
            'message' => '¿Dónde están ubicados y qué horarios tienen?',
            'portal'  => 'restaurant',
        ]);
        $locationResp->assertOk();
        $this->assertStringContainsString('Av. Las Brazas #120', (string) $locationResp->json('reply'));

        $saasResp = $this->postJson('/ai/public-chat', [
            'message' => '¿Qué planes SaaS tienen y cuánto cuestan?',
            'portal'  => 'corporate',
        ]);
        $saasResp->assertOk();
        $this->assertStringContainsString('Bs. 340', (string) $saasResp->json('reply'));
        $this->assertStringContainsString('Bs. 690', (string) $saasResp->json('reply'));
        $this->assertStringContainsString('Bs. 1,290', (string) $saasResp->json('reply'));

        $privacyResp = $this->postJson('/ai/public-chat', [
            'message' => 'Explícame la política de privacidad y el uso de Google',
            'portal'  => 'restaurant',
        ]);
        $privacyResp->assertOk();
        $this->assertStringContainsString('/privacy', (string) $privacyResp->json('reply'));

        $termsResp = $this->postJson('/ai/public-chat', [
            'message' => '¿Cuáles son los términos y condiciones de reserva?',
            'portal'  => 'restaurant',
        ]);
        $termsResp->assertOk();
        $this->assertStringContainsString('15 minutos', (string) $termsResp->json('reply'));

        // Seguimiento consultivo con memoria de historial (2 sucursales -> Enterprise Multi-Sede)
        $consultResp = $this->postJson('/ai/public-chat', [
            'message' => '¿Cuál es el más recomendado para mi situación?',
            'portal'  => 'corporate',
            'history' => [
                ['role' => 'user', 'content' => 'Tengo dos sucursales de puestos de parrillada y quiero expandirme con SaaS.'],
            ],
        ]);
        $consultResp->assertOk();
        $this->assertStringContainsString('Plan Enterprise Multi-Sede', (string) $consultResp->json('reply'));
    }

    public function test_public_ai_agent_auto_routes_vertex_express_aq_keys_to_aiplatform_endpoint(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Para tus dos sucursales y planes de expansión, te recomiendo directamente el **Plan Enterprise Multi-Sede (Bs. 1,290/mes)**.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        config([
            'services.gemini.api_key' => 'AQ.FakeAiStudioKey12345',
            'services.gemini.model'   => 'gemini-3.8-flash',
        ]);

        $resp = $this->postJson('/ai/public-chat', [
            'message' => 'Tengo dos sucursales y quiero expandirme, ¿qué plan me conviene?',
            'portal'  => 'corporate',
        ]);

        $resp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('engine', 'gemini-gemini-3.8-flash');
        $this->assertStringContainsString('Plan Enterprise Multi-Sede', (string) $resp->json('reply'));

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return str_contains($request->url(), 'generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent')
                && $request->hasHeader('x-goog-api-key', 'AQ.FakeAiStudioKey12345');
        });
    }
}
