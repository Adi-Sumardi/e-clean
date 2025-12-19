<?php

namespace App\Http\Controllers;

use App\Models\GuestComplaint;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestComplaintController extends Controller
{
    /**
     * Show the complaint form for a specific location (via barcode scan)
     */
    public function showForm(string $kodeOrId)
    {
        // Try to find by kode_lokasi or id
        $lokasi = Lokasi::where('kode_lokasi', $kodeOrId)
            ->orWhere('id', $kodeOrId)
            ->where('is_active', true)
            ->first();

        if (!$lokasi) {
            abort(404, 'Lokasi tidak ditemukan');
        }

        return view('guest-complaint.form', [
            'lokasi' => $lokasi,
            'jenisKeluhanOptions' => GuestComplaint::getJenisKeluhanOptions(),
        ]);
    }

    /**
     * Store the complaint from guest
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lokasi_id' => 'required|exists:lokasis,id',
            'nama_pelapor' => 'required|string|max:255',
            'email_pelapor' => 'nullable|email|max:255',
            'telepon_pelapor' => 'nullable|string|max:20',
            'jenis_keluhan' => 'required|in:' . implode(',', array_keys(GuestComplaint::getJenisKeluhanOptions())),
            'deskripsi_keluhan' => 'required|string|max:1000',
            'foto_keluhan' => 'nullable|image|max:5120', // Max 5MB
        ], [
            'lokasi_id.required' => 'Lokasi wajib dipilih',
            'lokasi_id.exists' => 'Lokasi tidak valid',
            'nama_pelapor.required' => 'Nama pelapor wajib diisi',
            'jenis_keluhan.required' => 'Jenis keluhan wajib dipilih',
            'deskripsi_keluhan.required' => 'Deskripsi keluhan wajib diisi',
            'foto_keluhan.image' => 'File harus berupa gambar',
            'foto_keluhan.max' => 'Ukuran foto maksimal 5MB',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Handle photo upload
        if ($request->hasFile('foto_keluhan')) {
            $photo = $request->file('foto_keluhan');
            $filename = 'complaint-' . Str::random(20) . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('complaints', $filename, 'public');
            $data['foto_keluhan'] = $path;
        }

        $complaint = GuestComplaint::create($data);

        // Get lokasi for redirect
        $lokasi = Lokasi::find($data['lokasi_id']);

        return redirect()
            ->route('guest-complaint.success', ['lokasi' => $lokasi->kode_lokasi])
            ->with('success', 'Keluhan Anda telah berhasil dikirim. Terima kasih atas laporannya.');
    }

    /**
     * Show success page
     */
    public function success(string $lokasiKode)
    {
        $lokasi = Lokasi::where('kode_lokasi', $lokasiKode)->first();

        return view('guest-complaint.success', [
            'lokasi' => $lokasi,
        ]);
    }
}
