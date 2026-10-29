<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\Subscriptions;

use Nafiswatsiq\Subbase\Filament\Resources\Subscriptions\Pages\CreateSubscription;
use Nafiswatsiq\Subbase\Filament\Resources\Subscriptions\Pages\EditSubscription;
use Nafiswatsiq\Subbase\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use Nafiswatsiq\Subbase\Filament\Resources\Subscriptions\Schemas\SubscriptionForm;
use Nafiswatsiq\Subbase\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use Nafiswatsiq\Subbase\Models\Subscription;
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

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'viewAny', static::getModel());
    }

    public static function canViewAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'viewAny', static::getModel());
    }

    public static function canCreate(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'create', static::getModel());
    }

    public static function canEdit(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'update', static::getModel(), null, $record);
    }

    public static function canDelete(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'delete', static::getModel(), null, $record);
    }

    public static function canDeleteAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'deleteAny', static::getModel());
    }

    public static function canForceDelete(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'forceDelete', static::getModel(), null, $record);
    }

    public static function canForceDeleteAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'forceDeleteAny', static::getModel());
    }

    public static function canRestore(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'restore', static::getModel(), null, $record);
    }

    public static function canRestoreAny(): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'restoreAny', static::getModel());
    }

    public static function canView(Model $record): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'view', static::getModel(), null, $record);
    }

    public static function getNavigationLabel(): string
    {
        return __('subbase::subbase/subscription.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('subbase::subbase/subscription.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
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
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'edit' => EditSubscription::route('/{record}/edit'),
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