<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Nafiswatsiq\Subbase\Models\Plan;

final class LicenseOfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')->relationship('product', 'name')->required()->searchable(),
            Select::make('plan_id')
                ->options(fn (): array => (config('subbase.models.plan', Plan::class))::query()->pluck('slug', 'id')->all())
                ->required()->searchable(),
            TextInput::make('sku')->required()->maxLength(120)->rule('regex:/^[a-z0-9][a-z0-9-]*$/'),
            TextInput::make('currency')->required()->length(3)->default('USD'),
            TextInput::make('retail_price')->required()->numeric()->minValue(0.0001),
            TextInput::make('wholesale_price')->required()->numeric()->minValue(0.0001),
            TextInput::make('wholesale_minimum')->required()->integer()->minValue(2)->default(12),
            TextInput::make('term_count')->required()->integer()->minValue(1)->maxValue(120)->default(1),
            Select::make('term_unit')->required()->options(['day' => 'Days', 'month' => 'Months', 'year' => 'Years'])->default('year'),
            Toggle::make('is_active')->default(true),
        ])->columns(2);
    }
}
