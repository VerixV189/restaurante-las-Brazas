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
}
