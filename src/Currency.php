<?php

namespace Supplycart\Money;

class Currency
{
    const EUR = 'EUR';

    public static function default()
    {
        return self::EUR;
    }

    public static function options()
    {
        $class = new \ReflectionClass(__CLASS__);

        return $class->getConstants();
    }
}
