<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\LicenseKey;
use Nafiswatsiq\Subbase\Models\LicenseOrder;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use Nafiswatsiq\Subbase\Models\LicenseSale;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;

final class LicenseProviderDashboard
{
    /**
     * @return array{provider_status: string, orders: int, keys: array<string,int>, currencies: array<string,array<string,string>>}
     */
    public function summary(LicenseProvider $provider, Authenticatable $actor): array
    {
        if (! SubbaseTenancy::allows($provider, 'view', $actor)
            || ! Gate::forUser($actor)->allows('viewLicenseProviderDashboard', $provider)
        ) {
            throw new AuthorizationException('Cannot view this reseller account.');
        }

        $currencyTotals = [];

        LicenseOrder::query()
            ->where('provider_id', $provider->getKey())
            ->where('status', 'fulfilled')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$currencyTotals): void {
                foreach ($orders as $order) {
                    $currency = strtoupper((string) $order->currency);
                    $currencyTotals[$currency] ??= ['purchases' => '0.0000', 'sales' => '0.0000', 'gross_margin' => '0.0000'];
                    $currencyTotals[$currency]['purchases'] = bcadd(
                        $currencyTotals[$currency]['purchases'], (string) $order->total_amount, 4,
                    );
                }
            });

        LicenseSale::query()
            ->where('provider_id', $provider->getKey())
            ->with('key.order')
            ->orderBy('id')
            ->chunkById(100, function ($sales) use (&$currencyTotals): void {
                foreach ($sales as $sale) {
                    $order = $sale->key->order;
                    $currency = strtoupper((string) $sale->currency);
                    $currencyTotals[$currency] ??= ['purchases' => '0.0000', 'sales' => '0.0000', 'gross_margin' => '0.0000'];
                    $currencyTotals[$currency]['sales'] = bcadd(
                        $currencyTotals[$currency]['sales'], (string) $sale->sale_price, 4,
                    );
                    $currencyTotals[$currency]['gross_margin'] = bcadd(
                        $currencyTotals[$currency]['gross_margin'],
                        bcsub((string) $sale->sale_price, (string) $order->unit_price, 4),
                        4,
                    );
                }
            });

        return [
            'provider_status' => (string) $provider->status,
            'orders' => LicenseOrder::query()->where('provider_id', $provider->getKey())->count(),
            'keys' => [
                'available' => LicenseKey::query()->where('provider_id', $provider->getKey())->where('status', 'available')->count(),
                'redeemed' => LicenseKey::query()->where('provider_id', $provider->getKey())->where('status', 'redeemed')->count(),
                'revoked' => LicenseKey::query()->where('provider_id', $provider->getKey())->where('status', 'revoked')->count(),
                'sales_recorded' => LicenseSale::query()->where('provider_id', $provider->getKey())->count(),
            ],
            'currencies' => $currencyTotals,
        ];
    }
}
