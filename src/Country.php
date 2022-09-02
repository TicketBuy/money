<?php

namespace Supplycart\Money;

class Country
{
    const THE_NETHERLANDS = 'The Netherlands';

    public static function default()
    {
        return self::THE_NETHERLANDS;
    }

    public static function options()
    {
        $class = new \ReflectionClass(__CLASS__);

        $values = array_values($class->getConstants());

        return array_combine($values, $values);
    }
}
