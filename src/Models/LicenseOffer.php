<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use Nafiswatsiq\Subbase\Support\Money;

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

    protected static function booted(): void
    {
        static::saving(function (self $offer): void {
            $currency = strtoupper(trim((string) $offer->currency));
            $retail = new Money((string) $offer->retail_price, $currency);
            $wholesale = new Money((string) $offer->wholesale_price, $currency);

            if ($retail->isZero() || $wholesale->isZero()
                || bccomp($retail->amount(), $wholesale->amount(), 4) < 0
                || (int) $offer->wholesale_minimum < 2
                || (int) $offer->term_count < 1
                || (int) $offer->term_count > 120
                || ! in_array($offer->term_unit, ['day', 'month', 'year'], true)
                || ! preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/D', (string) $offer->sku)
            ) {
                throw new InvalidArgumentException('Invalid license offer: check SKU, prices, wholesale MOQ and term.');
            }

            $offer->currency = $currency;
            $offer->retail_price = $retail->amount();
            $offer->wholesale_price = $wholesale->amount();
        });
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
