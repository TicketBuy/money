<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Supplycart\Money\Country;
use Supplycart\Money\Tax;

$factory->define(Tax::class, fn(Faker $faker) => [
    'name' => $name = $faker->randomElement(['GST', 'SST']),
    'rate' => $rate = $faker->randomFloat(2, 0, 20),
    'description' => $faker->sentence(3),
    'country' => $faker->randomElement(Country::options()),
    'is_active' => $faker->boolean(90),
]);
