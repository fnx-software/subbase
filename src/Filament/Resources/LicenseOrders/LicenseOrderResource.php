<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOrders;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\LicenseOrder;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOrders\Pages\ListLicenseOrders;

class LicenseOrderResource extends Resource
{
    protected static ?string $model = LicenseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationGroup = 'Licensing';

    public static function canViewAny(): bool
    {
        return Auth::check() && Gate::allows('viewLicenseOrders');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
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

    public static function table(Table $table): Table
    {
        return $table->columns([
                TextColumn::make('public_id')->label('Order')->searchable(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('provider.business_name')->label('Reseller'),
                TextColumn::make('offer.sku')->label('License offer'),
                TextColumn::make('quantity'),
                TextColumn::make('currency'),
                TextColumn::make('unit_price'),
                TextColumn::make('total_amount'),
                TextColumn::make('status')->badge(),
                TextColumn::make('fulfilled_at')->dateTime(),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListLicenseOrders::route('/')];
    }
}
