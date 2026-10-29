<?php

declare(strict_types=1);

namespace Nafiswatsiq\Subbase\Tests\Money;

use InvalidArgumentException;
use Nafiswatsiq\Subbase\Support\Money;
use Nafiswatsiq\Subbase\Tests\TestCase;

final class MoneyTest extends TestCase
{
    public function test_usd_amounts_are_precise_and_half_up_rounded(): void
    {
        $this->assertSame('0.30', (new Money('0.10', 'USD'))->plus(new Money('0.20', 'USD'))->amount());
        $this->assertSame('2.35', (new Money('2.345', 'USD'))->amount());
        $this->assertSame('2.34', (new Money('2.344', 'USD'))->amount());
    }

    public function test_currency_specific_minor_unit_scales(): void
    {
        $this->assertSame('100', (new Money('99.6', 'JPY'))->amount());
        $this->assertSame('1.235', (new Money('1.2345', 'KWD'))->amount());
        $this->assertSame('1.2346', (new Money('1.23456', 'CLF'))->amount());
        $this->assertSame(1235, (new Money('1.235', 'KWD'))->toMinorUnits());
        $this->assertSame('1.235', Money::fromMinorUnits('1235', 'KWD')->amount());
    }

    public function test_percentage_discount_calculates_exact_half_up(): void
    {
        $this->assertSame('0.05', (new Money('0.10', 'USD'))->percent('50')->amount());
        $this->assertSame('0.01', (new Money('0.05', 'USD'))->percent('10')->amount());
        $this->assertSame('0.000', (new Money('0.001', 'KWD'))->percent('40')->amount());
    }

    public function test_negative_money_is_not_accepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money('-1', 'USD');
    }

    public function test_scientific_notation_is_not_accepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money('1e4', 'USD');
    }

    public function test_cross_currency_subtraction_is_not_accepted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Money('10', 'USD'))->minus(new Money('1', 'JPY'));
    }

    public function test_percentage_above_one_hundred_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Money('10', 'USD'))->percent('100.1');
    }

    public function test_zero_amount_and_integer_conversion(): void
    {
        $this->assertTrue(Money::zero('EUR')->isZero());
        $this->assertSame(10001, (new Money('100.01', 'EUR'))->toMinorUnits());
    }
}
