<?php

namespace RemoteLogger\Tests;

use Monolog\Logger;
use RemoteLogger\Logging\CreateRemoteLogger;
use RemoteLogger\Logging\RemoteLoggerHandler;

class CreateRemoteLoggerTest extends TestCase
{
    public function test_it_creates_a_monolog_instance_with_the_remote_handler(): void
    {
        $logger = (new CreateRemoteLogger)([
            'url' => 'https://logs.example.test',
            'application' => 'test-app',
            'level' => 'info',
            'api_key' => null,
            'async' => false,
            'queue' => null,
            'verify_ssl' => true,
            'timeout' => 5,
        ]);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame('remote', $logger->getName());
        $this->assertInstanceOf(RemoteLoggerHandler::class, $logger->getHandlers()[0]);
    }
}
