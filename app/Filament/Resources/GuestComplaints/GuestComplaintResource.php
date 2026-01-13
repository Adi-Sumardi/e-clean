<?php

namespace App\Filament\Resources\GuestComplaints;

use App\Filament\Resources\GuestComplaints\Pages\ManageGuestComplaints;
use App\Models\ActivityReport;
use App\Models\GuestComplaint;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GuestComplaintResource extends Resource
{
    protected static ?string $model = GuestComplaint::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Keluhan Tamu';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Keluhan')
                    ->schema([
                        Select::make('lokasi_id')
                            ->label('Lokasi')
                            ->relationship('lokasi', 'nama_lokasi')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Select::make('jenis_keluhan')
                            ->label('Jenis Keluhan')
                            ->options(GuestComplaint::getJenisKeluhanOptions())
                            ->required()
                            ->disabled(),

                        Textarea::make('deskripsi_keluhan')
                            ->label('Deskripsi Keluhan')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),

                        FileUpload::make('foto_keluhan')
                            ->label('Foto Keluhan')
                            ->image()
                            ->disk('public')
                            ->directory('complaints')
                            ->visibility('public')
                            ->disabled()
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Informasi Pelapor')
                    ->schema([
                        TextInput::make('nama_pelapor')
                            ->label('Nama Pelapor')
                            ->disabled(),

                        TextInput::make('email_pelapor')
                            ->label('Email')
                            ->disabled(),

                        TextInput::make('telepon_pelapor')
                            ->label('Telepon')
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make('Penanganan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(GuestComplaint::getStatusOptions())
                            ->required(),

                        Textarea::make('catatan_penanganan')
                            ->label('Catatan Penanganan')
                            ->rows(3)
                            ->columnSpanFull(),

                        FileUpload::make('foto_penanganan')
                            ->label('Foto Penanganan')
                            ->image()
                            ->disk('public')
                            ->directory('complaint-handling')
                            ->visibility('public')
                            ->imageEditor()
                            ->openable()
                            ->downloadable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu Lapor')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.unit.nama_unit')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_pelapor')
                    ->label('Pelapor')
                    ->searchable(),

                TextColumn::make('jenis_keluhan')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => GuestComplaint::getJenisKeluhanOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'tumpahan' => 'danger',
                        'kotor' => 'warning',
                        'bau' => 'info',
                        'rusak' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => GuestComplaint::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('assignee.name')
                    ->label('Petugas Terjadwal')
                    ->placeholder('Tidak ada')
                    ->icon('heroicon-o-user')
                    ->sortable(),

                TextColumn::make('handler.name')
                    ->label('Ditangani Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('handled_at')
                    ->label('Waktu Penanganan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(GuestComplaint::getStatusOptions()),

                SelectFilter::make('jenis_keluhan')
                    ->options(GuestComplaint::getJenisKeluhanOptions()),

                SelectFilter::make('lokasi_id')
                    ->label('Lokasi')
                    ->relationship('lokasi', 'nama_lokasi')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('unit')
                    ->label('Unit')
                    ->relationship('lokasi.unit', 'nama_unit')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'petugas'])),
                DeleteAction::make()
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin'])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Keluhan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Waktu Lapor')
                                    ->dateTime('d M Y H:i'),

                                TextEntry::make('lokasi.nama_lokasi')
                                    ->label('Lokasi'),

                                TextEntry::make('lokasi.unit.nama_unit')
                                    ->label('Unit'),

                                TextEntry::make('jenis_keluhan')
                                    ->label('Jenis Keluhan')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => GuestComplaint::getJenisKeluhanOptions()[$state] ?? $state)
                                    ->color(fn (string $state): string => match ($state) {
                                        'tumpahan' => 'danger',
                                        'kotor' => 'warning',
                                        'bau' => 'info',
                                        'rusak' => 'gray',
                                        default => 'primary',
                                    }),
                            ]),

                        TextEntry::make('deskripsi_keluhan')
                            ->label('Deskripsi Keluhan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Pelapor')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('nama_pelapor')
                                    ->label('Nama Pelapor'),

                                TextEntry::make('email_pelapor')
                                    ->label('Email')
                                    ->placeholder('-'),

                                TextEntry::make('telepon_pelapor')
                                    ->label('Telepon')
                                    ->placeholder('-'),
                            ]),
                    ]),

                Section::make('Foto Keluhan')
                    ->schema([
                        ImageEntry::make('foto_keluhan')
                            ->hiddenLabel()
                            ->disk('public')
                            ->height(300)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (GuestComplaint $record): bool => !empty($record->foto_keluhan)),

                Section::make('Status Penanganan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => GuestComplaint::getStatusOptions()[$state] ?? $state)
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'resolved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),

                                TextEntry::make('handler.name')
                                    ->label('Ditangani Oleh')
                                    ->placeholder('-'),

                                TextEntry::make('handled_at')
                                    ->label('Waktu Penanganan')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                            ]),

                        TextEntry::make('catatan_penanganan')
                            ->label('Catatan Penanganan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Penanganan')
                    ->description('Foto sebelum dan sesudah dari laporan kebersihan')
                    ->schema(function (GuestComplaint $record) {
                        // Get related ActivityReport based on lokasi_id and handled_by
                        $activityReport = ActivityReport::where('lokasi_id', $record->lokasi_id)
                            ->where('petugas_id', $record->handled_by)
                            ->where('created_at', '>=', $record->created_at)
                            ->orderBy('created_at', 'asc')
                            ->first();

                        if (!$activityReport) {
                            return [
                                TextEntry::make('no_photos')
                                    ->hiddenLabel()
                                    ->state('Belum ada foto penanganan dari laporan kebersihan')
                                    ->columnSpanFull(),
                            ];
                        }

                        $components = [];

                        // Foto Sebelum
                        if (!empty($activityReport->foto_sebelum)) {
                            $components[] = Section::make('Foto Sebelum')
                                ->schema([
                                    ImageEntry::make('foto_sebelum_display')
                                        ->hiddenLabel()
                                        ->state($activityReport->foto_sebelum)
                                        ->disk('public')
                                        ->height(200)
                                        ->stacked()
                                        ->limit(5)
                                        ->limitedRemainingText(),
                                ])
                                ->columnSpan(1);
                        }

                        // Foto Sesudah
                        if (!empty($activityReport->foto_sesudah)) {
                            $components[] = Section::make('Foto Sesudah')
                                ->schema([
                                    ImageEntry::make('foto_sesudah_display')
                                        ->hiddenLabel()
                                        ->state($activityReport->foto_sesudah)
                                        ->disk('public')
                                        ->height(200)
                                        ->stacked()
                                        ->limit(5)
                                        ->limitedRemainingText(),
                                ])
                                ->columnSpan(1);
                        }

                        if (empty($components)) {
                            return [
                                TextEntry::make('no_photos')
                                    ->hiddenLabel()
                                    ->state('Tidak ada foto dalam laporan kebersihan')
                                    ->columnSpanFull(),
                            ];
                        }

                        return [
                            Grid::make(2)->schema($components),
                        ];
                    })
                    ->visible(fn (GuestComplaint $record): bool => in_array($record->status, ['in_progress', 'resolved', 'rejected'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGuestComplaints::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Guest complaints are created via public form
    }

    public static function canEdit($record): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'petugas']);
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'petugas']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = GuestComplaint::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
