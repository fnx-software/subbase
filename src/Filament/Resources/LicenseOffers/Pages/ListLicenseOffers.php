<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\LicenseOfferResource;

class ListLicenseOffers extends ListRecords
{
    protected static string $resource = LicenseOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
