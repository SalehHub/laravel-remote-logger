<?php

namespace RemoteLogger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RemoteLogger\RemoteLogger;
use RemoteLogger\RemoteLoggerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [RemoteLoggerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('logging.channels.daily', [
            'driver' => 'single',
            'path' => storage_path('logs/remote-logger-tests.log'),
        ]);
    }

    protected function tearDown(): void
    {
        RemoteLogger::flush();

        parent::tearDown();
    }
}
