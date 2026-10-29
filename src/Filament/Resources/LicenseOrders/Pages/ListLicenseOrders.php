<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Filament\Resources\LicenseOrders\Pages;

use Filament\Resources\Pages\ListRecords;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOrders\LicenseOrderResource;

class ListLicenseOrders extends ListRecords
{
    protected static string $resource = LicenseOrderResource::class;
}
