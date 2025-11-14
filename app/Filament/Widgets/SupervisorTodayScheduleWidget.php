<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use Carbon\Carbon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SupervisorTodayScheduleWidget extends TableWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '📅 Jadwal Hari Ini';

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['supervisor', 'admin']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                JadwalKebersihan::query()
                    ->whereDate('tanggal', Carbon::today())
                    ->with(['petugas', 'lokasi'])
                    ->orderBy('shift', 'asc')
            )
            ->columns([
                BadgeColumn::make('shift')
                    ->label('Shift')
                    ->colors([
                        'success' => 'pagi',
                        'warning' => 'siang',
                        'danger' => 'sore',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                IconColumn::make('has_report')
                    ->label('Status Laporan')
                    ->getStateUsing(function ($record) {
                        $hasReport = ActivityReport::where('petugas_id', $record->petugas_id)
                            ->where('lokasi_id', $record->lokasi_id)
                            ->whereDate('tanggal', Carbon::today())
                            ->whereIn('status', ['submitted', 'approved'])
                            ->exists();

                        return $hasReport;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('status_text')
                    ->label('Keterangan')
                    ->getStateUsing(function ($record) {
                        $hasReport = ActivityReport::where('petugas_id', $record->petugas_id)
                            ->where('lokasi_id', $record->lokasi_id)
                            ->whereDate('tanggal', Carbon::today())
                            ->whereIn('status', ['submitted', 'approved'])
                            ->exists();

                        return $hasReport ? 'Sudah Melapor' : 'Belum Melapor';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Melapor' => 'success',
                        'Belum Melapor' => 'gray',
                    }),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->emptyStateHeading('Tidak ada jadwal hari ini')
            ->emptyStateDescription('Belum ada jadwal yang ditentukan untuk hari ini')
            ->emptyStateIcon('heroicon-o-calendar')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }
}
