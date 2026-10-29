<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('subbase.licensing.tables');

        Schema::create($tables['products'], function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 80)->unique();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create($tables['offers'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('product_id')->constrained($tables['products'])->restrictOnDelete();
            $table->unsignedBigInteger('plan_id')->index();
            $table->string('sku', 120)->unique();
            $table->char('currency', 3);
            $table->decimal('retail_price', 20, 4);
            $table->decimal('wholesale_price', 20, 4);
            $table->unsignedSmallInteger('wholesale_minimum')->default(12);
            $table->unsignedSmallInteger('term_count')->default(1);
            $table->string('term_unit', 10)->default('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'currency', 'is_active'], 'sb_license_offer_lookup');
        });

        Schema::create($tables['providers'], function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type');
            $table->string('owner_id', 191);
            $table->string('business_name', 150);
            $table->string('status', 20)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id'], 'sb_license_provider_owner_unique');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        $tables = config('subbase.licensing.tables');
        Schema::dropIfExists($tables['providers']);
        Schema::dropIfExists($tables['offers']);
        Schema::dropIfExists($tables['products']);
    }
};
