<?php

namespace Nafiswatsiq\Subbase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nafiswatsiq\Subbase\Models\Feature;
use Nafiswatsiq\Subbase\Models\Plan;
use Nafiswatsiq\Subbase\Models\Subscription;

class Discount extends Model
{
    use SoftDeletes;

    protected $table = 'discounts';

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'currency',
        'min_amount',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'applies_to',
        'applicable_plans',
        'applicable_features',
        'priority',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'min_amount' => 'decimal:4',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applies_to' => 'array',
        'applicable_plans' => 'array',
        'applicable_features' => 'array',
        'used_count' => 'integer',
        'max_uses' => 'integer',
    ];

    /**
     * The plans that this discount applies to.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    /**
     * The features that this discount applies to.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class);
    }

    /**
     * The subscriptions that have used this discount.
     */
    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Subscription::class)
            ->withPivot(['discounted_amount', 'original_amount', 'currency', 'applied_at']);
    }

    public function isValid(): bool
    {
        $now = now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function calculateDiscount(float $amount, string $currency): float
    {
        if (! $this->isValid()) {
            return $amount;
        }

        if ($this->currency && $this->currency !== strtoupper($currency)) {
            return $amount;
        }

        if ($this->min_amount && $amount < $this->min_amount) {
            return $amount;
        }

        if ($this->type === 'percentage') {
            return $amount - ($amount * ($this->value / 100));
        } elseif ($this->type === 'fixed') {
            return max(0, $amount - $this->value);
        }

        return $amount;
    }

    public function appliesToPlan(int $planId): bool
    {
        if (empty($this->applies_to) || ! in_array('plans', $this->applies_to)) {
            return false;
        }

        if (empty($this->applicable_plans)) {
            return true;
        }

        return in_array($planId, $this->applicable_plans);
    }

    public function appliesToFeature(int $featureId): bool
    {
        if (empty($this->applies_to) || ! in_array('features', $this->applies_to)) {
            return false;
        }

        if (empty($this->applicable_features)) {
            return true;
        }

        return in_array($featureId, $this->applicable_features);
    }

    public function getFormattedValue(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        } elseif ($this->type === 'fixed') {
            return $this->currency . ' ' . number_format($this->value, 2);
        }

        return '';
    }
}