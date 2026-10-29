<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseSale extends Model
{
    protected $fillable = [
        'key_id', 'provider_id', 'sale_price', 'currency', 'external_reference', 'sold_at',
    ];

    protected function casts(): array
    {
        return ['sale_price' => 'decimal:4', 'sold_at' => 'datetime'];
    }

    public function getTable(): string
    {
        return config('subbase.licensing.tables.sales', 'subbase_license_sales');
    }

    public function key(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'key_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LicenseProvider::class, 'provider_id');
    }
}
