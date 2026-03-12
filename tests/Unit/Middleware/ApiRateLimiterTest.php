<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ApiRateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    private ApiRateLimiter $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ApiRateLimiter();
        RateLimiter::clear('api');
    }

    public function test_get_max_attempts_for_api(): void
    {
        $reflection = new \ReflectionMethod($this->middleware, 'getMaxAttempts');
        $reflection->setAccessible(true);

        $this->assertEquals(60, $reflection->invoke($this->middleware, 'api'));
        $this->assertEquals(10, $reflection->invoke($this->middleware, 'whatsapp'));
        $this->assertEquals(20, $reflection->invoke($this->middleware, 'uploads'));
        $this->assertEquals(5, $reflection->invoke($this->middleware, 'login'));
        $this->assertEquals(5, $reflection->invoke($this->middleware, 'export'));
        $this->assertEquals(60, $reflection->invoke($this->middleware, 'unknown'));
    }

    public function test_get_decay_seconds(): void
    {
        $reflection = new \ReflectionMethod($this->middleware, 'getDecaySeconds');
        $reflection->setAccessible(true);

        $this->assertEquals(60, $reflection->invoke($this->middleware, 'api'));
        $this->assertEquals(3600, $reflection->invoke($this->middleware, 'uploads'));
        $this->assertEquals(3600, $reflection->invoke($this->middleware, 'export'));
    }

    public function test_resolve_request_signature_with_user(): void
    {
        $user = \App\Models\User::factory()->create();
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => $user);

        $reflection = new \ReflectionMethod($this->middleware, 'resolveRequestSignature');
        $reflection->setAccessible(true);

        $expected = sha1('api|' . $user->id);
        $this->assertEquals($expected, $reflection->invoke($this->middleware, $request, 'api'));
    }

    public function test_resolve_request_signature_without_user(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => null);

        $reflection = new \ReflectionMethod($this->middleware, 'resolveRequestSignature');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->middleware, $request, 'api');
        $this->assertNotEmpty($result);
    }

    public function test_adds_rate_limit_headers(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(fn() => null);

        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'api');

        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
        $this->assertTrue($response->headers->has('X-RateLimit-Reset'));
        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_clear_static_method(): void
    {
        // Should not throw
        ApiRateLimiter::clear('test-key');
        $this->assertTrue(true);
    }
}
