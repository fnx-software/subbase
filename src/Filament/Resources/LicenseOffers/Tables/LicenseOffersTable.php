<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class LicenseOffersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sku')->searchable(),
            TextColumn::make('product.name')->label('Product'),
            TextColumn::make('currency'),
            TextColumn::make('retail_price')->label('Retail'),
            TextColumn::make('wholesale_price')->label('Wholesale'),
            TextColumn::make('wholesale_minimum')->label('Minimum quantity'),
            IconColumn::make('is_active')->boolean(),
        ])
            ->recordActions([EditAction::make()])
            ->defaultSort('id', 'desc');
    }
}
