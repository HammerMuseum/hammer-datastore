<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // The API docs package is a dev dependency, so it isn't installed in production.
        if (! class_exists(Scramble::class)) {
            return;
        }

        // Leave the 404 fallback route out of the API docs.
        Scramble::configure()->routes(
            fn (Route $route) => Str::startsWith($route->uri, 'api/') && ! $route->isFallback
        );
    }
}
