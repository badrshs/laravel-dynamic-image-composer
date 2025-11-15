<?php

namespace Badrshs\DynamicImageComposer;

use Illuminate\Support\ServiceProvider;
use Badrshs\DynamicImageComposer\Services\TemplateImageService;

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
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dynamic-image-composer');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Badrshs\DynamicImageComposer\Console\Commands\InstallCommand::class,
            ]);
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/dynamic-image-composer.php' => config_path('dynamic-image-composer.php'),
        ], 'dynamic-image-composer-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'dynamic-image-composer-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/dynamic-image-composer'),
        ], 'dynamic-image-composer-views');

        // Publish fonts
        $this->publishes([
            __DIR__ . '/../resources/fonts' => storage_path('app/public/fonts'),
        ], 'dynamic-image-composer-fonts');
    }
}
