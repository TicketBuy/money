<?php

namespace Supplycart\Money\Contracts;

interface Tax
{
    /**
     * @return string Tax rate in string. e.g. 10% tax rate => '10.0'
     */
    public function getTaxRate(): string;

    /**
     * @return string Tax description
     */
    public function getTaxDescription(): string;

    /**
     * @return string Country name where the tax is for
     */
    public function getTaxCountry(): string;

    /**
     * @return string Currency code e.g MYR
     */
    public function getTaxCurrency(): string;
}