<?php

namespace App\Filament\Resources\JadwalKebersihanans\Pages;

use App\Filament\Resources\JadwalKebersihanans\JadwalKebersihanResource;
use App\Filament\Resources\JadwalKebersihanans\Widgets\JadwalKebersihanStatsWidget;
use App\Models\JadwalKebersihan;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageJadwalKebersihanans extends ManageRecords
{
    protected static string $resource = JadwalKebersihanResource::class;

    protected function getHeaderActions(): array
    {
        // Petugas dan pengurus tidak bisa buat jadwal, hanya supervisor/admin/super_admin
        if (Auth::user()->hasAnyRole(['petugas', 'pengurus'])) {
            return [];
        }

        return [
            CreateAction::make()
                ->label('Buat Jadwal Baru')
                ->icon('heroicon-o-plus-circle')
                ->using(function (array $data): JadwalKebersihan {
                    return $this->handleJadwalCreation($data);
                })
                ->successNotification(function ($data) {
                    $start = Carbon::parse($data['tanggal_mulai']);
                    $end = Carbon::parse($data['tanggal_selesai']);
                    $days = $start->diffInDays($end) + 1;
                    $shifts = $data['shifts'] ?? [];
                    $totalShifts = count($shifts);
                    $totalJadwal = $days * $totalShifts;

                    return Notification::make()
                        ->success()
                        ->title('Jadwal berhasil dibuat!')
                        ->body("Berhasil membuat {$totalJadwal} jadwal ({$days} hari × {$totalShifts} shift) dari {$start->format('d/m/Y')} sampai {$end->format('d/m/Y')}");
                }),
        ];
    }

    protected function handleJadwalCreation(array $data): JadwalKebersihan
    {
        $tanggalMulai = Carbon::parse($data['tanggal_mulai']);
        $tanggalSelesai = Carbon::parse($data['tanggal_selesai']);
        $shifts = $data['shifts'] ?? [];

        // Hitung jumlah hari antara tanggal mulai dan selesai
        $jumlahHari = $tanggalMulai->diffInDays($tanggalSelesai) + 1;

        $createdJadwal = null;

        // Loop untuk setiap hari
        for ($i = 0; $i < $jumlahHari; $i++) {
            $tanggal = $tanggalMulai->copy()->addDays($i);

            // Loop untuk setiap shift yang dipilih
            foreach ($shifts as $shift) {
                $timeRange = \App\Models\LaporanKeterlambatan::getShiftTimeRange($shift);

                $jadwal = JadwalKebersihan::create([
                    'petugas_id' => $data['petugas_id'],
                    'lokasi_id' => $data['lokasi_id'],
                    'tanggal' => $tanggal,
                    'shift' => $shift,
                    'jam_mulai' => $timeRange['start'],
                    'jam_selesai' => $timeRange['end'],
                    'prioritas' => $data['prioritas'] ?? 'normal',
                    'catatan' => $data['catatan'] ?? null,
                    'status' => $data['status'] ?? 'active',
                    'created_by' => Auth::id(),
                ]);

                // Simpan jadwal pertama untuk return
                if ($i === 0 && !$createdJadwal) {
                    $createdJadwal = $jadwal;
                }
            }
        }

        return $createdJadwal;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            JadwalKebersihanStatsWidget::class,
        ];
    }
}
