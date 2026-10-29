<?php

namespace Nafiswatsiq\Subbase\Models;

use Nafiswatsiq\Subbase\Models\Concerns\ReplacesTranslatableJsonOnMassAssignment;
use Illuminate\Support\Str;
use Laravelcm\Subscriptions\Models\Plan as BasePlan;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends BasePlan
{
    use ReplacesTranslatableJsonOnMassAssignment;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_active',
        'featured',
        'price',
        'signup_fee',
        'currency',
        'prices',
        'trial_period',
        'trial_interval',
        'invoice_period',
        'invoice_interval',
        'grace_period',
        'grace_interval',
        'prorate_day',
        'prorate_period',
        'prorate_extend_due',
        'active_subscribers_limit',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'price' => 'decimal:4',
        'signup_fee' => 'decimal:4',
        'prices' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPriceForCurrency(?string $currency = null, bool $fallbackToBase = true): float
    {
        $targetCurrency = strtoupper((string) ($currency ?: $this->currency));
        $prices = $this->normalizedPrices();

        if (array_key_exists($targetCurrency, $prices)) {
            return (float) $prices[$targetCurrency];
        }

        if (! $fallbackToBase) {
            return 0.0;
        }

        return (float) ($this->price ?? 0.0);
    }

    /**
     * Exact financial accessor: no binary float conversion. Existing float API
     * remains for legacy presentation calls, not financial posting.
     */
    public function getPriceDecimalForCurrency(?string $currency = null, bool $fallbackToBase = true): string
    {
        $target = strtoupper((string) ($currency ?: $this->currency));
        $raw = $this->getRawOriginal('prices');
        $prices = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($prices)) {
            $prices = [];
        }

        if (array_key_exists($target, $prices) && is_numeric($prices[$target])) {
            return (new \Nafiswatsiq\Subbase\Support\Money((string) $prices[$target], $target))->amount();
        }

        if (! $fallbackToBase) {
            return \Nafiswatsiq\Subbase\Support\Money::zero($target)->amount();
        }

        $base = $this->getRawOriginal('price') ?? '0';

        return (new \Nafiswatsiq\Subbase\Support\Money((string) $base, $target))->amount();
    }

    public function getPriceForLocale(?string $locale = null, bool $fallbackToBase = true): float
    {
        return $this->getPriceForCurrency(static::currencyFromLocale($locale), $fallbackToBase);
    }

    public static function currencyFromLocale(?string $locale = null): string
    {
        $map = static::getLocaleCurrencyMap();
        $defaultCurrency = static::defaultCurrency();
        $resolvedLocale = strtolower(trim((string) ($locale ?: app()->getLocale())));

        if ($resolvedLocale === '') {
            return $defaultCurrency;
        }

        $localeNormalized = str_replace('_', '-', $resolvedLocale);

        if (array_key_exists($localeNormalized, $map)) {
            return $map[$localeNormalized];
        }

        $localePrefix = explode('-', $localeNormalized)[0];

        if (array_key_exists($localePrefix, $map)) {
            return $map[$localePrefix];
        }

        return $defaultCurrency;
    }

    public static function hasLocaleCurrencyMapping(string $locale): bool
    {
        $map = static::getLocaleCurrencyMap();
        $normalizedLocale = strtolower(str_replace('_', '-', trim($locale)));

        if ($normalizedLocale === '') {
            return false;
        }

        if (array_key_exists($normalizedLocale, $map)) {
            return true;
        }

        $localePrefix = explode('-', $normalizedLocale)[0];

        return array_key_exists($localePrefix, $map);
    }

    public static function getLocaleCurrencyMap(): array
    {
        return static::localeCurrencyMap();
    }

    public function hasCurrencyPrice(string $currency): bool
    {
        return array_key_exists(strtoupper($currency), $this->normalizedPrices());
    }

    public function getAvailableCurrencies(): array
    {
        return array_keys($this->normalizedPrices());
    }

    public function setPriceForCurrency(string $currency, int|float|string $price): self
    {
        $targetCurrency = strtoupper($currency);
        $prices = $this->normalizedPrices();
        $prices[$targetCurrency] = (new \Nafiswatsiq\Subbase\Support\Money((string) $price, $targetCurrency))->amount();

        $this->prices = $prices;
        $this->syncBasePriceFromCurrency();

        return $this;
    }

    public function syncBasePriceFromCurrency(?string $currency = null): self
    {
        $targetCurrency = strtoupper((string) ($currency ?: $this->currency));
        $prices = $this->normalizedPrices();

        if (empty($targetCurrency) || ! array_key_exists($targetCurrency, $prices)) {
            $firstCurrency = array_key_first($prices);

            if ($firstCurrency === null) {
                $this->price = '0';

                return $this;
            }

            $targetCurrency = strtoupper((string) $firstCurrency);
            $this->currency = $targetCurrency;
        }

        $this->price = $prices[$targetCurrency];

        return $this;
    }

    /**
     * @param  array<string, float>  $value
     */
    public function setPricesAttribute($value): void
    {
        $normalized = $this->normalizePrices((array) $value);

        $this->attributes['prices'] = json_encode($normalized);

        $targetCurrency = strtoupper((string) ($this->attributes['currency'] ?? $this->currency ?? ''));

        if ($targetCurrency !== '' && array_key_exists($targetCurrency, $normalized)) {
            $this->attributes['price'] = $normalized[$targetCurrency];

            return;
        }

        $firstCurrency = array_key_first($normalized);

        if ($firstCurrency === null) {
            return;
        }

        $this->attributes['currency'] = $firstCurrency;
        $this->attributes['price'] = $normalized[$firstCurrency];
    }

    protected static function booted(): void
    {
        static::saving(function (self $plan): void {
            if (empty($plan->prices) && ! empty($plan->currency)) {
                $plan->prices = [
                    strtoupper((string) $plan->currency) => (string) $plan->price,
                ];

                return;
            }

            $plan->syncBasePriceFromCurrency();
        });
    }

    /**
     * Get the discounts associated with this plan.
     */
    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class);
    }

    /**
     * Get active discounts for this plan, ordered by priority.
     */
    public function activeDiscounts()
    {
        return $this->discounts()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->orderByDesc('priority');
    }

    /**
     * Get the best applicable discount for this plan based on price.
     */
    public function getBestDiscount(float $amount, string $currency): ?Discount
    {
        return $this->activeDiscounts()
            ->get()
            ->filter(fn (Discount $discount): bool => $discount->calculateDiscount($amount, $currency) < $amount)
            ->first();
    }

    protected function normalizedPrices(): array
    {
        return $this->normalizePrices((array) ($this->prices ?? []));
    }

    /**
     * @param  array<string, float>  $prices
     * @return array<string, float>
     */
    protected function normalizePrices(array $prices): array
    {
        $normalized = [];

        foreach ($prices as $currency => $amount) {
            $currencyCode = $this->normalizePriceKey((string) $currency);

            if ($currencyCode === '' || ! is_numeric($amount)) {
                continue;
            }

            $normalized[$currencyCode] = (new \Nafiswatsiq\Subbase\Support\Money((string) $amount, $currencyCode))->amount();
        }

        ksort($normalized);

        return $normalized;
    }

    protected function normalizePriceKey(string $key): string
    {
        $normalizedKey = trim($key);

        if ($normalizedKey === '') {
            return '';
        }

        if (preg_match('/^[a-z]{2}([_-][a-z]{2})?$/i', $normalizedKey) === 1) {
            if (! static::hasLocaleCurrencyMapping($normalizedKey)) {
                return '';
            }

            return static::currencyFromLocale($normalizedKey);
        }

        return strtoupper($normalizedKey);
    }

    public static function localeCurrencyMap(): array
    {
        $configMap = config('subbase.locale_currency_map', []);
        $normalized = [];

        foreach ((array) $configMap as $locale => $currency) {
            $localeKey = strtolower(trim((string) $locale));
            $currencyCode = strtoupper(trim((string) $currency));

            if ($localeKey === '' || $currencyCode === '') {
                continue;
            }

            $normalized[$localeKey] = $currencyCode;
        }

        return $normalized;
    }

    /**
     * @return array<string, string> Currency code => label (for Select options).
     */
    public static function currencySelectOptions(): array
    {
        $currencyMap = static::localeCurrencyMap();
        $languageMap = static::localeLanguageMap();
        
        $options = [];
        foreach ($currencyMap as $locale => $code) {
            if (! isset($options[$code])) {
                $langName = $languageMap[$locale] ?? strtoupper($locale);
                $options[$code] = $code . ' - ' . $langName;
            }
        }

        ksort($options);

        return $options;
    }

    public static function normalizePriceKeyForForm(string $key): string
    {
        $normalizedKey = trim($key);

        if ($normalizedKey === '') {
            return '';
        }

        if (preg_match('/^[a-z]{2}([_-][a-z]{2})?$/i', $normalizedKey) === 1) {
            if (! static::hasLocaleCurrencyMapping($normalizedKey)) {
                return '';
            }

            return static::currencyFromLocale($normalizedKey);
        }

        return strtoupper($normalizedKey);
    }

    /**
     * @param  array<int, array<string, mixed>|mixed>  $rows
     * @return array{prices: array<string, float>, currency: string}
     */
    public static function pricesFromRepeaterRows(array $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $currencyCode = static::normalizePriceKeyForForm((string) ($row['currency'] ?? ''));
            $rawPrice = $row['price'] ?? null;

            if ($currencyCode === '' || ! is_numeric($rawPrice)) {
                continue;
            }

            $merged[$currencyCode] = (new \Nafiswatsiq\Subbase\Support\Money((string) $rawPrice, $currencyCode))->amount();
        }

        ksort($merged);

        $defaultCurrency = '';
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['is_default'])) {
                continue;
            }

            $candidate = static::normalizePriceKeyForForm((string) ($row['currency'] ?? ''));
            if ($candidate !== '' && array_key_exists($candidate, $merged)) {
                $defaultCurrency = $candidate;

                break;
            }
        }

        if ($defaultCurrency === '' && $merged !== []) {
            $defaultCurrency = (string) array_key_first($merged);
        }

        if ($defaultCurrency === '') {
            $defaultCurrency = static::defaultCurrency();
        }

        return [
            'prices' => $merged,
            'currency' => $defaultCurrency,
        ];
    }

    /**
     * Detect Filament repeater state vs persisted JSON object (currency => amount).
     */
    public static function formPricesLookLikeRepeaterRows(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        if ($value === []) {
            return true;
        }

        if (! array_is_list($value)) {
            return false;
        }

        $first = $value[0] ?? null;

        return is_array($first)
            && array_key_exists('price', $first)
            && array_key_exists('currency', $first);
    }

    /**
     * @return array<int, array{currency: string, price: float|string, is_default: bool}>
     */
    public function repeaterRowsForPricesRepeater(): array
    {
        $normalized = $this->normalizedPrices();

        if ($normalized === []) {
            return [[
                'currency' => static::currencyFromLocale(),
                'price' => '',
                'is_default' => true,
            ]];
        }

        $base = strtoupper(trim((string) ($this->currency ?? '')));
        $rows = [];
        foreach ($normalized as $currencyCode => $amount) {
            $code = strtoupper((string) $currencyCode);
            $rows[] = [
                'currency' => $code,
                'price' => $amount,
                'is_default' => $base !== '' && $code === $base,
            ];
        }

        if (! collect($rows)->contains(fn (array $r): bool => $r['is_default'])) {
            $rows[0]['is_default'] = true;
        }

        return $rows;
    }

    protected static function defaultCurrency(): string
    {
        $defaultCurrency = strtoupper(trim((string) config('subbase.default_currency', 'USD')));

        return $defaultCurrency !== '' ? $defaultCurrency : 'USD';
    }

    public static function localeLanguageMap(): array
    {
        $configMap = config('subbase.language_locale_map', []);
        $normalized = [];

        foreach ((array) $configMap as $locale => $language) {
            $localeKey = strtolower(trim((string) $locale));
            $languageName = trim((string) $language);

            if ($localeKey === '' || $languageName === '') {
                continue;
            }

            $normalized[$localeKey] = $languageName;
        }

        return $normalized;
    }

    /**
     * Locale key from app locale that exists in {@see localeLanguageMap()}, for form defaults.
     */
    public static function defaultFormLanguage(): string
    {
        $map = static::localeLanguageMap();
        $locale = strtolower(str_replace('_', '-', app()->getLocale()));

        if (array_key_exists($locale, $map)) {
            return $locale;
        }

        $prefix = explode('-', $locale)[0];

        if (array_key_exists($prefix, $map)) {
            return $prefix;
        }

        $first = array_key_first($map);

        return $first !== null ? $first : 'en';
    }

    /**
     * @param  array<string, string>  $nameByLocale
     * @param  array<string, string>  $descriptionByLocale
     * @return array<int, array{language: string, name: string, description: string}>
     */
    public static function repeaterRowsFromTranslations(array $nameByLocale, array $descriptionByLocale): array
    {
        $locales = array_unique(array_merge(array_keys($nameByLocale), array_keys($descriptionByLocale)));
        sort($locales);

        $rows = [];
        foreach ($locales as $locale) {
            $localeKey = (string) $locale;
            $rows[] = [
                'language' => $localeKey,
                'name' => (string) ($nameByLocale[$localeKey] ?? ''),
                'description' => (string) ($descriptionByLocale[$localeKey] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>|mixed>  $rows
     * @return array{name: array<string, string>, description: array<string, string>}
     */
    public static function translationsFromRepeaterRows(array $rows): array
    {
        $name = [];
        $description = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $locale = strtolower(trim((string) ($row['language'] ?? '')));
            if ($locale === '') {
                continue;
            }

            $name[$locale] = (string) ($row['name'] ?? '');
            $description[$locale] = (string) ($row['description'] ?? '');
        }

        return [
            'name' => $name,
            'description' => $description,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>|mixed>  $rows
     */
    public static function firstTranslationNameFromRepeaterRows(array $rows): string
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));

            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    /**
     * Build plan slug from first translation name + invoice interval + invoice period.
     *
     * @param  array<string, mixed>  $data
     */
    public static function slugFromFormData(array $data): string
    {
        $name = '';
        if (isset($data['translations']) && is_array($data['translations'])) {
            $name = static::firstTranslationNameFromRepeaterRows($data['translations']);
        }

        if ($name === '' && isset($data['name']) && is_array($data['name'])) {
            foreach ($data['name'] as $translatedName) {
                $candidate = trim((string) $translatedName);

                if ($candidate !== '') {
                    $name = $candidate;

                    break;
                }
            }
        }

        $invoiceInterval = trim((string) ($data['invoice_interval'] ?? ''));
        $invoicePeriod = trim((string) ($data['invoice_period'] ?? ''));

        $parts = array_filter([$name, $invoicePeriod, $invoiceInterval], fn (string $value): bool => $value !== '');

        if ($parts === []) {
            return '';
        }

        return Str::slug(implode(' ', $parts));
    }
}
