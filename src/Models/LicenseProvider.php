<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LicenseProvider extends Model
{
    protected $fillable = ['owner_type', 'owner_id', 'business_name', 'status', 'approved_at', 'suspended_at'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'suspended_at' => 'datetime'];
    }

    public function getTable(): string
    {
        return config('subbase.licensing.tables.providers', 'subbase_license_providers');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo('owner');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(LicenseOrder::class, 'provider_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(LicenseKey::class, 'provider_id');
    }

    public function approved(): bool
    {
        return $this->status === 'approved' && $this->approved_at !== null;
    }
}
