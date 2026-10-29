<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Models\LicenseOffer;
use Nafiswatsiq\Subbase\Models\LicenseOrder;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use Nafiswatsiq\Subbase\Support\Money;
use RuntimeException;

final class LicenseOrderService
{
    /**
     * Price and buyer are server-side snapshots, never accepted from clients.
     * Retail orders contain one key; wholesale orders obey the configured MOQ.
     */
    public function place(
        LicenseOffer $offer,
        Model $purchaser,
        Authenticatable $actor,
        int $quantity = 1,
        ?LicenseProvider $provider = null,
        ?string $idempotencyKey = null,
    ): LicenseOrder {
        if (! $purchaser->exists || $purchaser->getKey() === null) {
            throw new InvalidArgumentException('Purchaser must be saved before placing an order.');
        }

        if ($idempotencyKey !== null && ! preg_match('/^[a-zA-Z0-9:_-]{12,80}$/D', $idempotencyKey)) {
            throw new InvalidArgumentException('Invalid idempotency token.');
        }

        $isWholesale = $provider !== null;
        $max = (int) config('subbase.licensing.max_order_quantity', 500);
        if ($quantity < 1 || $quantity > $max) {
            throw new InvalidArgumentException('License quantity is outside allowed limits.');
        }

        if ($isWholesale) {
            if (! $provider->exists || ! $provider->approved()
                || $purchaser->getMorphClass() !== $provider->owner_type
                || (string) $purchaser->getKey() !== (string) $provider->owner_id
                || $quantity < (int) $offer->wholesale_minimum
            ) {
                throw new InvalidArgumentException('Reseller approval, owner and minimum quantity are required.');
            }

            if (! Gate::forUser($actor)->allows('purchaseWholesaleLicenses', $provider)) {
                throw new AuthorizationException('Not authorized to purchase wholesale keys.');
            }
        } elseif ($quantity !== 1 || ! Gate::forUser($actor)->allows('purchaseRetailLicense', $purchaser)) {
            throw new AuthorizationException('Retail licensing requires an authorized single-key purchaser.');
        }

        // Always check catalog state at order creation, not from stale client data.
        $offer = LicenseOffer::query()->with('product')->findOrFail($offer->getKey());
        if (! $offer->is_active || ! $offer->product->is_active
            || ! in_array($offer->term_unit, ['day', 'month', 'year'], true)
            || $offer->term_count < 1 || $offer->term_count > 120
        ) {
            throw new InvalidArgumentException('The license offer is unavailable.');
        }

        $retail = new Money((string) $offer->retail_price, (string) $offer->currency);
        $wholesale = new Money((string) $offer->wholesale_price, (string) $offer->currency);

        if ($retail->isZero() || bccomp($wholesale->amount(), $retail->amount(), 4) > 0) {
            throw new InvalidArgumentException('Invalid retail or wholesale offer price.');
        }

        $unit = $isWholesale ? $wholesale : $retail;
        if ($unit->isZero()) {
            throw new InvalidArgumentException('Free wholesale orders require a separate administrative grant.');
        }

        $total = new Money(bcmul($unit->amount(), (string) $quantity, 4), (string) $offer->currency);

        return DB::transaction(function () use ($offer, $purchaser, $quantity, $provider, $idempotencyKey, $isWholesale, $retail, $unit, $total): LicenseOrder {
            if ($idempotencyKey !== null) {
                $existing = LicenseOrder::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    if ((int) $existing->offer_id !== (int) $offer->getKey()
                        || (int) $existing->quantity !== $quantity
                        || (string) $existing->purchaser_type !== $purchaser->getMorphClass()
                        || (string) $existing->purchaser_id !== (string) $purchaser->getKey()
                        || (string) ($existing->provider_id ?? '') !== (string) ($provider?->getKey() ?? '')
                    ) {
                        throw new RuntimeException('Idempotency token was already used for another purchase.');
                    }

                    return $existing;
                }
            }

            return LicenseOrder::query()->create([
                'public_id' => (string) Str::uuid(),
                'offer_id' => $offer->getKey(),
                'provider_id' => $provider?->getKey(),
                'purchaser_type' => $purchaser->getMorphClass(),
                'purchaser_id' => (string) $purchaser->getKey(),
                'channel' => $isWholesale ? 'wholesale' : 'retail',
                'contract_plan_id' => $offer->plan_id,
                'contract_product_sku' => $offer->product->sku,
                'contract_term_count' => $offer->term_count,
                'contract_term_unit' => $offer->term_unit,
                'quantity' => $quantity,
                'currency' => strtoupper((string) $offer->currency),
                'retail_reference_price' => $retail->amount(),
                'unit_price' => $unit->amount(),
                'total_amount' => $total->amount(),
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
            ]);
        }, 3);
    }
}
