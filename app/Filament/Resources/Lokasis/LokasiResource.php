<?php

namespace App\Filament\Resources\Lokasis;

use App\Filament\Resources\Lokasis\Pages;
use App\Filament\Resources\Lokasis\Pages\ManageLokasis;
use App\Models\Lokasi;
use App\Services\QRCodeService;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                Select::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'nama_unit')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Unit'),

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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.nama_unit')
                    ->label('Unit')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

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
                    ->label('QR Code')
                    ->disk('public')
                    ->height(40)
                    ->width(40)
                    ->checkFileExistence(false)
                    ->defaultImageUrl(url('/images/no-qrcode.png'))
                    ->tooltip(fn (Lokasi $record): string => $record->qr_code ? 'QR Code tersedia' : 'QR Code belum dibuat'),

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
                SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'nama_unit')
                    ->searchable()
                    ->preload(),

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
            ])
            ->recordActions([
                Action::make('generate_qrcode')
                    ->label('Generate QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn (Lokasi $record): bool => empty($record->qr_code))
                    ->hidden(fn (): bool => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor']))
                    ->action(function (Lokasi $record): void {
                        $qrCodeService = new QRCodeService();
                        $qrCodeService->generateForLokasi($record);

                        Notification::make()
                            ->success()
                            ->title('QR Code Berhasil Dibuat')
                            ->body('QR Code untuk ' . $record->nama_lokasi . ' telah dibuat')
                            ->send();
                    })
                    ->successNotificationTitle('QR Code berhasil dibuat'),

                Action::make('regenerate_qrcode')
                    ->label('Regenerate QR Code')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Lokasi $record): bool => !empty($record->qr_code))
                    ->hidden(fn (): bool => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor']))
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate QR Code')
                    ->modalDescription('Apakah Anda yakin ingin membuat ulang QR Code untuk lokasi ini?')
                    ->modalSubmitActionLabel('Ya, Regenerate')
                    ->action(function (Lokasi $record): void {
                        $qrCodeService = new QRCodeService();
                        $qrCodeService->regenerateQRCode($record);

                        Notification::make()
                            ->success()
                            ->title('QR Code Berhasil Diperbarui')
                            ->body('QR Code untuk ' . $record->nama_lokasi . ' telah diperbarui')
                            ->send();
                    })
                    ->successNotificationTitle('QR Code berhasil diperbarui'),

                EditAction::make()
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),
                DeleteAction::make()
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
                    ->hidden(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor'])),
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

        // Admin, super_admin, dan supervisor bisa create lokasi
        return $user->hasAnyRole(['admin', 'super_admin', 'supervisor']);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Admin, super_admin, dan supervisor bisa edit
        return $user->hasAnyRole(['admin', 'super_admin', 'supervisor']);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        // Admin, super_admin, dan supervisor bisa delete
        return $user->hasAnyRole(['admin', 'super_admin', 'supervisor']);
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
