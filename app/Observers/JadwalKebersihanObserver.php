<?php

namespace App\Observers;

use App\Models\JadwalKebersihan;
use App\Services\WatZapService;
use App\Services\NotificationTemplateService;
use Illuminate\Support\Facades\Log;

class JadwalKebersihanObserver
{
    protected ?WatZapService $watzap = null;
    protected NotificationTemplateService $templates;

    public function __construct()
    {
        // Initialize services, skip WatZap if not configured
        if (config('services.watzap.api_key') && config('services.watzap.number_key')) {
            try {
                $this->watzap = new WatZapService();
            } catch (\Exception $e) {
                Log::warning('WatZap service not available: ' . $e->getMessage());
            }
        }
        $this->templates = new NotificationTemplateService();
    }

    /**
     * Handle the JadwalKebersihan "created" event.
     */
    public function created(JadwalKebersihan $jadwal): void
    {
        // Send notification when new schedule is assigned
        if ($this->watzap && $jadwal->petugas) {
            if (!$jadwal->petugas->phone) {
                Log::warning('Petugas has no phone number, skipping schedule assignment notification', [
                    'jadwal_id' => $jadwal->id,
                    'petugas_id' => $jadwal->petugas_id,
                    'petugas_name' => $jadwal->petugas->name,
                ]);
                return;
            }

            try {
                $message = $this->templates->scheduleAssigned($jadwal);

                $this->watzap->sendMessage(
                    $jadwal->petugas->phone,
                    $message,
                    [
                        'type' => 'schedule_assigned',
                        'jadwal_id' => $jadwal->id,
                    ]
                );

                Log::info('Schedule assignment notification sent', [
                    'jadwal_id' => $jadwal->id,
                    'petugas_id' => $jadwal->petugas_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send schedule assignment notification', [
                    'jadwal_id' => $jadwal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the JadwalKebersihan "updated" event.
     */
    public function updated(JadwalKebersihan $jadwal): void
    {
        // Send notification if schedule details changed
        if ($this->watzap
            && $jadwal->wasChanged(['tanggal', 'jam_mulai', 'jam_selesai', 'lokasi_id'])
            && $jadwal->petugas) {

            if (!$jadwal->petugas->phone) {
                Log::warning('Petugas has no phone number, skipping schedule update notification', [
                    'jadwal_id' => $jadwal->id,
                    'petugas_id' => $jadwal->petugas_id,
                    'petugas_name' => $jadwal->petugas->name,
                ]);
                return;
            }

            try {
                $message = "📅 *JADWAL DIUBAH*\n\n" .
                           "Halo {$jadwal->petugas->name},\n\n" .
                           "Jadwal kebersihan Anda telah diperbarui:\n\n" .
                           "📍 Lokasi: {$jadwal->lokasi->nama_lokasi}\n" .
                           "📆 Tanggal: {$jadwal->tanggal->format('d/m/Y')}\n" .
                           "⏰ Waktu: {$jadwal->jam_mulai->format('H:i')} - {$jadwal->jam_selesai->format('H:i')}\n\n" .
                           "Mohon perhatikan perubahan jadwal ini.\n\n" .
                           "Terima kasih! 🙏";

                $this->watzap->sendMessage(
                    $jadwal->petugas->phone,
                    $message,
                    [
                        'type' => 'schedule_updated',
                        'jadwal_id' => $jadwal->id,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Failed to send schedule update notification', [
                    'jadwal_id' => $jadwal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the JadwalKebersihan "deleted" event.
     */
    public function deleted(JadwalKebersihan $jadwal): void
    {
        // Send notification when schedule is cancelled
        if ($this->watzap && $jadwal->petugas) {
            if (!$jadwal->petugas->phone) {
                Log::warning('Petugas has no phone number, skipping schedule cancellation notification', [
                    'jadwal_id' => $jadwal->id,
                    'petugas_id' => $jadwal->petugas_id,
                    'petugas_name' => $jadwal->petugas->name,
                ]);
                return;
            }

            try {
                $message = "❌ *JADWAL DIBATALKAN*\n\n" .
                           "Halo {$jadwal->petugas->name},\n\n" .
                           "Jadwal kebersihan berikut telah dibatalkan:\n\n" .
                           "📍 Lokasi: {$jadwal->lokasi->nama_lokasi}\n" .
                           "📆 Tanggal: {$jadwal->tanggal->format('d/m/Y')}\n" .
                           "⏰ Waktu: {$jadwal->jam_mulai->format('H:i')} - {$jadwal->jam_selesai->format('H:i')}\n\n" .
                           "Terima kasih! 🙏";

                $this->watzap->sendMessage(
                    $jadwal->petugas->phone,
                    $message,
                    [
                        'type' => 'schedule_cancelled',
                        'jadwal_id' => $jadwal->id,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Failed to send schedule cancellation notification', [
                    'jadwal_id' => $jadwal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the JadwalKebersihan "restored" event.
     */
    public function restored(JadwalKebersihan $jadwalKebersihan): void
    {
        //
    }

    /**
     * Handle the JadwalKebersihan "force deleted" event.
     */
    public function forceDeleted(JadwalKebersihan $jadwalKebersihan): void
    {
        //
    }
}
