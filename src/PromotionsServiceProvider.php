<?php

namespace ItmoSoft\Promotions;

use Illuminate\Support\ServiceProvider;

class PromotionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/promotions.php', 'promotions');
        $this->app->singleton(PromotionService::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/promotions.php' => config_path('promotions.php')], 'promotions-config');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
