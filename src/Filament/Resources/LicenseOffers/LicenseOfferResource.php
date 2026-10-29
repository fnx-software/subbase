<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\LicenseOffer;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Pages\CreateLicenseOffer;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Pages\EditLicenseOffer;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Pages\ListLicenseOffers;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Schemas\LicenseOfferForm;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Tables\LicenseOffersTable;

class LicenseOfferResource extends Resource
{
    protected static ?string $model = LicenseOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationGroup = 'Licensing';

    protected static ?string $recordTitleAttribute = 'sku';

    public static function canViewAny(): bool
    {
        return Auth::check() && Gate::allows('manageLicenseCatalog');
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && SubbaseTenancy::allows($record, 'update', Auth::user());
    }

    public static function canDelete(Model $record): bool
    {
        // Never delete a SKU/price once license orders may reference it.
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && SubbaseTenancy::allows($record, 'view', Auth::user());
    }

    public static function getEloquentQuery(): Builder
    {
        return SubbaseTenancy::scope(parent::getEloquentQuery());
    }

    public static function form(Schema $schema): Schema
    {
        return LicenseOfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenseOffersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenseOffers::route('/'),
            'create' => CreateLicenseOffer::route('/create'),
            'edit' => EditLicenseOffer::route('/{record}/edit'),
        ];
    }
}
