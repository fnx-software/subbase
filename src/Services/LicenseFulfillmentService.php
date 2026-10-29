<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Nafiswatsiq\Subbase\Models\LicenseKey;
use Nafiswatsiq\Subbase\Models\LicenseOrder;
use RuntimeException;

final class LicenseFulfillmentService
{
    /**
     * Trusted backend only: caller MUST verify financial settlement before use.
     * Atomic and idempotent for the original gateway payment reference.
     */
    public function fulfillPaidOrder(LicenseOrder $order, int $paymentId): LicenseOrder
    {
        if ($paymentId < 1) {
            throw new RuntimeException('A confirmed payment reference is mandatory.');
        }

        return DB::transaction(function () use ($order, $paymentId): LicenseOrder {
            $locked = LicenseOrder::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->status === 'fulfilled') {
                if ((int) $locked->payment_id !== $paymentId
                    || $locked->keys()->count() !== (int) $locked->quantity
                ) {
                    throw new RuntimeException('License order is already fulfilled by a different payment.');
                }

                return $locked;
            }

            if (! in_array($locked->status, ['pending', 'awaiting_payment'], true) || $locked->keys()->exists()) {
                throw new RuntimeException('Order cannot be fulfilled in its current state.');
            }

            $prefix = strtoupper((string) config('subbase.licensing.key_prefix', 'LIC'));
            if (! preg_match('/^[A-Z0-9]{2,8}$/D', $prefix)) {
                throw new RuntimeException('License key prefix must be 2–8 alphanumeric characters.');
            }

            for ($i = 0; $i < (int) $locked->quantity; $i++) {
                $hex = strtoupper(bin2hex(random_bytes(16)));
                $code = $prefix.'-'.implode('-', str_split($hex, 8));

                LicenseKey::query()->create([
                    'order_id' => $locked->getKey(),
                    'offer_id' => $locked->offer_id,
                    'provider_id' => $locked->provider_id,
                    'code_hash' => hash('sha256', $code),
                    'code_ciphertext' => Crypt::encryptString($code),
                    'code_tail' => substr($hex, -8),
                    'status' => 'available',
                ]);
            }

            $locked->update([
                'status' => 'fulfilled',
                'payment_id' => $paymentId,
                'fulfilled_at' => now(),
            ]);

            return $locked;
        }, 3);
    }
}
