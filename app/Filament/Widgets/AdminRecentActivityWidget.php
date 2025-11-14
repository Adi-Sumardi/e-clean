<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AdminRecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Laporan Kegiatan Terbaru')
            ->query(
                ActivityReport::query()
                    ->with(['petugas', 'lokasi'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('kegiatan')
                    ->label('Kegiatan')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?int $state): string => $state ? $state . '/5' : 'N/A'),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }
}
