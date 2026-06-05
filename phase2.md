# Appswatch — PLAN.md ↔ FEATURES.md ↔ Code: Full Gap Analysis

## Context
- **PLAN.md**: 1034-line master plan (Phase 1-5 implementation roadmap with 10 tables, 7 API endpoints, 27 integrations, architecture, data model)
- **FEATURES.md**: 114-line status tracker claiming 51/54 features implemented (94%)
- **Actual code**: Explored via full parallel subagent analysis of all controllers, models, services, commands, views, routes, migrations, config, and the client package

---

## 🔴 CRITICAL BUG — 5 of 8 scheduled commands broken

`routes/console.php` references wrong command signatures. Laravel will silently skip all 5:

| console.php schedules | Command's actual $signature | Effect |
|---|---|---|
| `appswatch:check-uptime` | `appswatch:integrations:check-uptime` | Uptime checks never run |
| `appswatch:check-ssl` | `appswatch:integrations:check-ssl` | SSL checks never run |
| `appswatch:check-domains` | `appswatch:integrations:check-domains` | Domain checks never run |
| `appswatch:check-servers` | `appswatch:integrations:check-servers` | Server metrics never collected |
| `appswatch:run-backups` | `appswatch:integrations:run-backups` | DB backups never execute |

Only `appswatch:cleanup`, `appswatch:evaluate-alerts`, and `appswatch:check-service-vitals` work correctly.

---

## 🟡 FEATURES.md claims vs actual code (17 discrepancies)

| FEATURES.md Claim | Code Reality |
|---|---|
| Uptime Monitoring 100% | Command exists but schedule broken → 70% |
| SSL Certificate Checks 100% | Command exists but schedule broken → 70% |
| Domain WHOIS Expiry 100% | Command exists but schedule broken → 70% |
| Server Resource Monitoring 100% | Command exists but schedule broken → 70% |
| Database Backups 100% | Command exists but schedule broken → 70% |
| MySQL Health Monitoring 100% | No collector command gathers MySQL metrics; alerts evaluate empty data → 50% |
| Google Analytics 4 100% | Config UI only — zero data collection code exists → 30% |
| Google Search Console 100% | Config UI only → 30% |
| Cloudflare Analytics 100% | Config UI only → 30% |
| Microsoft Clarity 100% | Config UI only → 30% |
| Stripe 100% | Config UI only → 30% |
| GitHub/GitLab 100% | Config UI only → 30% |
| Mailgun/Postmark/SES 100% | Config UI only → 30% |
| Dark Mode 90% | CSS classes present but toggle state not persisted across page loads → 85% |
| Client Package 0% | Files in `/packages/appswatch` but never published to Packagist → 10% |
| Real-Time Updates 0% | Reverb completely unconfigured |
| API Documentation 0% | No Scramble/Swagger installed |

**FEATURES.md should be recalculated to ~78% actual completion, not 94%.**

---

## 🔴 PLAN.md features implemented vs missing

### ✅ Fully implemented (Phase 1-3 core)
- All 10 database tables with migrations
- All 7 ingestion API endpoints (`/api/ingest/{exceptions,logs,queues,queries,requests,schedules,metrics}`)
- API key authentication (BEARER token, hashed keys, rate limiting)
- Exception tracking (fingerprinting, grouping, stack traces, code snippets, request context)
- Log aggregation (batch ingestion, level/channel filtering, batch-grouped related logs)
- Queue monitoring (stats, status filtering, linked exceptions)
- HTTP request logging (method, URL, status, duration, memory, route performance)
- Database query profiling (slow query detection, duration, bindings)
- Scheduled task tracking (command, status, duration)
- Custom metrics (gauge/counter/histogram with tags)
- Multi-project support (selector, "+ New" modal, super admin aggregated view)
- Alert system (8 types, 4 channels, CRUD, cooldown, evaluation every minute)
- Project settings (name, retention, rate limit, API keys, deletion)
- Exception detail view (stack traces, similar exceptions, request context, release info, status actions)
- Dashboard (stats cards, Chart.js trends, uptime/SSL/server/mysql/backup cards, activity feed)
- Dark mode CSS (Tailwind classes on all views)
- Responsive design (mobile hamburger nav with full links + project selector)
- Data retention cleanup command
- Service vitals monitoring (mail, queue, notifications, redis, reverb — displayed with ✓/✗ badges)
- Docker setup (Dockerfile, nginx.conf, supervisord.conf, compose.yml)
- Client SDK package (local files — all collectors, transport, middleware, config)

### 🔴 Missing or broken (Phase 2-5)

**Phase 2 — Package gaps:**
- `MetricCollector.php` — referenced in PLAN.md, not implemented
- `MySqlHealthCollector.php` — referenced, not implemented
- `AsyncTransport.php` — referenced, not implemented
- `BufferedTransport.php` — referenced, not implemented
- `AppswatchJobMiddleware.php` — referenced, not implemented

**Phase 3 — UI gaps:**
- Breadcrumbs timeline in exception detail — data stored, not rendered
- Occurrence history graph (sparkline) in exception detail — not implemented
- Syntax highlighting for code snippets — plain `<pre>` used, no highlight.js/prism
- Real-time log tail via WebSocket — Reverb not configured
- MySQL health data flow — no collector pushes `integration_metrics` for mysql

**Phase 4 — Integration collectors (all 7 missing):**
- No GA4 polling command or service
- No Google Search Console polling
- No Cloudflare GraphQL collector
- No Microsoft Clarity API collector
- No Stripe API collector
- No GitHub/GitLab webhook receiver
- No Mailgun/Postmark/SES collector
- No GitLab CI pipeline monitoring
- No deployment markers on charts
- No CSV/JSON data export
- Exception similarity detection is basic (same class/file), no content-based grouping

**Phase 5 — Not started:**
- Meilisearch full-text search
- Laravel Reverb WebSocket
- Swagger/Scramble API documentation
- Pest testing suite
- Sentry migration bridge
- Paddle integration
- PostgreSQL metrics
- Log file rotation (retention days exists but no per-channel max-MB rotation)

---

## 🆕 NEW IDEAS (beyond both documents)

### 🤖 AI / Anomaly Detection
- **Statistical anomaly detection**: Z-score on error rates, response times, queue failures over 7-day rolling window. Alert "Unusual spike: 47 exceptions in 5 min vs avg of 3." No external API needed — pure PHP math.
- **AI error summaries**: Feed exception cluster data to LLM → "23 `QueryException` in `/api/checkout` — all missing `shipping_address` column after deploy abc123"
- **Auto-grouping cascading failures**: DB down → queue fails → 500 errors → single alert "Cascading failure from DB connection" instead of 15 separate notifications
- **Smart resolution suggestions**: "This `MethodNotAllowedHttpException` pattern was resolved last week by adding the route in `web.php`" — search previous resolutions

### 🔗 N8N Workflow Automation
- **N8N notification channel**: Add `n8n` as 5th channel in `SendAlertNotification` job → POST alert payload to n8n webhook → n8n creates Jira ticket, sends SMS, calls PagerDuty, restarts service, posts to Teams — 400+ integrations unlocked
- **N8N community node**: Publish `n8n-nodes-appswatch` — trigger workflows on: new exception, alert fired, deployment detected, uptime check failed
- **Bidirectional webhooks**: N8N calls back to Appswatch API to: resolve exception, mute alert, acknowledge incident, trigger backup
- **Workflow examples**: "Critical exception → Create GitHub issue + post to Slack #incidents + SMS on-call engineer" — all via N8N with zero Appswatch code changes

### 📱 Telegram Bot Integration
- **Telegram notification channel**: Send rich alert cards with inline buttons: `[Resolve] [Mute 1h] [Mute 24h] [View Details]`
- **Telegram bot commands**: `/status` → project health + uptime, `/exceptions` → latest 5 unresolved, `/resolve abc123`, `/mute abc123`, `/backup now`, `/uptime`, `/metrics`
- **Telegram channel broadcast**: Post major incidents to a public/team Telegram channel
- **Inline queries**: Type `@appswatch_bot error 500` in any chat → get matching exceptions

### 📊 UX Enhancements
- **Health Score** (0-100): Composite score per project from error rate + uptime + response time + queue health. Green/yellow/red color coding. Historical trend.
- **Incident Timeline**: Single merged view: deployments → error spikes → alert triggers → resolution actions. "What happened when."
- **Custom Dashboard Builder**: Drag-and-drop widget layout. Save layouts per user. Share via read-only link.
- **Project Comparison**: Side-by-side metrics for multiple projects on one screen.
- **Saved Filters**: Name and save exception/log filter combinations. "Production API Errors," "Slow Checkout Queries." Share via URL.
- **Bulk Actions**: Select multiple exceptions → resolve/mute/delete all at once.
- **Keyboard Shortcuts**: `r`=resolve, `m`=mute, `i`=ignore, `j/k`=navigate, `e`=expand, `f`=filter.
- **PWA with Push**: Install as mobile app. Push notifications for critical alerts. Offline-cached dashboard.
- **Loading Skeletons**: Shimmer placeholders instead of blank pages during data fetch.
- **Infinite Scroll**: Cursor-based pagination for exception/log lists (replaces "Load More" buttons).

### 🔄 Workflow & Automation
- **Webhook Actions**: Outbound webhooks on all events (exception created/resolved, alert triggered, deployment detected, uptime check failed). Users configure custom URLs. Enables any integration without native support.
- **Auto-Resolution Rules**: "If exception class=X and environment=staging → auto-mute." "If unresolved for 7 days and count < 3 → auto-resolve." Configurable rule engine.
- **Escalation Policies**: Alert → Slack → no ack in 15 min → Discord → no ack in 30 min → webhook (PagerDuty). Tiered notification with timeouts.
- **Scheduled Reports**: Weekly PDF/email: "23 exceptions, 99.8% uptime, 145ms avg response, 2 failed queues." Export with charts.
- **Slack/Discord Digest**: Daily summary posted to a channel. "Today: 5 new errors, 2 resolved, 0 downtime."

### 🔐 Security & Multi-User
- **Audit Log**: Track every action (resolve, mute, delete, settings change, API key rotation). Immutable. Filterable.
- **RBAC Roles**: Owner, Admin, Developer, Viewer per project. Granular permissions for view/modify/delete/configure.
- **2FA**: TOTP two-factor authentication for dashboard login.

### 🌐 Integration Expansions
- **Prometheus Exporter**: Expose `/metrics` endpoint in Prometheus format → Grafana users add Appswatch as native data source.
- **Sentry Import Tool**: One-click migration: import exceptions/projects from Sentry API.
- **Oh Dear / UptimeRobot Bridge**: Import existing uptime monitor configurations.
- **Linear / Jira / ClickUp**: "Create Issue" button on exception detail. Auto-create from alert rules. Link exceptions ↔ tickets.
- **SDKs for Other Languages**: Node.js SDK (Express, Next.js, Fastify), Python SDK (Django, Flask, FastAPI), plain PHP SDK (WordPress, Symfony, Slim).

---

## 📋 Prioritized Implementation Order

### 🚨 Immediate Bug Fix (15 minutes)
1. Fix 5 command signatures in `routes/console.php` — change `appswatch:check-uptime` → `appswatch:integrations:check-uptime` (same for ssl, domains, servers, backups)

### 🔥 Week 1 — High Impact / Low Effort
2. Add **Telegram notification channel** to `SendAlertNotification` (Bot API + inline keyboard buttons)
3. Add **N8N webhook channel** to `SendAlertNotification` (simple JSON POST)
4. Add **outbound webhook actions** on exception/alert events
5. Render **breadcrumbs timeline** in `exceptions/show.blade.php` (data exists, add blade section)
6. Add **occurrence history sparkline** to exception detail (Chart.js, data exists)
7. Fix **dark mode toggle persistence** (localStorage + init script)

### ⚡ Week 2-3 — Integration Collectors
8. Build 7 integration data pollers (GA4, GSC, Cloudflare, Clarity, Stripe, GitHub, Mailgun — each ~150-200 lines)
9. Wire each to `appswatch:integrations:poll-{service}` scheduled commands
10. Publish client package to Packagist (`baklysystems/appswatch`)

### 📅 Sprint 2 — Advanced Features
11. Statistical anomaly detection engine (Z-score, no external API)
12. Telegram bot with commands (`/status`, `/exceptions`, `/resolve`, `/mute`, `/backup`)
13. Incident timeline view (deployments + errors + alerts)
14. Health score per project with historical trend
15. Saved filters and custom views
16. Audit log for all user actions
17. Auto-resolution rules engine

### 🎯 Sprint 3+ — Ecosystem
18. N8N community node (`n8n-nodes-appswatch`)
19. Prometheus exporter endpoint
20. Scheduled PDF/email reports
21. PWA with push notifications
22. RBAC with team roles + 2FA
23. Swagger/Scramble API documentation
24. Meilisearch full-text search
25. Laravel Reverb real-time updates
26. Multi-language SDKs (Node.js, Python, plain PHP)