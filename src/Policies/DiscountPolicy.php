<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Policies;

use Illuminate\Contracts\Auth\Authenticatable as AuthUser;
use Nafiswatsiq\Subbase\Models\Discount;
use Nafiswatsiq\Subbase\Support\SubbasePermission;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'viewAny', Discount::class, $authUser);
    }

    public function view(AuthUser $authUser, Discount $discount): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'view', Discount::class, $authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'create', Discount::class, $authUser);
    }

    public function update(AuthUser $authUser, Discount $discount): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'update', Discount::class, $authUser);
    }

    public function delete(AuthUser $authUser, Discount $discount): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'delete', Discount::class, $authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'deleteAny', Discount::class, $authUser);
    }

    public function restore(AuthUser $authUser, Discount $discount): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'restore', Discount::class, $authUser);
    }

    public function forceDelete(AuthUser $authUser, Discount $discount): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'forceDelete', Discount::class, $authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'forceDeleteAny', Discount::class, $authUser);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'restoreAny', Discount::class, $authUser);
    }

    public function replicate(AuthUser $authUser, Discount $discount): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'replicate', Discount::class, $authUser);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return SubbasePermission::allows(config('subbase.permissions.discount'), 'reorder', Discount::class, $authUser);
    }
}