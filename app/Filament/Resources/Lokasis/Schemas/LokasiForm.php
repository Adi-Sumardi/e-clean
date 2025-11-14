<?php

namespace App\Filament\Resources\Lokasis\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LokasiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_lokasi')
                    ->required(),
                TextInput::make('nama_lokasi')
                    ->required(),
                Textarea::make('deskripsi')
                    ->columnSpanFull(),
                TextInput::make('kategori')
                    ->required(),
                TextInput::make('lantai'),
                TextInput::make('luas_area')
                    ->numeric(),
                TextInput::make('foto_lokasi'),
                Textarea::make('qr_code')
                    ->columnSpanFull(),
                TextInput::make('status_kebersihan')
                    ->required()
                    ->default('belum_dicek'),
                DateTimePicker::make('last_cleaned_at'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
