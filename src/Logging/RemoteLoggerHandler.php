<?php

namespace RemoteLogger\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use RemoteLogger\Jobs\SendLogToRemoteServer;

class RemoteLoggerHandler extends AbstractProcessingHandler
{
    protected string $url;
    protected string $application;
    protected ?string $apiKey;
    protected bool $async;
    protected ?string $queue;
    protected bool $verifySsl;
    protected int $timeout;

    public function __construct(array $config)
    {
        $level = $config['level'] ?? config('remote-logger.level', 'debug');

        parent::__construct(Level::fromName($level));

        $this->url = $config['url'] ?? config('remote-logger.url');
        $this->application = $config['application'] ?? config('remote-logger.application');
        $this->apiKey = $config['api_key'] ?? config('remote-logger.api_key');
        $this->async = $config['async'] ?? config('remote-logger.async', true);
        $this->queue = $config['queue'] ?? config('remote-logger.queue');
        $this->verifySsl = $config['verify_ssl'] ?? config('remote-logger.verify_ssl', true);
        $this->timeout = $config['timeout'] ?? config('remote-logger.timeout', 5);
    }

    protected function write(LogRecord $record): void
    {
        try {
            $data = [
                'application' => $this->application,
                'environment' => config('app.env', 'production'),
                'level' => strtolower($record->level->name),
                'message' => $record->message,
                'context' => $this->sanitizeData($record->context),
                'extra' => $this->sanitizeData($record->extra),
                'logged_at' => $record->datetime->format('Y-m-d H:i:s'),
            ];

            if ($this->async) {
                $job = SendLogToRemoteServer::dispatch($this->url, $data, $this->apiKey, $this->verifySsl, $this->timeout);

                if ($this->queue) {
                    $job->onQueue($this->queue);
                }
            } else {
                $this->sendSync($data);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the application
        }
    }

    protected function sendSync(array $data): void
    {
        $request = Http::timeout($this->timeout);

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        if ($this->apiKey) {
            $request = $request->withToken($this->apiKey);
        }

        $request->post($this->url, $data);
    }

    /**
     * Recursively sanitize data to remove non-serializable values (Closures, resources, etc.).
     */
    protected function sanitizeData(mixed $data): mixed
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = $this->sanitizeData($value);
            }

            return $sanitized;
        }

        if ($data instanceof \Closure) {
            return '[Closure]';
        }

        if (is_resource($data)) {
            return '[Resource: '.get_resource_type($data).']';
        }

        if (is_object($data)) {
            try {
                serialize($data);

                return $data;
            } catch (\Throwable) {
                return '[Unserializable: '.get_class($data).']';
            }
        }

        return $data;
    }
}
