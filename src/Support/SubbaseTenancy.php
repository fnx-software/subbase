<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Nafiswatsiq\Subbase\Contracts\TenantAccess;

final class SubbaseTenancy
{
    public static function enabled(): bool
    {
        return (bool) config('subbase.tenancy.enabled', false);
    }

    public static function scope(Builder $query): Builder
    {
        if (! self::enabled()) {
            return $query;
        }

        $actor = Auth::user();
        $resolver = self::resolver();
        if (! $actor instanceof Authenticatable || ! $resolver) {
            return $query->whereRaw('1 = 0');
        }

        return $resolver->scope($query, $actor);
    }

    public static function allows(?Model $record, string $ability, Authenticatable $actor): bool
    {
        if (! self::enabled()) {
            return true;
        }

        $resolver = self::resolver();

        return $resolver !== null && $resolver->allows($record, $ability, $actor);
    }

    private static function resolver(): ?TenantAccess
    {
        $class = config('subbase.tenancy.access');
        if (! is_string($class) || ! is_a($class, TenantAccess::class, true)) {
            return null;
        }

        $service = app($class);

        return $service instanceof TenantAccess ? $service : null;
    }
}
