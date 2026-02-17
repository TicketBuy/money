<?php

namespace Supplycart\Money\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Supplycart\Money\Currency;
use Supplycart\Money\Money;

class MoneyParseTest extends TestCase
{
    public function test_can_parse_money_from_string(): void
    {
        $money = Money::parse('1000');

        $this->assertEquals(1000, $money->getAmount());
    }

    public function test_can_parse_money_from_integer(): void
    {
        $money = Money::parse(1000);

        $this->assertEquals(1000, $money->getAmount());
    }

    public function test_can_parse_money_from_array(): void
    {
        $money = Money::parse(['amount' => 1200, 'currency' => Currency::EUR]);

        $this->assertEquals(1200, $money->getAmount());
    }

    public function test_can_parse_money_from_money_object(): void
    {
        $money = Money::parse(new Money(1500));

        $this->assertEquals(1500, $money->getAmount());
    }

    public function test_can_parse_money_from_float(): void
    {
        $money = Money::parse(1550.0);

        $this->assertEquals(1550, $money->getAmount());
    }

    public function test_can_parse_money_from_float_with_decimals(): void
    {
        $money = Money::parse(1550.50);

        $this->assertEquals(1551, $money->getAmount());
    }

    public function test_can_create_money_of_from_money_object(): void
    {
        $money = Money::of(Money::of(1500));

        $this->assertEquals(1500, $money->getAmount());
        $this->assertEquals(Currency::EUR, $money->getCurrency());
    }
}
