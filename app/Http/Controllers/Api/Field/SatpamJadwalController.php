<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Resources\FieldJadwalResource;
use App\Models\JadwalSatpam;

/**
 * Patrol schedules for satpam (security).
 *
 * @group Satpam
 */
class SatpamJadwalController extends BaseJadwalController
{
    protected function model(): string
    {
        return JadwalSatpam::class;
    }

    protected function resourceClass(): string
    {
        return FieldJadwalResource::class;
    }

    protected function ownerRole(): string
    {
        return 'satpam';
    }
}
