<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseOffer extends Model
{
    protected $fillable = [
        'product_id', 'plan_id', 'sku', 'currency', 'retail_price',
        'wholesale_price', 'wholesale_minimum', 'term_count', 'term_unit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'retail_price' => 'decimal:4',
            'wholesale_price' => 'decimal:4',
            'wholesale_minimum' => 'integer',
            'term_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('subbase.licensing.tables.offers', 'subbase_license_offers');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(LicenseProduct::class, 'product_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('subbase.models.plan', Plan::class), 'plan_id');
    }
}
