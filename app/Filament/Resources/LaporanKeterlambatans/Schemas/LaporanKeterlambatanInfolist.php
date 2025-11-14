<?php

namespace App\Filament\Resources\LaporanKeterlambatans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LaporanKeterlambatanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('jadwalKebersihan.id')
                    ->label('Jadwal kebersihan'),
                TextEntry::make('petugas.name')
                    ->label('Petugas'),
                TextEntry::make('lokasi.id')
                    ->label('Lokasi'),
                TextEntry::make('tanggal')
                    ->date(),
                TextEntry::make('shift'),
                TextEntry::make('batas_waktu_mulai')
                    ->time(),
                TextEntry::make('batas_waktu_selesai')
                    ->time(),
                TextEntry::make('status'),
                TextEntry::make('keterangan')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('waktu_terdeteksi')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
