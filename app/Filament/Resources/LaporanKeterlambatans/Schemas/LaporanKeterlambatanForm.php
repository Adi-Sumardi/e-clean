<?php

namespace App\Filament\Resources\LaporanKeterlambatans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class LaporanKeterlambatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('jadwal_kebersihan_id')
                    ->relationship('jadwalKebersihan', 'id')
                    ->required(),
                Select::make('petugas_id')
                    ->relationship('petugas', 'name')
                    ->required(),
                Select::make('lokasi_id')
                    ->relationship('lokasi', 'id')
                    ->required(),
                DatePicker::make('tanggal')
                    ->required(),
                TextInput::make('shift')
                    ->required(),
                TimePicker::make('batas_waktu_mulai')
                    ->required(),
                TimePicker::make('batas_waktu_selesai')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('terlewat'),
                Textarea::make('keterangan')
                    ->columnSpanFull(),
                DateTimePicker::make('waktu_terdeteksi')
                    ->required(),
            ]);
    }
}
