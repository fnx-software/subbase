<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class LicenseProduct extends Model
{
    protected $fillable = ['sku', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            if (! preg_match('/^[a-z0-9][a-z0-9-]{0,65}$/D', (string) $product->sku)
                || trim((string) $product->name) === ''
            ) {
                throw new InvalidArgumentException('A license product requires a valid SKU and name.');
            }
        });
    }

    public function getTable(): string
    {
        return config('subbase.licensing.tables.products', 'subbase_license_products');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(LicenseOffer::class, 'product_id');
    }
}
