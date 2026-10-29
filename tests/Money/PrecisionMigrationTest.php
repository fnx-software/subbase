<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Money;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class PrecisionMigrationTest extends TestCase
{
    public function test_existing_price_tables_are_widened_without_destroying_values(): void
    {
        config([
            'laravel-subscriptions.tables.plans' => 'sb_test_plans_precision',
            'subbase.tables.discounts' => 'sb_test_discounts_precision',
        ]);

        Schema::create('sb_test_plans_precision', static function (Blueprint $table): void {
            $table->id();
            $table->decimal('price', 18, 2);
            $table->decimal('signup_fee', 18, 2);
        });
        Schema::create('sb_test_discounts_precision', static function (Blueprint $table): void {
            $table->id();
            $table->decimal('value', 15, 2);
            $table->decimal('min_amount', 15, 2)->nullable();
        });
        Schema::create('discount_subscription', static function (Blueprint $table): void {
            $table->id();
            $table->decimal('discounted_amount', 15, 2)->nullable();
            $table->decimal('original_amount', 15, 2)->nullable();
        });

        DB::table('sb_test_plans_precision')->insert([
            'id' => 1, 'price' => '12.50', 'signup_fee' => '2.00',
        ]);

        $migration = require dirname(__DIR__, 2).'/database/migrations/upgrade_billing_decimal_columns.php';
        $migration->up();

        $this->assertSame(0, bccomp((string) DB::table('sb_test_plans_precision')->value('price'), '12.50', 4));

        DB::table('sb_test_plans_precision')->where('id', 1)->update([
            'price' => '1.2345',
            'signup_fee' => '0.001',
        ]);

        $this->assertSame(0, bccomp((string) DB::table('sb_test_plans_precision')->value('price'), '1.2345', 4));
        $this->assertSame(0, bccomp((string) DB::table('sb_test_plans_precision')->value('signup_fee'), '0.001', 4));
    }
}
