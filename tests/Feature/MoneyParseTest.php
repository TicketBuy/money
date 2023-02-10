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
}
