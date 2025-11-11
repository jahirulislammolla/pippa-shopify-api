<?php

namespace App\Providers;

use App\Repositories\ShopifyProductRepository;
use App\Repositories\ShopifyProductRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ShopifyProductRepositoryInterface::class, ShopifyProductRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
