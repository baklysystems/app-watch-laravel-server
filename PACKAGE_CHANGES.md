# Appswatch Client Package Changes — Sync Verification Feature

This file lists all changes made inside `vendor/baklysystems/app-watch-laravel-client/` that need to be applied to the standalone `baklysystems/app-watch-laravel-client` package repository.

---

## 1. New File: `src/Commands/PingCommand.php`

**Path:** `src/Commands/PingCommand.php`

A new artisan command `appswatch:ping` that tests the connection to the Appswatch server and verifies sync is working.

- Calls the server's `/api/ingest/ping` endpoint via `HttpTransport`
- Displays a CLI table with project info, data freshness per telemetry type (with ✅/⬜ indicators), data volumes, and a smart recommendation
- Supports `--json` flag for raw JSON output
- Detects SSL certificate errors and gives specific guidance (set `APPSWATCH_VERIFY_SSL=false` in dev/staging)
- Includes a general troubleshooting checklist for non-SSL failures

Key features:
- SSL error auto-detection: when `cURL error 60` or SSL/certificate keywords appear in the error, a dedicated SSL troubleshooting section is shown with the exact `.env` fix
- Color-coded output with emoji indicators
- Proper exit codes (`SUCCESS`/`FAILURE`)

Full source is in `vendor/baklysystems/app-watch-laravel-client/src/Commands/PingCommand.php`.

---

## 2. Modified File: `src/AppswatchServiceProvider.php`

**Path:** `src/AppswatchServiceProvider.php`

**Change:** Register the new `PingCommand` in the `boot()` method.

```diff
 if ($this->app->runningInConsole()) {
     $this->commands([
         Commands\FlushBufferCommand::class,
+        Commands\PingCommand::class,
     ]);
 }
```

---

## 3. Modified File: `src/Transport/HttpTransport.php`

**Path:** `src/Transport/HttpTransport.php`

**Change:** Added `verify` option to Guzzle client, reading from `config('appswatch.verify_ssl')`.

```diff
 $this->client = new Client([
     'http_errors' => false,
     'connect_timeout' => 5,
     'timeout' => 10,
+    'verify' => $this->config['verify_ssl'] ?? true,
     'headers' => [
         ...
     ],
 ]);
```

This allows disabling SSL certificate verification for dev/staging environments where the server may have a self-signed or mismatched certificate.

---

## 4. Modified File: `config/appswatch.php`

**Path:** `config/appswatch.php`

**Change:** Added `verify_ssl` configuration option above the transport section.

```php
/*
|--------------------------------------------------------------------------
| SSL Verification
|--------------------------------------------------------------------------
|
| Set to false to disable SSL certificate verification (useful for
| staging/dev environments with self-signed or mismatched certificates).
| In production, always keep this enabled (true).
|
*/
'verify_ssl' => env('APPSWATCH_VERIFY_SSL', true),
```

---

## 5. Server-Side Requirements

The client's `appswatch:ping` command calls `POST /api/ingest/ping` on the server. The server must implement:

1. **A `/api/ingest/ping` endpoint** (POST and GET) protected by `verify-api-key` middleware, returning:
   - `status`: `"connected"` or `"error"`
   - `message`: human-readable status
   - `project`: name, slug, environment, last_seen_at
   - `auth`: api_key_prefix, api_key_name, api_key_last_used_at, rate_limit
   - `config`: is_active, retention_days
   - `dalat_freshness`: per-type latest timestamps
   - `data_volumes`: per-type record counts
   - `sync_check`: server_time, server_timezone, recommendation

2. **A `last_seen_at` column on `projects`** — updated on every successful API call

3. **Project model** casting `last_seen_at` as `datetime`

---

## Summary

| File | Action |
|------|--------|
| `src/Commands/PingCommand.php` | **New** — `appswatch:ping` command with SSL error detection |
| `src/AppswatchServiceProvider.php` | **Modified** — registered `PingCommand` |
| `src/Transport/HttpTransport.php` | **Modified** — added `verify` option from config |
| `config/appswatch.php` | **Modified** — added `verify_ssl` config option |

## Quick Fix for SSL Certificate Errors

If a client gets `cURL error 60: SSL certificate problem`, add to the client's `.env`:

```
APPSWATCH_VERIFY_SSL=false
```

Then run:
```bash
php artisan config:clear
php artisan appswatch:ping
```

⚠️ Only disable SSL verification in dev/staging. Fix the certificate for production.