<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanObResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jam_mulai' => $this->jam_mulai?->format('H:i'),
            'jam_selesai' => $this->jam_selesai?->format('H:i'),
            // Office-boy-specific
            'jenis_pekerjaan' => $this->jenis_pekerjaan,
            'uraian' => $this->uraian,
            'foto_sebelum' => $this->mapPhotos($this->foto_sebelum),
            'foto_sesudah' => $this->mapPhotos($this->foto_sesudah),
            // Approval workflow
            'status' => $this->status,
            'catatan_petugas' => $this->catatan_petugas,
            'catatan_supervisor' => $this->catatan_supervisor,
            'rating' => $this->rating,
            'rejected_reason' => $this->rejected_reason,
            'approved_at' => $this->approved_at?->toISOString(),
            'petugas' => $this->whenLoaded('petugas', fn () => [
                'id' => $this->petugas->id,
                'name' => $this->petugas->name,
                'phone' => $this->petugas->phone,
            ]),
            'lokasi' => $this->whenLoaded('lokasi', fn () => new LokasiResource($this->lokasi)),
            'unit' => $this->unitPayload(),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** @param mixed $photos */
    protected function mapPhotos($photos): array
    {
        if (! is_array($photos)) {
            return [];
        }

        return array_map(fn ($p) => url('storage/' . $p), $photos);
    }

    protected function unitPayload(): ?array
    {
        $unit = $this->lokasi?->unit;

        return $unit ? [
            'id' => $unit->id,
            'kode_unit' => $unit->kode_unit,
            'nama_unit' => $unit->nama_unit,
        ] : null;
    }
}
