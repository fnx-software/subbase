<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseProduct extends Model
{
    protected $fillable = ['sku', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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
