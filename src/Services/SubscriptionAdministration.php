<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Laravelcm\Subscriptions\Models\Subscription;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;

/**
 * Tenant-safe cancellation of a named subscription.
 *
 * A paid subscription is not automatically refunded. The accompanying
 * payment module owns verified settlement and any provider refund.
 */
final class SubscriptionAdministration
{
    public function cancel(
        Model $billable,
        Authenticatable $actor,
        string $subscriptionName,
        bool $immediately = false,
    ): Subscription {
        if (! $billable->exists || ! method_exists($billable, 'planSubscriptions')) {
            throw new InvalidArgumentException('Subscription owner must be a saved, subscribable model.');
        }

        if (! preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $subscriptionName)) {
            throw new InvalidArgumentException('Subscription name is invalid.');
        }

        $ability = (string) config('subbase.permissions.manage_subscription', 'manageSubscription');

        if (! SubbaseTenancy::allows($billable, 'cancel', $actor)
            || ! Gate::forUser($actor)->allows($ability, $billable)
        ) {
            throw new AuthorizationException('You cannot manage this subscription.');
        }

        return DB::transaction(function () use ($billable, $subscriptionName, $immediately): Subscription {
            /** @var Subscription $subscription */
            $subscription = $billable->planSubscriptions()
                ->where('slug', $subscriptionName)
                ->lockForUpdate()
                ->firstOrFail();

            // Do not inadvertently extend a period or create new charges.
            // Repeated cancellation is idempotent for the same mode.
            if (! $subscription->canceled() || ($immediately && ! $subscription->ended())) {
                $subscription->cancel($immediately);
            }

            return $subscription->refresh();
        });
    }
}
