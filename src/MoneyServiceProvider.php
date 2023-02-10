<?php

namespace Supplycart\Money;

use Illuminate\Support\ServiceProvider;

class MoneyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/money.php', 'money'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/money.php' => config_path('money.php'),
        ]);
    }
}
