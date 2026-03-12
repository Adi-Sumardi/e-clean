<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\NotificationTemplateService;
use PHPUnit\Framework\TestCase;

class NotificationTemplateServiceTest extends TestCase
{
    private NotificationTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationTemplateService();
    }

    public function test_morning_work_reminder_contains_name(): void
    {
        $petugas = new User();
        $petugas->name = 'Budi';

        $result = $this->service->morningWorkReminder($petugas);

        $this->assertStringContainsString('Budi', $result);
        $this->assertStringContainsString('PENGINGAT TUGAS HARI INI', $result);
        $this->assertStringContainsString('E-Cleaning', $result);
    }

    public function test_shift_end_reminder_contains_name(): void
    {
        $petugas = new User();
        $petugas->name = 'Andi';

        $result = $this->service->shiftEndReminder($petugas);

        $this->assertStringContainsString('Andi', $result);
        $this->assertStringContainsString('PENGINGAT AKHIR SHIFT', $result);
    }

    public function test_weekly_performance_summary_high_rating(): void
    {
        $petugas = new User();
        $petugas->name = 'Siti';

        $stats = [
            'reports' => 10,
            'approved' => 9,
            'avg_rating' => 4.5,
            'attendance' => 5,
        ];

        $result = $this->service->weeklyPerformanceSummary($petugas, $stats);

        $this->assertStringContainsString('Siti', $result);
        $this->assertStringContainsString('10', $result);
        $this->assertStringContainsString('9', $result);
        $this->assertStringContainsString('4.5', $result);
        $this->assertStringContainsString('Kerja bagus', $result);
    }

    public function test_weekly_performance_summary_low_rating(): void
    {
        $petugas = new User();
        $petugas->name = 'Test';

        $stats = [
            'reports' => 5,
            'approved' => 3,
            'avg_rating' => 3.0,
            'attendance' => 3,
        ];

        $result = $this->service->weeklyPerformanceSummary($petugas, $stats);

        $this->assertStringContainsString('tingkatkan', $result);
    }
}
