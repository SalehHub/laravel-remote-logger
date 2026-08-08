<?php

namespace RemoteLogger\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger;
use RemoteLogger\Facades\RemoteLogger as RemoteLoggerFacade;
use RemoteLogger\Logging\RemoteLoggerHandler;
use RemoteLogger\RemoteLogger;
use RemoteLogger\RemoteLoggerServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_config_the_singleton_and_remote_log_channel(): void
    {
        $app = new Container;
        $app->instance('config', new Repository([
            'logging' => [
                'channels' => [
                    'remote' => ['level' => 'warning'],
                ],
            ],
        ]));

        (new RemoteLoggerServiceProvider($app))->register();

        $this->assertSame('warning', $app['config']->get('logging.channels.remote.level'));
        $this->assertSame('remote', $app['config']->get('logging.channels.remote.driver'));
        $this->assertSame(
            'http://localhost:8000/api/logs',
            $app['config']->get('remote-logger.url'),
        );
        $this->assertInstanceOf(RemoteLogger::class, $app->make('remote-logger-global'));
        $this->assertSame(
            $app->make('remote-logger-global'),
            $app->make('remote-logger-global'),
        );
    }

    public function test_it_boots_the_facade_publishable_config_and_log_driver(): void
    {
        RemoteLoggerFacade::setContext('facade-category', 'facade-subcategory');

        $this->assertSame('facade-category', RemoteLogger::getCategory());
        $this->assertSame('facade-subcategory', RemoteLogger::getSubcategory());

        $publishable = RemoteLoggerServiceProvider::pathsToPublish(
            RemoteLoggerServiceProvider::class,
            'remote-logger-config',
        );

        $this->assertSame(
            realpath(__DIR__.'/../src/config/remote-logger.php'),
            realpath((string) array_key_first($publishable)),
        );
        $this->assertSame('remote-logger.php', basename((string) reset($publishable)));

        $channel = $this->app->make('log')->channel('remote');

        $this->assertInstanceOf(IlluminateLogger::class, $channel);
        $this->assertInstanceOf(Logger::class, $channel->getLogger());
        $this->assertInstanceOf(
            RemoteLoggerHandler::class,
            $channel->getLogger()->getHandlers()[0],
        );
    }
}
