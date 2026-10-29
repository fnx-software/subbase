<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Support;

use InvalidArgumentException;
use OverflowException;

/**
 * Immutable positive money value. Financial APIs use canonical decimal strings,
 * not floats. All operations round HALF_UP to the currency's configured scale.
 */
final readonly class Money
{
    private string $amount;

    private string $currency;

    private int $scale;

    public function __construct(string $amount, string $currency)
    {
        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO 4217 code.');
        }

        if (! preg_match('/^(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,12})?$/D', trim($amount))) {
            throw new InvalidArgumentException('Money must be a nonnegative plain decimal string.');
        }

        $this->currency = $currency;
        $this->scale = self::currencyScale($currency);
        $this->amount = self::roundPositive(trim($amount), $this->scale);
    }

    public static function zero(string $currency): self
    {
        return new self('0', $currency);
    }

    public static function fromMinorUnits(int|string $minor, string $currency): self
    {
        $digits = (string) $minor;
        if (! preg_match('/^(?:0|[1-9][0-9]{0,17})$/D', $digits)) {
            throw new InvalidArgumentException('Minor units must be a nonnegative integer.');
        }

        $scale = self::currencyScale(strtoupper($currency));
        $base = '1'.str_repeat('0', $scale);

        return new self(bcdiv($digits, $base, $scale), $currency);
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function scale(): int
    {
        return $this->scale;
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', $this->scale) === 0;
    }

    public function equals(self $other): bool
    {
        $this->assertSameCurrency($other);

        return bccomp($this->amount, $other->amount, $this->scale) === 0;
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(bcadd($this->amount, $other->amount, $this->scale), $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        if (bccomp($this->amount, $other->amount, $this->scale) < 0) {
            throw new InvalidArgumentException('Money cannot become negative.');
        }

        return new self(bcsub($this->amount, $other->amount, $this->scale), $this->currency);
    }

    /** Percentage amount, with exact decimal rate and HALF_UP rounding. */
    public function percent(string $rate): self
    {
        if (! preg_match('/^(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,8})?$/D', $rate)
            || bccomp($rate, '100', 8) > 0
        ) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100.');
        }

        $workScale = $this->scale + 12;
        $unrounded = bcdiv(bcmul($this->amount, $rate, $workScale), '100', $workScale);

        return new self($unrounded, $this->currency);
    }

    /**
     * For gateways requiring exact minor-unit integers. Raises before integer overflow.
     */
    public function toMinorUnits(): int
    {
        $value = bcmul($this->amount, '1'.str_repeat('0', $this->scale), 0);

        if (bccomp($value, (string) PHP_INT_MAX, 0) > 0) {
            throw new OverflowException('Money amount exceeds integer minor-unit capacity.');
        }

        return (int) $value;
    }

    public static function currencyScale(string $currency): int
    {
        $code = strtoupper($currency);
        if (! preg_match('/^[A-Z]{3}$/D', $code)) {
            throw new InvalidArgumentException('Invalid currency code.');
        }

        $overrides = config('subbase.currency_scales', []);

        if (array_key_exists($code, $overrides)) {
            $scale = $overrides[$code];
            if (! is_int($scale) || $scale < 0 || $scale > 4) {
                throw new InvalidArgumentException('Currency scale override must be an integer between zero and four.');
            }

            return $scale;
        }

        if (in_array($code, [
            'BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG',
            'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
        ], true)) {
            return 0;
        }

        if (in_array($code, ['BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND'], true)) {
            return 3;
        }

        if (in_array($code, ['CLF', 'UYW'], true)) {
            return 4;
        }

        return 2;
    }

    private static function roundPositive(string $value, int $scale): string
    {
        $half = $scale === 0 ? '0.5' : '0.'.str_repeat('0', $scale).'5';

        return bcadd($value, $half, $scale);
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Arithmetic across currencies requires an explicit exchange rate.');
        }
    }
}
