<?php

namespace Supplycart\Money\Tests\Feature;

use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\MoneyMismatchException;
use Supplycart\Money\Currency;
use Supplycart\Money\RationalMoney;
use Supplycart\Money\Tests\TestCase;

class RationalMoneyTest extends TestCase
{
    public function test_can_get_amount(): void
    {
        $money = RationalMoney::of(100);

        $this->assertTrue($money->getAmount()->isEqualTo(100));
    }

    public function test_can_get_currency(): void
    {
        $money = RationalMoney::of(100);

        $this->assertEquals(Currency::EUR, $money->getCurrency());
    }

    public function test_can_convert_to_brick_money(): void
    {
        $money = RationalMoney::of(100);
        $brickMoney = $money->to(new DefaultContext, RoundingMode::HALF_UP);

        $this->assertEquals('100.00', (string) $brickMoney->getAmount());
    }

    public function test_can_add_scalar_value(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->add(50);

        $this->assertTrue($result->getAmount()->isEqualTo(150));
    }

    public function test_can_add_rational_money(): void
    {
        $money1 = RationalMoney::of(100);
        $money2 = RationalMoney::of(50);
        $result = $money1->add($money2);

        $this->assertTrue($result->getAmount()->isEqualTo(150));
    }

    public function test_can_subtract_scalar_value(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->subtract(30);

        $this->assertTrue($result->getAmount()->isEqualTo(70));
    }

    public function test_can_subtract_rational_money(): void
    {
        $money1 = RationalMoney::of(100);
        $money2 = RationalMoney::of(30);
        $result = $money1->subtract($money2);

        $this->assertTrue($result->getAmount()->isEqualTo(70));
    }

    public function test_can_multiply_by_scalar_value(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->multiply(3);

        $this->assertTrue($result->getAmount()->isEqualTo(300));
    }

    public function test_can_multiply_by_rational_money(): void
    {
        $money1 = RationalMoney::of(100);
        $money2 = RationalMoney::of(3);
        $result = $money1->multiply($money2);

        $this->assertTrue($result->getAmount()->isEqualTo(300));
    }

    public function test_can_divide_by_scalar_value(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->divide(4);

        $this->assertTrue($result->getAmount()->isEqualTo(25));
    }

    public function test_can_divide_by_rational_money(): void
    {
        $money1 = RationalMoney::of(100);
        $money2 = RationalMoney::of(4);
        $result = $money1->divide($money2);

        $this->assertTrue($result->getAmount()->isEqualTo(25));
    }

    public function test_division_preserves_exact_fraction(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->divide(3);

        // 100/3 should remain as exact fraction, not rounded
        $this->assertTrue($result->getAmount()->isEqualTo(BigRational::of('100/3')));
    }

    public function test_can_simplify_fraction(): void
    {
        $money = RationalMoney::of('50/100');
        $simplified = $money->simplified();

        $this->assertTrue($simplified->getAmount()->isEqualTo(BigRational::of('1/2')));
    }

    public function test_can_check_is_zero(): void
    {
        $zero = RationalMoney::of();
        $nonZero = RationalMoney::of(100);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    public function test_can_create_zero(): void
    {
        $zero = RationalMoney::zero();

        $this->assertTrue($zero->isZero());
        $this->assertEquals(Currency::EUR, $zero->getCurrency());
    }

    public function test_can_create_zero_with_currency(): void
    {
        $zero = RationalMoney::zero(Currency::USD);

        $this->assertTrue($zero->isZero());
        $this->assertEquals(Currency::USD, $zero->getCurrency());
    }

    public function test_chained_operations_preserve_precision(): void
    {
        $money = RationalMoney::of(100);

        // (100 / 3) * 3 should equal exactly 100
        $result = $money->divide(3)->multiply(3);

        $this->assertTrue($result->getAmount()->isEqualTo(100));
    }

    public function test_operations_return_new_instances(): void
    {
        $original = RationalMoney::of(100);

        $added = $original->add(50);
        $subtracted = $original->subtract(50);
        $multiplied = $original->multiply(2);
        $divided = $original->divide(2);

        // Original should remain unchanged
        $this->assertTrue($original->getAmount()->isEqualTo(100));
        $this->assertTrue($added->getAmount()->isEqualTo(150));
        $this->assertTrue($subtracted->getAmount()->isEqualTo(50));
        $this->assertTrue($multiplied->getAmount()->isEqualTo(200));
        $this->assertTrue($divided->getAmount()->isEqualTo(50));
    }

    public function test_add_throws_exception_on_currency_mismatch(): void
    {
        $eur = RationalMoney::of(100);
        $usd = RationalMoney::of(50, Currency::USD);

        $this->expectException(MoneyMismatchException::class);

        $eur->add($usd);
    }

    public function test_subtract_throws_exception_on_currency_mismatch(): void
    {
        $eur = RationalMoney::of(100);
        $usd = RationalMoney::of(50, Currency::USD);

        $this->expectException(MoneyMismatchException::class);

        $eur->subtract($usd);
    }

    public function test_divide_by_zero_throws_exception(): void
    {
        $money = RationalMoney::of(100);

        $this->expectException(DivisionByZeroException::class);

        $money->divide(0);
    }

    public function test_can_handle_negative_amounts(): void
    {
        $negative = RationalMoney::of(-100);

        $this->assertTrue($negative->getAmount()->isEqualTo(-100));
        $this->assertTrue($negative->isNegative());
        $this->assertFalse($negative->isPositive());
        $this->assertFalse($negative->isZero());
    }

    public function test_can_check_is_positive(): void
    {
        $positive = RationalMoney::of(100);
        $negative = RationalMoney::of(-100);
        $zero = RationalMoney::zero();

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($negative->isPositive());
        $this->assertFalse($zero->isPositive());
    }

    public function test_can_check_is_negative(): void
    {
        $positive = RationalMoney::of(100);
        $negative = RationalMoney::of(-100);
        $zero = RationalMoney::zero();

        $this->assertFalse($positive->isNegative());
        $this->assertTrue($negative->isNegative());
        $this->assertFalse($zero->isNegative());
    }

    public function test_subtract_can_result_in_negative(): void
    {
        $money = RationalMoney::of(50);
        $result = $money->subtract(100);

        $this->assertTrue($result->getAmount()->isEqualTo(-50));
        $this->assertTrue($result->isNegative());
    }

    public function test_add_negative_value(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->add(-30);

        $this->assertTrue($result->getAmount()->isEqualTo(70));
    }

    public function test_multiply_by_negative_value(): void
    {
        $money = RationalMoney::of(100);
        $result = $money->multiply(-2);

        $this->assertTrue($result->getAmount()->isEqualTo(-200));
        $this->assertTrue($result->isNegative());
    }

    public function test_divide_negative_by_positive(): void
    {
        $money = RationalMoney::of(-100);
        $result = $money->divide(2);

        $this->assertTrue($result->getAmount()->isEqualTo(-50));
        $this->assertTrue($result->isNegative());
    }

    public function test_get_currency_returns_string(): void
    {
        $money = RationalMoney::of(100);

        $this->assertIsString($money->getCurrency());
        $this->assertEquals(Currency::EUR, $money->getCurrency());
    }
}
