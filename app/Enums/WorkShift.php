<?php

namespace App\Enums;

enum WorkShift: string
{
    case PAGI = 'pagi';
    case STANDBY = 'standby';
    case SIANG = 'siang';
    case SWEEPING = 'sweeping';
    case SORE = 'sore';

    public function label(): string
    {
        return match($this) {
            self::PAGI => 'Pagi (05:30 - 07:30)',
            self::STANDBY => 'Standby (07:30 - 09:30)',
            self::SIANG => 'Siang (09:30 - 12:00)',
            self::SWEEPING => 'Sweeping (13:00 - 14:00)',
            self::SORE => 'Sore (14:00 - 16:30)',
        };
    }

    public function shortLabel(): string
    {
        return match($this) {
            self::PAGI => 'Pagi',
            self::STANDBY => 'Standby',
            self::SIANG => 'Siang',
            self::SWEEPING => 'Sweeping',
            self::SORE => 'Sore',
        };
    }

    public function jamMulai(): string
    {
        return match($this) {
            self::PAGI => '05:30',
            self::STANDBY => '07:30',
            self::SIANG => '09:30',
            self::SWEEPING => '13:00',
            self::SORE => '14:00',
        };
    }

    public function jamSelesai(): string
    {
        return match($this) {
            self::PAGI => '07:30',
            self::STANDBY => '09:30',
            self::SIANG => '12:00',
            self::SWEEPING => '14:00',
            self::SORE => '16:30',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PAGI => 'info',
            self::STANDBY => 'warning',
            self::SIANG => 'success',
            self::SWEEPING => 'primary',
            self::SORE => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label()
        ])->toArray();
    }

    public static function fromTime(string $time): ?self
    {
        $timeMinutes = self::timeToMinutes($time);

        foreach (self::cases() as $shift) {
            $startMinutes = self::timeToMinutes($shift->jamMulai());
            $endMinutes = self::timeToMinutes($shift->jamSelesai());

            if ($timeMinutes >= $startMinutes && $timeMinutes < $endMinutes) {
                return $shift;
            }
        }

        return null;
    }

    private static function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int) $hours * 60 + (int) $minutes;
    }
}
