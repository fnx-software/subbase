<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Deny by default. Authentication does not imply permission to manage billing.
 *
 * Works with native Laravel Gate, Spatie Permission, Shield, or application
 * Gate::before() logic. Spatie is optional and never disables authorization.
 */
final class SubbasePermission
{
    public static function allows(
        ?string $permission,
        ?string $ability = null,
        ?string $subject = null,
        ?Authenticatable $actor = null,
    ): bool {
        $actor ??= Auth::user();

        if (! $actor instanceof Authenticatable) {
            return false;
        }

        $permission = trim((string) $permission);

        if ($permission === '') {
            if (blank($ability) || blank($subject)) {
                return false;
            }

            $permission = Str::studly($ability).':'.class_basename($subject);
        }

        return Gate::forUser($actor)->allows($permission);
    }
}
