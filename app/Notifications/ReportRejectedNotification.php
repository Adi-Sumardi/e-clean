<?php

namespace App\Notifications;

use App\Models\ActivityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ReportRejectedNotification extends Notification
{
    use Queueable;

    public ActivityReport $report;

    public function __construct(ActivityReport $report)
    {
        $this->report = $report;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'lokasi' => $this->report->lokasi->nama_lokasi ?? 'N/A',
            'tanggal' => $this->report->tanggal->format('d M Y'),
            'rejected_reason' => $this->report->rejected_reason,
            'title' => 'Laporan Ditolak',
            'message' => "Laporan Anda di {$this->report->lokasi->nama_lokasi} pada tanggal {$this->report->tanggal->format('d M Y')} telah ditolak. Harap perbaiki dan kirim ulang.",
            'icon' => 'heroicon-o-x-circle',
            'iconColor' => 'danger',
        ];
    }

    public function toFilament(object $notifiable): FilamentNotification
    {
        return FilamentNotification::make()
            ->warning()
            ->title('Laporan Ditolak')
            ->body("Laporan Anda di {$this->report->lokasi->nama_lokasi} pada {$this->report->tanggal->format('d M Y')} ditolak. Alasan: {$this->report->rejected_reason}")
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url(route('filament.admin.resources.activity-reports.index', [
                        'tableFilters' => [
                            'status' => ['value' => 'rejected']
                        ]
                    ])),
                \Filament\Notifications\Actions\Action::make('edit')
                    ->label('Perbaiki Laporan')
                    ->url(route('filament.admin.resources.activity-reports.index')),
            ]);
    }
}
