<?php

namespace Supplycart\Money\Tests\Feature;

use Supplycart\Money\Currency;
use Supplycart\Money\Money;
use Supplycart\Money\Tests\Stubs\Product;
use Supplycart\Money\Tests\TestCase;

class MoneyValueTest extends TestCase
{
    public function test_can_save_money_value_using_string()
    {
        $product = new Product;
        $product->unit_price = '100';

        $this->assertEquals(new Money(100), $product->unit_price);
    }

    public function test_can_save_money_value_using_money_object()
    {
        $product = new Product;
        $product->unit_price = new Money(100);

        $this->assertEquals(new Money(100), $product->unit_price);
    }

    public function test_can_save_money_value_using_array()
    {
        $product = new Product;
        $product->unit_price = [
            'amount' => 100,
        ];

        $this->assertEquals(new Money(100), $product->unit_price);
    }
}