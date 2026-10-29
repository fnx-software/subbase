<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Licensing;

use Nafiswatsiq\Subbase\Filament\Resources\LicenseKeys\LicenseKeyResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\LicenseOfferResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOrders\LicenseOrderResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\LicenseProductResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\LicenseProviderResource;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class LicenseAdminResourcesTest extends TestCase
{
    public function test_filament_five_resources_boot_as_classes(): void
    {
        foreach ([
            LicenseProductResource::class,
            LicenseOfferResource::class,
            LicenseProviderResource::class,
            LicenseOrderResource::class,
            LicenseKeyResource::class,
        ] as $resource) {
            $this->assertTrue(class_exists($resource));
            $this->assertFalse($resource::canCreate() && in_array($resource, [
                LicenseProviderResource::class,
                LicenseOrderResource::class,
                LicenseKeyResource::class,
            ], true));
        }
    }

    public function test_public_users_cannot_open_license_catalog_admin(): void
    {
        $this->assertFalse(LicenseProductResource::canViewAny());
        $this->assertFalse(LicenseOfferResource::canViewAny());
        $this->assertFalse(LicenseProviderResource::canViewAny());
        $this->assertFalse(LicenseOrderResource::canViewAny());
        $this->assertFalse(LicenseKeyResource::canViewAny());
    }
}
