<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Subscriptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravelcm\Subscriptions\Traits\HasPlanSubscriptions;
use Nafiswatsiq\Subbase\Models\Subscription;
use Nafiswatsiq\Subbase\Services\SubscriptionAdministration;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class BillingOrganizationFixture extends Model
{
    use HasPlanSubscriptions;

    protected $table = 'sb_billing_organizations';
}

final class SubscriptionAdministrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sb_billing_organizations', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sb_billing_subscriptions', static function (Blueprint $table): void {
            $table->id();
            $table->string('subscriber_type');
            $table->unsignedBigInteger('subscriber_id');
            $table->string('slug');
            $table->unsignedBigInteger('plan_id');
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        config([
            'laravel-subscriptions.models.subscription' => Subscription::class,
            'laravel-subscriptions.tables.subscriptions' => 'sb_billing_subscriptions',
            'subbase.tenancy.enabled' => false,
            'subbase.permissions.manage_subscription' => 'manageSubscription',
        ]);

        DB::table('sb_billing_organizations')->insert([
            ['id' => 1, 'name' => 'Owner 1', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Owner 2', 'created_at' => now(), 'updated_at' => now()],
        ]);

        foreach ([1, 2] as $id) {
            DB::table('sb_billing_subscriptions')->insert([
                'subscriber_type' => BillingOrganizationFixture::class,
                'subscriber_id' => $id,
                'slug' => 'default',
                'plan_id' => 1,
                'name' => json_encode(['en' => 'Subscription'], JSON_THROW_ON_ERROR),
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Gate::define('manageSubscription', fn (User $actor, Model $billable): bool => $actor->name === 'owner' && $billable->getKey() === 1);
    }

    public function test_authorized_owner_can_cancel_at_end_without_affecting_other_tenant(): void
    {
        $actor = new User;
        $actor->name = 'owner';
        $org = BillingOrganizationFixture::findOrFail(1);

        $subscription = (new SubscriptionAdministration)->cancel($org, $actor, 'default');

        $this->assertNotNull($subscription->canceled_at);
        $this->assertTrue($subscription->ends_at->isFuture());
        $this->assertNull(DB::table('sb_billing_subscriptions')->where('subscriber_id', 2)->value('canceled_at'));
    }

    public function test_access_to_another_organization_is_denied(): void
    {
        $actor = new User;
        $actor->name = 'owner';

        $this->expectException(AuthorizationException::class);
        (new SubscriptionAdministration)->cancel(BillingOrganizationFixture::findOrFail(2), $actor, 'default');
    }

    public function test_no_gate_grant_does_not_cancel_a_subscription(): void
    {
        $actor = new User;
        $actor->name = 'visitor';

        $this->expectException(AuthorizationException::class);
        (new SubscriptionAdministration)->cancel(BillingOrganizationFixture::findOrFail(1), $actor, 'default');
    }

    public function test_tenant_mode_without_resolver_denies_even_an_allowed_actor(): void
    {
        config(['subbase.tenancy.enabled' => true, 'subbase.tenancy.access' => null]);

        $actor = new User;
        $actor->name = 'owner';

        $this->expectException(AuthorizationException::class);
        (new SubscriptionAdministration)->cancel(BillingOrganizationFixture::findOrFail(1), $actor, 'default');
    }
}
