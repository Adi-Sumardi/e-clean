<?php

namespace App\Filament\Resources\Lokasis;

use App\Filament\Forms\Components\MapPicker;
use App\Filament\Resources\Lokasis\Pages;
use App\Filament\Resources\Lokasis\Pages\ManageLokasis;
use App\Models\Lokasi;
use App\Services\BarcodeService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LokasiResource extends Resource
{
    protected static ?string $model = Lokasi::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Lokasi';

    // Remove navigationGroup - make it a top-level menu
    // protected static string | \UnitEnum | null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_lokasi')
                    ->label('Kode Lokasi')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('Contoh: RK-1A, TL-L2'),

                TextInput::make('nama_lokasi')
                    ->label('Nama Lokasi')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Contoh: Ruang Kelas 1A'),

                Select::make('kategori')
                    ->label('Kategori')
                    ->required()
                    ->options([
                        'ruang_kelas' => 'Ruang Kelas',
                        'toilet' => 'Toilet',
                        'kantor' => 'Kantor',
                        'aula' => 'Aula',
                        'taman' => 'Taman',
                        'koridor' => 'Koridor',
                        'lainnya' => 'Lainnya',
                    ])
                    ->native(false),

                TextInput::make('lantai')
                    ->label('Lantai')
                    ->maxLength(255)
                    ->placeholder('Contoh: Lantai 1, Lantai 2'),

                TextInput::make('luas_area')
                    ->label('Luas Area (m²)')
                    ->numeric()
                    ->step(0.01)
                    ->suffix('m²'),

                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull(),

                FileUpload::make('foto_lokasi')
                    ->label('Foto Lokasi')
                    ->image()
                    ->directory('lokasi')
                    ->visibility('public')
                    ->imageEditor()
                    ->columnSpanFull(),

                Select::make('status_kebersihan')
                    ->label('Status Kebersihan')
                    ->options([
                        'bersih' => 'Bersih',
                        'kotor' => 'Kotor',
                        'belum_dicek' => 'Belum Dicek',
                    ])
                    ->default('belum_dicek')
                    ->native(false),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                Section::make('Koordinat GPS')
                    ->description('Koordinat GPS diperlukan untuk validasi foto lokasi. Petugas harus berada dalam radius 50m dari koordinat ini saat mengambil foto.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        MapPicker::make('map_picker')
                            ->label('Pilih Lokasi di Peta')
                            ->defaultLocation(-6.2088, 106.8456)
                            ->defaultZoom(15)
                            ->height(350)
                            ->columnSpanFull(),

                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->minValue(-90)
                            ->maxValue(90)
                            ->placeholder('-6.2088')
                            ->live()
                            ->helperText('Akan terisi otomatis dari peta'),

                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->minValue(-180)
                            ->maxValue(180)
                            ->placeholder('106.8456')
                            ->live()
                            ->helperText('Akan terisi otomatis dari peta'),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(2)
                            ->placeholder('Alamat lengkap lokasi (opsional)')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_lokasi')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama_lokasi')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ruang_kelas' => 'info',
                        'toilet' => 'warning',
                        'kantor' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),

                TextColumn::make('lantai')
                    ->label('Lantai')
                    ->sortable(),

                ImageColumn::make('qr_code')
                    ->label('Barcode')
                    ->disk('public')
                    ->height(40)
                    ->width(120)
                    ->defaultImageUrl(url('/images/no-barcode.png'))
                    ->tooltip(fn (Lokasi $record): string => $record->qr_code ? 'Barcode tersedia' : 'Barcode belum dibuat'),

                IconColumn::make('has_gps')
                    ->label('GPS')
                    ->getStateUsing(fn (Lokasi $record): bool => $record->latitude && $record->longitude)
                    ->boolean()
                    ->trueIcon('heroicon-o-map-pin')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (Lokasi $record): string =>
                        $record->latitude && $record->longitude
                            ? "GPS: {$record->latitude}, {$record->longitude}"
                            : 'GPS belum diisi'
                    ),

                TextColumn::make('status_kebersihan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bersih' => 'success',
                        'kotor' => 'danger',
                        'belum_dicek' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
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
                SelectFilter::make('kategori')
                    ->options([
                        'ruang_kelas' => 'Ruang Kelas',
                        'toilet' => 'Toilet',
                        'kantor' => 'Kantor',
                        'aula' => 'Aula',
                        'taman' => 'Taman',
                        'koridor' => 'Koridor',
                        'lainnya' => 'Lainnya',
                    ]),
                SelectFilter::make('status_kebersihan')
                    ->options([
                        'bersih' => 'Bersih',
                        'kotor' => 'Kotor',
                        'belum_dicek' => 'Belum Dicek',
                    ]),
                SelectFilter::make('gps_status')
                    ->label('Status GPS')
                    ->options([
                        'has_gps' => 'Sudah ada GPS',
                        'no_gps' => 'Belum ada GPS',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'has_gps' => $query->whereNotNull('latitude')->whereNotNull('longitude'),
                            'no_gps' => $query->where(function ($q) {
                                $q->whereNull('latitude')->orWhereNull('longitude');
                            }),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('generate_barcode')
                    ->label('Generate Barcode')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn (Lokasi $record) => !$record->qr_code)
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus']))
                    ->action(function (Lokasi $record) {
                        $barcodeService = new BarcodeService();
                        $barcodeService->generateForLokasi($record);

                        Notification::make()
                            ->success()
                            ->title('Barcode Berhasil Dibuat')
                            ->body('Barcode untuk ' . $record->nama_lokasi . ' telah dibuat')
                            ->send();
                    }),

                Action::make('regenerate_barcode')
                    ->label('Regenerate Barcode')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Lokasi $record) => $record->qr_code)
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus']))
                    ->requiresConfirmation()
                    ->action(function (Lokasi $record) {
                        $barcodeService = new BarcodeService();
                        $barcodeService->regenerateBarcode($record);

                        Notification::make()
                            ->success()
                            ->title('Barcode Berhasil Diperbarui')
                            ->body('Barcode untuk ' . $record->nama_lokasi . ' telah diperbarui')
                            ->send();
                    }),

                EditAction::make()
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
                DeleteAction::make()
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->hidden(fn () => Auth::user()->hasAnyRole(['petugas', 'pengurus'])),
            ])
            ->defaultSort('kode_lokasi');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLokasis::route('/'),
            'print-qr' => Pages\PrintQRCodes::route('/print-qr'),
        ];
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        // Hanya admin & super_admin yang bisa create lokasi
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Hanya admin & super_admin yang bisa edit
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        // Hanya admin & super_admin yang bisa delete
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canViewAny(): bool
    {
        // Bypass Shield permission check - use role-based authorization instead
        // Petugas bisa view via direct URL/widgets, tapi tidak tampil di navigation
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Hide dari sidebar navigation untuk petugas (mereka akses via dashboard/widgets)
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'pengurus']);
    }
}
