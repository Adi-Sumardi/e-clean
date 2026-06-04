<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shared resource for the satpam / OB / toko schedule tables, which all have
 * the same shape.
 */
class FieldJadwalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'shift' => $this->shift,
            'jam_mulai' => $this->jam_mulai?->format('H:i'),
            'jam_selesai' => $this->jam_selesai?->format('H:i'),
            'status' => $this->status,
            'catatan' => $this->catatan,
            'petugas' => $this->whenLoaded('petugas', fn () => [
                'id' => $this->petugas->id,
                'name' => $this->petugas->name,
                'phone' => $this->petugas->phone,
            ]),
            'lokasi' => $this->whenLoaded('lokasi', fn () => new LokasiResource($this->lokasi)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
