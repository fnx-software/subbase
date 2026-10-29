<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\LicenseProductResource;

class CreateLicenseProduct extends CreateRecord
{
    protected static string $resource = LicenseProductResource::class;
}
