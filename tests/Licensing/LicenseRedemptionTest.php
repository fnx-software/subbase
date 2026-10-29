<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Licensing;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Services\LicenseFulfillmentService;
use Nafiswatsiq\Subbase\Services\LicenseInventoryService;
use Nafiswatsiq\Subbase\Services\LicenseOrderService;
use Nafiswatsiq\Subbase\Services\LicenseProviderService;
use Nafiswatsiq\Subbase\Services\LicenseRedemptionService;

final class LicenseRedemptionTest extends LicenseTestCase
{
    public function test_license_year_starts_on_customer_redemption_and_never_redeems_twice(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->startOfDay());

        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 123);
        $keys = app(LicenseInventoryService::class)->reveal($order, $this->actor('merchant'));

        $this->travelTo(now()->addMonths(2));
        $key = app(LicenseRedemptionService::class)->redeem($keys[0], $this->organization(2), $this->actor('customer'));
        $this->assertSame('redeemed', $key->status);
        $this->assertNull($key->code_ciphertext);
        $this->assertSame('2', $key->redeemed_by_id);
        $this->assertNotNull($key->subscription_id);

        $subscription = $this->organization(2)->planSubscriptions()->firstOrFail();
        $this->assertSame('license-crm', $subscription->slug);
        $this->assertSame('2027-12-29', $subscription->ends_at->toDateString());

        $this->expectException(InvalidArgumentException::class);
        app(LicenseRedemptionService::class)->redeem($keys[0], $this->organization(2), $this->actor('customer'));
    }

    public function test_a_second_key_extends_same_product_subscription_without_duplicate(): void
    {
        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 123);
        $keys = app(LicenseInventoryService::class)->reveal($order, $this->actor('merchant'));
        $service = app(LicenseRedemptionService::class);
        $service->redeem($keys[0], $this->organization(2), $this->actor('customer'));
        $firstEnd = $this->organization(2)->planSubscriptions()->firstOrFail()->ends_at->copy();
        $service->redeem($keys[1], $this->organization(2), $this->actor('customer'));
        $second = $this->organization(2)->planSubscriptions()->firstOrFail();

        $this->assertSame(1, $this->organization(2)->planSubscriptions()->count());
        $this->assertSame($firstEnd->addYearNoOverflow()->toDateString(), $second->ends_at->toDateString());
    }

    public function test_invalid_key_never_creates_subscription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        try {
            app(LicenseRedemptionService::class)->redeem('LIC-FAKE-KEY', $this->organization(2), $this->actor('customer'));
        } finally {
            $this->assertSame(0, DB::table('sb_lic_test_subscriptions')->count());
        }
    }

    public function test_suspending_provider_does_not_invalidate_purchased_customer_keys(): void
    {
        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 128);
        $keys = app(LicenseInventoryService::class)->reveal($order, $this->actor('merchant'));

        app(LicenseProviderService::class)->suspend($provider, $this->actor('admin'));
        $redeemed = app(LicenseRedemptionService::class)->redeem($keys[0], $this->organization(2), $this->actor('customer'));

        $this->assertSame('redeemed', $redeemed->status);
    }
}
