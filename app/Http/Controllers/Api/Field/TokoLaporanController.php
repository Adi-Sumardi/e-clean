<?php

namespace App\Http\Controllers\Api\Field;

use App\Http\Resources\LaporanTokoResource;
use App\Models\LaporanToko;
use Illuminate\Http\Request;

/**
 * Daily store checklist reports for petugas toko, including the supervisor
 * approval workflow.
 *
 * @group Petugas Toko
 */
class TokoLaporanController extends BaseLaporanController
{
    protected function model(): string
    {
        return LaporanToko::class;
    }

    protected function resourceClass(): string
    {
        return LaporanTokoResource::class;
    }

    protected function ownerRole(): string
    {
        return 'petugas_toko';
    }

    protected function storeRules(): array
    {
        return [
            'checklist' => 'nullable|array',
            'checklist.*.item' => 'required_with:checklist|string|max:255',
            'checklist.*.done' => 'required_with:checklist|boolean',
            'kondisi_stok' => 'nullable|in:aman,menipis,kosong',
            'catatan_stok' => 'nullable|string|max:1000',
            'foto' => 'nullable|array|max:10',
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
            'checklist' => $validated['checklist'] ?? null,
            'kondisi_stok' => $validated['kondisi_stok'] ?? null,
            'catatan_stok' => $validated['catatan_stok'] ?? null,
            'catatan_petugas' => $validated['catatan_petugas'] ?? null,
            'foto' => $this->storePhotos($request, 'foto', 'laporan-toko') ?: null,
        ];
    }
}
