<?php

declare(strict_types=1);

namespace Cranbri\Laravel\Livepeer;

use Cranbri\LaravelLivepeer\Http\Controllers\WebhookController;
use Cranbri\Livepeer\Livepeer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LivepeerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/livepeer.php', 'livepeer'
        );

        $this->app->singleton('livepeer', function ($app) {
            $config = $app['config']['livepeer'];
            return new Livepeer($config['api_key']);
        });

        $this->app->alias('livepeer', Livepeer::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/livepeer.php' => config_path('livepeer.php'),
            ], 'livepeer-config');
        }

        Route::macro('livepeerWebhooks', function (string $url) {
            return Route::post($url, WebhookController::class)->name('livepeer-webhooks');
        });
    }
}