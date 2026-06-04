<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Resources\LaporanSatpamResource;
use App\Models\LaporanSatpam;
use Illuminate\Http\Request;

/**
 * Patrol reports for satpam, including the supervisor approval workflow.
 *
 * @group Satpam
 */
class SatpamLaporanController extends BaseLaporanController
{
    protected function model(): string
    {
        return LaporanSatpam::class;
    }

    protected function resourceClass(): string
    {
        return LaporanSatpamResource::class;
    }

    protected function ownerRole(): string
    {
        return 'satpam';
    }

    protected function storeRules(): array
    {
        return [
            'kondisi' => 'required|in:aman,perhatian,bahaya',
            'temuan' => 'nullable|string|max:1000',
            'tindakan' => 'nullable|string|max:1000',
            'foto' => 'nullable|array|max:5',
            'foto.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
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
            'kondisi' => $validated['kondisi'],
            'temuan' => $validated['temuan'] ?? null,
            'tindakan' => $validated['tindakan'] ?? null,
            'catatan_petugas' => $validated['catatan_petugas'] ?? null,
            'foto' => $this->storePhotos($request, 'foto', 'laporan-satpam') ?: null,
        ];
    }
}
