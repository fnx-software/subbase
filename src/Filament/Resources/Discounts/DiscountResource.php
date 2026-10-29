<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\Discounts;

use Nafiswatsiq\Subbase\Filament\Resources\Discounts\Pages\CreateDiscount;
use Nafiswatsiq\Subbase\Filament\Resources\Discounts\Pages\EditDiscount;
use Nafiswatsiq\Subbase\Filament\Resources\Discounts\Pages\ListDiscounts;
use Nafiswatsiq\Subbase\Filament\Resources\Discounts\Schemas\DiscountForm;
use Nafiswatsiq\Subbase\Filament\Resources\Discounts\Tables\DiscountsTable;
use Nafiswatsiq\Subbase\Models\Discount;
use Nafiswatsiq\Subbase\Support\SubbasePermission;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'viewAny', static::getModel());
    }

    public static function canViewAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'viewAny', static::getModel());
    }

    public static function canCreate(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'create', static::getModel());
    }

    public static function canEdit(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'update', static::getModel()), null, $record);
    }

    public static function canDelete(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'delete', static::getModel()), null, $record);
    }

    public static function canDeleteAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'deleteAny', static::getModel());
    }

    public static function canForceDelete(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'forceDelete', static::getModel()), null, $record);
    }

    public static function canForceDeleteAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'forceDeleteAny', static::getModel());
    }

    public static function canRestore(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'restore', static::getModel()), null, $record);
    }

    public static function canRestoreAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'restoreAny', static::getModel());
    }

    public static function canReplicate(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'replicate', static::getModel()), null, $record);
    }

    public static function canView(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'view', static::getModel()), null, $record);
    }

    public static function getNavigationLabel(): string
    {
        return __('subbase::subbase/discount.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('subbase::subbase/plan.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return DiscountForm::configure($schema);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function table(Table $table): Table
    {
        return DiscountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscounts::route('/'),
            'create' => CreateDiscount::route('/create'),
            'edit' => EditDiscount::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return SubbaseTenancy::scope(parent::getEloquentQuery());
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return SubbaseTenancy::scope(
            parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]),
        );
    }
}