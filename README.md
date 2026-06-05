# Appswatch — Self-Hosted Laravel Monitoring Platform

A self-hosted observability platform for Laravel applications. Combines exception tracking, log aggregation, queue monitoring, database profiling, HTTP request logging, scheduled task tracking, custom metrics, uptime/SSL/domain health checks, and 15+ third-party integrations — all in a single dashboard.

## Features

### Core Monitoring

| Surface | Details |
|---|---|
| **Exception Tracking** | Auto-capture with fingerprint grouping, stack traces, code snippets, breadcrumbs, request context, and status management (unresolved/resolved/ignored/muted) |
| **Log Aggregation** | Batch ingestion with level/channel filtering, full-text search, and batch-grouped related logs by request |
| **Queue Monitoring** | Per-queue stats, status filtering (pending/processing/completed/failed), linked exceptions, throughput metrics |
| **HTTP Request Logging** | Method, URL, status code, duration, memory usage, route performance rankings |
| **Database Query Profiling** | Slow query detection, duration tracking, parameterized SQL display, N+1 detection |
| **Scheduled Task Tracking** | Command tracking with status (started/completed/failed/skipped), output capture, duration |
| **Custom Metrics** | Gauge, counter, and histogram types with tag-based dimensions for application-specific telemetry |
| **Multi-Project Support** | Manage unlimited Laravel apps from one dashboard with project selector and isolated data |

### Alerting & Notifications

- **8 alert types**: Exception rate, log level, queue failure, slow query, metric threshold, MySQL connection saturation, MySQL replication lag, backup stale
- **7 notification channels**: Email, Slack, Discord, Webhook, Telegram, N8N, IFTTT
- Configurable cooldown per alert rule to prevent notification spam
- Alert evaluation every minute via scheduled command

### Third-Party Integrations

| Integration | Data Collected |
|---|---|
| **Google Analytics 4** | Page views, active users, sessions, bounce rate, top pages, conversions |
| **Google Search Console** | Search queries, clicks, impressions, CTR, average position, crawl errors |
| **Cloudflare** | Requests, bandwidth, cache hit ratio, threats blocked, WAF events |
| **Microsoft Clarity** | Sessions, rage clicks, dead clicks, JS errors |
| **Stripe** | MRR/ARR, successful/failed charges, refunds, disputes, subscription churn |
| **GitHub** | Commits, PRs, deployments, releases, GitHub Actions workflow status |
| **GitLab** | Commits, MRs, deployments, pipeline status (GitLab CI) |
| **Mailgun / Postmark / SES** | Delivery rate, bounces, complaints, opens, clicks |
| **Uptime Monitoring** | HTTP/S health checks, response time, outage detection |
| **SSL Certificate Checks** | Expiry date, issuer, chain validity, expiry warnings at 7/14/30 days |
| **Domain Expiry** | WHOIS expiry dates with early warnings |
| **Server Metrics** | CPU %, memory %, disk %, load average |
| **MySQL Health** | Active/max connections, buffer pool hit rate, slow queries, QPS, replication lag |
| **Database Backups** | mysqldump/pg_dump with retention rotation and status tracking |

### Advanced Features

- **Anomaly Detection** — Z-score based statistical analysis on error rates, response times, and queue failures
- **Health Score** — Composite 0-100 score per project from error rate, uptime, response time, and queue health
- **Incident Timeline** — Merged view: deployments → error spikes → alert triggers → resolution actions
- **Auto-Resolution Rules** — Configurable rule engine (e.g., "If Exception class=X and environment=staging → auto-mute")
- **Audit Log** — Immutable, filterable log of all user actions (resolve, mute, delete, settings change, API key rotation)
- **Saved Filters** — Name and save exception/log filter combinations, share via URL
- **Weekly Reports** — Scheduled PDF/email reports with exception summaries, uptime %, response times, and queue failures
- **Telegram Bot** — `/status`, `/exceptions`, `/resolve`, `/mute`, `/backup` commands with inline button actions
- **N8N Integration** — Structured JSON webhooks enabling 400+ workflow automations (Jira, PagerDuty, SMS, etc.)
- **Prometheus Exporter** — Expose `/metrics` endpoint in Prometheus format for Grafana users
- **Dark Mode** — Full light/dark theme support persisted via localStorage
- **Responsive Design** — Mobile-first with hamburger navigation and full feature parity
- **Docker Support** — Dockerfile, nginx, supervisord, and docker-compose.yml for one-command deployment

## Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                    APPSWATCH DASHBOARD (Central)                  │
│  ┌───────────┐ ┌──────────┐ ┌──────────┐ ┌─────────────────┐    │
│  │ Exceptions│ │   Logs   │ │  Queues  │ │   Performance   │    │
│  │  Dashboard│ │  Viewer  │ │ Monitor  │ │    Tracing      │    │
│  └───────────┘ └──────────┘ └──────────┘ └─────────────────┘    │
│  ┌───────────┐ ┌──────────┐ ┌──────────┐ ┌─────────────────┐    │
│  │ Schedules │ │   HTTP   │ │    DB    │ │  Alerts /       │    │
│  │ Monitor   │ │ Requests │ │ Queries  │ │  Notifications  │    │
│  └───────────┘ └──────────┘ └──────────┘ └─────────────────┘    │
│                                                                   │
│  Ingestion API (REST) — POST /api/ingest/{resource}               │
│  Storage Layer — MySQL 8                                          │
└──────────────────────────────────────────────────────────────────┘
         ▲                               ▲
         │                               │
  Laravel App #1                 Laravel App #N
  (appswatch package)            (appswatch package)
```

- **Push model**: Client apps push data to the central server via REST API
- **Async by default**: All ingestion is queued on the client side to never block the app
- **Batched ingestion**: Data is sent in batches at configurable intervals
- **Project isolation**: Each app gets a unique API key and project ID
- **Fail-safe**: If the central server is down, data is buffered locally and retried

## Tech Stack

| Layer | Technology |
|---|---|
| **Dashboard** | Laravel 12 + PHP 8.2+ |
| **Client Package** | Composer package (`baklysystems/app-watch-laravel-client`) |
| **Database** | MySQL 8 |
| **Queue** | Laravel Horizon (Redis) |
| **Frontend** | Blade + Alpine.js + Tailwind CSS + Chart.js |
| **Auth** | Laravel Breeze |
| **Real-time** | Laravel Reverb (WebSocket) |
| **Containerization** | Docker + Nginx + Supervisor |

## Quick Start — Central Dashboard

```bash
# Clone the repository
git clone https://github.com/baklysystems/appswatch.git
cd appswatch

# Install dependencies
composer install
npm install && npm run build

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate

# Start the development server
composer run dev
```

### Docker Deployment

```bash
cd docker
docker-compose up -d
```

## Client Package Installation

Install the monitoring agent in any Laravel app you want to monitor:

```bash
composer require baklysystems/app-watch-laravel-client
```

Set environment variables in the client app's `.env`:

```env
APPSWATCH_SERVER_URL=https://your-appswatch-instance.com
APPSWATCH_API_KEY=your-project-api-key
APPSWATCH_ENABLED=true
APPSWATCH_ENVIRONMENT=production
APPSWATCH_RELEASE=v1.2.3
```

The package auto-discovers and begins collecting exceptions, logs, queries, queue jobs, scheduled tasks, HTTP requests, and MySQL health metrics — zero configuration required beyond the environment variables.

### Manual Usage

```php
use Appswatch;

// Log a custom event
Appswatch::log('info', 'User signed up', ['user_id' => 42]);

// Capture an exception manually
Appswatch::exception($e, ['user_id' => $userId]);

// Send a custom metric
Appswatch::metric('users.signed_up', 1, 'count', ['plan' => 'pro']);
```

See [packages/appswatch/README.md](packages/appswatch/README.md) for full client package documentation.

## API

### Ingestion Endpoints

All endpoints accept batched JSON payloads with Bearer token authentication:

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/ingest/exceptions` | Batch exceptions |
| POST | `/api/ingest/logs` | Batch log entries |
| POST | `/api/ingest/queues` | Batch queue job records |
| POST | `/api/ingest/queries` | Batch database queries |
| POST | `/api/ingest/requests` | Batch HTTP request records |
| POST | `/api/ingest/schedules` | Batch scheduled task records |
| POST | `/api/ingest/metrics` | Batch custom metrics |

### Prometheus Metrics Endpoint

```
GET /api/prometheus/metrics
```

Exposes metrics in Prometheus format for Grafana integration.

## Scheduled Commands

| Command | Frequency | Description |
|---|---|---|
| `appswatch:integrations:check-uptime` | Every 1 min | HTTP health checks |
| `appswatch:integrations:check-ssl` | Daily | SSL certificate expiry checks |
| `appswatch:integrations:check-domains` | Daily | WHOIS domain expiry checks |
| `appswatch:integrations:check-servers` | Every 5 min | Server resource monitoring |
| `appswatch:integrations:run-backups` | Daily | Database backup execution |
| `appswatch:check-service-vitals` | Every 5 min | Mail/queue/notification/redis/reverb health |
| `appswatch:evaluate-alerts` | Every 1 min | Alert rule evaluation |
| `appswatch:detect-anomalies` | Every 5 min | Statistical anomaly detection |
| `appswatch:evaluate-auto-resolution` | Every 5 min | Auto-resolution rule engine |
| `appswatch:cleanup` | Daily | Data retention enforcement |
| `appswatch:send-weekly-report` | Weekly | Email/PDF report generation |
| `appswatch:integrations:poll-ga4` | Every 30 min | Google Analytics 4 metrics |
| `appswatch:integrations:poll-gsc` | Every 6 hours | Google Search Console data |
| `appswatch:integrations:poll-cloudflare` | Every 15 min | Cloudflare analytics |
| `appswatch:integrations:poll-clarity` | Every 60 min | Microsoft Clarity metrics |
| `appswatch:integrations:poll-stripe` | Every 30 min | Stripe revenue metrics |
| `appswatch:integrations:poll-github` | Webhook | GitHub deployment/CI events |
| `appswatch:integrations:poll-email` | Every 30 min | Email provider deliverability |

## Directory Structure

```
sentry/
├── app/
│   ├── Console/Commands/         # All scheduled commands
│   ├── Http/
│   │   ├── Controllers/Api/      # Ingestion, webhooks, Prometheus
│   │   └── Controllers/Web/      # Dashboard UI controllers
│   ├── Jobs/                     # Queued jobs (alerts, reports, webhooks)
│   ├── Models/                   # Eloquent models
│   └── Services/                 # Business logic + integration services
├── config/                       # Laravel config + services.php
├── database/migrations/          # All table migrations
├── docker/                       # Dockerfile, nginx, supervisord, compose
├── packages/appswatch/           # Client Composer package
├── resources/views/              # Blade templates (dashboard, exceptions, logs, etc.)
├── routes/
│   ├── api.php                   # Ingestion + webhook routes
│   ├── console.php               # Scheduled command registration
│   └── web.php                   # Dashboard routes
└── PLAN.md                       # Full implementation plan & feature comparison matrix
```

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Documentation

- [PLAN.md](PLAN.md) — Full implementation plan with 27-tool feature comparison matrix, architecture, data model, and integration design
- [FEATURES.md](FEATURES.md) — Feature implementation status tracker
- [phase2.md](phase2.md) — Gap analysis and prioritized implementation roadmap
- [tasks.md](tasks.md) — Detailed task breakdown with file paths and acceptance criteria