<?php

namespace App\Filament\Resources\Lokasis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LokasisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_lokasi')
                    ->searchable(),
                TextColumn::make('nama_lokasi')
                    ->searchable(),
                TextColumn::make('kategori')
                    ->searchable(),
                TextColumn::make('lantai')
                    ->searchable(),
                TextColumn::make('luas_area')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('foto_lokasi')
                    ->searchable(),
                TextColumn::make('status_kebersihan')
                    ->searchable(),
                TextColumn::make('last_cleaned_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
