<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jam_mulai' => $this->jam_mulai?->format('H:i'),
            'jam_selesai' => $this->jam_selesai?->format('H:i'),
            'kegiatan' => $this->kegiatan,
            'foto_sebelum' => $this->foto_sebelum ? array_map(function($foto) {
                return url('storage/' . $foto);
            }, is_array($this->foto_sebelum) ? $this->foto_sebelum : []) : [],
            'foto_sesudah' => $this->foto_sesudah ? array_map(function($foto) {
                return url('storage/' . $foto);
            }, is_array($this->foto_sesudah) ? $this->foto_sesudah : []) : [],
            'koordinat_lokasi' => $this->koordinat_lokasi,
            'status' => $this->status,
            'catatan_petugas' => $this->catatan_petugas,
            'catatan_supervisor' => $this->catatan_supervisor,
            'rating' => $this->rating,
            'rejected_reason' => $this->rejected_reason,
            'approved_at' => $this->approved_at?->toISOString(),
            'petugas' => $this->whenLoaded('petugas', function() {
                return [
                    'id' => $this->petugas->id,
                    'name' => $this->petugas->name,
                    'phone' => $this->petugas->phone,
                ];
            }),
            'lokasi' => $this->whenLoaded('lokasi', function() {
                return new LokasiResource($this->lokasi);
            }),
            'jadwal' => $this->whenLoaded('jadwal', function() {
                return new JadwalKebersihanResource($this->jadwal);
            }),
            'approver' => $this->whenLoaded('approver', function() {
                return $this->approver ? [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
