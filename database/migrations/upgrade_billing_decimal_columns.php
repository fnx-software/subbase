<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retain already-published 2-decimal migrations; increase precision via a
     * forward-only safe widening migration for 3/4-decimal currencies.
     */
    public function up(): void
    {
        Schema::table(config('laravel-subscriptions.tables.plans', 'plans'), function (Blueprint $table): void {
            $table->decimal('price', 20, 4)->change();
            $table->decimal('signup_fee', 20, 4)->change();
        });

        Schema::table(config('subbase.tables.discounts', 'discounts'), function (Blueprint $table): void {
            $table->decimal('value', 20, 4)->change();
            $table->decimal('min_amount', 20, 4)->nullable()->change();
        });

        Schema::table('discount_subscription', function (Blueprint $table): void {
            $table->decimal('discounted_amount', 20, 4)->nullable()->change();
            $table->decimal('original_amount', 20, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Decreasing precision would silently destroy 3/4-decimal amounts.
        // Roll back via an explicitly reviewed data-safe migration.
    }
};
