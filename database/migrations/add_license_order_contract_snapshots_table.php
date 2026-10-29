<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('subbase.licensing.tables');
        Schema::table($tables['orders'], static function (Blueprint $table): void {
            $table->unsignedBigInteger('contract_plan_id')->nullable();
            $table->string('contract_product_sku', 80)->nullable();
            $table->unsignedSmallInteger('contract_term_count')->nullable();
            $table->string('contract_term_unit', 10)->nullable();
        });

        // Earlier prototype orders never included a frozen license term.
        // Backfill a best-effort snapshot from their CURRENT catalog values;
        // immutable historical terms cannot be reconstructed after edits.
        DB::table($tables['orders'])->orderBy('id')->chunkById(100, function ($orders) use ($tables): void {
            foreach ($orders as $order) {
                $offer = DB::table($tables['offers'])->where('id', $order->offer_id)->first();
                $product = $offer
                    ? DB::table($tables['products'])->where('id', $offer->product_id)->first()
                    : null;

                if (! $offer || ! $product) {
                    continue;
                }

                DB::table($tables['orders'])->where('id', $order->id)->update([
                    'contract_plan_id' => $offer->plan_id,
                    'contract_product_sku' => $product->sku,
                    'contract_term_count' => $offer->term_count,
                    'contract_term_unit' => $offer->term_unit,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Deleting purchased terms would destroy contractual entitlements.
        // A production rollback requires a reviewed data-safe migration.
    }
};
