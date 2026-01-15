<?php

namespace Supplycart\Money;

use ReflectionClass;

class Country
{
    public const string THE_NETHERLANDS = 'The Netherlands';

    public static function default(): string
    {
        return self::THE_NETHERLANDS;
    }

    public static function options(): array
    {
        $class = new ReflectionClass(self::class);

        $values = array_values($class->getConstants());

        return array_combine($values, $values);
    }
}
