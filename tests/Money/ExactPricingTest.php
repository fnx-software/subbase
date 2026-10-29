<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Money;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nafiswatsiq\Subbase\Helpers\PlanPriceHelper;
use Nafiswatsiq\Subbase\Models\Plan;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class ExactPricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'laravel-subscriptions.tables.plans' => 'sb_pricing_plans',
            'subbase.tables.discounts' => 'discounts',
        ]);

        Schema::create('sb_pricing_plans', static function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->text('name');
            $table->text('description')->nullable();
            $table->decimal('price', 20, 4);
            $table->char('currency', 3);
            $table->json('prices');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('discounts', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->decimal('value', 20, 4);
            $table->decimal('min_amount', 20, 4)->nullable();
            $table->char('currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('discount_plan', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('discount_id');
        });

        DB::table('sb_pricing_plans')->insert([
            'id' => 1,
            'name' => json_encode(['en' => 'Plan'], JSON_THROW_ON_ERROR),
            'slug' => 'plan',
            'price' => '1.235',
            'currency' => 'KWD',
            'prices' => json_encode(['KWD' => '1.235', 'USD' => '0.30'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('discounts')->insert([
            'id' => 1,
            'name' => 'Ten percent',
            'code' => 'TEN',
            'type' => 'percentage',
            'value' => '10',
            'currency' => 'KWD',
            'is_active' => true,
            'priority' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('discount_plan')->insert(['plan_id' => 1, 'discount_id' => 1]);
    }

    public function test_exact_quote_applies_priority_discount_without_float(): void
    {
        $plan = Plan::query()->findOrFail(1);
        $quote = PlanPriceHelper::resolveExactWithDiscounts($plan, 'KWD');

        $this->assertSame('1.235', $quote['original_amount']);
        $this->assertSame('0.124', $quote['discount_amount']);
        $this->assertSame('1.111', $quote['final_amount']);
        $this->assertSame('KWD', $quote['currency']);
    }

    public function test_discount_only_applies_to_matching_currency(): void
    {
        $plan = Plan::query()->findOrFail(1);
        $quote = PlanPriceHelper::resolveExactWithDiscounts($plan, 'USD');

        $this->assertSame('0.30', $quote['original_amount']);
        $this->assertSame('0.00', $quote['discount_amount']);
        $this->assertSame('0.30', $quote['final_amount']);
    }
}
