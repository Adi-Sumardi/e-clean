<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanTokoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jam_mulai' => $this->jam_mulai?->format('H:i'),
            'jam_selesai' => $this->jam_selesai?->format('H:i'),
            // Store-specific
            'checklist' => is_array($this->checklist) ? $this->checklist : [],
            'kondisi_stok' => $this->kondisi_stok,
            'catatan_stok' => $this->catatan_stok,
            'foto' => $this->mapPhotos($this->foto),
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
