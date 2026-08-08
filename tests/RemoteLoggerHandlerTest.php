<?php

namespace RemoteLogger\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Monolog\Logger;
use RemoteLogger\Jobs\SendLogToRemoteServer;
use RemoteLogger\Logging\CreateRemoteLogger;
use RemoteLogger\Logging\RemoteLoggerHandler;
use RemoteLogger\RemoteLogger;
use RuntimeException;

class RemoteLoggerHandlerTest extends TestCase
{
    public function test_it_sends_synchronous_logs_with_sanitized_context(): void
    {
        Http::fake([
            'logs.example.test/*' => Http::response(null, 202),
        ]);

        RemoteLogger::setContext('global-category', 'global-subcategory');

        $resource = fopen('php://memory', 'r');
        $exception = new RuntimeException('Sensitive exception', 17);
        $serializable = (object) ['safe' => true];
        $unserializable = new UnserializableValue;

        $logger = $this->logger([
            'async' => false,
            'api_key' => 'sync-token',
            'verify_ssl' => false,
            'timeout' => 8,
        ]);

        $logger->info('A structured log', [
            'category' => 'context-category',
            'subcategory' => 'context-subcategory',
            'nested' => [
                'closure' => static fn (): string => 'hidden',
                'resource' => $resource,
            ],
            'exception' => $exception,
            'serializable' => $serializable,
            'unserializable' => $unserializable,
            'scalar' => 42,
        ]);

        fclose($resource);

        Http::assertSentCount(1);

        $request = Http::recorded()->first()[0];
        $data = $request->data();

        $this->assertSame('https://logs.example.test/entries', $request->url());
        $this->assertTrue($request->hasHeader('Authorization', 'Bearer sync-token'));
        $this->assertSame('coverage-app', $data['application']);
        $this->assertSame('testing', $data['environment']);
        $this->assertSame('info', $data['level']);
        $this->assertSame('A structured log', $data['message']);
        $this->assertSame('global-category', $data['category']);
        $this->assertSame('global-subcategory', $data['subcategory']);
        $this->assertSame('[Closure]', $data['context']['nested']['closure']);
        $this->assertSame('[Resource: stream]', $data['context']['nested']['resource']);
        $this->assertSame([
            'class' => RuntimeException::class,
            'message' => 'Sensitive exception',
            'code' => 17,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $data['context']['exception']);
        $this->assertSame($serializable, $data['context']['serializable']);
        $this->assertSame(
            '[Unserializable: '.UnserializableValue::class.']',
            $data['context']['unserializable'],
        );
        $this->assertSame(42, $data['context']['scalar']);
        $this->assertArrayNotHasKey('category', $data['context']);
        $this->assertArrayNotHasKey('subcategory', $data['context']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $data['logged_at'],
        );
    }

    public function test_it_dispatches_queued_logs_with_context_categories(): void
    {
        Bus::fake();

        $logger = $this->logger([
            'async' => true,
            'queue' => 'remote-logs',
        ]);

        $logger->warning('Queued log', [
            'category' => 'jobs',
            'subcategory' => 'imports',
            'attempt' => 2,
        ]);

        Bus::assertDispatched(SendLogToRemoteServer::class, function (SendLogToRemoteServer $job): bool {
            $data = $this->property($job, 'data');

            return $job->queue === 'remote-logs'
                && $this->property($job, 'url') === 'https://logs.example.test/entries'
                && $data['level'] === 'warning'
                && $data['category'] === 'jobs'
                && $data['subcategory'] === 'imports'
                && $data['context'] === ['attempt' => 2];
        });
    }

    public function test_it_uses_configured_defaults_and_silently_handles_transport_errors(): void
    {
        config()->set('remote-logger', [
            'url' => 'https://defaults.example.test/entries',
            'application' => 'defaults-app',
            'api_key' => null,
            'level' => 'error',
            'async' => false,
            'queue' => null,
            'verify_ssl' => true,
            'timeout' => 3,
        ]);

        Http::fake(function (): never {
            throw new RuntimeException('Transport failed');
        });

        $logger = new Logger('defaults');
        $logger->pushHandler(new RemoteLoggerHandler([]));

        $logger->error('This must not escape');

        $this->addToAssertionCount(1);
    }

    private function logger(array $overrides = []): Logger
    {
        return (new CreateRemoteLogger)(array_merge([
            'url' => 'https://logs.example.test/entries',
            'application' => 'coverage-app',
            'level' => 'debug',
            'api_key' => null,
            'async' => false,
            'queue' => null,
            'verify_ssl' => true,
            'timeout' => 5,
        ], $overrides));
    }

    private function property(object $object, string $property): mixed
    {
        return (fn (): mixed => $this->{$property})->call($object);
    }
}

class UnserializableValue
{
    public function __serialize(): array
    {
        throw new RuntimeException('Cannot serialize');
    }
}
