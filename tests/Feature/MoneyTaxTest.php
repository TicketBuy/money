<?php

namespace Supplycart\Money\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Supplycart\Money\Country;
use Supplycart\Money\Currency;
use Supplycart\Money\Money;

class MoneyTaxTest extends TestCase
{
    public function test_can_get_tax_amount_for_a_money(): void
    {
        $money = Money::of(252)->withTax(new Tax);

        $this->assertEquals(252, $money->getAmount());
        $this->assertEquals('0.0921', $money->getTaxRate());
        $this->assertEquals('0.23', $money->getTaxAmount());
        $this->assertEquals('18.57', $money->getTaxAmount(80));
    }

    public function test_can_get_tax_amount_for_a_money_for_4_decimal_place(): void
    {
        $money = Money::of(10000, Currency::EUR, 4)->withTax(new Tax);

        $this->assertEquals(10000, $money->getAmount());
        $this->assertEquals('0.092100', $money->getTaxRate());
        $this->assertEquals('0.092100', $money->getTaxAmount());
        $this->assertEquals('7.368000', $money->getTaxAmount(80));
    }

    public function test_can_get_after_tax_amount(): void
    {
        $money = Money::of(252)->withTax(new Tax);
        $this->assertEquals(275, $money->afterTax()->getAmount());

        $money = Money::of(252)->withTax(new Tax);
        $this->assertEquals(17338, $money->afterTax(63)->getAmount());

        $money = Money::of(252);
        $this->assertEquals(252, $money->afterTax()->getAmount());
    }

    public function test_can_get_after_tax_amount_for_4_decimal(): void
    {
        $money = Money::of(10000, Currency::EUR, 4)->withTax(new Tax);

        $this->assertEquals(10921, $money->afterTax()->getAmount());
        $this->assertEquals(688023, $money->afterTax(63)->getAmount());
        $this->assertEquals(68.8023, $money->afterTax(63)->getDecimalAmount());

        $money = Money::of(10000, Currency::EUR, 4);
        $this->assertEquals(10000, $money->afterTax()->getAmount());
        $this->assertEquals(1.0000, $money->afterTax()->getDecimalAmount());
    }

    public function test_can_get_before_tax_amount(): void
    {
        $money = Money::of(267)->withTax(new Tax);
        $this->assertEquals(244, $money->beforeTax()->getAmount());
        $this->assertEquals(2.44, $money->beforeTax()->getDecimalAmount());
    }

    public function test_can_get_before_tax_amount_for_4_decimal_place(): void
    {
        $money = Money::of(10921,Currency::EUR, 4)->withTax(new Tax);
        $this->assertEquals(10000, $money->beforeTax()->getAmount());
        $this->assertEquals(1.0000, $money->beforeTax()->getDecimalAmount());
    }

    public function test_can_get_tax_from_price_incl_tax(): void
    {
        $money = Money::of(267)->withTax(new Tax);
        $this->assertEquals(23, $money->getTaxAmountFromInclusiveTax()->getAmount());
        $this->assertEquals(0.23, $money->getTaxAmountFromInclusiveTax()->getDecimalAmount());
    }

    public function test_can_get_tax_from_price_incl_tax_for_4_decimal_place(): void
    {
        $money = Money::of(10921, Currency::EUR, 4)->withTax(new Tax);
        $this->assertEquals(921, $money->getTaxAmountFromInclusiveTax()->getAmount());
        $this->assertEquals(0.0921, $money->getTaxAmountFromInclusiveTax()->getDecimalAmount());
    }
}

class Tax implements \Supplycart\Money\Contracts\Tax
{
    public function getTaxRate(): string
    {
        return '9.21';
    }

    public function getTaxDescription(): string
    {
        return '';
    }

    public function getTaxCountry(): string
    {
        return Country::THE_NETHERLANDS;
    }

    public function getTaxCurrency(): string
    {
        return Currency::EUR;
    }
}
