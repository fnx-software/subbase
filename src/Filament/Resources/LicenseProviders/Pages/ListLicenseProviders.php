<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\Pages;

use Filament\Resources\Pages\ListRecords;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\LicenseProviderResource;

class ListLicenseProviders extends ListRecords
{
    protected static string $resource = LicenseProviderResource::class;
}
