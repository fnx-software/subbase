<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\LicenseOfferResource;

class CreateLicenseOffer extends CreateRecord
{
    protected static string $resource = LicenseOfferResource::class;
}
