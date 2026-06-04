<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Resources\FieldJadwalResource;
use App\Models\JadwalOb;

/**
 * Area schedules for office boys.
 *
 * @group Office Boy
 */
class ObJadwalController extends BaseJadwalController
{
    protected function model(): string
    {
        return JadwalOb::class;
    }

    protected function resourceClass(): string
    {
        return FieldJadwalResource::class;
    }

    protected function ownerRole(): string
    {
        return 'office_boy';
    }
}
