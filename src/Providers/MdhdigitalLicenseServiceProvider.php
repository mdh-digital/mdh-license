<?php

namespace Mdhdigital\MdhLicense\Providers;

use App\Http\Middleware\Authenticate;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mdhdigital\MdhLicense\Middleware\DeviceMobile;
use Mdhdigital\MdhLicense\Middleware\IsLicense; 

class MdhdigitalLicenseServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->publishFiles();
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }

    /**
     * Bootstrap the application events.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function boot(Router $router)
    { 
        $router->middlewareGroup('auth',[Authenticate::class]);
        $router->middlewareGroup('is_license', [IsLicense::class]);
        $router->middlewareGroup('device_mobile', [DeviceMobile::class]);
    }

    /**
     * Publish config file for the installer.
     *
     * @return void
     */
    protected function publishFiles()
    { 
        
    }
}
