<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FaultlineException;
use App\Jobs\SendExceptionNotification;
use App\Models\Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

use function Sentry\captureException;

use Sentry\ClientBuilder;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ExceptionReporter
{
    public function report(Throwable $e): void
    {
        if ($this->shouldIgnore($e)) {
            return;
        }

        $key = 'exception-report:'.get_class($e).':'.$e->getMessage();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        RateLimiter::hit($key, 60);

        $user = Auth::user();

        if (! $user->sentry_dsn) {
            return;
        }

        $effectiveDsn = $user->sentry_dsn;
        $sentToSentry = (bool) ($effectiveDsn && $user?->notify_sentry !== false);

        $sentryEventId = null;
        if ($sentToSentry) {
            $sentryEventId = $this->captureToSentry($e, $effectiveDsn);
        }

        $url = request()?->fullUrl() ?? 'cli';

        try {
            $record = Exception::create([
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'url' => $url,
                'type' => $this->typeFromException($e),
                'stack_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'sent_to_sentry' => $sentToSentry,
                'sentry_event_id' => $sentryEventId,
            ]);

            SendExceptionNotification::dispatch($record);
        } catch (Throwable) {
        }
    }

    private function captureToSentry(Throwable $e, string $dsn): ?string
    {
        $isCustomDsn = $dsn !== config('sentry.dsn');

        if ($isCustomDsn) {
            $client = ClientBuilder::create(['dsn' => $dsn])->getClient();
            $hub = new Hub($client);

            $hub->captureException($e);
            $eventId = $hub->getLastEventId();

            return $eventId ? (string) $eventId : null;
        }

        captureException($e);
        $eventId = SentrySdk::getCurrentHub()->getLastEventId();

        return $eventId ? (string) $eventId : null;
    }

    private function shouldIgnore(Throwable $e): bool
    {
        $ignored = [
            ModelNotFoundException::class,
            HttpResponseException::class,
            ValidationException::class,
            HttpException::class,
            MaxAttemptsExceededException::class,
        ];

        foreach ($ignored as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        return false;
    }

    private function typeFromException(Throwable $e): string
    {
        $types = [
            FaultlineException::class => 'domain',
        ];

        foreach ($types as $class => $type) {
            if ($e instanceof $class) {
                return $type;
            }
        }

        return 'generic';
    }
}
