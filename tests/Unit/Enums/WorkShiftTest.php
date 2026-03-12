<?php

namespace Tests\Unit\Enums;

use App\Enums\WorkShift;
use PHPUnit\Framework\TestCase;

class WorkShiftTest extends TestCase
{
    public function test_all_shift_cases_exist(): void
    {
        $cases = WorkShift::cases();
        $this->assertCount(5, $cases);

        $values = array_map(fn($c) => $c->value, $cases);
        $this->assertContains('pagi', $values);
        $this->assertContains('standby', $values);
        $this->assertContains('siang', $values);
        $this->assertContains('sweeping', $values);
        $this->assertContains('sore', $values);
    }

    public function test_label_returns_formatted_string(): void
    {
        $this->assertEquals('Pagi (05:30 - 07:30)', WorkShift::PAGI->label());
        $this->assertEquals('Standby (07:30 - 09:30)', WorkShift::STANDBY->label());
        $this->assertEquals('Siang (09:30 - 12:00)', WorkShift::SIANG->label());
        $this->assertEquals('Sweeping (13:00 - 14:00)', WorkShift::SWEEPING->label());
        $this->assertEquals('Sore (14:00 - 16:30)', WorkShift::SORE->label());
    }

    public function test_short_label(): void
    {
        $this->assertEquals('Pagi', WorkShift::PAGI->shortLabel());
        $this->assertEquals('Sore', WorkShift::SORE->shortLabel());
    }

    public function test_jam_mulai(): void
    {
        $this->assertEquals('05:30', WorkShift::PAGI->jamMulai());
        $this->assertEquals('07:30', WorkShift::STANDBY->jamMulai());
        $this->assertEquals('09:30', WorkShift::SIANG->jamMulai());
        $this->assertEquals('13:00', WorkShift::SWEEPING->jamMulai());
        $this->assertEquals('14:00', WorkShift::SORE->jamMulai());
    }

    public function test_jam_selesai(): void
    {
        $this->assertEquals('07:30', WorkShift::PAGI->jamSelesai());
        $this->assertEquals('09:30', WorkShift::STANDBY->jamSelesai());
        $this->assertEquals('12:00', WorkShift::SIANG->jamSelesai());
        $this->assertEquals('14:00', WorkShift::SWEEPING->jamSelesai());
        $this->assertEquals('16:30', WorkShift::SORE->jamSelesai());
    }

    public function test_color(): void
    {
        $this->assertEquals('info', WorkShift::PAGI->color());
        $this->assertEquals('warning', WorkShift::STANDBY->color());
        $this->assertEquals('success', WorkShift::SIANG->color());
        $this->assertEquals('primary', WorkShift::SWEEPING->color());
        $this->assertEquals('danger', WorkShift::SORE->color());
    }

    public function test_options_returns_keyed_array(): void
    {
        $options = WorkShift::options();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('pagi', $options);
        $this->assertArrayHasKey('sore', $options);
        $this->assertEquals('Pagi (05:30 - 07:30)', $options['pagi']);
    }

    public function test_from_time_returns_correct_shift(): void
    {
        $this->assertEquals(WorkShift::PAGI, WorkShift::fromTime('06:00'));
        $this->assertEquals(WorkShift::STANDBY, WorkShift::fromTime('08:00'));
        $this->assertEquals(WorkShift::SIANG, WorkShift::fromTime('10:00'));
        $this->assertEquals(WorkShift::SWEEPING, WorkShift::fromTime('13:30'));
        $this->assertEquals(WorkShift::SORE, WorkShift::fromTime('15:00'));
    }

    public function test_from_time_returns_null_for_out_of_range(): void
    {
        $this->assertNull(WorkShift::fromTime('03:00'));
        $this->assertNull(WorkShift::fromTime('22:00'));
    }

    public function test_from_time_boundary_start(): void
    {
        // Exact start time should be included
        $this->assertEquals(WorkShift::PAGI, WorkShift::fromTime('05:30'));
    }

    public function test_from_time_boundary_end(): void
    {
        // Exact end time should NOT be included (< not <=)
        // 07:30 is the end of PAGI and start of STANDBY
        $this->assertEquals(WorkShift::STANDBY, WorkShift::fromTime('07:30'));
    }

    public function test_shift_times_are_contiguous_where_expected(): void
    {
        // PAGI ends at 07:30, STANDBY starts at 07:30
        $this->assertEquals(WorkShift::PAGI->jamSelesai(), WorkShift::STANDBY->jamMulai());
        // STANDBY ends at 09:30, SIANG starts at 09:30
        $this->assertEquals(WorkShift::STANDBY->jamSelesai(), WorkShift::SIANG->jamMulai());
        // SWEEPING ends at 14:00, SORE starts at 14:00
        $this->assertEquals(WorkShift::SWEEPING->jamSelesai(), WorkShift::SORE->jamMulai());
    }
}
