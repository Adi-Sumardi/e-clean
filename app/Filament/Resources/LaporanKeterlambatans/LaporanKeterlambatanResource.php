<?php

namespace App\Filament\Resources\LaporanKeterlambatans;

use App\Filament\Resources\LaporanKeterlambatans\Pages\CreateLaporanKeterlambatan;
use App\Filament\Resources\LaporanKeterlambatans\Pages\EditLaporanKeterlambatan;
use App\Filament\Resources\LaporanKeterlambatans\Pages\ListLaporanKeterlambatans;
use App\Filament\Resources\LaporanKeterlambatans\Pages\ViewLaporanKeterlambatan;
use App\Filament\Resources\LaporanKeterlambatans\Schemas\LaporanKeterlambatanForm;
use App\Filament\Resources\LaporanKeterlambatans\Schemas\LaporanKeterlambatanInfolist;
use App\Filament\Resources\LaporanKeterlambatans\Tables\LaporanKeterlambatansTable;
use App\Models\LaporanKeterlambatan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LaporanKeterlambatanResource extends Resource
{
    protected static ?string $model = LaporanKeterlambatan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static UnitEnum|string|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return 'Laporan Keterlambatan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Laporan Keterlambatan';
    }

    public static function canCreate(): bool
    {
        // Hanya sistem yang bisa create (via command), user tidak bisa manual create
        return false;
    }

    public static function canEdit($record): bool
    {
        // Tidak bisa di-edit
        return false;
    }

    public static function canDelete($record): bool
    {
        // Hanya admin dan supervisor yang bisa delete
        return auth()->user()->hasAnyRole(['admin', 'supervisor']);
    }

    public static function canViewAny(): bool
    {
        // Hanya admin dan supervisor yang bisa lihat
        return auth()->user()->hasAnyRole(['admin', 'supervisor']);
    }

    public static function form(Schema $schema): Schema
    {
        return LaporanKeterlambatanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LaporanKeterlambatanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaporanKeterlambatansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaporanKeterlambatans::route('/'),
            'create' => CreateLaporanKeterlambatan::route('/create'),
            'view' => ViewLaporanKeterlambatan::route('/{record}'),
            'edit' => EditLaporanKeterlambatan::route('/{record}/edit'),
        ];
    }
}
