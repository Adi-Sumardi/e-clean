<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'foto_masuk' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'lokasi_absen_masuk' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'foto_masuk.image' => 'File harus berupa gambar',
            'foto_masuk.max' => 'Ukuran gambar maksimal 5MB',
            'lokasi_absen_masuk.max' => 'Lokasi maksimal 255 karakter',
            'keterangan.max' => 'Keterangan maksimal 500 karakter',
        ];
    }
}
