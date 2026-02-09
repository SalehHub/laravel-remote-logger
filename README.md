# Laravel Remote Logger

Send your Laravel application logs to a centralized remote logging server.

## Features

- Easy installation and configuration
- Bearer token authentication
- Asynchronous logging (queued) to avoid slowing down requests
- Automatic retry on failure (3 attempts with backoff)
- Configurable timeout and SSL verification
- Fallback to local logging on failure

## Installation

```bash
composer require salehhub/laravel-remote-logger
```

The package auto-registers via Laravel's package discovery.

## Configuration

### 1. Add Environment Variables

```env
REMOTE_LOGGER_URL=https://your-logging-server.com/api/logs
REMOTE_LOGGER_API_KEY=your-api-key
REMOTE_LOGGER_APP_NAME=MyApp
```

All available environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `REMOTE_LOGGER_URL` | `http://localhost:8000/api/logs` | Remote server endpoint |
| `REMOTE_LOGGER_API_KEY` | `null` | Bearer token for authentication |
| `REMOTE_LOGGER_APP_NAME` | `APP_NAME` | Application identifier |
| `REMOTE_LOGGER_LEVEL` | `debug` | Minimum log level to send |
| `REMOTE_LOGGER_ASYNC` | `true` | Send logs via queue |
| `REMOTE_LOGGER_QUEUE` | `null` | Queue name (null = default queue) |
| `REMOTE_LOGGER_VERIFY_SSL` | `true` | Verify SSL certificates |
| `REMOTE_LOGGER_TIMEOUT` | `5` | HTTP request timeout in seconds |

### 2. Add the Remote Channel

In `config/logging.php`:

```php
'channels' => [

    'remote' => [
        'driver' => 'remote',
    ],

    // Or stack with a local channel as backup
    'stack' => [
        'driver' => 'stack',
        'channels' => ['remote', 'daily'],
        'ignore_exceptions' => false,
    ],
],
```

Set as default:

```env
LOG_CHANNEL=remote
```

### 3. Queue Setup (for async logging)

```bash
php artisan queue:work
```

## Usage

```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => 123]);
Log::error('Payment failed', ['order_id' => 456]);
Log::warning('High memory usage detected');
```

## Remote Server Payload

Your server should accept POST requests with this JSON structure:

```json
{
    "application": "MyApp",
    "environment": "production",
    "level": "info",
    "message": "User logged in",
    "context": {"user_id": 123},
    "extra": {},
    "logged_at": "2024-02-08 10:30:00"
}
```

The request includes an `Authorization: Bearer <api_key>` header when `REMOTE_LOGGER_API_KEY` is set.

## Publishing Config

```bash
php artisan vendor:publish --tag=remote-logger-config
```

## Synchronous Mode

Set `REMOTE_LOGGER_ASYNC=false` to send logs synchronously (not recommended for production).

## License

MIT
