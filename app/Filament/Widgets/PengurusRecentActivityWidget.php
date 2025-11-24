<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PengurusRecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()->hasRole('pengurus');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('📋 Aktivitas Terbaru')
            ->description('10 laporan terakhir yang disubmit')
            ->query(
                ActivityReport::query()
                    ->with(['petugas', 'lokasi'])
                    ->latest('tanggal')
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->iconColor('success'),

                Tables\Columns\TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->icon('heroicon-m-map-pin')
                    ->iconColor('warning')
                    ->limit(30),

                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pagi' => 'info',
                        'siang' => 'warning',
                        'malam' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => '✓ Approved',
                        'rejected' => '✗ Rejected',
                        'pending' => '⏳ Pending',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->state(fn (ActivityReport $record): string =>
                        $record->rating ? $record->rating . ' ⭐' : 'N/A'
                    )
                    ->alignCenter()
                    ->color(fn (ActivityReport $record): string =>
                        $record->rating >= 4 ? 'success' : ($record->rating >= 3 ? 'warning' : 'danger')
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-m-clock'),
            ])
            ->paginated(false);
    }
}
