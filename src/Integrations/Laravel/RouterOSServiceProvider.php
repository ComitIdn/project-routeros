<?php

namespace RouterOSFramework\Integrations\Laravel;

use RouterOSFramework\Core\RouterOS;

if (!class_exists('Illuminate\Support\ServiceProvider')) {
    // Standalone fallback when running outside of Laravel
    abstract class BaseServiceProvider {}
} else {
    class_alias('Illuminate\Support\ServiceProvider', 'RouterOSFramework\Integrations\Laravel\BaseServiceProvider');
}

/**
 * Class RouterOSServiceProvider
 * Seamless integration for Laravel 8.x, 9.x, 10.x, 11.x, and beyond.
 */
class RouterOSServiceProvider extends BaseServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind singleton into Laravel Service Container
        if (property_exists($this, 'app') && method_exists($this->app, 'singleton')) {
            $this->app->singleton('routeros', function ($app) {
                $config = config('routeros', [
                    'host'     => env('ROUTEROS_HOST', '192.168.88.1'),
                    'user'     => env('ROUTEROS_USER', 'admin'),
                    'pass'     => env('ROUTEROS_PASS', ''),
                    'port'     => (int)env('ROUTEROS_PORT', 8728),
                    'timeout'  => (int)env('ROUTEROS_TIMEOUT', 3),
                    'database' => [
                        'driver' => 'sqlite',
                        'file'   => database_path('routeros.sqlite'),
                    ],
                ]);

                return new RouterOS($config);
            });

            $this->app->alias('routeros', RouterOS::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (function_exists('config_path') && method_exists($this, 'publishes')) {
            $this->publishes([
                __DIR__ . '/../../../config.php' => config_path('routeros.php'),
            ], 'routeros-config');
        }
    }
}
