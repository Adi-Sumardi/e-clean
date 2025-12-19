<?php

namespace App\Filament\Widgets;

use App\Models\GuestComplaint;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class GuestComplaintsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Keluhan Tamu Terbaru';

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'petugas']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GuestComplaint::query()
                    ->with(['lokasi', 'lokasi.unit'])
                    ->unresolved()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M H:i')
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable(),

                TextColumn::make('nama_pelapor')
                    ->label('Pelapor')
                    ->limit(20),

                TextColumn::make('jenis_keluhan')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => GuestComplaint::getJenisKeluhanOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'tumpahan' => 'danger',
                        'kotor' => 'warning',
                        'bau' => 'info',
                        'rusak' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => GuestComplaint::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
