<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ApiRequestLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ApiRequestLoggerTest extends TestCase
{
    private ApiRequestLogger $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ApiRequestLogger();
    }

    public function test_logs_request_and_response(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf()
            ->times(2); // Once for request, once for response

        Log::shouldReceive('info')
            ->with('API Request', \Mockery::type('array'))
            ->once();

        Log::shouldReceive('info')
            ->with('API Response', \Mockery::type('array'))
            ->once();

        $request = Request::create('/api/test', 'GET');

        $this->middleware->handle($request, function () {
            return new Response('OK');
        });
    }

    public function test_response_is_passed_through(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->andReturnNull();

        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response('Test Body', 200);
        });

        $this->assertEquals('Test Body', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
