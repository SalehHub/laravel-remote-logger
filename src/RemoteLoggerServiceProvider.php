<?php

namespace RemoteLogger;

use Illuminate\Support\ServiceProvider;

class RemoteLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/remote-logger.php', 'remote-logger'
        );

        $this->app['config']->set('logging.channels.remote', array_merge(
            ['driver' => 'remote'],
            $this->app['config']->get('logging.channels.remote', []),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/config/remote-logger.php' => config_path('remote-logger.php'),
        ], 'remote-logger-config');

        $this->app->make('log')->extend('remote', function ($app, $config) {
            return (new Logging\CreateRemoteLogger)($config);
        });
    }
}
