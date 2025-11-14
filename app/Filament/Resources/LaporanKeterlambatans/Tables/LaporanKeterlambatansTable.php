<?php

namespace App\Filament\Resources\LaporanKeterlambatans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LaporanKeterlambatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('petugas.name')
                    ->label('Nama Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                BadgeColumn::make('shift')
                    ->label('Shift')
                    ->colors([
                        'success' => 'pagi',
                        'warning' => 'siang',
                        'danger' => 'sore',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('batas_waktu')
                    ->label('Waktu Shift')
                    ->getStateUsing(fn ($record) => $record->batas_waktu_mulai . ' - ' . $record->batas_waktu_selesai)
                    ->badge()
                    ->color('gray'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'warning',
                        'danger' => 'terlewat',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'terlewat' ? 'Terlewat' : 'Warning'),

                TextColumn::make('waktu_terdeteksi')
                    ->label('Waktu Deteksi')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'warning' => 'Warning',
                        'terlewat' => 'Terlewat',
                    ]),

                SelectFilter::make('shift')
                    ->label('Shift')
                    ->options([
                        'pagi' => 'Pagi',
                        'siang' => 'Siang',
                        'sore' => 'Sore',
                    ]),

                SelectFilter::make('petugas_id')
                    ->label('Petugas')
                    ->relationship('petugas', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('waktu_terdeteksi', 'desc');
    }
}
