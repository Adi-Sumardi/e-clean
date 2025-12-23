<?php

namespace App\Filament\Resources\JadwalKebersihanans;

use App\Enums\WorkShift;
use App\Filament\Resources\JadwalKebersihanans\Pages\ManageJadwalKebersihanans;
use App\Models\JadwalKebersihan;
use App\Models\LaporanKeterlambatan;
use App\Models\Lokasi;
use App\Models\Unit;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JadwalKebersihanResource extends Resource
{
    protected static ?string $model = JadwalKebersihan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Jadwal Kebersihan';

    // Remove navigationGroup - make it a top-level menu
    // protected static string | \UnitEnum | null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return 'Jadwal Kebersihan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jadwal Kebersihan';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal_mulai')
                    ->label('📅 Tanggal Mulai')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->minDate(today())
                    ->default(today())
                    ->reactive()
                    ->helperText('Pilih tanggal mulai jadwal'),

                DatePicker::make('tanggal_selesai')
                    ->label('📅 Tanggal Selesai')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->minDate(function ($get) {
                        return $get('tanggal_mulai') ?? today();
                    })
                    ->default(today())
                    ->reactive()
                    ->helperText(function ($get) {
                        $start = $get('tanggal_mulai');
                        $end = $get('tanggal_selesai');

                        if ($start && $end) {
                            $days = \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end)) + 1;
                            return "Total: {$days} hari jadwal akan dibuat";
                        }

                        return 'Pilih tanggal selesai jadwal';
                    }),

                CheckboxList::make('shifts')
                    ->label('⏰ Shift')
                    ->required()
                    ->options(WorkShift::options())
                    ->columns(3)
                    ->gridDirection('row')
                    ->helperText('Pilih satu atau lebih shift (jam otomatis dari shift)')
                    ->columnSpanFull(),

                Select::make('petugas_id')
                    ->label('👤 Petugas')
                    ->required()
                    ->searchable()
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'petugas');
                    })->pluck('name', 'id'))
                    ->placeholder('Pilih Petugas')
                    ->helperText('Petugas yang akan bertugas'),

                Select::make('unit_id')
                    ->label('🏢 Unit')
                    ->required()
                    ->searchable()
                    ->options(Unit::where('is_active', true)->pluck('nama_unit', 'id'))
                    ->placeholder('Pilih Unit terlebih dahulu')
                    ->helperText('Pilih unit untuk menampilkan lokasi')
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('lokasi_id', null))
                    ->dehydrated(false),

                Select::make('lokasi_id')
                    ->label('📍 Lokasi')
                    ->required()
                    ->searchable()
                    ->options(function ($get) {
                        $unitId = $get('unit_id');
                        if (!$unitId) {
                            return [];
                        }
                        return Lokasi::where('is_active', true)
                            ->where('unit_id', $unitId)
                            ->pluck('nama_lokasi', 'id');
                    })
                    ->placeholder(fn ($get) => $get('unit_id') ? 'Pilih Lokasi' : 'Pilih Unit terlebih dahulu')
                    ->helperText('Lokasi yang akan dibersihkan')
                    ->disabled(fn ($get) => !$get('unit_id')),

                Select::make('prioritas')
                    ->label('⭐ Prioritas')
                    ->options([
                        'rendah' => 'Rendah',
                        'normal' => 'Normal',
                        'tinggi' => 'Tinggi',
                    ])
                    ->default('normal')
                    ->native(false)
                    ->helperText('Tingkat prioritas pekerjaan'),

                Select::make('status')
                    ->label('📌 Status Jadwal')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->default('active')
                    ->native(false)
                    ->helperText('Jadwal aktif akan muncul di dashboard petugas'),

                Textarea::make('catatan')
                    ->label('📝 Catatan')
                    ->rows(3)
                    ->placeholder('Tambahkan catatan khusus untuk petugas...')
                    ->helperText('Catatan akan terlihat oleh petugas')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => WorkShift::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => WorkShift::tryFrom($state)?->shortLabel() ?? ucfirst($state))
                    ->sortable(),

                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('prioritas')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'rendah' => 'gray',
                        'normal' => 'info',
                        'tinggi' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('shift')
                    ->options(WorkShift::options()),
                SelectFilter::make('prioritas')
                    ->options([
                        'rendah' => 'Rendah',
                        'normal' => 'Normal',
                        'tinggi' => 'Tinggi',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
                DeleteAction::make()
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
                ])
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageJadwalKebersihanans::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Petugas hanya bisa lihat jadwal sendiri
        if ($user->hasRole('petugas')) {
            return $query->where('petugas_id', $user->id);
        }

        // Role lain bisa lihat semua jadwal
        return $query;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        // Hanya supervisor, admin, super_admin yang bisa create jadwal
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Supervisor, admin, super_admin bisa edit
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        // Supervisor, admin, super_admin bisa delete
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }

    public static function canViewAny(): bool
    {
        // Bypass Shield permission check - use role-based authorization instead
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Hide dari sidebar navigation untuk petugas
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'pengurus']);
    }
}
