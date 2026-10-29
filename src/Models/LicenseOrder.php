<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LicenseOrder extends Model
{
    protected $fillable = [
        'public_id', 'offer_id', 'provider_id', 'purchaser_type', 'purchaser_id',
        'channel', 'quantity', 'currency', 'retail_reference_price', 'unit_price',
        'total_amount', 'status', 'idempotency_key', 'payment_id', 'fulfilled_at',
        'contract_plan_id', 'contract_product_sku', 'contract_term_count', 'contract_term_unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'contract_term_count' => 'integer',
            'unit_price' => 'decimal:4',
            'retail_reference_price' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function getTable(): string
    {
        return config('subbase.licensing.tables.orders', 'subbase_license_orders');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(LicenseOffer::class, 'offer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LicenseProvider::class, 'provider_id');
    }

    public function purchaser(): MorphTo
    {
        return $this->morphTo('purchaser', 'purchaser_type', 'purchaser_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(LicenseKey::class, 'order_id');
    }
}
