<?php

namespace Tests\Unit;

use App\Services\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_converts_exact_peso_values_to_centavos(): void
    {
        $this->assertSame(1, Money::toCents('0.01'));
        $this->assertSame(1250, Money::toCents('12.5'));
        $this->assertSame(123456789, Money::toCents('1234567.89'));
        $this->assertSame('1234567.89', Money::fromCents(123456789));
    }

    public function test_it_rejects_invalid_or_out_of_range_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toCents('1.234');
    }
}
