<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseKeys\Pages;

use Filament\Resources\Pages\ListRecords;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseKeys\LicenseKeyResource;

class ListLicenseKeys extends ListRecords
{
    protected static string $resource = LicenseKeyResource::class;
}
