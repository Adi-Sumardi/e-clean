<?php

namespace App\Filament\Resources\Penilaians;

use App\Filament\Resources\Penilaians\Pages\ManagePenilaians;
use App\Models\ActivityReport;
use App\Models\Penilaian;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenilaianResource extends Resource
{
    protected static ?string $model = Penilaian::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Penilaian';

    // Remove navigationGroup - make it a top-level menu
    // protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('petugas_id')
                    ->label('Petugas')
                    ->required()
                    ->searchable()
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'petugas');
                    })->pluck('name', 'id'))
                    ->placeholder('Pilih Petugas')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('⚠️ Penilaian otomatis dibuat oleh sistem saat supervisor approve laporan'),

                TextInput::make('periode_bulan')
                    ->label('Periode Bulan')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(12)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Bulan periode penilaian (1-12)'),

                TextInput::make('periode_tahun')
                    ->label('Periode Tahun')
                    ->required()
                    ->numeric()
                    ->minValue(2020)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Tahun periode penilaian'),

                TextInput::make('skor_kualitas')
                    ->label('Skor Kualitas')
                    ->numeric()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Dihitung otomatis dari rating laporan'),

                TextInput::make('skor_ketepatan_waktu')
                    ->label('Skor Ketepatan Waktu')
                    ->numeric()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Dihitung otomatis dari keterlambatan'),

                TextInput::make('skor_kebersihan')
                    ->label('Skor Kebersihan')
                    ->numeric()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Dihitung otomatis dari kelengkapan laporan'),

                TextInput::make('total_skor')
                    ->label('Total Skor')
                    ->numeric()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Jumlah dari 3 skor di atas'),

                TextInput::make('rata_rata')
                    ->label('Rata-rata')
                    ->numeric()
                    ->step(0.01)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Total skor / 3'),

                TextInput::make('kategori')
                    ->label('Kategori')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Sangat Baik / Baik / Cukup / Kurang'),

                Textarea::make('catatan')
                    ->label('Catatan Penilaian')
                    ->rows(4)
                    ->columnSpanFull()
                    ->placeholder('Berikan catatan dan saran untuk perbaikan...')
                    ->helperText('✏️ Field ini bisa diedit untuk menambahkan catatan manual'),

                Select::make('penilai_id')
                    ->label('Penilai')
                    ->searchable()
                    ->options(User::whereHas('roles', function ($query) {
                        $query->whereIn('name', ['supervisor', 'admin']);
                    })->pluck('name', 'id'))
                    ->placeholder('Supervisor yang meng-approve laporan')
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('periode_bulan')
                    ->label('Periode')
                    ->formatStateUsing(function ($record) {
                        $bulan = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        return $bulan[$record->periode_bulan] . ' ' . $record->periode_tahun;
                    })
                    ->sortable(),

                TextColumn::make('skor_kualitas')
                    ->label('Kualitas')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->sortable(),

                TextColumn::make('skor_ketepatan_waktu')
                    ->label('Ketepatan')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('skor_kebersihan')
                    ->label('Kebersihan')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rata_rata')
                    ->label('Rata-rata')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2))
                    ->sortable(),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sangat Baik' => 'success',
                        'Baik' => 'info',
                        'Cukup' => 'warning',
                        'Kurang' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('penilai.name')
                    ->label('Penilai')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('petugas_id')
                    ->label('Petugas')
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'petugas');
                    })->pluck('name', 'id')),
                SelectFilter::make('penilai_id')
                    ->label('Penilai')
                    ->options(User::whereHas('roles', function ($query) {
                        $query->whereIn('name', ['supervisor', 'admin']);
                    })->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn () => auth()->user()->hasRole('pengurus')),
                DeleteAction::make()
                    ->hidden(fn () => auth()->user()->hasRole('pengurus')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->hidden(fn () => auth()->user()->hasRole('pengurus')),
            ])
            ->defaultSort('periode_tahun', 'desc')
            ->defaultSort('periode_bulan', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePenilaians::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Petugas hanya bisa lihat penilaian sendiri
        if ($user->hasRole('petugas')) {
            return $query->where('petugas_id', $user->id);
        }

        // Role lain bisa lihat semua penilaian
        return $query;
    }

    public static function canCreate(): bool
    {
        // Disable manual creation - penilaian dibuat otomatis saat approval
        return false;
    }

    public static function canEdit($record): bool
    {
        // Allow edit only for catatan field
        $user = auth()->user();
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        // Disable delete - penilaian adalah historical record
        return false;
    }

    public static function canViewAny(): bool
    {
        // Bypass Shield permission check - use role-based authorization instead
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Hide dari sidebar navigation untuk petugas
        return auth()->user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'pengurus']);
    }
}
