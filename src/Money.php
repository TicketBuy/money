<?php

namespace Supplycart\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\CustomContext;
use Brick\Money\Currency as BrickCurrency;
use Brick\Money\Money as BrickMoney;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonException;
use JsonSerializable;
use Stringable;
use Supplycart\Money\Contracts\Tax as TaxContract;

final class Money implements Arrayable, Jsonable, Stringable, JsonSerializable
{
    private BrickMoney $instance;

    private ?TaxContract $tax = null;

    public int $scale;

    public static RoundingMode $roundingMode = RoundingMode::HALF_UP;

    public function __construct($amount = 0, string $currency = Currency::EUR, $scale = 2)
    {
        $this->instance = $this->createInstance(
            $amount ?? 0,
            $currency,
            $scale
        );

        $this->scale = $scale;
    }

    public static function of($amount = 0, string $currency = Currency::EUR, $decimal = 2): Money
    {
        return new Money($amount, $currency, $decimal);
    }

    public static function parse($value, $currency = null): Money
    {
        $currency = $currency ?? Currency::default();

        if ($value instanceof self) {
            return new Money($value->getAmount(), $value->getCurrency());
        }

        if ($value instanceof BrickMoney) {
            return new Money($value->getMinorAmount(), $value->getCurrency());
        }

        if (is_array($value) && array_key_exists('amount', $value)) {
            return new Money(data_get($value, 'amount', 0), data_get($value, 'currency', $currency));
        }

        if (is_float($value)) {
            return new Money((string) BigDecimal::of($value)->getUnscaledValue(), $currency);
        }

        return new Money($value, $currency);
    }

    public static function fromCents(int $amount, string $currency = Currency::EUR): Money
    {
        $instance = BrickMoney::ofMinor($amount, $currency);

        return new Money($instance->getMinorAmount(), $currency);
    }

    public static function fromDecimal(string $amount, string $currency = Currency::EUR): Money
    {
        $instance = BrickMoney::of($amount, $currency);

        return new Money($instance->getMinorAmount(), $currency);
    }

    public function toRational(): RationalMoney
    {
        return new RationalMoney($this->instance->getAmount()->toBigRational(), $this->instance->getCurrency());
    }

    public static function fromRational(RationalMoney $amount, Context $context, string $currency = Currency::EUR): Money
    {
        $instance = $amount->to($context, self::$roundingMode);

        return new Money($instance->getMinorAmount(), $currency);
    }

    public function getAmount(): int
    {
        return $this->instance->getAmount()
            ->dividedBy($this->getDivider(), $this->scale, self::$roundingMode)
            ->getUnscaledValue()
            ->toInt();
    }

    public function getDecimalAmount(): string
    {
        return $this->instance
            ->getAmount()
            ->dividedBy($this->getDivider(), $this->scale, self::$roundingMode)
            ->toScale($this->scale, self::$roundingMode);
    }

    public function format($locale = null): string
    {
        $locale = $locale ?? Locale::$currencies[(string) $this->instance->getCurrency()];

        return $this->instance->formatTo($locale);
    }

    public function toNumberFormat($decimal = 2, $decimal_separator = '.', $thousands_separator = ','): string
    {
        return number_format($this->getDecimalAmount(), $decimal, $decimal_separator, $thousands_separator);
    }

    public function getContext(): Context
    {
        return $this->instance->getContext();
    }

    public function getCurrency(): string
    {
        return (string) $this->instance->getCurrency();
    }

    public function add($value): Money
    {
        if (!$value instanceof self) {
            $value = self::of($value, $this->getCurrency(), $this->scale);
        }

        return new Money(
            $this->instance->plus(
                $value->multiply($this->getDivider()),
					self::$roundingMode
				)->getMinorAmount(),
				$this->getCurrency(),
				$this->scale
			);
    }

    public function addCents($value): Money
    {
        if (!$value instanceof self) {
            $value = self::fromCents($value, $this->getCurrency());
        }

        return new Money(
            $this->instance->plus(
                $value->multiply($this->getDivider()),
                self::$roundingMode
            )->getMinorAmount(),
            $this->getCurrency(),
            $this->scale
        );
    }

    public function subtract($value): Money
    {
        if (!$value instanceof self) {
            $value = self::of($value, $this->getCurrency(), $this->scale);
        }
		
		return new Money(
            $this->instance->minus($value->multiply($this->getDivider()))->getMinorAmount(),
            $this->instance->getCurrency(),
            $this->scale
        );
    }

    public function subtractCents($value): static
    {
        if (!$value instanceof self) {
            $value = self::fromCents($value, $this->getCurrency());
        }
		
        return new Money(
            $this->instance->minus($value->multiply($this->getDivider()))->getMinorAmount(),
            $this->instance->getCurrency(),
            $this->scale
        );
    }

    public function multiply($value): Money
    {
        $value = $this->instance->multipliedBy($value, self::$roundingMode);

        return new Money($value->getMinorAmount(), $value->getCurrency(), $this->scale);
    }

    public function divide($value): Money
    {
        $value = $this->instance->dividedBy($value, self::$roundingMode);

        return new Money($value->getMinorAmount(), $this->instance->getCurrency(), $this->scale);
    }

    public function withTax(TaxContract $tax): Money
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTaxAmount($quantity = 1): Money
    {
        if (!$this->tax) {
            return self::zero($this->getCurrency());
        }

        $taxValue = $this->instance->toRational()
            ->multipliedBy($this->getTaxRate())
            ->multipliedBy($quantity)
            ->to($this->instance->getContext(), self::$roundingMode);

        return self::of($taxValue->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function getTaxAmountFromInclusiveTax(): Money
    {
        if (!$this->tax) {
            return $this;
        }

        $taxFromInclusive = $this->instance->toRational()
            ->multipliedBy($this->getTaxRate())
            ->dividedBy($this->getTaxRate()->plus(1))
            ->to($this->instance->getContext(), self::$roundingMode);

        return new Money($taxFromInclusive->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function getTaxRate(): BigDecimal
    {
        if (!$this->tax) {
            return BigDecimal::zero();
        }

        // Take a tax rate of decimals into account, hence $this->scale + 2.
        return BigRational::of($this->tax->getTaxRate())
            ->dividedBy(100)
            ->toScale($this->scale + 2, self::$roundingMode);
    }

    public function afterTax($quantity = 1): Money
    {
        if (!$this->tax) {
            return $this;
        }

        $afterTax = $this->instance->toRational()
            ->multipliedBy($this->getTaxRate()->plus(1))
            ->multipliedBy($quantity)
            ->to($this->instance->getContext(), self::$roundingMode);

        return new Money($afterTax->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public function beforeTax(): Money
    {
        if (!$this->tax) {
            return $this;
        }

        $beforeTax = $this->instance->toRational()
            ->dividedBy($this->getTaxRate()->plus(1))
            ->to($this->instance->getContext(), self::$roundingMode);

        return new Money($beforeTax->getMinorAmount(), $this->getCurrency(), $this->scale);
    }

    public static function zero(string $currency = Currency::EUR): Money
    {
        return new Money(0, $currency);
    }

    public function isZero(): bool
    {
        return $this->instance->isZero();
    }

    public function __toString()
    {
        return $this->getDecimalAmount();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
        ];
    }

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * This function is to cater more than two decimal points
     */
    public function getDivider(): int
    {
        return $this->scale === 2 ? 1 : 10 ** ($this->scale - 2);
    }

    public function convertToDifferentDecimalPoint(int $newDecimalPoint): Money
    {
        $differenceInScale = $newDecimalPoint - $this->scale;

        $dividerOrMultiplier = 10 ** abs($differenceInScale);

        $newValue = $this->scale < $newDecimalPoint
            ? $this->instance->multipliedBy($dividerOrMultiplier, self::$roundingMode)
            : $this->instance->dividedBy($dividerOrMultiplier, self::$roundingMode);

        return new Money($newValue->getMinorAmount(), $newValue->getCurrency(), $newDecimalPoint);
    }

    private function createInstance($amount = 0, string $currency = Currency::EUR, $scale = 2)
    {
        $brickCurrency = BrickCurrency::of($currency);
        $brickCurrency = new BrickCurrency($brickCurrency->getCurrencyCode(), $brickCurrency->getNumericCode(), $brickCurrency->getName(), 2);

        $context = new CustomContext($scale);
        $bigRational = BigRational::of($amount)->dividedBy(10 ** $brickCurrency->getDefaultFractionDigits());

        return BrickMoney::create($bigRational, $brickCurrency, $context, self::$roundingMode);
    }
}
