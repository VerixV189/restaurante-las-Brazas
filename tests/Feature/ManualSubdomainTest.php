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
            'selected_plan'   => 'Plan Steakhouse Pro ($99 USD/mes - Mensual)',
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
}
