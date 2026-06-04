<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_unit' => $this->kode_unit,
            'nama_unit' => $this->nama_unit,
            'deskripsi' => $this->deskripsi,
            'alamat' => $this->alamat,
            'penanggung_jawab' => $this->penanggung_jawab,
            'telepon' => $this->telepon,
            'is_active' => $this->is_active,
            'lokasi_count' => $this->whenCounted('lokasis'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
