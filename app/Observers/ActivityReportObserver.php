<?php

namespace App\Observers;

use App\Models\ActivityReport;
use App\Notifications\ReportApprovedNotification;
use App\Notifications\ReportRejectedNotification;
use App\Services\FontteService;
use App\Services\NotificationTemplateService;
use App\Services\PenilaianService;
use Illuminate\Support\Facades\Log;

class ActivityReportObserver
{
    protected FontteService $fontte;
    protected NotificationTemplateService $templates;
    protected PenilaianService $penilaianService;

    public function __construct()
    {
        $this->fontte = new FontteService();
        $this->templates = new NotificationTemplateService();
        $this->penilaianService = new PenilaianService();
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

                    $this->fontte->sendMessage(
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
        if ($report->wasChanged('status') && $report->petugas && $report->petugas->phone) {
            try {
                $message = match($report->status) {
                    'approved' => $this->templates->reportApproved($report),
                    'rejected' => $this->templates->reportRejected($report),
                    default => null,
                };

                if ($message) {
                    // Send WhatsApp notification
                    $this->fontte->sendMessage(
                        $report->petugas->phone,
                        $message,
                        [
                            'type' => 'report_status_changed',
                            'report_id' => $report->id,
                            'new_status' => $report->status,
                        ]
                    );

                    // Send in-app notification
                    if ($report->status === 'approved') {
                        $report->petugas->notify(new ReportApprovedNotification($report));
                    } elseif ($report->status === 'rejected') {
                        $report->petugas->notify(new ReportRejectedNotification($report));
                    }

                    Log::info('Report status notification sent', [
                        'report_id' => $report->id,
                        'status' => $report->status,
                        'petugas_id' => $report->petugas_id,
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
