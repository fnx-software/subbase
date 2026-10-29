<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\LicenseProductResource;

class ListLicenseProducts extends ListRecords
{
    protected static string $resource = LicenseProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
