<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Policies;

use Illuminate\Contracts\Auth\Authenticatable as AuthUser;
use Nafiswatsiq\Subbase\Models\Plan;
use Nafiswatsiq\Subbase\Support\SubbasePermission;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'viewAny', Plan::class, $authUser);
    }

    public function view(AuthUser $authUser, Plan $plan): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'view', Plan::class, $authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'create', Plan::class, $authUser);
    }

    public function update(AuthUser $authUser, Plan $plan): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'update', Plan::class, $authUser);
    }

    public function delete(AuthUser $authUser, Plan $plan): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'delete', Plan::class, $authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'deleteAny', Plan::class, $authUser);
    }

    public function restore(AuthUser $authUser, Plan $plan): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'restore', Plan::class, $authUser);
    }

    public function forceDelete(AuthUser $authUser, Plan $plan): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'forceDelete', Plan::class, $authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'forceDeleteAny', Plan::class, $authUser);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'restoreAny', Plan::class, $authUser);
    }

    public function replicate(AuthUser $authUser, Plan $plan): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'replicate', Plan::class, $authUser);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.plan'), 'reorder', Plan::class, $authUser);
    }
}