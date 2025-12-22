<?php

namespace App\Http\Controllers;

use App\Models\GuestComplaint;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use App\Services\FontteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestComplaintController extends Controller
{
    /**
     * Show the complaint form for a specific location (via barcode scan)
     */
    public function showForm(string $kodeOrId)
    {
        // Try to find by kode_lokasi first
        $lokasi = Lokasi::where('kode_lokasi', $kodeOrId)
            ->where('is_active', true)
            ->first();

        // Try by ID if not found by kode
        if (!$lokasi && is_numeric($kodeOrId)) {
            $lokasi = Lokasi::where('id', $kodeOrId)
                ->where('is_active', true)
                ->first();
        }

        if (!$lokasi) {
            return response()->view('guest-complaint.not-found', [
                'kode' => $kodeOrId,
            ], 404);
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

        // Send WhatsApp notification to petugas
        $this->sendNotificationToPetugas($complaint, $lokasi);

        return redirect()
            ->route('guest-complaint.success', ['lokasi' => $lokasi->kode_lokasi])
            ->with('success', 'Keluhan Anda telah berhasil dikirim. Terima kasih atas laporannya.');
    }

    /**
     * Send WhatsApp notification to petugas assigned to this location
     */
    protected function sendNotificationToPetugas(GuestComplaint $complaint, Lokasi $lokasi): void
    {
        try {
            $fontteService = new FontteService();

            if (!$fontteService->isConfigured()) {
                Log::warning('Fontte not configured, skipping complaint notification');
                return;
            }

            // Get petugas assigned to this location today
            $petugasIds = JadwalKebersihan::where('lokasi_id', $lokasi->id)
                ->where('tanggal', today())
                ->where('status', 'active')
                ->pluck('petugas_id')
                ->unique()
                ->toArray();

            // If no petugas today, get supervisors
            if (empty($petugasIds)) {
                $petugasUsers = User::role('supervisor')
                    ->where('is_active', true)
                    ->whereNotNull('phone')
                    ->get()
                    ->all();
            } else {
                $petugasUsers = User::whereIn('id', $petugasIds)
                    ->where('is_active', true)
                    ->whereNotNull('phone')
                    ->get()
                    ->all();
            }

            if (empty($petugasUsers)) {
                Log::info('No petugas/supervisor to notify for complaint', [
                    'complaint_id' => $complaint->id,
                    'lokasi_id' => $lokasi->id,
                ]);
                return;
            }

            $result = $fontteService->sendGuestComplaintNotification($complaint, $petugasUsers);

            Log::info('Guest complaint notification sent', [
                'complaint_id' => $complaint->id,
                'lokasi_id' => $lokasi->id,
                'sent' => $result['sent'],
                'failed' => $result['failed'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send complaint notification: ' . $e->getMessage(), [
                'complaint_id' => $complaint->id,
                'lokasi_id' => $lokasi->id,
            ]);
        }
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
