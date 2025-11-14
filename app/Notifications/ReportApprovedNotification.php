<?php

namespace App\Notifications;

use App\Models\ActivityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class ReportApprovedNotification extends Notification
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
            'rating' => $this->report->rating,
            'catatan_supervisor' => $this->report->catatan_supervisor,
            'title' => 'Laporan Disetujui',
            'message' => "Laporan Anda di {$this->report->lokasi->nama_lokasi} pada tanggal {$this->report->tanggal->format('d M Y')} telah disetujui!",
            'icon' => 'heroicon-o-check-circle',
            'iconColor' => 'success',
        ];
    }

    public function toFilament(object $notifiable): FilamentNotification
    {
        $body = "Laporan Anda di {$this->report->lokasi->nama_lokasi} pada {$this->report->tanggal->format('d M Y')} telah disetujui!";

        if ($this->report->rating) {
            $body .= " Rating: {$this->report->rating}/5";
        }

        return FilamentNotification::make()
            ->success()
            ->title('Laporan Disetujui')
            ->body($body)
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url(route('filament.admin.resources.activity-reports.index')),
            ]);
    }
}
