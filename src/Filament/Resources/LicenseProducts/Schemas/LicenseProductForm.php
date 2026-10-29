<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class LicenseProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')->required()->maxLength(80)->rule('regex:/^[a-z0-9][a-z0-9-]*$/'),
            TextInput::make('name')->required()->maxLength(150),
            Toggle::make('is_active')->default(true),
        ])->columns(2);
    }
}
