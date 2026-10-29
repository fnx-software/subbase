<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\Pages\ListLicenseProviders;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\Tables\LicenseProvidersTable;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use UnitEnum;

class LicenseProviderResource extends Resource
{
    protected static ?string $model = LicenseProvider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Licensing';

    public static function canViewAny(): bool
    {
        return Auth::check() && Gate::allows('manageLicenseProviders');
    }

    public static function canCreate(): bool
    {
        return false; // Providers apply through the authenticated marketplace.
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Approval/suspension must use protected services.
    }

    public static function canDelete(Model $record): bool
    {
        return false; // Historical wholesale orders belong to the partner.
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && SubbaseTenancy::allows($record, 'view', Auth::user());
    }

    public static function getEloquentQuery(): Builder
    {
        return SubbaseTenancy::scope(parent::getEloquentQuery());
    }

    public static function table(Table $table): Table
    {
        return LicenseProvidersTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListLicenseProviders::route('/')];
    }
}
