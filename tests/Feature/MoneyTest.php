<?php

namespace Supplycart\Money\Tests\Feature;

use Brick\Money\Exception\MoneyMismatchException;
use Supplycart\Money\Currency;
use Supplycart\Money\Money;
use Supplycart\Money\Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_get_amount_from_money(): void
    {
        $money = new Money(1000);

        $this->assertEquals(1000, $money->getAmount());
    }

    public function test_can_get_decimal_value_from_money(): void
    {
        $money = new Money(1000);

        $this->assertEquals(10.000, $money->getDecimalAmount());
    }

    public function test_can_get_decimal_value_from_money_for_4_decimal_point(): void
    {
        $money = new Money(10000, Currency::EUR, 4);

        $this->assertEquals(1.0000, $money->getDecimalAmount());
    }

    public function test_can_get_currency_format(): void
    {
        $money = new Money(1000);

        $this->assertStringContainsString('€', $money->format());
        $this->assertStringContainsString('10,00', $money->format());
        $this->assertTrue(true);
    }

    public function test_can_add_integer(): void
    {
        $money = new Money(1000);

        $this->assertEquals(1500, $money->add(500)->getAmount());
    }

    public function test_can_add_money(): void
    {
        $money = new Money(1000);
        $money2 = new Money(500);

        $this->assertEquals(1500, $money->add($money2)->getAmount());
    }

    public function test_can_add_money_for_4_decimal_place(): void
    {
        $money = new Money(10000, Currency::EUR, 4);
        $money2 = new Money(500, Currency::EUR, 4);

        $this->assertEquals(10500, $money->add($money2)->getAmount());
        $this->assertEquals(1.0500, $money->add($money2)->getDecimalAmount());
    }

    public function test_can_minus_integer(): void
    {
        $money = new Money(1000);

        $this->assertEquals(500, $money->subtract(500)->getAmount());
    }

    public function test_can_minus_money(): void
    {
        $money = new Money(1000);
        $money2 = new Money(500);

        $this->assertEquals(500, $money->subtract($money2)->getAmount());
    }

    public function test_can_minus_money_for_4_decimal_place(): void
    {
        $money = new Money(10000, Currency::EUR, 4);
        $money2 = new Money(500, Currency::EUR, 4);

        $this->assertEquals(9500, $money->subtract($money2)->getAmount());
        $this->assertEquals(0.9500, $money->subtract($money2)->getDecimalAmount());
    }

    public function test_can_multiply_money(): void
    {
        $money = new Money(1000);

        $this->assertEquals(5000, $money->multiply(5)->getAmount());
    }

    public function test_can_multiply_money_for_4_decimal_place(): void
    {
        $money = new Money(10000, Currency::EUR, 4);

        $this->assertEquals(50000, $money->multiply(5)->getAmount());
        $this->assertEquals(5.0000, $money->multiply(5)->getDecimalAmount());
    }

    public function test_can_divide_money(): void
    {
        $money = new Money(1000);

        $this->assertEquals(200, $money->divide(5)->getAmount());
    }

    public function test_can_divide_money_for_4_decimal_place(): void
    {
        $money = new Money(10000, Currency::EUR, 4);

        $this->assertEquals(2000, $money->divide(5)->getAmount());
        $this->assertEquals(0.2000, $money->divide(5)->getDecimalAmount());
    }

    public function test_can_create_zero_money(): void
    {
        $money = Money::zero();

        $this->assertEquals(0, $money->getAmount());
    }

    public function test_can_check_money_is_zero(): void
    {
        $money = Money::zero();

        $this->assertTrue($money->isZero());
    }

    public function test_can_create_money_from_decimal(): void
    {
        $this->assertEquals(1500, Money::fromDecimal(15.0)->getAmount());
        $this->assertEquals(1500, Money::fromDecimal(15.00)->getAmount());
        $this->assertEquals(1500, Money::fromDecimal(15)->getAmount());
        $this->assertEquals(1500, Money::fromDecimal('15.0')->getAmount());
        $this->assertEquals(1500, Money::fromDecimal('15')->getAmount());
    }

    public function test_can_convert_money_to_array(): void
    {
        $money = Money::zero();

        $this->assertIsArray((array) $money);
        $this->assertEquals([
            'amount' => 0,
            'currency' => Currency::EUR,
        ], $money->toArray());

        $money = Money::of(252);

        $this->assertIsArray((array) $money);
        $this->assertEquals([
            'amount' => 252,
            'currency' => Currency::EUR,
        ], $money->toArray());
    }

    public function test_can_multiply_four_decimal(): void
    {
        $money = Money::of(90001, Currency::EUR, 4);
        $result = $money->multiply(1.0001)->getAmount();
        $this->assertEquals(90010, $result);

        $money = Money::of(1000001, Currency::EUR, 4);
        $result1 = $money->multiply(9.1236)->getAmount();
        $this->assertEquals(9123609, $result1);
    }

    public function test_number_format_working(): void
    {
        $money = Money::of(12341234, Currency::EUR, 4);
        $result = $money->toNumberFormat(2);

        $this->assertEquals('1,234.12', $result);
    }

    public function test_can_convert_from_4dp_to_2dp(): void
    {
        $money = Money::of(120001, Currency::EUR, 4);
        $result = $money->convertToDifferentDecimalPoint(2);

        // previous
        $this->assertEquals('12.0001', $money->getDecimalAmount());
        $this->assertEquals(120001, $money->getAmount());

        // after
        $this->assertEquals('12.00', $result->getDecimalAmount());
        $this->assertEquals(1200, $result->getAmount());
    }

    public function test_can_convert_from_2dp_to_4dp(): void
    {
        $money = Money::of(1201, Currency::EUR, 2);
        $result = $money->convertToDifferentDecimalPoint(4);

        // previous
        $this->assertEquals('12.01', $money->getDecimalAmount());
        $this->assertEquals(1201, $money->getAmount());

        // after
        $this->assertEquals('12.0100', $result->getDecimalAmount());
        $this->assertEquals(120100, $result->getAmount());
    }

    public function test_rounding_missing_one_cent_with_4_decimal_places(): void
    {
        $money = Money::of('12345', Currency::EUR, 4);
        $result = $money->multiply('0.5')->getAmount();

        $this->assertEquals(6173, $result);
    }

    public function test_rounding_missing_one_cent_with_2_decimal_places(): void
    {
        $money = Money::of('12345', Currency::EUR);
        $result = $money->multiply('0.5')->getAmount();

        $this->assertEquals(6173, $result);
    }

    public function test_add_throws_exception_on_currency_mismatch(): void
    {
        $eur = Money::of(1000);
        $usd = Money::of(500, Currency::USD);

        $this->expectException(MoneyMismatchException::class);

        $eur->add($usd);
    }

    public function test_subtract_throws_exception_on_currency_mismatch(): void
    {
        $eur = Money::of(1000);
        $usd = Money::of(500, Currency::USD);

        $this->expectException(MoneyMismatchException::class);

        $eur->subtract($usd);
    }

    public function test_add_cents_throws_exception_on_currency_mismatch(): void
    {
        $eur = Money::of(1000);
        $usd = Money::of(500, Currency::USD);

        $this->expectException(MoneyMismatchException::class);

        $eur->addCents($usd);
    }

    public function test_subtract_cents_throws_exception_on_currency_mismatch(): void
    {
        $eur = Money::of(1000);
        $usd = Money::of(500, Currency::USD);

        $this->expectException(MoneyMismatchException::class);

        $eur->subtractCents($usd);
    }

    public function test_add_same_currency_succeeds(): void
    {
        $usd1 = Money::of(1000, Currency::USD);
        $usd2 = Money::of(500, Currency::USD);

        $result = $usd1->add($usd2);

        $this->assertEquals(1500, $result->getAmount());
    }

    public function test_subtract_same_currency_succeeds(): void
    {
        $usd1 = Money::of(1000, Currency::USD);
        $usd2 = Money::of(300, Currency::USD);

        $result = $usd1->subtract($usd2);

        $this->assertEquals(700, $result->getAmount());
    }

    public function test_operations_return_new_instances(): void
    {
        $original = Money::of(1000);

        $added = $original->add(500);
        $subtracted = $original->subtract(500);
        $multiplied = $original->multiply(2);
        $divided = $original->divide(2);

        $this->assertEquals(1000, $original->getAmount());
        $this->assertEquals(1500, $added->getAmount());
        $this->assertEquals(500, $subtracted->getAmount());
        $this->assertEquals(2000, $multiplied->getAmount());
        $this->assertEquals(500, $divided->getAmount());
    }

    public function test_money_preserves_currency(): void
    {
        $usd = Money::of(1000, Currency::USD);

        $this->assertEquals(Currency::USD, $usd->getCurrency());
        $this->assertEquals(Currency::USD, $usd->add(500)->getCurrency());
        $this->assertEquals(Currency::USD, $usd->subtract(500)->getCurrency());
        $this->assertEquals(Currency::USD, $usd->multiply(2)->getCurrency());
        $this->assertEquals(Currency::USD, $usd->divide(2)->getCurrency());
    }

    public function test_money_to_string_returns_decimal_amount(): void
    {
        $money = Money::of(1050);

        $this->assertEquals('10.50', (string) $money);
    }

    public function test_money_to_json(): void
    {
        $money = Money::of(1500, Currency::USD);

        $json = json_decode($money->toJson(), true);

        $this->assertEquals(1500, $json['amount']);
        $this->assertEquals(Currency::USD, $json['currency']);
    }

    public function test_money_json_serialize(): void
    {
        $money = Money::of(1500, Currency::USD);

        $serialized = $money->jsonSerialize();

        $this->assertEquals(['amount' => 1500, 'currency' => Currency::USD], $serialized);
    }

    public function test_money_is_zero(): void
    {
        $this->assertTrue(Money::zero()->isZero());
        $this->assertTrue(Money::of()->isZero());
        $this->assertFalse(Money::of(1)->isZero());
    }

    public function test_money_zero_with_currency(): void
    {
        $zero = Money::zero(Currency::USD);

        $this->assertTrue($zero->isZero());
        $this->assertEquals(Currency::USD, $zero->getCurrency());
    }

    public function test_money_from_cents(): void
    {
        $money = Money::fromCents(1500);

        $this->assertEquals(1500, $money->getAmount());
    }

    public function test_money_from_cents_with_currency(): void
    {
        $money = Money::fromCents(1500, Currency::USD);

        $this->assertEquals(1500, $money->getAmount());
        $this->assertEquals(Currency::USD, $money->getCurrency());
    }
}
