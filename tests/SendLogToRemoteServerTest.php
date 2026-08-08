<?php

namespace RemoteLogger\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RemoteLogger\Jobs\SendLogToRemoteServer;
use RuntimeException;

class SendLogToRemoteServerTest extends TestCase
{
    public function test_it_sends_a_successful_authenticated_request(): void
    {
        Http::fake([
            'logs.example.test/*' => Http::response(null, 204),
        ]);

        $data = ['message' => 'Everything worked', 'level' => 'info'];

        (new SendLogToRemoteServer(
            'https://logs.example.test/entries',
            $data,
            'secret-token',
            false,
            9,
        ))->handle();

        Http::assertSent(function (Request $request) use ($data): bool {
            return $request->url() === 'https://logs.example.test/entries'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request->data() === $data;
        });
    }

    public function test_it_throws_when_the_remote_server_rejects_the_log(): void
    {
        Http::fake([
            'logs.example.test/*' => Http::response(null, 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Remote logging server returned status: 503');

        (new SendLogToRemoteServer(
            'https://logs.example.test/entries',
            ['message' => 'Unavailable'],
        ))->handle();
    }

    public function test_it_logs_a_permanent_failure_to_the_daily_channel(): void
    {
        $exception = new RuntimeException('Connection refused');

        Log::shouldReceive('channel')
            ->once()
            ->with('daily')
            ->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('SendLogToRemoteServer job failed permanently', [
                'message' => '',
                'level' => '',
                'exception' => 'Connection refused',
            ]);

        (new SendLogToRemoteServer(
            'https://logs.example.test/entries',
            [],
        ))->failed($exception);
    }
}
