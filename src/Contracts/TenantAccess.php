<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Host-owned isolation contract. Can handle global products, scoped plans
 * and subscriptions differently without requiring a tenancy library.
 */
interface TenantAccess
{
    public function scope(Builder $query, Authenticatable $actor): Builder;

    public function allows(?Model $record, string $ability, Authenticatable $actor): bool;
}
