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

        Route::get('/auth-bridge/login', 'CallMeNP\LaraAuthBridge\Controllers\ApiController@getSession');
        Route::post('/auth-bridge/login', 'CallMeNP\LaraAuthBridge\Controllers\ApiController@doLogin');
        Route::delete('/auth-bridge/login', 'CallMeNP\LaraAuthBridge\Controllers\ApiController@doLogout');
    }

    public function register()
    {
        //
    }
}
