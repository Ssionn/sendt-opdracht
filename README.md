# FaultLine

> Backend Developer — Laravel Error Handling opdracht

FaultLine is een kleine Laravel-applicatie met een productiewaardige exception-pipeline: elke onafgevangen `Throwable` wordt gelogd, opgeslagen met volledige context, en doorgestuurd naar een extern kanaal (Slack en/of e-mail) — zonder de request te vertragen.

Daarbovenop: een kleine UI om exceptions te bekijken/triggeren, queue-backed notificaties, rate limiting en een optionele Sentry-integratie.

---

## Stack

| | |
|---|---|
| Framework | **Laravel 13** |
| PHP | 8.3 |
| DB | MySQL (SQLite werkt ook) |
| Queue | `database` driver |

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
composer run dev
```

Open <http://localhost:8000>, maak een account aan, stel je notificatiekanalen in via **Settings**, en gebruik de **Trigger Exception** knop.

---

## Hoe de error pipeline werkt

```
                ┌──────────────────────────┐
   Throwable ──►│  bootstrap/app.php       │
                │  ->reportable(fn $e ...) │
                └────────────┬─────────────┘
                             │
                             ▼
                ┌──────────────────────────┐
                │  ExceptionReporter       │  ◄── filtert framework-ruis
                │  (Service / SRP)         │  ◄── rate-limit op duplicaten
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
                              └─────────────────┘     └─────────────────┘
```

### Belangrijke bestanden

| Pad | Verantwoordelijkheid |
|---|---|
| `bootstrap/app.php` | Registreert `reportable()` + `render()` regels. |
| `app/Exceptions/FaultlineException.php` | Custom `DomainException` voor business-rule violations. |
| `app/Services/ExceptionReporter.php` | Beslist wat gerapporteerd wordt, dedupliceert, slaat op, dispatcht de job. |
| `app/Jobs/SendExceptionNotification.php` | Queued job die notificaties naar actieve kanalen stuurt per user. |
| `app/Notifications/ExceptionOccurred.php` | Mail channel. |
| `app/Notifications/ExceptionOccurredChannel.php` | Slack channel (Block Kit). |
| `app/Services/ExceptionTypeMapper.php` | Mapt `ExceptionType` enum naar concrete class + default message. |
| `app/Enums/ExceptionType.php` | Vijf demo types (`domain`, `runtime`, `logic`, `invalid_argument`, `bad_method`). |
| `app/Http/Controllers/ExceptionController.php@trigger` | Demo-endpoint om een exception te triggeren. |

---

## Architectuurkeuzes

### 1. Handler in `bootstrap/app.php`
Laravel 11+ idioom. De echte logica zit in `ExceptionReporter` — het bootstrap-bestand is alleen de registratie.

### 2. `ExceptionReporter` als service
Synchrone flow binnen de request (DB-row wordt geschreven vóór de response). Alleen de notificatie gaat via de queue.

### 3. Notificaties via een Job
`SendExceptionNotification` is een `ShouldQueue` Job die per user de juiste kanalen aanspreekt. Als Slack down is, gaat de mail alsnog door.

### 4. Rate limiting op report-niveau
Keyed op `class + message`, max 5 per minuut. Checkt vóór de database-write.

### 5. HTTP status codes per exception class
`AuthenticationException` → 401, `ModelNotFoundException` → 404, `ValidationException` → 422, rest → 500. Alleen voor JSON-requests.

### 6. Sentry als opt-in
Per-user DSN, geïsoleerde `Sentry\Hub`, event IDs opgeslagen voor deep-links.

### 7. `database` queue standaard
Geen externe dependencies nodig. Wissel naar `redis` of `sync` in `.env`.

---

## Bonusfeatures

- Queue-based notificaties
- Rate limiting
- Sentry-integratie (per-user DSN, bi-directioneel resolve)
- Mini-dashboard UI (list/detail/trigger/resolve/delete, Slack OAuth, user preferences)

---

## Demo

Via de UI: **Trigger Exception** knop → kies een type → submit.

1. Rij verschijnt in de `exceptions` tabel.
2. Slack-bericht arriveert (indien ingeschakeld).
3. E-mail arriveert (check `storage/logs/laravel.log` bij `MAIL_MAILER=log`).
4. Sentry event ID wordt ingevuld (indien DSN is geconfigureerd).

---

## Toekomstige verbeteringen

- Per-tenant rate limit budgets
- Grouping/fingerprinting van soortgelijke exceptions
- Retry/backoff policy op de Job
- Mail-body gelijktrekken met Slack-context
