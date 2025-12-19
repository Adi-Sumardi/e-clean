<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\ManageUnits;
use App\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Unit';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_unit')
                    ->label('Kode Unit')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('Contoh: UNIT-A, GD-1'),

                TextInput::make('nama_unit')
                    ->label('Nama Unit')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Gedung A, Gedung Utama'),

                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->columnSpanFull(),

                TextInput::make('alamat')
                    ->label('Alamat')
                    ->maxLength(255),

                TextInput::make('penanggung_jawab')
                    ->label('Penanggung Jawab')
                    ->maxLength(255),

                TextInput::make('telepon')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(20),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_unit')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_unit')
                    ->label('Nama Unit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('penanggung_jawab')
                    ->label('Penanggung Jawab')
                    ->searchable(),

                TextColumn::make('lokasis_count')
                    ->label('Jumlah Lokasi')
                    ->counts('lokasis')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),
                DeleteAction::make()
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin'])),
            ])
            ->defaultSort('kode_unit');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnits::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor']);
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor']);
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'pengurus']);
    }
}
