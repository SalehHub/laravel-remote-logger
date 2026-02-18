<?php

namespace RemoteLogger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendLogToRemoteServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];
    public int $timeout = 30;

    public function __construct(
        protected string $url,
        protected array $data,
        protected ?string $apiKey = null,
        protected bool $verifySsl = true,
        protected int $httpTimeout = 5,
    ) {
        $this->data = $this->sanitizeData($data);
    }

    /**
     * Sanitize data to remove non-serializable values like Closures.
     *
     * @param mixed $data
     * @param array $seenObjects
     * @return mixed
     */
    protected function sanitizeData($data, &$seenObjects = [])
    {
        if ($data instanceof \Closure) {
            return '[Closure]';
        }

        if (is_object($data)) {
            $objId = spl_object_id($data);
            if (isset($seenObjects[$objId])) {
                return '[Circular Reference]';
            }
            $seenObjects[$objId] = true;
            
            // Convert object to array for processing
            $data = (array) $data;
        }

        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = $this->sanitizeData($value, $seenObjects);
            }
            return $sanitized;
        }

        return $data;
    }

    public function handle(): void
    {
        $request = Http::timeout($this->httpTimeout);

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        if ($this->apiKey) {
            $request = $request->withToken($this->apiKey);
        }

        $response = $request->post($this->url, $this->data);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Remote logging server returned status: '.$response->status()
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('daily')->error('SendLogToRemoteServer job failed permanently', [
            'message' => $this->data['message'] ?? '',
            'level' => $this->data['level'] ?? '',
            'exception' => $exception->getMessage(),
        ]);
    }
}
