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

        Schema::create($tables['orders'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('offer_id')->constrained($tables['offers'])->restrictOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained($tables['providers'])->restrictOnDelete();
            $table->string('purchaser_type');
            $table->string('purchaser_id', 191);
            $table->string('channel', 20);
            $table->unsignedSmallInteger('quantity');
            $table->char('currency', 3);
            $table->decimal('retail_reference_price', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('total_amount', 20, 4);
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->unsignedBigInteger('payment_id')->nullable()->unique();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'status', 'created_at'], 'sb_license_order_provider_status');
            $table->index(['purchaser_type', 'purchaser_id'], 'sb_license_order_purchaser');
        });

        Schema::create($tables['keys'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('order_id')->constrained($tables['orders'])->restrictOnDelete();
            $table->foreignId('offer_id')->constrained($tables['offers'])->restrictOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained($tables['providers'])->restrictOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->text('code_ciphertext')->nullable();
            $table->string('code_tail', 8);
            $table->string('status', 20)->default('available');
            $table->string('redeemed_by_type')->nullable();
            $table->string('redeemed_by_id', 191)->nullable();
            $table->string('subscription_id', 191)->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'status', 'created_at'], 'sb_license_key_inventory');
            $table->index(['redeemed_by_type', 'redeemed_by_id'], 'sb_license_key_redeemer');
        });

        Schema::create($tables['sales'], function (Blueprint $table) use ($tables): void {
            $table->id();
            $table->foreignId('key_id')->unique()->constrained($tables['keys'])->restrictOnDelete();
            $table->foreignId('provider_id')->constrained($tables['providers'])->restrictOnDelete();
            $table->decimal('sale_price', 20, 4);
            $table->char('currency', 3);
            $table->string('external_reference', 120)->nullable();
            $table->timestamp('sold_at');
            $table->timestamps();
            $table->index(['provider_id', 'sold_at'], 'sb_license_reseller_sales');
        });
    }

    public function down(): void
    {
        $tables = config('subbase.licensing.tables');
        Schema::dropIfExists($tables['sales']);
        Schema::dropIfExists($tables['keys']);
        Schema::dropIfExists($tables['orders']);
    }
};
