<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LokasiResource extends JsonResource
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
            'kode_lokasi' => $this->kode_lokasi,
            'nama_lokasi' => $this->nama_lokasi,
            'kategori' => $this->kategori,
            'lantai' => $this->lantai,
            'deskripsi' => $this->deskripsi,
            'foto' => $this->foto ? url('storage/' . $this->foto) : null,
            'is_active' => $this->is_active,
            'unit' => $this->whenLoaded('unit', fn () => $this->unit ? [
                'id' => $this->unit->id,
                'kode_unit' => $this->unit->kode_unit,
                'nama_unit' => $this->unit->nama_unit,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
