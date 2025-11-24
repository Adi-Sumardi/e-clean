<?php

namespace App\Filament\Resources\ActivityReports;

use App\Filament\Forms\Components\WatermarkCameraField;
use App\Filament\Resources\ActivityReports\Pages;
use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ActivityReportResource extends Resource
{
    protected static ?string $model = ActivityReport::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Laporan Kegiatan';

    // Remove navigationGroup - make it a top-level menu
    // protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return 'Laporan Pekerjaan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Laporan Pekerjaan';
    }

    public static function form(Schema $schema): Schema
    {
        $user = Auth::user();
        $isPetugas = $user->hasRole('petugas');

        return $schema
            ->components([
                Select::make('petugas_id')
                    ->label('Petugas')
                    ->required()
                    ->searchable()
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'petugas');
                    })->pluck('name', 'id'))
                    ->default($isPetugas ? $user->id : null)
                    ->disabled($isPetugas)
                    ->dehydrated()
                    ->placeholder('Pilih Petugas'),

                Select::make('lokasi_id')
                    ->label('Lokasi')
                    ->required()
                    ->searchable()
                    ->options(function ($record) use ($isPetugas, $user) {
                        if ($isPetugas) {
                            // Petugas: hanya tampilkan lokasi dari jadwal mereka hari ini
                            $options = JadwalKebersihan::where('petugas_id', $user->id)
                                ->whereDate('tanggal', now()->toDateString())
                                ->with('lokasi')
                                ->get()
                                ->pluck('lokasi.nama_lokasi', 'lokasi.id')
                                ->unique();

                            // Saat edit, include lokasi yang sudah dipilih sebelumnya
                            if ($record && $record->lokasi_id && !$options->has($record->lokasi_id)) {
                                $lokasi = Lokasi::find($record->lokasi_id);
                                if ($lokasi) {
                                    $options->put($record->lokasi_id, $lokasi->nama_lokasi);
                                }
                            }

                            return $options;
                        } else {
                            // Supervisor/Admin: tampilkan semua lokasi aktif
                            return Lokasi::where('is_active', true)->pluck('nama_lokasi', 'id');
                        }
                    })
                    ->placeholder($isPetugas ? 'Pilih lokasi dari jadwal hari ini' : 'Pilih Lokasi')
                    ->helperText($isPetugas ? 'Hanya menampilkan lokasi sesuai jadwal Anda hari ini' : null)
                    ->disabled(fn ($get) => $isPetugas && $get('jadwal_id'))
                    ->dehydrated(),

                Select::make('jadwal_id')
                    ->label('Jadwal Terkait')
                    ->searchable()
                    ->options(function ($record) use ($isPetugas, $user) {
                        if ($isPetugas) {
                            // Petugas: hanya tampilkan jadwal mereka hari ini
                            $options = JadwalKebersihan::where('petugas_id', $user->id)
                                ->whereDate('tanggal', now()->toDateString())
                                ->with('lokasi')
                                ->get()
                                ->mapWithKeys(function ($jadwal) {
                                    return [$jadwal->id => $jadwal->lokasi->nama_lokasi . ' - Shift ' . ucfirst($jadwal->shift) . ' (' . $jadwal->tanggal->format('d/m/Y') . ')'];
                                });

                            // Saat edit, include jadwal yang sudah dipilih sebelumnya
                            if ($record && $record->jadwal_id && !$options->has($record->jadwal_id)) {
                                $jadwal = JadwalKebersihan::with('lokasi')->find($record->jadwal_id);
                                if ($jadwal) {
                                    $options->put($record->jadwal_id, $jadwal->lokasi->nama_lokasi . ' - Shift ' . ucfirst($jadwal->shift) . ' (' . $jadwal->tanggal->format('d/m/Y') . ')');
                                }
                            }

                            return $options;
                        } else {
                            // Supervisor/Admin: tampilkan semua jadwal
                            return JadwalKebersihan::with(['petugas', 'lokasi'])
                                ->get()
                                ->mapWithKeys(function ($jadwal) {
                                    return [$jadwal->id => $jadwal->tanggal->format('d/m/Y') . ' - ' . $jadwal->lokasi->nama_lokasi . ' (' . ucfirst($jadwal->shift) . ')'];
                                });
                        }
                    })
                    ->placeholder($isPetugas ? 'Pilih jadwal hari ini' : 'Pilih Jadwal (Opsional)')
                    ->helperText($isPetugas ? 'Pilih jadwal yang sesuai dengan pekerjaan ini' : null)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) use ($isPetugas) {
                        // Auto-fill lokasi berdasarkan jadwal yang dipilih
                        if ($state && $isPetugas) {
                            $jadwal = JadwalKebersihan::find($state);
                            if ($jadwal) {
                                $set('lokasi_id', $jadwal->lokasi_id);
                                $set('tanggal', $jadwal->tanggal->format('Y-m-d'));
                            }
                        }
                    }),

                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now())
                    ->disabled(fn ($get) => $isPetugas && $get('jadwal_id'))
                    ->dehydrated()
                    ->helperText(function ($get) use ($isPetugas) {
                        if ($isPetugas && $get('jadwal_id')) {
                            return 'Tanggal otomatis dari jadwal yang dipilih';
                        }
                        return null;
                    }),

                TimePicker::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->required()
                    ->seconds(false),

                TimePicker::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->seconds(false),

                Textarea::make('kegiatan')
                    ->label('Deskripsi Kegiatan')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull()
                    ->placeholder('Deskripsikan kegiatan pembersihan yang dilakukan...'),

                WatermarkCameraField::make('foto_sebelum')
                    ->label('Foto Sebelum Dibersihkan')
                    ->photoType('before')
                    ->lokasiId(fn ($get): ?int => $get('lokasi_id'))
                    ->activityReportId(fn (?ActivityReport $record): ?int => $record?->id)
                    ->columnSpanFull()
                    ->helperText('Gunakan kamera untuk mengambil foto dengan watermark GPS otomatis'),

                WatermarkCameraField::make('foto_sesudah')
                    ->label('Foto Sesudah Dibersihkan')
                    ->photoType('after')
                    ->lokasiId(fn ($get): ?int => $get('lokasi_id'))
                    ->activityReportId(fn (?ActivityReport $record): ?int => $record?->id)
                    ->columnSpanFull()
                    ->helperText('Gunakan kamera untuk mengambil foto dengan watermark GPS otomatis'),

                Textarea::make('catatan_petugas')
                    ->label('Catatan Petugas')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Catatan tambahan dari petugas...')
                    ->hidden($isPetugas),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('submitted')
                    ->native(false)
                    ->hidden($isPetugas)
                    ->dehydrated(),

                TextInput::make('rating')
                    ->label('Rating (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->step(1)
                    ->hidden($isPetugas),

                Textarea::make('catatan_supervisor')
                    ->label('Catatan Supervisor')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Catatan dari supervisor...')
                    ->hidden($isPetugas),

                Textarea::make('rejected_reason')
                    ->label('Alasan Penolakan')
                    ->rows(3)
                    ->columnSpanFull()
                    ->hidden(fn ($get) => $get('status') !== 'rejected' || $isPetugas),

                Select::make('approved_by')
                    ->label('Disetujui Oleh')
                    ->searchable()
                    ->options(User::whereHas('roles', function ($query) {
                        $query->whereIn('name', ['supervisor', 'admin']);
                    })->pluck('name', 'id'))
                    ->placeholder('Pilih Supervisor/Admin')
                    ->hidden($isPetugas),
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

                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                TextColumn::make('kegiatan')
                    ->label('Kegiatan')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?int $state): string => $state ? $state . '/5' : 'N/A')
                    ->sortable(),

                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                SelectFilter::make('petugas_id')
                    ->label('Petugas')
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'petugas');
                    })->pluck('name', 'id')),
                SelectFilter::make('lokasi_id')
                    ->label('Lokasi')
                    ->options(Lokasi::pluck('nama_lokasi', 'id')),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Laporan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('petugas.name')
                                    ->label('Nama Petugas')
                                    ->icon('heroicon-o-user'),
                                TextEntry::make('lokasi.nama_lokasi')
                                    ->label('Lokasi')
                                    ->icon('heroicon-o-map-pin'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d M Y')
                                    ->icon('heroicon-o-calendar'),
                                TextEntry::make('jam_mulai')
                                    ->label('Jam Mulai')
                                    ->time('H:i')
                                    ->icon('heroicon-o-clock'),
                                TextEntry::make('jam_selesai')
                                    ->label('Jam Selesai')
                                    ->time('H:i')
                                    ->icon('heroicon-o-clock'),
                            ]),

                        TextEntry::make('kegiatan')
                            ->label('Deskripsi Kegiatan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Dokumentasi')
                    ->schema([
                        ImageEntry::make('foto_sebelum')
                            ->label('Foto Sebelum')
                            ->disk('public')
                            ->columnSpan(1)
                            ->height(200)
                            ->visible(fn ($record) => $record->foto_sebelum),

                        ImageEntry::make('foto_sesudah')
                            ->label('Foto Sesudah')
                            ->disk('public')
                            ->columnSpan(1)
                            ->height(200)
                            ->visible(fn ($record) => $record->foto_sesudah),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Status & Penilaian')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'submitted' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('rating')
                                    ->label('Rating')
                                    ->badge()
                                    ->color('warning')
                                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) : 'Belum ada rating')
                                    ->visible(fn ($record) => $record->rating),

                                TextEntry::make('approved_at')
                                    ->label('Disetujui Pada')
                                    ->dateTime('d M Y H:i')
                                    ->visible(fn ($record) => $record->approved_at),
                            ]),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('catatan_petugas')
                            ->label('Catatan Petugas')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan')
                            ->visible(fn ($record) => $record->catatan_petugas),

                        TextEntry::make('catatan_supervisor')
                            ->label('Catatan Supervisor')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan')
                            ->color(fn ($record) => $record->status === 'rejected' ? 'danger' : 'success')
                            ->visible(fn ($record) => $record->catatan_supervisor),

                        TextEntry::make('rejected_reason')
                            ->label('Alasan Penolakan')
                            ->columnSpanFull()
                            ->color('danger')
                            ->visible(fn ($record) => $record->rejected_reason),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityReports::route('/'),
            'create' => Pages\CreateActivityReport::route('/create'),
            'edit' => Pages\EditActivityReport::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Petugas hanya bisa lihat laporan sendiri
        if ($user->hasRole('petugas')) {
            return $query->where('petugas_id', $user->id);
        }

        // Role lain (admin, supervisor, pengurus) bisa lihat semua
        return $query;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        // Petugas, supervisor, admin bisa create
        return $user->hasAnyRole(['petugas', 'supervisor', 'admin', 'super_admin']);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Admin & Super Admin bisa edit semua
        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            return true;
        }

        // Supervisor bisa edit untuk approve/reject
        if ($user->hasRole('supervisor')) {
            return true;
        }

        // Petugas hanya bisa edit laporan sendiri yang masih draft/submitted
        if ($user->hasRole('petugas')) {
            return $record->petugas_id === $user->id && in_array($record->status, ['draft', 'submitted']);
        }

        // Pengurus tidak bisa edit
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        // Hanya admin & super admin yang bisa delete
        return $user->hasAnyRole(['admin', 'super_admin']);
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
