<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\LicenseOrder;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use RuntimeException;

final class LicenseInventoryService
{
    /**
     * Explicitly-authorized export only. Never expose codes in ordinary
     * model serialization, Filament tables, webhook logs, or mail diagnostics.
     *
     * @return array<int, string>
     */
    public function reveal(LicenseOrder $order, Authenticatable $actor): array
    {
        $order = LicenseOrder::query()->findOrFail($order->getKey());

        if (! SubbaseTenancy::allows($order, 'viewLicenseKeys', $actor)
            || ! Gate::forUser($actor)->allows('viewPurchasedLicenseKeys', $order)
        ) {
            throw new AuthorizationException('License inventory is private.');
        }

        if ($order->status !== 'fulfilled') {
            throw new RuntimeException('License keys are not available until payment has cleared.');
        }

        return $order->keys()
            ->where('status', 'available')
            ->orderBy('id')
            ->get(['code_ciphertext'])
            ->map(static fn ($key): string => Crypt::decryptString($key->code_ciphertext))
            ->all();
    }
}
