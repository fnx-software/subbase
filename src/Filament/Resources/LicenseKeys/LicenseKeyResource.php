<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseKeys;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\LicenseKey;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseKeys\Pages\ListLicenseKeys;

class LicenseKeyResource extends Resource
{
    protected static ?string $model = LicenseKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationGroup = 'Licensing';

    public static function canViewAny(): bool
    {
        return Auth::check() && Gate::allows('viewLicenseOrders');
    }

    public static function canCreate(): bool
    {
        return false; // Only issue through a verified paid order.
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
                TextColumn::make('id')->label('License ID'),
                TextColumn::make('code_tail')->label('Last 8 characters'),
                TextColumn::make('order.public_id')->label('Order'),
                TextColumn::make('provider.business_name')->label('Provider'),
                TextColumn::make('status')->badge(),
                TextColumn::make('redeemed_at')->dateTime(),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListLicenseKeys::route('/')];
    }
}
