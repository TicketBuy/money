<?php

namespace Supplycart\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money as BrickMoney;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Supplycart\Money\Contracts\Tax as TaxContract;
use Stringable;

final class Money implements Arrayable, Jsonable, Stringable, \JsonSerializable
{
    private BrickMoney $instance;

    private ?TaxContract $tax = null;

    public int $scale;

    public static int $roundingMode = RoundingMode::HALF_UP;

    public function __construct($amount = 0, string $currency = Currency::EUR, $scale = 2)
    {
        $this->instance = BrickMoney::ofMinor($amount ?? 0, $currency, new CustomContext($scale),
            static::$roundingMode);
        $this->scale = $scale;
    }

    public static function of($amount = 0, string $currency = Currency::EUR, $decimal = 2)
    {
        return new static($amount, $currency, $decimal);
    }

    /**
     * @param int|float|array|Money|BrickMoney $value
     * @param null $currency
     * @return static
     */
    public static function parse($value, $currency = null): Money
    {
        $currency = $currency ?? Currency::default();

        if ($value instanceof Money) {
            return new static($value->getAmount(), $value->getCurrency());
        }

        if ($value instanceof BrickMoney) {
            return new static($value->getMinorAmount(), $value->getCurrency());
        }

        if (is_array($value) && array_key_exists('amount', $value)) {
            return new static(data_get($value, 'amount', 0), data_get($value, 'currency', $currency));
        }

        if (is_float($value)) {
            return new static((string) BigDecimal::of($value)->getUnscaledValue(), $currency);
        }

        return new static($value, $currency);
    }

    public static function fromCents(int $amount, string $currency = Currency::EUR)
    {
        $instance = BrickMoney::ofMinor($amount, $currency);

        return new static($instance->getMinorAmount(), $currency);
    }

    public static function fromDecimal(string $amount, string $currency = Currency::EUR)
    {
        $instance = BrickMoney::of($amount, $currency);

        return new static($instance->getMinorAmount(), $currency);
    }

    public function getAmount(): int
    {
        return $this->instance->getAmount()->dividedBy($this->getDivider(), $this->scale, static::$roundingMode)->getUnscaledValue()->toInt();
    }

    public function getDecimalAmount($scale = 2): string
    {
        return $this->instance
            ->getAmount()
            ->dividedBy($this->getDivider(), $this->scale, static::$roundingMode)
            ->toScale($this->scale, static::$roundingMode);
    }

    /**
     * @deprecated use `getDecimalAmount()`
     */
    public function toDecimal()
    {
        return $this->getDecimalAmount(2);
    }

    /**
     * @deprecated use `format()`
     */
    public function toCurrencyFormat()
    {
        return $this->format();
    }

    public function format($locale = null)
    {
        $locale = $locale ?? Locale::$currencies[(string) $this->instance->getCurrency()];

        return $this->instance->formatTo($locale);
    }

    public function toNumberFormat($decimal = 2)
    {
        return number_format($this->getDecimalAmount(), $decimal);
    }

    public function getCurrency(): string
    {
        return (string) $this->instance->getCurrency();
    }

    public function add($value): Money
    {
        if (!$value instanceof Money) {
            $value = Money::of($value, $this->getCurrency(), $this->scale);
        }

        return new static(
            $this->instance->plus(
                $value->multiply($this->getDivider()),
                static::$roundingMode
            )->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function addCents($value)
    {
        if (!$value instanceof Money) {
            $value = Money::fromCents($value, $this->getCurrency());
        }

        return new static(
            $this->instance->plus(
                $value->multiply($this->getDivider()),
                static::$roundingMode
            )->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function subtract($value): Money
    {
        if (!$value instanceof Money) {
            $value = Money::of($value, $this->getCurrency(), $this->scale);
        }

        return new static($this->instance->minus($value->multiply($this->getDivider()))->getMinorAmount()
            , $this->instance->getCurrency(), $this->scale);
    }

    public function subtractCents($value)
    {
        if (!$value instanceof Money) {
            $value = Money::fromCents($value, $this->getCurrency());
        }

        return new static(
            $this->instance->plus(
                $value->multiply($this->getDivider()),
                static::$roundingMode
            )->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function multiply($value): Money
    {
        $value = $this->instance->multipliedBy($value, static::$roundingMode);

        return new static($value->getMinorAmount(), $value->getCurrency(), $this->scale);
    }

    public function divide($value): Money
    {
        $value = $this->instance->dividedBy($value, static::$roundingMode);

        return new static($value->getMinorAmount(), $this->instance->getCurrency(), $this->scale);
    }

    public function withTax(TaxContract $tax): Money
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTaxAmount($quantity = 1): Money
    {
        if (!$this->tax) {
            return static::zero($this->getCurrency());
        }

        $taxValue = $this->instance->toRational()
            ->multipliedBy($this->getTaxRate())
            ->multipliedBy($quantity)
            ->to($this->instance->getContext(), static::$roundingMode);

        return static::of($taxValue->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function getTaxAmountFromInclusiveTax(): Money
    {
        if (!$this->tax) {
            return $this;
        }

        $taxFromInclusive = $this->instance->toRational()
            ->multipliedBy($this->getTaxRate())
            ->dividedBy($this->getTaxRate()->plus(1))
            ->to($this->instance->getContext(), static::$roundingMode);

        return new static($taxFromInclusive->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function getTaxRate(): BigDecimal
    {
        if (!$this->tax) {
            return BigDecimal::zero();
        }

        return BigRational::of($this->tax->getTaxRate())
            ->dividedBy(100)
            ->toScale($this->scale, static::$roundingMode);
    }

    public function afterTax($quantity = 1): Money
    {
        if (!$this->tax) {
            return $this;
        }

        $afterTax = $this->instance->toRational()
            ->multipliedBy($this->getTaxRate()->plus(1))
            ->multipliedBy($quantity)
            ->to($this->instance->getContext(), static::$roundingMode);

        return new static($afterTax->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function beforeTax(): Money
    {
        if (!$this->tax) {
            return $this;
        }

        $beforeTax = $this->instance->toRational()
            ->dividedBy($this->getTaxRate()->plus(1))
            ->to($this->instance->getContext(), static::$roundingMode);

        return new static($beforeTax->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public static function zero(string $currency = Currency::EUR): Money
    {
        return new static(0, $currency);
    }

    public function isZero(): bool
    {
        return $this->instance->isZero();
    }

    public function __toString()
    {
        return (string) $this->getDecimalAmount(2);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * This function is to cater more than two decimal points
     * @return int
     */
    public function getDivider(): int
    {
        return $this->scale === 2 ? 1 : pow(10, $this->scale - 2);
    }

    public function convertToDifferentDecimalPoint(int $newDecimalPoint): Money
    {
        $differenceInScale = $newDecimalPoint - $this->scale;

        $dividerOrMultiplier = pow(10, abs($differenceInScale));

        $newValue = $this->scale < $newDecimalPoint
            ? $this->instance->multipliedBy($dividerOrMultiplier, Money::$roundingMode)
            : $this->instance->dividedBy($dividerOrMultiplier, Money::$roundingMode);

        return new Money($newValue->getMinorAmount(), $newValue->getCurrency(), $newDecimalPoint);
    }
}
