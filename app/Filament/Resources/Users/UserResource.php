<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 10 ? 'warning' : 'success';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        // Hanya admin & super_admin yang bisa akses user management
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        // Hanya admin & super_admin yang bisa create user
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        // Hanya admin & super_admin yang bisa edit user
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        // Hanya super_admin yang bisa delete user
        return $user->hasRole('super_admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        // Menu User Management hanya muncul untuk admin & super_admin
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
