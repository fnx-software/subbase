<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Licensing;

use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Models\LicenseKey;
use Nafiswatsiq\Subbase\Models\LicenseOrder;
use Nafiswatsiq\Subbase\Services\LicenseFulfillmentService;
use Nafiswatsiq\Subbase\Services\LicenseInventoryService;
use Nafiswatsiq\Subbase\Services\LicenseOrderService;
use Nafiswatsiq\Subbase\Services\LicenseProviderService;
use Nafiswatsiq\Subbase\Services\LicenseResaleService;
use RuntimeException;

final class LicenseWholesaleTest extends LicenseTestCase
{
    public function test_reseller_buys_twelve_for_one_hundred_each_and_earns_forty_per_sale(): void
    {
        $offer = $this->offer();
        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place(
            $offer, $this->organization(1), $this->actor('merchant'), 12, $provider, 'order_wholesale_123',
        );

        $this->assertSame('wholesale', $order->channel);
        $this->assertSame('150.0000', $order->retail_reference_price);
        $this->assertSame('100.0000', $order->unit_price);
        $this->assertSame('1200.0000', $order->total_amount);
        $this->assertSame('pending', $order->status);
        $this->assertSame(0, LicenseKey::query()->count());

        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 9876);
        $this->assertSame(12, LicenseKey::query()->count());
        $this->assertSame(12, LicenseKey::query()->distinct()->count('code_hash'));
        $this->assertSame(12, count(app(LicenseInventoryService::class)->reveal($order, $this->actor('merchant'))));

        $key = $order->keys()->firstOrFail();
        $resale = app(LicenseResaleService::class)->record(
            $key, $provider, $this->actor('merchant'), '140.00', 'SHOP-ORDER-1',
        );

        $this->assertSame('40.0000', $resale['gross_margin']);
        $this->assertSame('140.0000', $resale['sale']->sale_price);
        $this->assertSame('USD', $resale['currency']);

        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 9876);
        $this->assertSame(12, $order->keys()->count());
    }

    public function test_pending_orders_cannot_export_license_keys(): void
    {
        $order = app(LicenseOrderService::class)->place(
            $this->offer(), $this->organization(1), $this->actor('merchant'), 12, $this->reseller(),
        );

        $this->expectException(RuntimeException::class);
        app(LicenseInventoryService::class)->reveal($order, $this->actor('merchant'));
    }

    public function test_unapproved_reseller_cannot_purchase(): void
    {
        $provider = app(LicenseProviderService::class)->apply(
            $this->organization(1), $this->actor('merchant'), 'Pending shop',
        );

        $this->expectException(InvalidArgumentException::class);
        app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
    }

    public function test_quantity_below_wholesale_minimum_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 11, $this->reseller());
    }

    public function test_retail_checkout_is_one_key_at_official_price(): void
    {
        $order = app(LicenseOrderService::class)->place(
            $this->offer(), $this->organization(2), $this->actor('customer'),
        );

        $this->assertSame('retail', $order->channel);
        $this->assertSame(1, $order->quantity);
        $this->assertSame('150.0000', $order->total_amount);
        $this->assertNull($order->provider_id);
    }

    public function test_idempotency_reuses_same_order_and_refuses_cross_account_reuse(): void
    {
        $service = app(LicenseOrderService::class);
        $offer = $this->offer();
        $provider = $this->reseller();

        $first = $service->place($offer, $this->organization(1), $this->actor('merchant'), 12, $provider, 'same_key_123456');
        $second = $service->place($offer, $this->organization(1), $this->actor('merchant'), 12, $provider, 'same_key_123456');
        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(1, LicenseOrder::query()->count());

        $this->expectException(RuntimeException::class);
        $service->place($offer, $this->organization(2), $this->actor('customer'), 1, null, 'same_key_123456');
    }

    public function test_another_actor_cannot_export_reseller_stock(): void
    {
        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 17);

        $this->expectException(AuthorizationException::class);
        app(LicenseInventoryService::class)->reveal($order, $this->actor('customer'));
    }

    public function test_wholesale_resale_price_cannot_exceed_public_retail_by_default(): void
    {
        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 101);

        $this->expectException(InvalidArgumentException::class);
        app(LicenseResaleService::class)->record($order->keys()->firstOrFail(), $provider, $this->actor('merchant'), '160.00');
    }

    public function test_a_different_gateway_payment_cannot_fulfill_existing_order_twice(): void
    {
        $provider = $this->reseller();
        $order = app(LicenseOrderService::class)->place($this->offer(), $this->organization(1), $this->actor('merchant'), 12, $provider);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 1);

        $this->expectException(RuntimeException::class);
        app(LicenseFulfillmentService::class)->fulfillPaidOrder($order, 2);
    }
}
