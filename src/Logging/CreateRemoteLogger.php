<?php

namespace RemoteLogger\Logging;

use Monolog\Logger;

class CreateRemoteLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('remote');
        $logger->pushHandler(new RemoteLoggerHandler($config));

        return $logger;
    }
}
