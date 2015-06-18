<?php

namespace CallMeNP\LaraAuthBridge;

use Illuminate\Support\ServiceProvider;
use Route;

class LaraAuthBridgeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/lara-auth-bridge.php' => config_path('lara-auth-bridge.php'),
        ], 'config');

        Route::controller('/auth-bridge', 'CallMeNP\LaraAuthBridge\Controllers\ApiController');
    }

    public function register()
    {
        //
    }
}
