<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jadwal_id' => 'required|exists:jadwal_kebersihanans,id',
            'lokasi_id' => 'required|exists:lokasis,id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'kegiatan' => 'required|string|max:1000',
            'foto_sebelum' => 'nullable|array|max:5',
            'foto_sebelum.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'foto_sesudah' => 'nullable|array|max:5',
            'foto_sesudah.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'koordinat_lokasi' => 'nullable|string|max:100',
            'catatan_petugas' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,submitted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'jadwal_id.required' => 'Jadwal harus dipilih',
            'jadwal_id.exists' => 'Jadwal tidak valid',
            'lokasi_id.required' => 'Lokasi harus dipilih',
            'lokasi_id.exists' => 'Lokasi tidak valid',
            'tanggal.required' => 'Tanggal harus diisi',
            'jam_mulai.required' => 'Jam mulai harus diisi',
            'jam_selesai.required' => 'Jam selesai harus diisi',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai',
            'kegiatan.required' => 'Kegiatan harus diisi',
            'kegiatan.max' => 'Kegiatan maksimal 1000 karakter',
            'foto_sebelum.*.image' => 'File harus berupa gambar',
            'foto_sebelum.*.max' => 'Ukuran gambar maksimal 5MB',
            'foto_sesudah.*.image' => 'File harus berupa gambar',
            'foto_sesudah.*.max' => 'Ukuran gambar maksimal 5MB',
        ];
    }
}
