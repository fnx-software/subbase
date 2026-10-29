<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseKey extends Model
{
    protected $fillable = [
        'order_id', 'offer_id', 'provider_id', 'code_hash',
        'code_ciphertext', 'code_tail', 'status', 'redeemed_by_type',
        'redeemed_by_id', 'subscription_id', 'redeemed_at',
    ];

    protected $hidden = ['code_hash', 'code_ciphertext'];

    protected function casts(): array
    {
        return ['redeemed_at' => 'datetime'];
    }

    public function getTable(): string
    {
        return config('subbase.licensing.tables.keys', 'subbase_license_keys');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LicenseOrder::class, 'order_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(LicenseOffer::class, 'offer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LicenseProvider::class, 'provider_id');
    }
}
