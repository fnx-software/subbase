<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\LicenseProduct;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Pages\CreateLicenseProduct;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Pages\EditLicenseProduct;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Pages\ListLicenseProducts;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Schemas\LicenseProductForm;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Tables\LicenseProductsTable;

class LicenseProductResource extends Resource
{
    protected static ?string $model = LicenseProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationGroup = 'Licensing';

    protected static ?string $recordTitleAttribute = 'name';

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
        return LicenseProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenseProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenseProducts::route('/'),
            'create' => CreateLicenseProduct::route('/create'),
            'edit' => EditLicenseProduct::route('/{record}/edit'),
        ];
    }
}
