<?php

namespace Supplycart\Money;

class Country
{
    public const THE_NETHERLANDS = 'The Netherlands';

    public static function default(): string
    {
        return self::THE_NETHERLANDS;
    }

    public static function options(): array
    {
        $class = new \ReflectionClass(__CLASS__);

        $values = array_values($class->getConstants());

        return array_combine($values, $values);
    }
}
