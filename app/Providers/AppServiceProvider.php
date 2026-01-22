<?php

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            // @link https://github.com/barryvdh/laravel-ide-helper
            if (class_exists(IdeHelperServiceProvider::class)) {
                $this->app->register(IdeHelperServiceProvider::class);
            }

            // @link https://laravel.com/docs/12.x/telescope
            if (class_exists(TelescopeServiceProvider::class)) {
                $this->app->register(TelescopeServiceProvider::class);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
