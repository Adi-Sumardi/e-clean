<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Resources\FieldJadwalResource;
use App\Models\JadwalToko;

/**
 * Store shift schedules for petugas toko.
 *
 * @group Petugas Toko
 */
class TokoJadwalController extends BaseJadwalController
{
    protected function model(): string
    {
        return JadwalToko::class;
    }

    protected function resourceClass(): string
    {
        return FieldJadwalResource::class;
    }

    protected function ownerRole(): string
    {
        return 'petugas_toko';
    }
}
