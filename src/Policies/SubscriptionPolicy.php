<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Policies;

use Illuminate\Contracts\Auth\Authenticatable as AuthUser;
use Nafiswatsiq\Subbase\Models\Subscription;
use Nafiswatsiq\Subbase\Support\SubbasePermission;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'viewAny', Subscription::class, $authUser);
    }

    public function view(AuthUser $authUser, Subscription $subscription): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'view', Subscription::class, $authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'create', Subscription::class, $authUser);
    }

    public function update(AuthUser $authUser, Subscription $subscription): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'update', Subscription::class, $authUser);
    }

    public function delete(AuthUser $authUser, Subscription $subscription): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'delete', Subscription::class, $authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'deleteAny', Subscription::class, $authUser);
    }

    public function restore(AuthUser $authUser, Subscription $subscription): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'restore', Subscription::class, $authUser);
    }

    public function forceDelete(AuthUser $authUser, Subscription $subscription): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'forceDelete', Subscription::class, $authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'forceDeleteAny', Subscription::class, $authUser);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'restoreAny', Subscription::class, $authUser);
    }

    public function replicate(AuthUser $authUser, Subscription $subscription): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'replicate', Subscription::class, $authUser);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.subscription'), 'reorder', Subscription::class, $authUser);
    }
}