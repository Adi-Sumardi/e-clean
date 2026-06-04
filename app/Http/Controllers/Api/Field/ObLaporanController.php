<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Resources\LaporanObResource;
use App\Models\LaporanOb;
use Illuminate\Http\Request;

/**
 * Area service reports for office boys, including the supervisor approval
 * workflow. Supports before/after photos.
 *
 * @group Office Boy
 */
class ObLaporanController extends BaseLaporanController
{
    protected function model(): string
    {
        return LaporanOb::class;
    }

    protected function resourceClass(): string
    {
        return LaporanObResource::class;
    }

    protected function ownerRole(): string
    {
        return 'office_boy';
    }

    protected function storeRules(): array
    {
        return [
            'jenis_pekerjaan' => 'nullable|string|max:255',
            'uraian' => 'nullable|string|max:1000',
            'foto_sebelum' => 'nullable|array|max:5',
            'foto_sebelum.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_sesudah' => 'nullable|array|max:5',
            'foto_sesudah.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    protected function buildAttributes(array $validated, Request $request): array
    {
        return [
            'jadwal_id' => $validated['jadwal_id'] ?? null,
            'lokasi_id' => $validated['lokasi_id'],
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'jenis_pekerjaan' => $validated['jenis_pekerjaan'] ?? null,
            'uraian' => $validated['uraian'] ?? null,
            'catatan_petugas' => $validated['catatan_petugas'] ?? null,
            'foto_sebelum' => $this->storePhotos($request, 'foto_sebelum', 'laporan-ob/before') ?: null,
            'foto_sesudah' => $this->storePhotos($request, 'foto_sesudah', 'laporan-ob/after') ?: null,
        ];
    }
}
