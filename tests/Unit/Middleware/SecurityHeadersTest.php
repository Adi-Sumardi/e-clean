<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    private SecurityHeaders $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeaders();
    }

    private function processRequest(bool $secure = false): Response
    {
        $request = Request::create('/test', 'GET', [], [], [], $secure ? ['HTTPS' => 'on'] : []);

        return $this->middleware->handle($request, function () {
            return new Response('OK');
        });
    }

    public function test_sets_x_content_type_options(): void
    {
        $response = $this->processRequest();
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_sets_x_frame_options(): void
    {
        $response = $this->processRequest();
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_sets_x_xss_protection(): void
    {
        $response = $this->processRequest();
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function test_sets_referrer_policy(): void
    {
        $response = $this->processRequest();
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    public function test_sets_permissions_policy(): void
    {
        $response = $this->processRequest();
        $policy = $response->headers->get('Permissions-Policy');

        $this->assertStringContainsString('geolocation=(self)', $policy);
        $this->assertStringContainsString('camera=(self)', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
    }

    public function test_sets_hsts_for_https(): void
    {
        $response = $this->processRequest(secure: true);
        $hsts = $response->headers->get('Strict-Transport-Security');

        $this->assertNotNull($hsts);
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
    }

    public function test_does_not_set_hsts_for_http(): void
    {
        $response = $this->processRequest(secure: false);
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_sets_content_security_policy(): void
    {
        $response = $this->processRequest();
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_csp_allows_required_external_resources(): void
    {
        $response = $this->processRequest();
        $csp = $response->headers->get('Content-Security-Policy');

        // Leaflet maps need these
        $this->assertStringContainsString('https://unpkg.com', $csp);
        $this->assertStringContainsString('https://fonts.googleapis.com', $csp);
        $this->assertStringContainsString('https://nominatim.openstreetmap.org', $csp);
    }

    public function test_response_body_is_preserved(): void
    {
        $response = $this->processRequest();
        $this->assertEquals('OK', $response->getContent());
    }
}
