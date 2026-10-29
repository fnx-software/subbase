<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Tenancy;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Nafiswatsiq\Subbase\Contracts\TenantAccess;
use Nafiswatsiq\Subbase\Support\SubbaseTenancy;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class TenantRecord extends Model
{
    protected $table = 'tenant_records';
}

final class FakeTenantAccess implements TenantAccess
{
    public function scope(Builder $query, Authenticatable $actor): Builder
    {
        return $query->where('tenant_id', 5);
    }

    public function allows(?Model $record, string $ability, Authenticatable $actor): bool
    {
        return $record === null || (int) $record->getAttribute('tenant_id') === 5;
    }
}

final class SubbaseTenancyTest extends TestCase
{
    public function test_multitenant_mode_without_resolver_denies_all_queries(): void
    {
        config(['subbase.tenancy.enabled' => true, 'subbase.tenancy.access' => null]);

        $this->assertFalse(SubbaseTenancy::allows(new TenantRecord, 'view', new User));
        $this->assertStringContainsString('1 = 0', SubbaseTenancy::scope(TenantRecord::query())->toSql());
    }

    public function test_tenant_resolver_scopes_queries_and_record_access(): void
    {
        config(['subbase.tenancy.enabled' => true, 'subbase.tenancy.access' => FakeTenantAccess::class]);

        Auth::setUser(new User);

        $this->assertStringContainsString('"tenant_id" = ?', SubbaseTenancy::scope(TenantRecord::query())->toSql());
        $allowed = new TenantRecord;
        $allowed->tenant_id = 5;
        $denied = new TenantRecord;
        $denied->tenant_id = 6;

        $this->assertTrue(SubbaseTenancy::allows($allowed, 'view', new User));
        $this->assertFalse(SubbaseTenancy::allows($denied, 'view', new User));
    }

    public function test_standalone_mode_does_not_require_a_tenant_resolver(): void
    {
        config(['subbase.tenancy.enabled' => false, 'subbase.tenancy.access' => null]);

        $this->assertTrue(SubbaseTenancy::allows(new TenantRecord, 'view', new User));
    }
}
