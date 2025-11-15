<?php

namespace Molham\DynamicImageComposer;

use Illuminate\Support\ServiceProvider;
use Molham\DynamicImageComposer\Services\TemplateImageService;

class DynamicImageComposerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dynamic-image-composer.php',
            'dynamic-image-composer'
        );

        // Register main composer service
        $this->app->singleton(DynamicImageComposer::class, function ($app) {
            return new DynamicImageComposer();
        });

        // Register template service
        $this->app->singleton(TemplateImageService::class, function ($app) {
            return new TemplateImageService($app->make(DynamicImageComposer::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dynamic-image-composer.php' => config_path('dynamic-image-composer.php'),
        ], 'dynamic-image-composer-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'dynamic-image-composer-migrations');

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}
