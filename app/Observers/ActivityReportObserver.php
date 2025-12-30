<?php

namespace App\Observers;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Setting;
use App\Notifications\ReportApprovedNotification;
use App\Notifications\ReportRejectedNotification;
use App\Services\WatZapService;
use App\Services\NotificationTemplateService;
use App\Services\PenilaianService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityReportObserver
{
    protected WatZapService $watzap;
    protected NotificationTemplateService $templates;
    protected PenilaianService $penilaianService;

    public function __construct()
    {
        $this->watzap = new WatZapService();
        $this->templates = new NotificationTemplateService();
        $this->penilaianService = new PenilaianService();
    }

    /**
     * Handle the ActivityReport "creating" event.
     * Set reporting_status based on jadwal timing.
     */
    public function creating(ActivityReport $report): void
    {
        // Skip if already set (e.g., auto-generated expired reports)
        if ($report->reporting_status && $report->reporting_status !== ActivityReport::REPORTING_STATUS_ONTIME) {
            return;
        }

        // Only calculate status if there's a jadwal linked
        if (!$report->jadwal_id) {
            $report->reporting_status = ActivityReport::REPORTING_STATUS_ONTIME;
            return;
        }

        $jadwal = JadwalKebersihan::find($report->jadwal_id);
        if (!$jadwal) {
            $report->reporting_status = ActivityReport::REPORTING_STATUS_ONTIME;
            return;
        }

        $now = Carbon::now();
        $toleranceMinutes = Setting::get('reporting_tolerance_minutes', 10);

        // Create datetime from jadwal tanggal and jam_selesai
        $jadwalEndTime = Carbon::parse($jadwal->tanggal->format('Y-m-d') . ' ' . $jadwal->jam_selesai->format('H:i:s'));
        $toleranceEndTime = $jadwalEndTime->copy()->addMinutes($toleranceMinutes);

        if ($now->lessThanOrEqualTo($jadwalEndTime)) {
            // On time - reported before or at jam_selesai
            $report->reporting_status = ActivityReport::REPORTING_STATUS_ONTIME;
            $report->late_minutes = null;
        } elseif ($now->lessThanOrEqualTo($toleranceEndTime)) {
            // Late - reported after jam_selesai but within tolerance
            $report->reporting_status = ActivityReport::REPORTING_STATUS_LATE;
            $report->late_minutes = (int) abs($now->diffInMinutes($jadwalEndTime));
        } else {
            // Expired - reported after tolerance (shouldn't happen normally since cron handles this)
            $report->reporting_status = ActivityReport::REPORTING_STATUS_EXPIRED;
            $report->late_minutes = (int) abs($now->diffInMinutes($jadwalEndTime));
        }

        Log::info('ActivityReport reporting_status set', [
            'jadwal_id' => $jadwal->id,
            'jam_selesai' => $jadwalEndTime->format('Y-m-d H:i:s'),
            'tolerance_end' => $toleranceEndTime->format('Y-m-d H:i:s'),
            'now' => $now->format('Y-m-d H:i:s'),
            'reporting_status' => $report->reporting_status,
            'late_minutes' => $report->late_minutes,
        ]);
    }

    /**
     * Handle the ActivityReport "created" event.
     */
    public function created(ActivityReport $report): void
    {
        // Notify supervisor when new report is submitted
        if ($report->status === 'submitted') {
            try {
                // Get supervisors (users with supervisor role)
                $supervisors = \App\Models\User::role('supervisor')
                    ->whereNotNull('phone')
                    ->get();

                foreach ($supervisors as $supervisor) {
                    $message = $this->templates->reportSubmitted($report, $supervisor);

                    $this->watzap->sendMessage(
                        $supervisor->phone,
                        $message,
                        [
                            'type' => 'report_submitted',
                            'report_id' => $report->id,
                            'supervisor_id' => $supervisor->id,
                        ]
                    );
                }

                Log::info('Report submission notification sent to supervisors', [
                    'report_id' => $report->id,
                    'supervisor_count' => $supervisors->count(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send report submission notification', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the ActivityReport "updated" event.
     */
    public function updated(ActivityReport $report): void
    {
        // === AUTO-UPDATE PENILAIAN WHEN APPROVED ===
        if ($report->wasChanged('status') && $report->status === 'approved') {
            try {
                $penilaian = $this->penilaianService->updatePenilaianAfterApproval($report);

                if ($penilaian) {
                    Log::info('Penilaian updated automatically after approval', [
                        'report_id' => $report->id,
                        'penilaian_id' => $penilaian->id,
                        'petugas_id' => $report->petugas_id,
                        'rata_rata' => $penilaian->rata_rata,
                        'kategori' => $penilaian->kategori,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to update penilaian after approval', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify petugas when report status changed
        if ($report->wasChanged('status') && $report->petugas) {
            try {
                $message = match($report->status) {
                    'approved' => $this->templates->reportApproved($report),
                    'rejected' => $this->templates->reportRejected($report),
                    default => null,
                };

                if ($message) {
                    $whatsappSent = false;

                    // Send WhatsApp notification if configured and petugas has phone
                    if ($this->watzap->isConfigured() && $report->petugas->phone) {
                        $this->watzap->sendMessage(
                            $report->petugas->phone,
                            $message,
                            [
                                'type' => 'report_status_changed',
                                'report_id' => $report->id,
                                'new_status' => $report->status,
                            ]
                        );
                        $whatsappSent = true;
                    } elseif (!$report->petugas->phone) {
                        Log::warning('Petugas has no phone number, skipping WhatsApp notification', [
                            'report_id' => $report->id,
                            'petugas_id' => $report->petugas_id,
                            'petugas_name' => $report->petugas->name,
                        ]);
                    }

                    // Send in-app notification (always, regardless of phone)
                    if ($report->status === 'approved') {
                        $report->petugas->notify(new ReportApprovedNotification($report));
                    } elseif ($report->status === 'rejected') {
                        $report->petugas->notify(new ReportRejectedNotification($report));
                    }

                    Log::info('Report status notification sent', [
                        'report_id' => $report->id,
                        'status' => $report->status,
                        'petugas_id' => $report->petugas_id,
                        'whatsapp_sent' => $whatsappSent,
                        'in_app_sent' => true,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send report status notification', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the ActivityReport "deleted" event.
     */
    public function deleted(ActivityReport $report): void
    {
        //
    }

    /**
     * Handle the ActivityReport "restored" event.
     */
    public function restored(ActivityReport $report): void
    {
        //
    }

    /**
     * Handle the ActivityReport "force deleted" event.
     */
    public function forceDeleted(ActivityReport $report): void
    {
        //
    }
}
