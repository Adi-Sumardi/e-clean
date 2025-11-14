<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenilaianResource extends JsonResource
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
            'periode_bulan' => $this->periode_bulan,
            'periode_tahun' => $this->periode_tahun,
            'skor_kehadiran' => $this->skor_kehadiran,
            'skor_kualitas' => $this->skor_kualitas,
            'skor_ketepatan_waktu' => $this->skor_ketepatan_waktu,
            'skor_kebersihan' => $this->skor_kebersihan,
            'total_skor' => $this->total_skor,
            'rata_rata' => $this->rata_rata,
            'kategori' => $this->kategori,
            'catatan' => $this->catatan,

            // Petugas relationship
            'petugas' => $this->whenLoaded('petugas', function() {
                return [
                    'id' => $this->petugas->id,
                    'name' => $this->petugas->name,
                    'email' => $this->petugas->email,
                    'phone' => $this->petugas->phone,
                ];
            }),

            // Penilai (evaluator) relationship
            'penilai' => $this->whenLoaded('penilai', function() {
                return [
                    'id' => $this->penilai->id,
                    'name' => $this->penilai->name,
                    'email' => $this->penilai->email,
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
