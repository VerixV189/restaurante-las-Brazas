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
        $this->assertStringContainsString('lasbrazas-static-v1', $swContent);
        $this->assertStringContainsString('lasbrazas-session-v1', $swContent);
        $this->assertStringContainsString('CLEAR_AUTH_CACHE', $swContent);

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
}
