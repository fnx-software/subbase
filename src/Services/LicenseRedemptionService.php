<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Models\LicenseKey;
use Nafiswatsiq\Subbase\Models\Plan;
use RuntimeException;

final class LicenseRedemptionService
{
    /**
     * Activation date is the start of the license term, not the reseller
     * purchase date. The same code cannot ever activate a second subscriber.
     */
    public function redeem(string $code, Model $billable, Authenticatable $actor): LicenseKey
    {
        if (! $billable->exists || $billable->getKey() === null
            || ! method_exists($billable, 'planSubscriptions')
            || ! method_exists($billable, 'newPlanSubscription')
        ) {
            throw new InvalidArgumentException('Activation requires a saved, subscribable account.');
        }

        if (! Gate::forUser($actor)->allows('redeemLicenseKey', $billable)) {
            throw new AuthorizationException('Cannot activate a license for this account.');
        }

        $code = strtoupper(trim($code));
        if (! preg_match('/^[A-Z0-9]{2,8}-(?:[A-F0-9]{8}-){3}[A-F0-9]{8}$/D', $code)) {
            throw new InvalidArgumentException('Invalid or already redeemed license key.');
        }

        return DB::transaction(function () use ($code, $billable): LicenseKey {
            $key = LicenseKey::query()
                ->where('code_hash', hash('sha256', $code))
                ->lockForUpdate()
                ->first();

            if (! $key || $key->status !== 'available') {
                throw new InvalidArgumentException('Invalid or already redeemed license key.');
            }

            $order = $key->order()->lockForUpdate()->firstOrFail();
            if ($order->status !== 'fulfilled') {
                throw new RuntimeException('This license has not been paid for.');
            }

            $offer = $key->offer()->firstOrFail();
            $product = $offer->product()->firstOrFail();
            if (! preg_match('/^[a-z0-9][a-z0-9-]{0,65}$/D', (string) $product->sku)) {
                throw new RuntimeException('License product SKU is invalid.');
            }

            $planClass = config('subbase.models.plan', Plan::class);
            $plan = $planClass::query()->findOrFail($offer->plan_id);

            $subscriptionName = 'license-'.$product->sku;
            $subscription = $billable->planSubscriptions()
                ->where('slug', $subscriptionName)
                ->lockForUpdate()
                ->first();

            if ($subscription && (string) $subscription->plan_id !== (string) $plan->getKey()) {
                throw new RuntimeException('This product already has a different subscription plan.');
            }

            $start = now();
            if ($subscription && $subscription->ends_at && $subscription->ends_at->isFuture()) {
                $start = $subscription->ends_at->copy();
            }

            $end = match ($offer->term_unit) {
                'day' => $start->copy()->addDays((int) $offer->term_count),
                'month' => $start->copy()->addMonthsNoOverflow((int) $offer->term_count),
                'year' => $start->copy()->addYearsNoOverflow((int) $offer->term_count),
                default => throw new RuntimeException('Unsupported license term.'),
            };

            if (! $subscription) {
                $subscription = $billable->newPlanSubscription($subscriptionName, $plan);
            }

            $subscription->forceFill([
                'starts_at' => $subscription->starts_at && $subscription->starts_at->isPast()
                    ? $subscription->starts_at : now(),
                'ends_at' => $end,
                'trial_ends_at' => null,
                'canceled_at' => null,
            ])->save();

            $key->update([
                'status' => 'redeemed',
                'code_ciphertext' => null,
                'redeemed_by_type' => $billable->getMorphClass(),
                'redeemed_by_id' => (string) $billable->getKey(),
                'subscription_id' => (string) $subscription->getKey(),
                'redeemed_at' => now(),
            ]);

            return $key;
        }, 3);
    }
}
