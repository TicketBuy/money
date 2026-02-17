<?php

namespace Supplycart\Money;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
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

    public static function of($amount = 0, string $currency = Currency::EUR): self
    {
        $brickCurrency = BrickCurrency::of($currency);
        $brickCurrency = new BrickCurrency($brickCurrency->getCurrencyCode(), $brickCurrency->getNumericCode(), $brickCurrency->getName(), 2);

        $bigRational = BigRational::of((string) $amount);

        return new RationalMoney($bigRational, $brickCurrency);
    }

    public function getAmount(): BigRational
    {
        return $this->instance->getAmount();
    }

    public function getCurrency(): string
    {
        return (string) $this->instance->getCurrency();
    }

    public function to(Context $context, RoundingMode $roundingMode): BrickMoney
    {
        return $this->instance->to($context, $roundingMode);
    }

    public function add($value): self
    {
        if ($value instanceof self) {
            if (! $this->instance->getCurrency()->is($value->getCurrency())) {
                throw MoneyMismatchException::currencyMismatch($this->instance->getCurrency(), $value->instance->getCurrency());
            }

            $value = $value->getAmount();
        }

        return new self($this->instance->plus((string) $value)->getAmount(), $this->instance->getCurrency());
    }

    public function subtract($value): self
    {
        if ($value instanceof self) {
            if (! $this->instance->getCurrency()->is($value->getCurrency())) {
                throw MoneyMismatchException::currencyMismatch($this->instance->getCurrency(), $value->instance->getCurrency());
            }

            $value = $value->getAmount();
        }

        return new self($this->instance->minus((string) $value)->getAmount(), $this->instance->getCurrency());
    }

    public function multiply($value): self
    {
        if ($value instanceof self) {
            $value = $value->getAmount();
        }

        return new self($this->instance->multipliedBy((string) $value)->getAmount(), $this->instance->getCurrency());
    }

    public function divide($value): self
    {
        if ($value instanceof self) {
            $value = $value->getAmount();
        }

        return new self($this->instance->dividedBy((string) $value)->getAmount(), $this->instance->getCurrency());
    }

    public function simplified(): self
    {
        return new self($this->instance->simplified()->getAmount(), $this->instance->getCurrency());
    }

    public function isZero(): bool
    {
        return $this->instance->getAmount()->isZero();
    }

    public function isPositive(): bool
    {
        return $this->instance->getAmount()->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->instance->getAmount()->isNegative();
    }

    public static function zero(string $currency = Currency::EUR): self
    {
        return self::of(0, $currency);
    }
}
