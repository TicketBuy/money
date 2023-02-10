<?php

namespace Supplycart\Money;

class Currency
{
    public const EUR = 'EUR';

    public static function default(): string
    {
        return self::EUR;
    }

    public static function options(): array
    {
        return (new \ReflectionClass(__CLASS__))->getConstants();
    }
}
