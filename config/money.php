<?php

use Supplycart\Money\Country;
use Supplycart\Money\Currency;
use Supplycart\Money\Locale;

return [
    'default' => [
        'country' => Country::THE_NETHERLANDS,
        'currency' => Currency::EUR,
        'locale' => Locale::$countries[Country::THE_NETHERLANDS],
    ],
];
