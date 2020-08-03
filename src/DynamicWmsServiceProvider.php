<?php

namespace Booni3\DynamicWms;

use Booni3\Linnworks\Linnworks;
use Illuminate\Support\ServiceProvider;

class DynamicWmsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('dynamic-wms.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'dynamic-wms');

        $this->app->singleton(DynamicWms::class, function ($app) {
            $config = $app->make('config');

            return new DynamicWms($config->get('dynamic-wms'));
        });

        $this->app->alias(DynamicWms::class, 'dynamic-wms');
    }
}
