<?php

namespace Supplycart\Money;

use ReflectionClass;

class Currency
{
    public const string EUR = 'EUR';

    public const string USD = 'USD';

    public static function default(): string
    {
        return self::EUR;
    }

    public static function options(): array
    {
        return (new ReflectionClass(self::class))->getConstants();
    }
}
