# Appswatch — Self-Hosted Laravel Monitoring Platform

> Implementation plan & feature comparison across Flare, Sentry, Telescope, CloudWatch, Blackfire, Nightwatch, Prometheus, and Grafana.

---

## 1. Platform Feature Comparison Matrix

| Feature | Flare | Sentry | Laravel Telescope | AWS CloudWatch | Blackfire | Nightwatch (Laravel) | Prometheus | Grafana | **Our Target** |
|---|---|---|---|---|---|---|---|---|---|
| **Exception Tracking** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Stack Trace w/ Context** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Log Aggregation** | ✅ (limited) | ✅ (breadcrumbs) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Queue Job Monitoring** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Scheduled Task Monitoring** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **HTTP Request/Response Logging** | ❌ | ✅ (breadcrumbs) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Database Query Profiling** | ❌ | ✅ (perf) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Performance Tracing** | ❌ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **CPU/Memory Profiling** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ (node) | ❌ | ✅ |
| **Custom Metrics** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ |
| **Alerting / Notifications** | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ✅ (Alertmanager) | ✅ | ✅ |
| **Real-Time Dashboard** | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Multi-App / Multi-Project** | ✅ | ✅ | ❌ (1 per app) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **OpenTelemetry Support** | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ (future) |
| **Error Grouping / Fingerprinting** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Source Map Support** | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ (PHP, not needed) |
| **Rate Limiting / Throttling** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Data Retention Policies** | ✅ | ✅ | ❌ (manual) | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ |
| **API / Webhook Integrations** | ✅ | ✅ | ❌ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ |
| **Self-Hosted** | ❌ | ✅ (OSS) | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **Laravel-Native Integration** | ✅ | ❌ (generic) | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ |
| **Release / Deployment Tracking** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Spam / Bot Detection in Errors** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (future) |
| **Dark Mode** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ (themes) | ✅ |
| **Google Analytics Integration** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (plugin) | ✅ |
| **Google Search Console Integration** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (plugin) | ✅ |
| **Cloudflare Analytics Integration** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Microsoft Clarity Integration** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Stripe / Payment Monitoring** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (plugin) | ✅ |
| **GitHub / GitLab Commit Tracking** | ✅ (release) | ✅ (release) | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (plugin) | ✅ |
| **GitLab CI/CD Integration** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Database Backup & Rotation** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Uptime / Health Check Monitoring** | ❌ | ✅ (cron) | ❌ | ❌ | ❌ | ❌ | ✅ (blackbox) | ✅ | ✅ |
| **Server Resource Monitoring** | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| **SSL Certificate Expiry Monitoring** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (blackbox) | ✅ | ✅ |
| **DNS / Domain Expiry Monitoring** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ (blackbox) | ✅ | ✅ |
| **Mail / SMTP Delivery Monitoring** | ❌ | ❌ | ✅ (mail) | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Horizon / Redis Queue Monitoring** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ✅ (plugin) | ✅ |
| **MySQL Server Health Monitoring** | ❌ | ❌ | ❌ | ✅ (RDS) | ❌ | ❌ | ✅ (exporter) | ✅ | ✅ |

### Key Takeaways from the Comparison

1. **No single platform covers all features.** Flare + Telescope + Sentry together cover ~80%, but nothing touches queue jobs + scheduled tasks the way Telescope does.
2. **Telescope is the closest Laravel-native reference** but lacks multi-app support, alerting, and error grouping.
3. **Flare has the best Laravel error UI**, but is SaaS-only and lacks queue/log aggregation.
4. **Prometheus + Grafana** are excellent for infrastructure metrics but weak on application-level tracing (exceptions, queries, queues).
5. **Our system will combine the best of all**: Telescope's depth + Flare's error UX + Sentry's grouping + Prometheus-like custom metrics, all self-hosted and multi-app.
6. **Third-party integrations are a key differentiator**: Pulling Google Analytics, Search Console, Cloudflare, Stripe, uptime, SSL, and server metrics into one dashboard prevents tab-hopping and gives full-picture observability.

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      APPSWATCH DASHBOARD (Central)                      │
│  ┌───────────┐  ┌──────────┐  ┌──────────┐  ┌───────────────┐          │
│  │ Exceptions│  │  Logs    │  │  Queues  │  │  Performance  │          │
│  │ Dashboard │  │ Viewer   │  │ Monitor  │  │  Tracing      │          │
│  └───────────┘  └──────────┘  └──────────┘  └───────────────┘          │
│  ┌───────────┐  ┌──────────┐  ┌──────────┐  ┌───────────────┐          │
│  │ Schedules │  │  HTTP     │  │  DB      │  │  Alerts /     │          │
│  │ Monitor   │  │ Requests  │  │ Queries  │  │  Notifications│          │
│  └───────────┘  └──────────┘  └──────────┘  └───────────────┘          │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                      Ingestion API (REST)                         │  │
│  │  POST /api/ingest/exceptions    POST /api/ingest/logs             │  │
│  │  POST /api/ingest/queues        POST /api/ingest/queries          │  │
│  │  POST /api/ingest/requests      POST /api/ingest/metrics          │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │                    Storage Layer (MySQL 8)                        │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                              ▲                ▲
                              │                │
                 ┌────────────┘                └────────────┐
                 │                                          │
     ┌───────────────────────┐              ┌───────────────────────┐
│   Laravel App #1      │              │   Laravel App #N      │
│   ┌────────────────┐  │              │   ┌────────────────┐  │
│   │   appswatch    │  │              │   │   appswatch    │  │
│   │   package      │  │              │   │   package      │  │
│   └────────────────┘  │              │   └────────────────┘  │
     └───────────────────────┘              └───────────────────────┘
```

### Architecture Principles

1. **Push model**: Client apps push data to the central server via REST API.
2. **Async by default**: All ingestion is queued on the client side to never block the app.
3. **Batched ingestion**: Data is sent in batches at configurable intervals to reduce HTTP overhead.
4. **Project/App isolation**: Each app gets a unique API key and project ID. Data is fully isolated.
5. **Fail-safe**: If the central server is down, data is buffered locally (database/cache) and retried.

---

## 3. Technology Stack

| Layer | Technology | Rationale |
|---|---|---|
| **Dashboard** | Laravel 11+ | Consistent ecosystem |
| **Client Package** | Laravel Package (PHP 8.2+) | Composer-installable, zero-config for Laravel apps |
| **Database** | MySQL 8 | Solid relational database, widely supported |
| **Queue (Central)** | Laravel Horizon (Redis) | Process ingested data async + queue monitoring UI |
| **Frontend** | Vue 3 + Inertia.js + Tailwind CSS | Modern SPA-like UX, server-rendered |
| **UI/UX Design** | uiuxpromax skill | Design system, component patterns, layout decisions, accessibility |
| **Auth (Dashboard)** | Laravel Breeze | Simple login/register/password-reset |
| **Charts / Graphs** | Chart.js / ApexCharts | Lightweight, self-hosted |
| **WebSocket (Real-time)** | Laravel Reverb | Real-time updates on dashboard |
| **Search** | Meilisearch (optional, future) | Full-text search on exceptions/logs |
| **Containerization** | Docker (for deployment) | Easy self-hosting |

---

## 4. Directory Structure (Monorepo)

```
sentry/
├── dashboard/                     # Laravel application (central server)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   └── Ingestion/   # Ingestion controllers
│   │   │   │   │       ├── ExceptionController.php
│   │   │   │   │       ├── LogController.php
│   │   │   │   │       ├── QueueController.php
│   │   │   │   │       ├── QueryController.php
│   │   │   │   │       ├── RequestController.php
│   │   │   │   │       └── MetricController.php
│   │   │   │   └── Web/             # Dashboard UI controllers
│   │   │   ├── Middleware/
│   │   │   │   └── VerifyApiKey.php
│   │   │   └── Resources/
│   │   ├── Models/
│   │   │   ├── Project.php
│   │   │   ├── Exception.php
│   │   │   ├── LogEntry.php
│   │   │   ├── QueueJob.php
│   │   │   ├── DatabaseQuery.php
│   │   │   ├── HttpRequest.php
│   │   │   ├── Metric.php
│   │   │   ├── IntegrationMetric.php
│   │   │   ├── UptimeCheck.php
│   │   │   └── Alert.php
│   │   ├── Services/
│   │   │   ├── ExceptionFingerprinter.php
│   │   │   ├── AlertService.php
│   │   │   └── RetentionService.php
│   │   ├── Integrations/
│   │   │   ├── GoogleAnalyticsService.php
│   │   │   ├── GoogleSearchConsoleService.php
│   │   │   ├── CloudflareService.php
│   │   │   ├── MicrosoftClarityService.php
│   │   │   ├── StripeService.php
│   │   │   ├── GitHubService.php
│   │   │   ├── UptimeService.php
│   │   │   ├── ServerMonitorService.php
│   │   │   ├── SslCheckService.php
│   │   │   ├── DomainExpiryService.php
│   │   │   └── MailProviderService.php
│   │   └── Jobs/
│   │       ├── ProcessException.php
│   │       ├── ProcessBatchLogs.php
│   │       ├── SendAlertNotification.php
│   │       └── PollIntegrationMetric.php
│   ├── database/
│   │   └── migrations/
│   ├── resources/
│   │   └── js/                      # Vue 3 + Inertia frontend
│   └── routes/
│       ├── api.php                  # Ingestion API routes
│       └── web.php                  # Dashboard routes
│
├── packages/
│   └── appswatch/                   # Composer package for client apps
│       ├── src/
│       │   ├── AppswatchServiceProvider.php
│       │   ├── Appswatch.php        # Facade
│       │   ├── Collectors/
│       │   │   ├── ExceptionCollector.php
│       │   │   ├── LogCollector.php
│       │   │   ├── QueueCollector.php
│       │   │   ├── QueryCollector.php
│       │   │   ├── RequestCollector.php
│       │   │   ├── ScheduleCollector.php
│       │   │   ├── MySqlHealthCollector.php
│       │   │   └── MetricCollector.php
│       │   ├── Transport/
│       │   │   ├── HttpTransport.php
│       │   │   ├── AsyncTransport.php
│       │   │   └── BufferedTransport.php
│       │   ├── Middleware/
│       │   │   ├── AppswatchRequestMiddleware.php
│       │   │   └── AppswatchJobMiddleware.php
│       │   ├── Listeners/
│       │   │   ├── ExceptionListener.php
│       │   │   ├── QueryListener.php
│       │   │   └── ScheduleListener.php
│       │   └── Config/
│       │       └── appswatch.php    # Config stub for publishing
│       ├── config/
│       │   └── appswatch.php
│       ├── database/
│       │   └── migrations/          # Local buffer table (for resilience)
│       └── composer.json
│
├── docker/
│   ├── Dockerfile
│   └── docker-compose.yml
│
├── docs/
│   ├── installation.md
│   ├── configuration.md
│   ├── api-reference.md
│   └── architecture.md
│
├── PLAN.md                         # This file
└── README.md
```

---

## 5. Data Model (Core Tables — Central Server)

### 5.1 Projects
```
projects
├── id (uuid)
├── name
├── api_key (hashed in DB, plain shown once)
├── environment (production / staging / local)
├── slug
├── retention_days (default: 30)
├── rate_limit (requests per minute)
├── is_active
├── metadata (JSON) — framework version, PHP version, etc.
└── timestamps
```

### 5.2 Exceptions
```
exceptions
├── id (uuid)
├── project_id (FK)
├── fingerprint (indexed) — hash of class + file + line pattern for grouping
├── class
├── message
├── file
├── line
├── code_snippet (JSON) — surrounding lines
├── stack_trace (JSON / TEXT)
├── request_data (JSON) — URL, method, headers, body, IP
├── user_data (JSON) — authenticated user ID/email if available
├── breadcrumbs (JSON) — logs, queries, requests leading up to crash
├── environment (string)
├── release (string) — git commit hash or version
├── severity (enum: debug/info/warning/error/critical)
├── status (enum: unresolved/resolved/ignored/muted)
├── occurrence_count (int, updated on each duplicate)
├── first_seen_at
├── last_seen_at
└── timestamps

Indexes: (project_id, fingerprint), (project_id, status), (project_id, last_seen_at DESC)
```

### 5.3 Log Entries
```
log_entries
├── id (uuid)
├── project_id (FK)
├── batch_id (uuid) — groups logs from the same request
├── level (enum: debug/info/warning/error/critical)
├── message
├── context (JSON)
├── channel
├── file
├── line
├── trace_id (nullable) — for linking to a request
├── occurred_at
└── timestamps

Indexes: (project_id, level, occurred_at DESC), (project_id, batch_id)
```

### 5.4 Queue Jobs
```
queue_jobs
├── id (uuid)
├── project_id (FK)
├── connection
├── queue
├── job_name (class name)
├── payload (JSON)
├── attempt
├── max_attempts
├── status (enum: pending/processing/completed/failed)
├── exception_id (FK, nullable) — link to exception if failed
├── queued_at
├── started_at
├── finished_at
├── duration_ms
└── timestamps

Indexes: (project_id, queue, status), (project_id, job_name)
```

### 5.5 Database Queries
```
database_queries
├── id (uuid)
├── project_id (FK)
├── batch_id (uuid) — groups queries from same request
├── sql (TEXT) — parameterized
├── bindings (JSON)
├── duration_ms
├── connection_name
├── file
├── line
├── is_slow (boolean) — flagged if exceeds threshold
├── trace_id (nullable)
├── occurred_at
└── timestamps

Indexes: (project_id, occurred_at DESC), (project_id, is_slow), (project_id, batch_id)
```

### 5.6 HTTP Requests
```
http_requests
├── id (uuid)
├── project_id (FK)
├── trace_id (uuid) — unique ID for linking logs + queries
├── method
├── url
├── route_name
├── controller_action
├── status_code
├── duration_ms
├── memory_usage_mb
├── request_headers (JSON)
├── request_body (JSON, nullable)
├── response_headers (JSON)
├── response_body (JSON, nullable, truncated)
├── ip_address
├── user_agent
├── user_id (nullable)
├── occurred_at
└── timestamps

Indexes: (project_id, occurred_at DESC), (project_id, route_name), (trace_id)
```

### 5.7 Scheduled Tasks
```
scheduled_tasks
├── id (uuid)
├── project_id (FK)
├── command (string)
├── description
├── expression (cron)
├── status (enum: started/completed/failed/skipped)
├── exception_id (FK, nullable)
├── output (TEXT, nullable, truncated)
├── duration_ms
├── started_at
├── finished_at
└── timestamps
```

### 5.8 Custom Metrics
```
metrics
├── id (uuid)
├── project_id (FK)
├── name
├── value (float)
├── unit (string, nullable)
├── tags (JSON) — { "endpoint": "/api/users", "region": "us-east" }
├── type (enum: gauge/counter/histogram)
├── recorded_at
└── timestamps
```

### 5.9 Alerts & Notifications
```
alerts
├── id (uuid)
├── project_id (FK)
├── name
├── type (enum: exception_rate/log_level/queue_failure/query_slow/metric_threshold)
├── conditions (JSON)
├── channels (JSON) — [ "mail", "slack", "webhook" ]
├── cooldown_minutes
├── is_active
├── last_triggered_at (nullable)
└── timestamps
```

### 5.10 API Keys (Internal, for client apps)
```
api_keys
├── id (uuid)
├── project_id (FK)
├── key (hashed, bcrypt)
├── key_prefix (first 8 chars, for UI display)
├── name (e.g., "Production Server 1")
├── last_used_at
└── timestamps
```

---

## 6. Client Package (`baklysystems/appswatch`) Design

### 6.1 Installation (Target UX)

```bash
composer require baklysystems/appswatch
php artisan appswatch:install
```

The install command prompts for:
- Central server URL
- API Key
- Project/Environment name

### 6.2 Auto-Discovery (Zero Config)

The package auto-registers:
- **Exception handler** → hooks into `report()` and `render()`
- **Log listener** → taps into Laravel's log channels via custom Monolog handler
- **Query listener** → `DB::listen()`
- **Queue listener** → hooks into `JobProcessing`, `JobProcessed`, `JobFailed` events
- **Schedule listener** → hooks into `ScheduledTaskStarting`, `ScheduledTaskFinished`, `ScheduledTaskFailed`
- **HTTP middleware** → wraps every request, captures request/response data
- **Octane support** → resets state between requests

### 6.3 Configuration File (`config/appswatch.php`)

```php
return [
    'server_url' => env('APPSWATCH_SERVER_URL', 'http://appswatch.local'),
    'api_key' => env('APPSWATCH_API_KEY'),
    'environment' => env('APPSWATCH_ENVIRONMENT', env('APP_ENV')),
    'release' => env('APPSWATCH_RELEASE'),

    'enabled' => env('APPSWATCH_ENABLED', true),

    'sampling' => [
        'requests' => env('APPSWATCH_SAMPLE_REQUESTS', 1.0), // 1.0 = 100%
        'queries' => env('APPSWATCH_SAMPLE_QUERIES', 1.0),
        'exceptions' => env('APPSWATCH_SAMPLE_EXCEPTIONS', 1.0),
    ],

    'capture' => [
        'exceptions' => true,
        'logs' => true,
        'queries' => true,
        'queue_jobs' => true,
        'scheduled_tasks' => true,
        'http_requests' => true,
        'breadcrumbs' => true,
    ],

    'transport' => [
        'driver' => env('APPSWATCH_TRANSPORT', 'async'), // sync | async | buffered
        'batch_size' => 50,
        'flush_interval_seconds' => 5,
        'retry_attempts' => 3,
        'retry_delay_seconds' => 10,
    ],

    'privacy' => [
        'mask_request_fields' => ['password', 'password_confirmation', 'token', 'secret'],
        'mask_response_fields' => [],
        'max_request_body_kb' => 64,
        'max_response_body_kb' => 64,
        'max_stack_trace_frames' => 50,
    ],

    'query' => [
        'slow_threshold_ms' => 100,
        'log_bindings' => false,
    ],

    'breadcrumbs' => [
        'max_count' => 100,
    ],
];
```

### 6.4 Transport Strategies

| Strategy | Behavior | Use Case |
|---|---|---|
| **sync** | Sends immediately, blocks request | Low-traffic / debugging |
| **async** | Dispatches to queue, non-blocking | **Default**, production use |
| **buffered** | Accumulates data in local buffer table, flushes periodically | No queue configured, fallback |

### 6.5 Buffer Table (Fallback Resilience)

```php
// Migration in the package
Schema::create('appswatch_buffer', function (Blueprint $table) {
    $table->id();
    $table->string('endpoint');        // e.g., 'exceptions', 'logs'
    $table->json('payload');
    $table->integer('attempts')->default(0);
    $table->timestamp('last_attempt_at')->nullable();
    $table->timestamps();
});
```

A scheduled command `appswatch:flush-buffer` retries failed deliveries.

---

## 7. Ingestion API Design

All endpoints follow a consistent pattern:

```
POST /api/ingest/{resource}
Authorization: Bearer {api_key}
Content-Type: application/json
X-Appswatch-Project: {project-slug}

Body: { "batch": [ ...items... ] }
```

### 7.1 Endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/ingest/exceptions` | Batch exceptions |
| POST | `/api/ingest/logs` | Batch log entries |
| POST | `/api/ingest/queues` | Batch queue job records |
| POST | `/api/ingest/queries` | Batch database queries |
| POST | `/api/ingest/requests` | Batch HTTP request records |
| POST | `/api/ingest/schedules` | Batch scheduled task records |
| POST | `/api/ingest/metrics` | Batch custom metrics |

### 7.2 Response Format

```json
{
  "status": "ok",
  "ingested": 45,
  "rejected": 0,
  "errors": []
}
```

### 7.3 Authentication & Rate Limiting

- API key validated via `VerifyApiKey` middleware
- Rate limit: configurable per project (default: 600 req/min)
- When rate limit exceeded: HTTP 429 with `Retry-After` header
- Server validates project exists and is active

---

## 8. Dashboard UI Pages

### 8.1 Project Overview (Home)
- Cards: total exceptions, log volume, avg response time, queue failures (last 24h)
- Mini charts: exception trend, request volume trend
- Recent activity feed
- Project selector (if managing multiple)

### 8.2 Exceptions
- List grouped by fingerprint, sorted by last seen
- Filter: status, severity, date range, search
- Detail view:
  - Stack trace with syntax-highlighted code snippets
  - Breadcrumbs timeline
  - Request context
  - Occurrence history graph
  - Actions: resolve, ignore, mute, delete
- "Similar exceptions" suggestions

### 8.3 Logs
- Real-time log tail (WebSocket)
- Filter: level, channel, date range, search
- Grouped by batch (request context)
- Click a log → see surrounding logs + associated request

### 8.4 Queue Monitor
- Table of jobs grouped by queue name
- Filter: status, queue, date range
- Job detail: payload, attempts, duration, linked exception
- Metrics: throughput, avg duration, failure rate per queue

### 8.5 Performance
- Database query table sorted by slowest
- N+1 detection (heuristic)
- HTTP request table with duration + memory
- Route performance rankings

### 8.6 Scheduled Tasks
- Timeline of task executions
- Filter: command, status
- Detail: output, duration, linked exception

### 8.7 Custom Metrics
- Line/bar charts from custom metrics
- Group by tags
- Time range selector

### 8.8 Alerts
- Create/edit alert rules
- Alert history log
- Notification channel config (Mail, Slack webhook, Discord webhook, generic webhook)

### 8.9 Settings
- Project settings (name, retention, rate limit)
- API key management
- Team members (future)
- Data export

---

## 9. Alerting System

### 9.1 Alert Types

| Type | Condition Example |
|---|---|
| `exception_rate` | > 10 new exceptions in 5 minutes |
| `exception_reopened` | A resolved exception occurs again |
| `log_level` | Any `critical` or `emergency` log |
| `queue_failure` | Any failed job |
| `query_slow` | Query > 500ms |
| `metric_threshold` | `cpu_usage` > 90 |
| `mysql_connection_saturation` | Active connections > 80% of max_connections |
| `mysql_replication_lag` | Replication lag > 10 seconds |
| `mysql_slow_query_rate` | > 5 slow queries per minute |
| `backup_failed` | Any database backup failure |
| `backup_stale` | No successful backup in > 2 days |

### 9.2 Notification Channels

- Email (Laravel Mail)
- Slack (incoming webhook)
- Discord (webhook)
- Generic webhook (JSON POST to custom URL)

### 9.3 Cooldown

- Prevents alert spam
- Configurable per alert rule (default: 5 minutes)

---

## 10. Third-Party Integrations

Appswatch pulls data from external services via scheduled polling (or webhooks where available) and stores it alongside application data, creating a single-pane-of-glass observability experience.

### 10.1 Integration Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                     APPSWATCH Central Server                      │
│                                                                   │
│  ┌───────────────────────┐    ┌───────────────────────────────┐  │
│  │  Integration Scheduler │    │  Integration Data Store        │  │
│  │  (Laravel Scheduled    │───▶│  integration_metrics table     │  │
│  │   Commands)            │    │  (polymorphic, multi-service)  │  │
│  └───────────────────────┘    └───────────────────────────────┘  │
│           │                                                       │
│           │  Polls external APIs every N minutes                  │
│           ▼                                                       │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │  External APIs:  GA4 │ GSC │ Cloudflare │ Clarity │ Stripe  │ │
│  │                  GitHub │ Uptime │ SSL │ DNS │ Server SSH   │ │
│  └─────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

All integrations are **pull-based** (scheduled commands) by default. Webhook-based integrations (Stripe, GitHub) receive events in real-time via dedicated webhook endpoints.

### 10.2 Supported Integrations

#### Analytics & SEO

| Integration | Data Pulled | API | Schedule | Value |
|---|---|---|---|---|
| **Google Analytics 4** | Page views, sessions, bounce rate, top pages, real-time users, events, conversions | GA4 Data API | Every 60 min | See traffic trends alongside error spikes |
| **Google Search Console** | Search queries, clicks, impressions, CTR, average position, crawl errors, index coverage | Search Console API | Every 6 hours | Monitor SEO health alongside app health |
| **Microsoft Clarity** | Heatmaps, session recordings, rage clicks, dead clicks, JS errors | Clarity API | Every 60 min | Correlate UX issues with backend errors |
| **Cloudflare Analytics** | Requests, bandwidth, threats blocked, cache hit ratio, unique visitors, WAF events | Cloudflare GraphQL API | Every 15 min | CDN/security metrics alongside app metrics |

#### Payments & Business

| Integration | Data Pulled | API | Schedule | Value |
|---|---|---|---|---|
| **Stripe** | Revenue (MRR/ARR), successful/failed charges, refunds, disputes, subscription churn | Stripe API + Webhooks | Every 30 min + real-time webhooks | Monitor payment health, catch revenue drops early |
| **Paddle** (alternative) | Same as Stripe for SaaS billing | Paddle API | Every 30 min | Digital product sellers |

#### Infrastructure & Uptime

| Integration | Data Pulled | Method | Schedule | Value |
|---|---|---|---|---|
| **Uptime Monitor** | HTTP/S ping, status codes, response time, SSL expiry days, DNS expiry | Built-in HTTP client | Every 1-5 min | Built-in health checks for all projects |
| **Server Metrics** | CPU %, memory %, disk %, load average, network I/O | SSH + `vmstat`/`free` or agent script | Every 5 min | Infrastructure monitoring without Prometheus |
| **MySQL Server Health** | Active/idle/max connections, slow queries count, buffer pool hit rate, InnoDB I/O, QPS, replication lag, table sizes, uptime | Collected by the client package via `SHOW STATUS` / `SHOW VARIABLES` (no SSH — the app already has DB access) | Every 5 min | Database server health alongside app-level query profiling |
| **SSL Certificate** | Expiry date, issuer, SANs, chain validity | Built-in TLS check | Every 24 hours | Prevent certificate expirations |
| **Domain Expiry** | WHOIS expiry date, registrar, nameservers | WHOIS lookup | Every 24 hours | Prevent domain expiration surprises |
| **Database Backups** | Backup status, size, duration, retention compliance, success/failure tracking | Central server runs `mysqldump` / `pg_dump` via SSH to project servers, or receives backup reports from the client package | Daily + on-demand | Know backup health without checking each server manually |

#### Development & Deployment

| Integration | Data Pulled | API | Schedule | Value |
|---|---|---|---|---|
| **GitHub** | Commits, pull requests, deployments, releases, workflow runs (GitHub Actions status) | GitHub API + Webhooks | Webhook real-time | Link deployments to error spikes, track releases |
| **GitLab** | Commits, merge requests, deployments, releases, pipeline status (GitLab CI), environments | GitLab API + Webhooks | Webhook real-time | Full GitLab ecosystem monitoring alongside app health |
| **Sentry.io** (optional bridge) | If already using Sentry, pull its data for migration or co-existence | Sentry API | Every 15 min | Gradual migration path |

#### Email & Communication

| Integration | Data Pulled | API | Schedule | Value |
|---|---|---|---|---|
| **Mailgun / Postmark / SES** | Delivery rate, bounces, complaints, opens, clicks | Provider API + Webhooks | Every 30 min + webhooks | Monitor email deliverability |
| **Slack / Discord** (notification target) | Send alerts to channels | Incoming Webhook | Event-driven | Alert notifications |

### 10.3 Integration Configuration (per Project)

Each project can enable/disable integrations and configure credentials:

```php
// Stored in projects.integrations_config (JSON column)
{
  "google_analytics": {
    "enabled": true,
    "property_id": "123456789",
    "credentials_json": "encrypted-service-account-key"
  },
  "google_search_console": {
    "enabled": true,
    "site_url": "https://example.com",
    "credentials_json": "encrypted-service-account-key"
  },
  "cloudflare": {
    "enabled": true,
    "zone_id": "abc123",
    "api_token": "encrypted-token"
  },
  "clarity": {
    "enabled": true,
    "project_id": "abc123",
    "api_token": "encrypted-token"
  },
  "stripe": {
    "enabled": true,
    "secret_key": "encrypted-key",
    "webhook_secret": "encrypted-secret"
  },
  "uptime": {
    "enabled": true,
    "urls": ["https://example.com", "https://api.example.com/health"],
    "interval_seconds": 60
  },
  "server_monitor": {
    "enabled": true,
    "hosts": [
      { "host": "192.168.1.10", "ssh_user": "deploy", "ssh_key": "encrypted-key" }
    ],
    "interval_seconds": 300
  },
  "mysql_monitor": {
    "enabled": true,
    "collect_interval_seconds": 300
  },
  "ssl_monitor": {
    "enabled": true,
    "domains": ["example.com", "api.example.com"]
  },
  "domain_monitor": {
    "enabled": true,
    "domains": ["example.com"]
  },
  "github": {
    "enabled": true,
    "repo": "owner/repo",
    "token": "encrypted-token"
  },
  "gitlab": {
    "enabled": true,
    "host": "https://gitlab.com", // or self-hosted URL
    "project_id": "12345",
    "token": "encrypted-token"
  },
  "db_backups": {
    "enabled": true,
    "servers": [
      {
        "host": "192.168.1.10",
        "ssh_user": "deploy",
        "ssh_key": "encrypted-key",
        "database": "my_app",
        "db_type": "mysql", // mysql | pgsql
        "retention_days": 30,
        "schedule": "0 3 * * *" // daily at 3am
      }
    ],
    "storage_path": "/backups/databases"
  },
  "mail_provider": {
    "enabled": true,
    "provider": "mailgun", // mailgun | postmark | ses
    "domain": "mg.example.com",
    "api_key": "encrypted-key"
  }
}
```

### 10.4 Data Model — Integration Metrics Table

```
integration_metrics
├── id (uuid)
├── project_id (FK)
├── integration (string) — google_analytics, stripe, cloudflare, etc.
├── metric_name (string) — e.g., 'page_views', 'revenue', 'requests'
├── metric_value (float)
├── unit (string, nullable) — 'count', 'ms', 'gb', '%', 'usd'
├── dimensions (JSON) — e.g., { "page": "/home", "country": "US" }
├── recorded_at (timestamp) — when the metric was captured
└── timestamps

Indexes: (project_id, integration, metric_name, recorded_at DESC)
```

### 10.5 Built-in Health Checks (Uptime)

The central server runs its own health check scheduler:

- **HTTP checks**: GET/POST to a defined URL, validate status code and response time
- **SSL checks**: Verify certificate expiry, chain validity
- **DNS checks**: WHOIS lookup, domain expiry warning
- **Alerting**: Triggers alerts on downtime, SSL expiring within 7/14/30 days, domain expiring within 30/60/90 days
- **Status page** (future): Generate a public status page per project

### 10.6 Dashboard Integration Pages (New)

#### 10.6.1 Analytics Overview
- Combined GA4 + GSC + Clarity metrics on one page
- Traffic vs Error Rate overlay chart
- Top pages by traffic with error counts per page
- Google Search Console: top queries, clicks, position alongside page performance

#### 10.6.2 Business Metrics
- Stripe revenue charts (MRR, ARR)
- Charge success rate, refund rate, dispute count
- Subscription churn tracking
- Revenue vs error correlation (did a deploy that caused errors also drop revenue?)

#### 10.6.3 Infrastructure
- Server CPU/Memory/Disk usage gauges per host
- MySQL connection gauge, buffer pool hit rate, slow queries trend, replication lag
- Cloudflare CDN metrics (requests, bandwidth, cache ratio, threats)
- Uptime % with outage timeline
- SSL certificate countdown widgets
- Domain expiry countdown widgets

#### 10.6.4 Deployments
- GitHub/GitLab deployment timeline
- Deployment markers on exception/performance charts
- CI/CD status (passing/failing workflows) for both GitHub Actions and GitLab CI
- Quick correlation: "Deploy at 14:32 → Error spike at 14:35"

#### 10.6.5 Database Backups
- Backup history per server: date, size, duration, status
- Retention policy status (which backups are kept vs rotated out)
- Next scheduled backup countdown
- Disk usage of backup storage
- Backup success rate over time
- One-click "Backup Now" trigger

### 10.7 Integration Schedule (Commands)

| Command | Frequency | Description |
|---|---|---|
| `appswatch:integrations:poll-ga4` | Every 60 min | Fetch GA4 metrics for all projects |
| `appswatch:integrations:poll-gsc` | Every 6 hours | Fetch GSC data |
| `appswatch:integrations:poll-cloudflare` | Every 15 min | Fetch Cloudflare analytics |
| `appswatch:integrations:poll-clarity` | Every 60 min | Fetch Clarity metrics |
| `appswatch:integrations:poll-stripe` | Every 30 min | Fetch Stripe metrics |
| `appswatch:integrations:poll-servers` | Every 5 min | SSH into servers, collect CPU/mem/disk |
| `appswatch:integrations:poll-mysql` | Every 5 min | Pull MySQL health via client package (SHOW STATUS / SHOW VARIABLES — no SSH needed) |
| `appswatch:integrations:check-uptime` | Every 1 min | HTTP health checks |
| `appswatch:integrations:check-ssl` | Every 24 hours | SSL certificate checks |
| `appswatch:integrations:check-domains` | Every 24 hours | WHOIS domain expiry checks |
| `appswatch:integrations:poll-github` | Event-driven | Webhook receiver |
| `appswatch:integrations:poll-gitlab` | Event-driven | Webhook receiver (GitLab CI pipeline events) |
| `appswatch:integrations:poll-mailgun` | Event-driven | Webhook + every 30 min poll |
| `appswatch:integrations:run-backups` | Daily (cron) | Execute database backups via SSH to configured servers |
| `appswatch:integrations:rotate-backups` | Daily | Delete backups older than retention_days per server |

### 10.8 Smart Additions — Phase Priority

| Tier | Integrations | Phase |
|---|---|---|
| **Tier 1 (MVP)** | Uptime, SSL, Domain monitoring, Server metrics (built-in, no external APIs needed) | Phase 3 |
| **Tier 2 (High Value)** | Google Analytics, Cloudflare, Stripe, GitHub | Phase 4 |
| **Tier 3 (Analytics Depth)** | Google Search Console, Microsoft Clarity, Mailgun | Phase 5 |
| **Tier 1.5 (Operations)** | GitLab CI, Database backups & rotation | Phase 4 |
| **Tier 4 (Ecosystem)** | Sentry bridge, Paddle, PostgreSQL metrics, custom webhooks | Phase 5+ |

---

## 11. Data Retention & Cleanup

- Configurable per project (default: 30 days)
- Scheduled command: `appswatch:cleanup` — hard-deletes records older than retention
- Exceptions marked as `resolved` are auto-deleted after their retention period
- **No S3 archival** — simple deletion only for early phases
- MySQL partitioning on `occurred_at` / `created_at` columns for efficient bulk deletes
- Retention periods stored per-project, allowing different projects to have different windows

---

## 12. Implementation Phases

### Phase 1 — Foundation (Week 1-2)

- [x] Scaffold `dashboard/` Laravel application (with Laravel Breeze auth)
- [ ] Scaffold `packages/appswatch/` Composer package
- [ ] Database migrations for all core tables
- [ ] Ingestion API endpoints (all 7 resources)
- [ ] API key authentication + rate limiting
- [ ] Basic exception collection + display (MVP)

### Phase 2 — Core Collection (Week 3-4)

- [ ] Exception collector in package (auto-capture, fingerprinting)
- [ ] Log collector (Monolog handler)
- [ ] HTTP request collector (middleware)
- [ ] Queue collector (event listeners)
- [ ] Query collector (DB listener)
- [ ] Schedule collector (event listeners)
- [ ] Transport layer (sync + async + buffered)

### Phase 3 — Dashboard UI + Tier 1 Integrations (Week 5-7)

- [ ] **Use uiuxpromax skill** for design system, layout, component patterns, and UX decisions
- [ ] Vue 3 + Inertia.js setup with Tailwind
- [ ] Project selector + onboarding
- [ ] Exceptions page (list + detail + grouping)
- [ ] Logs page (real-time tail)
- [ ] Queue monitor page
- [ ] Performance pages (queries + requests)
- [ ] Scheduled tasks page
- [ ] Custom metrics page
- [ ] Settings page (project management, API keys)
- [ ] **Integration: Uptime monitoring** (built-in HTTP checks, status page)
- [ ] **Integration: SSL certificate monitoring**
- [ ] **Integration: Domain expiry monitoring**
- [ ] **Integration: Server resource monitoring** (SSH-based CPU/mem/disk)
- [ ] **Integration: MySQL health monitoring** (via package — connections, buffer pool, slow queries, QPS, replication lag)

### Phase 4 — Advanced Features + Tier 2 Integrations (Week 8-9)

- [ ] **Use uiuxpromax skill** for alert configuration UX, dark mode design, and integration dashboard pages
- [ ] Alerting system (rules + channels + cooldown)
- [ ] Breadcrumbs collection
- [ ] Release tracking
- [ ] Exception similarity detection (basic)
- [ ] Data export (CSV/JSON)
- [ ] Dark mode toggle
- [ ] **Integration: Google Analytics 4** (traffic metrics, pages, real-time)
- [ ] **Integration: Cloudflare Analytics** (CDN, security, cache metrics)
- [ ] **Integration: Stripe** (revenue, charges, disputes, churn)
- [ ] **Integration: GitLab** (commits, MRs, pipelines, deployments, CI status)
- [ ] **Integration: Database backups & rotation** (scheduled via SSH, retention enforcement, alerting)

### Phase 5 — Polish + Tier 3 Integrations (Week 10+)

- [ ] Docker Compose setup for one-command deployment
- [ ] Documentation (installation, configuration, API reference)
- [ ] Performance optimization (indexing, caching, pagination)
- [ ] WebSocket real-time updates (Laravel Reverb)
- [ ] Data retention / cleanup command
- [ ] Health check endpoint
- [ ] Testing suite (pest)
- [ ] **Integration: Google Search Console** (SEO metrics)
- [ ] **Integration: Microsoft Clarity** (UX analytics)
- [ ] **Integration: Mailgun/Postmark** (email deliverability)
- [ ] **Integration: Deployment markers** on all charts

---

## 13. Final Decisions (All Resolved)

| # | Decision | Choice |
|---|---|---|
| 1 | **Database** | MySQL 8 |
| 2 | **Queue Worker** | Laravel Horizon (Redis) on central server |
| 3 | **Data Retention** | Hard-delete after retention period (no S3 archival) |
| 4 | **OpenTelemetry** | Deferred to Phase 4+ |
| 5 | **Package Name** | `baklysystems/appswatch` |
| 6 | **Dashboard Auth** | Laravel Breeze (simple login/register) |
| 7 | **Third-Party Integrations** | GA4, GSC, Cloudflare, Clarity, Stripe, GitHub, GitLab CI, uptime, SSL, domains, server metrics, MySQL health, DB backups, mail providers |

---

## 14. Next Steps

1. ✅ All planning decisions finalized
2. Begin Phase 1 implementation:
   - Scaffold Laravel dashboard application with Breeze auth
   - Scaffold the `baklysystems/appswatch` package
   - Create MySQL database migrations for all 10 tables
   - Build ingestion API with API key authentication + rate limiting
3. Work toward MVP: capture exceptions from one demo Laravel app → display on dashboard

---

*Plan version: 2.0 (Final) — All decisions resolved — Last updated: 2026-06-04*
