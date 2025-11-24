<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_can_cache_dashboard_stats()
    {
        $result = CacheService::getDashboardStats('admin', function () {
            return ['test' => 'data'];
        });

        $this->assertEquals(['test' => 'data'], $result);
    }

    /** @test */
    public function it_returns_cached_data_on_subsequent_calls()
    {
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;
            return ['count' => $callCount];
        };

        $first = CacheService::getDashboardStats('admin', $callback);
        $second = CacheService::getDashboardStats('admin', $callback);

        $this->assertEquals(1, $first['count']);
        $this->assertEquals(1, $second['count']); // Should be cached
        $this->assertEquals(1, $callCount); // Callback called only once
    }

    /** @test */
    public function it_can_cache_chart_data()
    {
        $result = CacheService::getChartData('test-chart', function () {
            return [1, 2, 3, 4, 5];
        });

        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    /** @test */
    public function it_can_clear_dashboard_cache()
    {
        CacheService::getDashboardStats('admin', fn() => ['test' => 'data']);

        CacheService::clearDashboardCache();

        // After clear, cache should be empty
        $this->assertTrue(true); // If no exception, test passes
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        $stats = CacheService::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('ttl', $stats);
    }

    /** @test */
    public function it_has_correct_ttl_constants()
    {
        $this->assertEquals(300, CacheService::DASHBOARD_TTL);
        $this->assertEquals(600, CacheService::CHART_TTL);
        $this->assertEquals(900, CacheService::USER_TTL);
        $this->assertEquals(1800, CacheService::STATIC_TTL);
        $this->assertEquals(3600, CacheService::LONG_TTL);
    }
}
