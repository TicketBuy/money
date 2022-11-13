<?php

namespace Supplycart\Money;

use Brick\Math\BigRational;
use Brick\Money\Context;
use Brick\Money\Currency as BrickCurrency;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money as BrickMoney;
use Brick\Money\RationalMoney as BrickRationalMoney;

final class RationalMoney
{
    private BrickRationalMoney $instance;

    public function __construct(BigRational $amount, BrickCurrency $currency)
    {
        $this->instance = new BrickRationalMoney($amount, $currency);
    }

    public static function of($amount, $currency): RationalMoney
    {
        $amount = BigRational::of($amount);

        if (! $currency instanceof BrickCurrency) {
            $currency = BrickCurrency::of($currency);
        }

        return new RationalMoney($amount, $currency);
    }

    public function getAmount(): BigRational
    {
        return $this->instance->getAmount();
    }

    public function to(Context $context, int $roundingMode): BrickMoney
    {
        return $this->instance->to($context, $roundingMode);
    }

    /**
     * @throws MoneyMismatchException
     */
    public function add($value): RationalMoney
    {
        return new RationalMoney($this->instance->plus($value)->getAmount(), $this->instance->getCurrency());
    }

    /**
     * @throws MoneyMismatchException
     */
    public function subtract($value): RationalMoney
    {
        return new RationalMoney($this->instance->minus($value)->getAmount(), $this->instance->getCurrency());
    }

    public function multiply($value): RationalMoney
    {
        return new RationalMoney($this->instance->multipliedBy($value)->getAmount(), $this->instance->getCurrency());
    }

    public function divide($value): RationalMoney
    {
        return new RationalMoney($this->instance->dividedBy($value)->getAmount(), $this->instance->getCurrency());
    }

    public function simplified() : RationalMoney
    {
        return new RationalMoney($this->instance->simplified()->getAmount(), $this->instance->getCurrency());
    }
}
