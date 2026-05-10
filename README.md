# FaultLine

> Backend Developer — Laravel Error Handling assignment

FaultLine is a small Laravel application that demonstrates a robust, production-style
exception pipeline: every unhandled `Throwable` is logged, persisted with full context,
and broadcast to an external channel (Slack and/or e-mail) — without slowing down
the request that triggered it.

On top of the required brief I've added a tiny UI to inspect/trigger exceptions, a
queue-backed notification path, rate limiting and an optional Sentry
integration.

---

## Stack

| | |
|---|---|
| Framework | **Laravel 13** (the brief mentions 10/11; 13 was used because it's the current LTS line and the API surface for `bootstrap/app.php` exception handling is identical) |
| PHP | 8.3 |
| DB | MySQL (SQLite works too — switch in `.env`) |
| Queue | `database` driver (any driver works) |

---

## Quick start

```sh
git clone https://github.com/Ssionn/sendt-opdracht.git
cd sendt-opdracht
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate
composer run dev   # serves app + queue worker + vite + log tail
```

Then open <http://localhost:8000>, register an account, configure your notification
channels in **Settings**, and use the **Trigger Exception** button to fire one.

---

## How the error pipeline works

```
                ┌──────────────────────────┐
   Throwable ──►│  bootstrap/app.php       │
                │  ->reportable(fn $e ...) │
                └────────────┬─────────────┘
                             │
                             ▼
                ┌──────────────────────────┐
                │  ExceptionReporter       │  ◄── ignores framework noise
                │  (Service / SRP)         │  ◄── rate limits duplicates
                └────────────┬─────────────┘
                             │
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
         ┌─────────┐   ┌──────────┐   ┌──────────────────────────┐
         │ Sentry  │   │ DB row   │   │ SendExceptionNotification│
         │ (opt.)  │   │ (model)  │   │   (queued Job)           │
         └─────────┘   └──────────┘   └────────────┬─────────────┘
                                                   │
                                       ┌───────────┴───────────┐
                                       ▼                       ▼
                              ┌─────────────────┐     ┌─────────────────┐
                              │ Slack channel   │     │ Mail channel    │
                              │ (rich blocks)   │     │ (MailMessage)   │
                              └─────────────────┘     └─────────────────┘
```

### Key files

| Path | Responsibility |
|---|---|
| `bootstrap/app.php` | Wires global `reportable()` + per-exception `render()` rules. |
| `app/Exceptions/FaultlineException.php` | Custom `DomainException` for business-rule violations (required by the brief). |
| `app/Services/ExceptionReporter.php` | Pure service that decides *what* to report, dedupes via rate limiter, persists, and dispatches the job. |
| `app/Jobs/SendExceptionNotification.php` | Queued job that fans out to the active notification channels per user. |
| `app/Notifications/ExceptionOccurred.php` | Mail channel. |
| `app/Notifications/ExceptionOccurredChannel.php` | Slack channel (block kit, includes message + URL + file + line + truncated trace). |
| `app/Services/ExceptionTypeMapper.php` | Maps the `ExceptionType` enum to a concrete class + default message — keeps the demo controller dumb. |
| `app/Enums/ExceptionType.php` | The five demo types (`domain`, `runtime`, `logic`, `invalid_argument`, `bad_method`). |
| `app/Http/Controllers/ExceptionController.php@trigger` | Demo endpoint to fire any of those types on demand. |

---

## Architectural choices & trade-offs

### 1. Centralised handler in `bootstrap/app.php`, not a custom Handler class
Laravel 11+ moved exception handling out of `App\Exceptions\Handler` and into a
fluent `withExceptions()` callback. I kept it there because:

- It's the framework idiom (Laravel conventions criterion).
- The actual logic lives in `ExceptionReporter` — the bootstrap file is only the
  *registration*.

### 2. `ExceptionReporter` is a service, not a Listener / static helper
A service was chosen over an event listener because the reporting flow is
**synchronous within the request** (we want the DB row written before the response
is sent, so the dashboard reflects reality immediately). Only the *outbound
notification* is deferred to a queue.

It implements SRP by separating four concerns into private methods:
`shouldIgnore()`, `captureToSentry()`, `typeFromException()`, and the orchestration
in `report()`.

### 3. Notifications are dispatched from a Job, not directly
`SendExceptionNotification` is a `ShouldQueue` Job that *itself* dispatches the
Notifications. Two reasons:

- The job owns the per-channel routing logic (different users have different Slack
  tokens / channels / opt-ins) — this state is messy and shouldn't live in the
  Notification class itself.
- If a user has *both* Slack and Mail enabled and Slack is down, the Mail still
  goes out and the job can be retried independently.

The Notification classes themselves stay focused on *formatting* one channel each
— Slack has its own class (`ExceptionOccurredChannel`) using Block Kit, Mail has
`ExceptionOccurred`. Adding a new channel (Discord, Telegram, webhook) is a
new Notification + a branch in the Job, nothing else changes.

### 4. Rate limiting on the report side, not the notification side
Keyed by `class + message`, capped at 5 per minute. The check runs **before**
we hit the database, so a runaway exception loop can't fill the table.

Trade-off: it's per-server-process (uses the cache store). For multi-node setups
you'd want a Redis-backed limiter — already supported, just change `CACHE_STORE`.

### 5. HTTP status code rendering is split per exception class
Rather than one big switch, each renderable lives in its own `render()` callback
in `bootstrap/app.php`:

- `AuthenticationException` → `401`
- `ModelNotFoundException` → `404`
- `ValidationException` → `422` (with errors payload)
- everything else (only when `expectsJson()`) → generic `500`

HTML responses still get Laravel's default error pages, so this only kicks in for
API-style calls.

### 6. Sentry as an opt-in side-channel
Sentry is wired in but **never required**. Each user can paste their own DSN in
Settings; if it matches `config('sentry.dsn')` we use the global SDK, otherwise
we spin up an isolated `Sentry\Hub` so one user's events don't leak into another
user's project. Event IDs are stored so the UI can deep-link back to Sentry,
and exceptions can be resolved on both sides from one click.

### 7. Why a `database` queue by default
Zero external dependencies for the reviewer. Swap `QUEUE_CONNECTION=redis` or
`sync` (for debugging) in `.env` — nothing else changes.

---

## Bonus features

- **Queue-based notifications** — `SendExceptionNotification implements ShouldQueue`,
  and the Notifications themselves also implement `ShouldQueue`, so the originating
  request returns immediately.
- **Rate limiting** — see §4 above.
- **Sentry integration** — per-user DSN, custom hub, bi-directional resolve.
- **Mini-dashboard UI** — list / detail / trigger / resolve / delete exceptions,
  Slack OAuth login, per-user notification preferences.

---

## Demo: triggering an exception

Authenticated, via the UI: **Trigger Exception** button, pick a type, hit submit.

Authenticated, via HTTP:

```sh
curl -X POST http://localhost:8000/exceptions/trigger \
     -H "Accept: application/json" \
     -H "X-XSRF-TOKEN: ..." \
     --cookie "..." \
     -d "type=domain&message=Customer is not allowed to do that"
```

Via tinker:

```php
php artisan tinker
>>> throw new \App\Exceptions\FaultlineException('boom');
```

In all three cases you should see:

1. A row appear in the `exceptions` table.
2. A Slack message arrive in your configured channel (if enabled).
3. An e-mail arrive at your address (if enabled — check `storage/logs/laravel.log`
   if `MAIL_MAILER=log`).
4. The Sentry event ID populated on the row (if Sentry DSN is set).

---

## What I'd add next (out of scope)

- **Per-tenant rate limit budgets** instead of a global 5/min — currently a noisy
  app could starve a quiet one.
- **Grouping/fingerprinting** of similar exceptions à la Sentry's issue grouping,
  so the dashboard doesn't drown in duplicates.
- **Retry/backoff policy on the Job** — currently uses the default; for a
  flaky Slack webhook you'd want `$tries` + `$backoff`.
- **Mail notification body parity with Slack** — the mail message could include
  the same URL/file/line/trace context the Slack message already has.
