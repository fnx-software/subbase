<?php

namespace Nafiswatsiq\Subbase;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Nafiswatsiq\Subbase\Filament\Resources\Plans\PlanResource;
use Nafiswatsiq\Subbase\Filament\Resources\Subscriptions\SubscriptionResource;
use Nafiswatsiq\Subbase\Filament\Resources\Discounts\DiscountResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProducts\LicenseProductResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOffers\LicenseOfferResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseProviders\LicenseProviderResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseOrders\LicenseOrderResource;
use Nafiswatsiq\Subbase\Filament\Resources\LicenseKeys\LicenseKeyResource;

class SubbasePlugin implements Plugin
{
    public function getId(): string
    {
        return 'subbase';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PlanResource::class,
            SubscriptionResource::class,
            DiscountResource::class,
            LicenseProductResource::class,
            LicenseOfferResource::class,
            LicenseProviderResource::class,
            LicenseOrderResource::class,
            LicenseKeyResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
      // 
    }

    public static function make(): static
    {
        return app(static::class);
    }
}