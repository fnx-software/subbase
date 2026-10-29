<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Authorization;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Nafiswatsiq\Subbase\Models\Plan;
use Nafiswatsiq\Subbase\Support\SubbasePermission;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class SubbasePermissionTest extends TestCase
{
    public function test_authenticated_user_without_any_permission_is_denied(): void
    {
        $actor = new User();

        $this->assertFalse(SubbasePermission::allows(null, 'viewAny', Plan::class, $actor));
    }

    public function test_an_explicit_native_laravel_gate_can_authorize_the_actor(): void
    {
        Gate::define('ViewAny:Plan', fn (User $user): bool => true);

        $this->assertTrue(SubbasePermission::allows(null, 'viewAny', Plan::class, new User()));
    }

    public function test_a_configured_permission_is_used_without_requiring_spatie(): void
    {
        Gate::define('manage billing plans', fn (User $user): bool => true);

        $this->assertTrue(
            SubbasePermission::allows('manage billing plans', 'viewAny', Plan::class, new User()),
        );
    }

    public function test_no_context_is_never_implicitly_authorized(): void
    {
        $this->assertFalse(SubbasePermission::allows(null, actor: new User()));
    }

    public function test_different_actor_is_not_implicitly_authorized(): void
    {
        Gate::define('ViewAny:Plan', fn (User $user): bool => $user->name === 'allowed');

        $user = new User();
        $user->name = 'other';

        $this->assertFalse(SubbasePermission::allows(null, 'viewAny', Plan::class, $user));
    }
}
