<?php

namespace App\Http\Controllers;

use App\Models\ActivityReport;
use App\Models\GuestComplaint;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GuestComplaintController extends Controller
{
    /**
     * Show the complaint form for a specific location (via QR Code scan)
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
            'tipeLaporanOptions' => GuestComplaint::getTipeLaporanOptions(),
            'lastCleaning' => $this->getLastCleaning($lokasi),
        ]);
    }

    /**
     * Get the most recent cleaning info for a location, so the guest can see
     * the room was already cleaned by {petugas} at {jam} before reporting.
     *
     * @return array{petugas:string, jam:?string, tanggal:\Illuminate\Support\Carbon, is_today:bool}|null
     */
    protected function getLastCleaning(Lokasi $lokasi): ?array
    {
        $report = ActivityReport::with('petugas')
            ->where('lokasi_id', $lokasi->id)
            ->whereIn('status', ['submitted', 'approved'])
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_selesai')
            ->orderByDesc('created_at')
            ->first();

        if (! $report) {
            return null;
        }

        $jam = $report->jam_selesai ?? $report->jam_mulai;

        return [
            'petugas' => $report->petugas?->name ?? 'Petugas',
            'jam' => $jam ? $jam->format('H:i') : null,
            'tanggal' => $report->tanggal,
            'is_today' => $report->tanggal?->isToday() ?? false,
        ];
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
            'telepon_pelapor' => ['nullable', 'string', 'max:20', 'regex:/^(08|628|\+628)[0-9]{8,12}$/'],
            'jenis_keluhan' => 'required|in:' . implode(',', array_keys(GuestComplaint::getJenisKeluhanOptions())),
            'tipe_laporan' => 'required|in:' . implode(',', array_keys(GuestComplaint::getTipeLaporanOptions())),
            'deskripsi_keluhan' => 'required|string|max:1000',
            'foto_keluhan' => 'nullable|image|max:5120', // Max 5MB
        ], [
            'lokasi_id.required' => 'Lokasi wajib dipilih',
            'lokasi_id.exists' => 'Lokasi tidak valid',
            'nama_pelapor.required' => 'Nama pelapor wajib diisi',
            'jenis_keluhan.required' => 'Jenis keluhan wajib dipilih',
            'tipe_laporan.required' => 'Tipe laporan wajib dipilih',
            'tipe_laporan.in' => 'Tipe laporan tidak valid',
            'deskripsi_keluhan.required' => 'Deskripsi keluhan wajib diisi',
            'foto_keluhan.image' => 'File harus berupa gambar',
            'foto_keluhan.max' => 'Ukuran foto maksimal 5MB',
            'telepon_pelapor.regex' => 'Format nomor telepon tidak valid (contoh: 08123456789)',
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

        // Get lokasi for redirect
        $lokasi = Lokasi::find($data['lokasi_id']);

        // Auto-assign to petugas (sesuai tipe laporan) yang bertugas di lokasi ini
        $assignedPetugas = $this->getAssignedPetugas($lokasi, $data['tipe_laporan']);
        if ($assignedPetugas) {
            $data['assigned_to'] = $assignedPetugas->id;
            $data['assigned_at'] = now();
        }

        $complaint = GuestComplaint::create($data);

        // Send WhatsApp notification to petugas
        $this->sendNotificationToPetugas($complaint, $lokasi);

        return redirect()
            ->route('guest-complaint.success', ['lokasi' => $lokasi->kode_lokasi])
            ->with('success', 'Keluhan Anda telah berhasil dikirim. Terima kasih atas laporannya.')
            ->with('has_phone', !empty($data['telepon_pelapor']));
    }

    /**
     * Model jadwal + status "aktif" per tipe laporan (kebersihan/office_boy/satpam).
     *
     * @return array{model: class-string, statuses: string[]}
     */
    protected function jadwalConfigForTipe(string $tipe): array
    {
        return match ($tipe) {
            GuestComplaint::TIPE_OFFICE_BOY => [
                'model' => \App\Models\JadwalOb::class,
                'statuses' => ['pending', 'in_progress'],
            ],
            GuestComplaint::TIPE_SATPAM => [
                'model' => \App\Models\JadwalSatpam::class,
                'statuses' => ['pending', 'in_progress'],
            ],
            default => [
                'model' => JadwalKebersihan::class,
                'statuses' => ['active'],
            ],
        };
    }

    /**
     * Get petugas (sesuai tipe laporan) yang sedang bertugas di lokasi ini
     * berdasarkan jadwal domain terkait dan shift saat ini.
     */
    protected function getAssignedPetugas(Lokasi $lokasi, string $tipe = GuestComplaint::TIPE_KEBERSIHAN): ?User
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $cfg = $this->jadwalConfigForTipe($tipe);
        $jadwalModel = $cfg['model'];

        // Cari jadwal yang aktif hari ini di lokasi ini, sesuai jam saat ini
        $jadwal = $jadwalModel::where('lokasi_id', $lokasi->id)
            ->where('tanggal', $now->toDateString())
            ->whereIn('status', $cfg['statuses'])
            ->where('jam_mulai', '<=', $currentTime)
            ->where('jam_selesai', '>=', $currentTime)
            ->first();

        // Jika tidak ada jadwal pada jam ini, cari jadwal hari ini yang paling dekat
        if (!$jadwal) {
            $jadwal = $jadwalModel::where('lokasi_id', $lokasi->id)
                ->where('tanggal', $now->toDateString())
                ->whereIn('status', $cfg['statuses'])
                ->orderBy('jam_mulai')
                ->first();
        }

        if ($jadwal) {
            return User::find($jadwal->petugas_id);
        }

        return null;
    }

    /**
     * Send WhatsApp notification to petugas assigned to this location
     */
    protected function sendNotificationToPetugas(GuestComplaint $complaint, Lokasi $lokasi): void
    {
        try {
            $watzapService = new WhatsAppService();

            if (!$watzapService->isConfigured()) {
                Log::warning('WatZap not configured, skipping complaint notification');
                return;
            }

            // Get petugas (sesuai tipe laporan) assigned to this location today
            $cfg = $this->jadwalConfigForTipe($complaint->tipe_laporan ?? GuestComplaint::TIPE_KEBERSIHAN);
            $jadwalModel = $cfg['model'];
            $petugasIds = $jadwalModel::where('lokasi_id', $lokasi->id)
                ->where('tanggal', today())
                ->whereIn('status', $cfg['statuses'])
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

            $result = $watzapService->sendGuestComplaintNotification($complaint, $petugasUsers);

            Log::info('Guest complaint notification sent', [
                'complaint_id' => $complaint->id,
                'lokasi_id' => $lokasi->id,
                'sent' => $result['sent'],
                'failed' => $result['failed'],
            ]);

            // Web Push ke PWA petugas.
            $webPush = app(\App\Services\WebPushService::class);
            foreach ($petugasUsers as $petugasUser) {
                $webPush->sendToUser(
                    $petugasUser,
                    'Keluhan Tamu Baru',
                    "{$lokasi->nama_lokasi}: " . \Illuminate\Support\Str::limit($complaint->deskripsi_keluhan, 80),
                    [
                        'type' => 'guest_complaint',
                        'ref_id' => $complaint->id,
                        'lokasi_id' => $lokasi->id,
                        'url' => '/beranda',
                    ]
                );
            }

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
