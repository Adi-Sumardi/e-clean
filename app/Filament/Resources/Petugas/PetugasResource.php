<?php

namespace App\Filament\Resources\Petugas;

use App\Filament\Resources\Petugas\Pages\ManagePetugas;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PetugasResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Petugas';

    protected static string | \UnitEnum | null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Petugas';

    protected static ?string $pluralModelLabel = 'Petugas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Petugas')
                    ->description('Data petugas kebersihan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Petugas')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Nomor WhatsApp')
                            ->tel()
                            ->helperText('Format: 62812xxxx (untuk notifikasi WhatsApp)')
                            ->placeholder('628123456789')
                            ->maxLength(20),

                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->helperText('Kosongkan jika tidak ingin mengubah password')
                            ->minLength(8)
                            ->maxLength(255)
                            ->placeholder('Minimal 8 karakter'),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Petugas yang non-aktif tidak dapat login ke aplikasi mobile')
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Petugas')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email disalin!')
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('phone')
                    ->label('No. WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor disalin!')
                    ->placeholder('Belum diisi')
                    ->icon('heroicon-o-phone'),

                ToggleColumn::make('is_active')
                    ->label('Status Aktif')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->afterStateUpdated(function ($record, $state) {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Status Berhasil Diubah')
                            ->body("Status {$record->name} berhasil diubah menjadi " . ($state ? 'aktif' : 'non-aktif'))
                            ->send();
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-Aktif')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit')
                    ->modalWidth('3xl')
                    ->modalHeading(fn ($record) => "Edit Petugas: {$record->name}")
                    ->mutateFormDataUsing(function (array $data): array {
                        // Remove password from data if empty
                        if (empty($data['password'])) {
                            unset($data['password']);
                        } else {
                            // Hash password if provided
                            $data['password'] = Hash::make($data['password']);
                        }
                        return $data;
                    })
                    ->successNotificationTitle('Petugas berhasil diperbarui')
                    ->after(function ($record) {
                        // Show notification with updated info
                        Notification::make()
                            ->success()
                            ->title('Data Tersimpan')
                            ->body("Data {$record->name} berhasil diperbarui")
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePetugas::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show users with 'petugas' role
        return parent::getEloquentQuery()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'petugas');
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getEloquentQuery()->count();
        return $count > 10 ? 'success' : 'warning';
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        // Supervisor, admin & super_admin bisa akses
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }

    public static function canCreate(): bool
    {
        // Disable create - petugas dibuat lewat User Management
        return false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        // Supervisor, admin & super_admin bisa edit
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        // Disable delete - untuk safety
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Menu muncul untuk supervisor, admin & super_admin
        return $user->hasAnyRole(['supervisor', 'admin', 'super_admin']);
    }
}
