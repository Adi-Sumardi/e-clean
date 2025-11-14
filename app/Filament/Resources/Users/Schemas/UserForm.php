<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->description('Informasi dasar pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
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
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText(fn (string $context): string =>
                                $context === 'edit'
                                    ? 'Kosongkan jika tidak ingin mengubah password'
                                    : 'Minimal 8 karakter'
                            )
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Role & Permission')
                    ->description('Atur role pengguna untuk menentukan hak akses')
                    ->schema([
                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->helperText('Pilih role untuk pengguna (petugas, supervisor, atau admin)')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Petugas yang non-aktif tidak dapat login ke aplikasi mobile')
                            ->inline(false)
                            ->columnSpanFull(),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->helperText('Waktu verifikasi email')
                            ->displayFormat('d/m/Y H:i')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
