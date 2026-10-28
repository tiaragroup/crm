<?php

namespace Webkul\Tax\Providers;

use Illuminate\Support\ServiceProvider;

class TaxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
