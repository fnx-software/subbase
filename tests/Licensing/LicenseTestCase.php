<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Licensing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravelcm\Subscriptions\Traits\HasPlanSubscriptions;
use Nafiswatsiq\Subbase\Models\LicenseOffer;
use Nafiswatsiq\Subbase\Models\LicenseProduct;
use Nafiswatsiq\Subbase\Models\LicenseProvider;
use Nafiswatsiq\Subbase\Models\Plan;
use Nafiswatsiq\Subbase\Models\Subscription;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class LicenseTestOrganization extends Model
{
    use HasPlanSubscriptions;

    protected $table = 'sb_lic_test_organizations';

    protected $guarded = [];
}

abstract class LicenseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'laravel-subscriptions.tables.plans' => 'sb_lic_test_plans',
            'laravel-subscriptions.tables.subscriptions' => 'sb_lic_test_subscriptions',
            'laravel-subscriptions.models.plan' => Plan::class,
            'laravel-subscriptions.models.subscription' => Subscription::class,
            'subbase.models.plan' => Plan::class,
            'subbase.licensing.key_prefix' => 'LIC',
            'subbase.licensing.reseller_max_retail_price' => true,
        ]);

        Schema::create('sb_lic_test_organizations', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sb_lic_test_plans', static function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->text('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 20, 4)->default(0);
            $table->char('currency', 3)->default('USD');
            $table->unsignedSmallInteger('invoice_period')->default(1);
            $table->string('invoice_interval')->default('year');
            $table->unsignedSmallInteger('trial_period')->default(0);
            $table->string('trial_interval')->default('day');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sb_lic_test_subscriptions', static function (Blueprint $table): void {
            $table->id();
            $table->string('subscriber_type');
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('slug');
            $table->text('name');
            $table->text('description')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ([
            'create_license_catalog_and_providers_table',
            'create_license_orders_and_keys_table',
        ] as $name) {
            $migration = require dirname(__DIR__, 2).'/database/migrations/'.$name.'.php';
            $migration->up();
        }

        DB::table('sb_lic_test_organizations')->insert([
            ['id' => 1, 'name' => 'Reseller shop', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Customer business', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Second customer', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('sb_lic_test_plans')->insert([
            'id' => 11,
            'slug' => 'crm-individual',
            'name' => json_encode(['en' => 'CRM Individual'], JSON_THROW_ON_ERROR),
            'description' => json_encode(['en' => 'Yearly CRM'], JSON_THROW_ON_ERROR),
            'price' => '150.00',
            'currency' => 'USD',
            'invoice_period' => 1,
            'invoice_interval' => 'year',
            'trial_period' => 0,
            'trial_interval' => 'day',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Gate::define('applyForLicenseProvider', static fn (User $actor, Model $owner): bool =>
            $actor->name === 'merchant' && (int) $owner->getKey() === 1);
        Gate::define('manageLicenseProviders', static fn (User $actor): bool => $actor->name === 'admin');
        Gate::define('purchaseWholesaleLicenses', static fn (User $actor, LicenseProvider $provider): bool =>
            $actor->name === 'merchant' && (int) $provider->owner_id === 1);
        Gate::define('purchaseRetailLicense', static fn (User $actor, Model $purchaser): bool =>
            $actor->name === 'customer' && (int) $purchaser->getKey() === 2);
        Gate::define('viewPurchasedLicenseKeys', static fn (User $actor, \Nafiswatsiq\Subbase\Models\LicenseOrder $order): bool =>
            $actor->name === 'merchant' && (int) $order->provider_id === (int) LicenseProvider::query()->value('id'));
        Gate::define('redeemLicenseKey', static fn (User $actor, Model $billable): bool =>
            $actor->name === 'customer' && (int) $billable->getKey() === 2);
        Gate::define('recordLicenseResale', static fn (User $actor, LicenseProvider $provider): bool =>
            $actor->name === 'merchant' && (int) $provider->owner_id === 1);

        config(['app.key' => 'base64:'.base64_encode(str_repeat('z', 32))]);
    }

    protected function offer(): LicenseOffer
    {
        $product = LicenseProduct::query()->firstOrCreate(
            ['sku' => 'crm'],
            ['name' => 'Fenix CRM', 'is_active' => true],
        );

        return LicenseOffer::query()->firstOrCreate(
            ['sku' => 'crm-individual-year-usd'],
            [
                'product_id' => $product->getKey(),
                'plan_id' => 11,
                'currency' => 'USD',
                'retail_price' => '150.00',
                'wholesale_price' => '100.00',
                'wholesale_minimum' => 12,
                'term_count' => 1,
                'term_unit' => 'year',
                'is_active' => true,
            ],
        );
    }

    protected function reseller(): LicenseProvider
    {
        $merchant = $this->actor('merchant');
        $org = LicenseTestOrganization::query()->findOrFail(1);
        $service = app(\Nafiswatsiq\Subbase\Services\LicenseProviderService::class);
        $provider = $service->apply($org, $merchant, 'Electronics Store');

        return $service->approve($provider, $this->actor('admin'));
    }

    protected function actor(string $name): User
    {
        $user = new User;
        $user->name = $name;

        return $user;
    }

    protected function organization(int $id): LicenseTestOrganization
    {
        return LicenseTestOrganization::query()->findOrFail($id);
    }
}
