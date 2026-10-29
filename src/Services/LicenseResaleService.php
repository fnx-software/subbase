<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Models\LicenseKey;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use Nafiswatsiq\Subbase\Models\LicenseSale;
use Nafiswatsiq\Subbase\Support\Money;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use RuntimeException;

final class LicenseResaleService
{
    /**
     * Resellers receive the full difference between their declared local sale
     * and the original wholesale snapshot, before their expenses and taxes.
     * This does NOT process the end customer's payment.
     *
     * @return array{sale: LicenseSale, gross_margin: string, currency: string}
     */
    public function record(
        LicenseKey $key,
        LicenseProvider $provider,
        Authenticatable $actor,
        string $salePrice,
        ?string $externalReference = null,
    ): array {
        if (! SubbaseTenancy::allows($provider, 'recordLicenseResale', $actor)
            || ! Gate::forUser($actor)->allows('recordLicenseResale', $provider)
        ) {
            throw new AuthorizationException('Not authorized to record this reseller sale.');
        }

        return DB::transaction(function () use ($key, $provider, $salePrice, $externalReference): array {
            $key = LicenseKey::query()->lockForUpdate()->findOrFail($key->getKey());
            if ((string) $key->provider_id !== (string) $provider->getKey()
                || ! in_array($key->status, ['available', 'redeemed'], true)
            ) {
                throw new RuntimeException('This key does not belong to the reseller inventory.');
            }

            $order = $key->order()->firstOrFail();
            if ($order->status !== 'fulfilled') {
                throw new RuntimeException('Resale requires a paid wholesale license.');
            }

            $price = new Money($salePrice, (string) $order->currency);
            if ($price->isZero()) {
                throw new InvalidArgumentException('A resale price is required.');
            }

            if (config('subbase.licensing.reseller_max_retail_price', true)
                && bccomp($price->amount(), (string) $order->retail_reference_price, 4) > 0
            ) {
                throw new InvalidArgumentException('The resale price exceeds published retail price.');
            }

            $existing = LicenseSale::query()->where('key_id', $key->getKey())->first();
            if ($existing) {
                throw new RuntimeException('This key already has a recorded reseller sale.');
            }

            if ($externalReference !== null && mb_strlen($externalReference) > 120) {
                throw new InvalidArgumentException('External sale reference is too long.');
            }

            $sale = LicenseSale::query()->create([
                'key_id' => $key->getKey(),
                'provider_id' => $provider->getKey(),
                'sale_price' => $price->amount(),
                'currency' => $order->currency,
                'external_reference' => $externalReference,
                'sold_at' => now(),
            ]);

            return [
                'sale' => $sale,
                'gross_margin' => bcsub($price->amount(), (string) $order->unit_price, 4),
                'currency' => (string) $order->currency,
            ];
        });
    }
}
