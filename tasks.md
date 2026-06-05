# Appswatch — Implementation Task List

> Generated from `phase2.md` gap analysis. Each task includes exact file paths, implementation details, and acceptance criteria.

---

## 🚨 PHASE 0 — Critical Bug Fixes (Immediate)

### Task BUG-001: Fix 5 Broken Scheduled Command Signatures
**Priority:** CRITICAL | **Effort:** 15 min | **Status:** 🔴 Not Started

**Problem:** `routes/console.php` schedules commands using `appswatch:command-name` but the actual `$signature` in the command classes uses `appswatch:integrations:command-name`. Laravel silently skips these because it cannot find matching command signatures.

**Files to modify:**
- `routes/console.php`

**Implementation:**
1. Open `routes/console.php`
2. Find the line scheduling `appswatch:check-uptime` → change to `appswatch:integrations:check-uptime`
3. Find the line scheduling `appswatch:check-ssl` → change to `appswatch:integrations:check-ssl`
4. Find the line scheduling `appswatch:check-domains` → change to `appswatch:integrations:check-domains`
5. Find the line scheduling `appswatch:check-servers` → change to `appswatch:integrations:check-servers`
6. Find the line scheduling `appswatch:run-backups` → change to `appswatch:integrations:run-backups`
7. Leave `appswatch:cleanup`, `appswatch:evaluate-alerts`, `appswatch:check-service-vitals` as-is (these match)
8. Run `php artisan schedule:list` to verify all 8 commands appear
9. Run `php artisan appswatch:integrations:check-uptime` manually to verify execution succeeds
10. Run `php artisan appswatch:integrations:check-ssl` manually
11. Run `php artisan appswatch:integrations:check-domains` manually
12. Run `php artisan appswatch:integrations:check-servers` manually
13. Run `php artisan appswatch:integrations:run-backups` manually

**Acceptance Criteria:**
- [ ] `php artisan schedule:list` shows all 8 commands including the 5 integration ones
- [ ] Each of the 5 integration commands runs without error when executed manually
- [ ] Dashboard starts showing real uptime/SSL/domain/server/backup data within 5 minutes
- [ ] Zero Laravel "Command not found" errors in logs after deploy

---

### Task BUG-002: Fix Alert Notification Channel Configs
**Priority:** HIGH | **Effort:** 10 min | **Status:** 🔴 Not Started

**Problem:** `SendAlertNotification` job reads `config('services.slack.webhook_url')` and `config('services.discord.webhook_url')` but `config/services.php` does not define these keys. Alerts to Slack/Discord will silently fail at runtime.

**Files to modify:**
- `config/services.php`
- `app/Jobs/SendAlertNotification.php` (verify logic)

**Implementation:**
1. Add to `config/services.php`:
   ```php
   'slack' => [
       'webhook_url' => env('SLACK_WEBHOOK_URL'),
   ],
   'discord' => [
       'webhook_url' => env('DISCORD_WEBHOOK_URL'),
   ],
   ```
2. Add to `.env.example`:
   ```
   SLACK_WEBHOOK_URL=
   DISCORD_WEBHOOK_URL=
   ```
3. Verify `SendAlertNotification.php` handles missing webhook URLs gracefully (log warning, don't throw exception)

**Acceptance Criteria:**
- [ ] Slack and Discord webhook URLs are configurable via .env
- [ ] Missing webhook URLs produce a logged warning, not a thrown exception
- [ ] Existing mail and webhook channels still work

---

## 🔥 PHASE 1 — High Impact / Low Effort (Week 1)

### Task W1-001: Add Telegram Notification Channel
**Priority:** HIGH | **Effort:** 2-3 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/TelegramNotificationService.php`
- `app/Http/Controllers/Api/TelegramWebhookController.php`

**Files to modify:**
- `app/Jobs/SendAlertNotification.php`
- `config/services.php`
- `routes/api.php`
- `.env.example`
- `resources/views/settings/index.blade.php` (add Telegram config section)
- `app/Http/Controllers/Web/SettingsController.php` (handle Telegram config save)

**Implementation Details:**

**Step 1 — Create TelegramNotificationService:**
- Constructor accepts `$botToken` from `config('services.telegram.bot_token')`
- Method `sendAlert($chatId, $alert, $project, $details)`:
  - Build message with: project name, alert name, alert type, conditions triggered, timestamp
  - Add inline keyboard with buttons:
    - `[🔍 View Details]` → links to Appswatch dashboard (configurable base URL)
    - `[✅ Resolve]` → callback_data: `resolve:{exception_id}`
    - `[🔇 Mute 1h]` → callback_data: `mute:{exception_id}:60`
    - `[🔇 Mute 24h]` → callback_data: `mute:{exception_id}:1440`
  - POST to `https://api.telegram.org/bot{$botToken}/sendMessage` via Guzzle
  - Parse response, return success/failure
  - Handle rate limits (Telegram allows ~30 msg/sec per chat)
- Method `sendStatusReport($chatId, $projectHealth)`:
  - Compact summary: errors today, uptime %, avg response time, queue failures
  - Used for `/status` command response

**Step 2 — Create TelegramWebhookController:**
- Endpoint: `POST /api/telegram/webhook`
- Parse incoming Telegram update JSON
- Handle `message` type with entities where `entity.type === 'bot_command'`:
  - `/start` → Welcome message: "Appswatch Bot connected to project X. Commands: /status, /exceptions, /resolve, /mute, /backup, /uptime, /metrics"
  - `/status` → Query IntegrationMetric for latest health data, call sendStatusReport()
  - `/exceptions` → Query latest 5 unresolved exceptions for project, format as message list with fingerprint IDs
  - `/resolve <fingerprint_id>` → Call ExceptionService to set status=resolved, reply "Resolved ✅"
  - `/mute <fingerprint_id> [duration_minutes]` → Call ExceptionService to set status=muted, reply "Muted for X minutes 🔇"
  - `/backup now` → Dispatch RunBackups job for project, reply "Backup initiated ⏳"
  - `/uptime` → Query latest uptime checks, reply with % and response times
  - `/metrics` → Reply with latest custom metrics summary
- Handle `callback_query` type (inline button clicks):
  - Parse callback_data: `resolve:{id}`, `mute:{id}:{minutes}`
  - Execute action, edit message to show result
  - Answer callback query with toast notification
- Verify webhook authenticity by comparing `X-Telegram-Bot-Api-Secret-Token` header with `config('services.telegram.webhook_secret')`
- Map Telegram `chat_id` to Appswatch project via `projects.telegram_chat_id` or a dedicated `telegram_subscriptions` table

**Step 3 — Modify SendAlertNotification:**
- Add `telegram` to channel handling:
  ```php
  if (in_array('telegram', $this->alert->channels)) {
      $telegram = app(TelegramNotificationService::class);
      $chatIds = $this->project->telegram_chat_ids ?? [config('services.telegram.default_chat_id')];
      foreach ($chatIds as $chatId) {
          $telegram->sendAlert($chatId, $this->alert, $this->project, $details);
      }
  }
  ```
- Add `n8n` channel to enable n8n as notification target (see W1-002)

**Step 4 — Config & DB Changes:**
- Add to `config/services.php`:
  ```php
  'telegram' => [
      'bot_token' => env('TELEGRAM_BOT_TOKEN'),
      'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
      'default_chat_id' => env('TELEGRAM_DEFAULT_CHAT_ID'),
      'dashboard_url' => env('APP_URL'),
  ],
  ```
- Add migration for `telegram_subscriptions` table:
  - `id` (uuid)
  - `project_id` (fk to projects)
  - `chat_id` (string)
  - `chat_type` (enum: private/group/channel)
  - `chat_name` (nullable string, display name)
  - `subscribed_events` (json) — ["alerts", "daily_digest", "deployments"]
  - `is_active` (boolean)
  - `timestamps`
- Add to `.env.example`:
  ```
  TELEGRAM_BOT_TOKEN=
  TELEGRAM_WEBHOOK_SECRET=
  TELEGRAM_DEFAULT_CHAT_ID=
  ```
- Register webhook route: `Route::post('/api/telegram/webhook', [TelegramWebhookController::class, 'handle'])->withoutMiddleware([VerifyCsrfToken::class]);`
- Add API route for setting webhook: `POST /api/telegram/set-webhook` (admin only, calls Telegram API)

**Step 5 — Settings UI:**
- In Settings → Integrations panel, add "Telegram Bot" section:
  - Enable/disable toggle
  - Bot Token field (password-masked)
  - "Set Webhook" button (calls Telegram API to register webhook URL)
  - List of subscribed chats with remove buttons
  - "How to connect" instructions: "1. Create bot via @BotFather, 2. Enter token above, 3. Click Set Webhook, 4. Send /start to your bot"
  - Webhook status indicator

**Acceptance Criteria:**
- [ ] Telegram bot receives `/start` and responds with welcome + command list
- [ ] `/status` returns real project health data
- [ ] `/exceptions` returns latest 5 unresolved exceptions
- [ ] `/resolve <id>` changes exception status and replies with confirmation
- [ ] `/mute <id> [minutes]` mutes exception and replies
- [ ] Inline buttons on alert messages work (Resolve, Mute)
- [ ] Alert rules with `telegram` channel deliver to configured chats
- [ ] Webhook secret validation prevents unauthorized access
- [ ] Settings UI allows configuring bot token and viewing connected chats

---

### Task W1-002: Add N8N Webhook Notification Channel
**Priority:** HIGH | **Effort:** 1-2 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/N8nNotificationService.php`

**Files to modify:**
- `app/Jobs/SendAlertNotification.php`
- `config/services.php`
- `.env.example`
- `resources/views/settings/index.blade.php`
- `app/Http/Controllers/Web/SettingsController.php`

**Implementation Details:**

**Step 1 — Create N8nNotificationService:**
- Method `sendAlert($webhookUrl, $payload)`:
  - Build standardized payload:
    ```json
    {
      "event": "alert.triggered",
      "timestamp": "2026-06-04T18:00:00Z",
      "project": {
        "id": "uuid",
        "name": "My App",
        "environment": "production",
        "slug": "my-app"
      },
      "alert": {
        "id": "uuid",
        "name": "High Error Rate",
        "type": "exception_rate",
        "conditions": { "threshold": 10, "window_minutes": 5 },
        "details": "47 exceptions in 5 minutes (threshold: 10)"
      },
      "appswatch_url": "https://appswatch.example.com/projects/uuid/exceptions"
    }
    ```
  - POST to `$webhookUrl` via Guzzle with 15-second timeout
  - Log success/failure
  - Support custom headers via `config('services.n8n.webhook_headers')`
- Method `sendEvent($webhookUrl, $eventType, $data)` — generic event sender for non-alert events

**Step 2 — Modify SendAlertNotification:**
- Add `n8n` channel handler:
  ```php
  if (in_array('n8n', $this->alert->channels)) {
      $n8n = app(N8nNotificationService::class);
      $webhookUrl = $this->alert->conditions['n8n_webhook_url'] ?? config('services.n8n.default_webhook_url');
      $n8n->sendAlert($webhookUrl, $this->alert, $this->project, $details);
  }
  ```

**Step 3 — Outbound Webhook Actions System:**
- Create `app/Services/WebhookActionService.php`:
  - Configurable per-project list of webhook URLs + event subscriptions
  - Events: `exception.created`, `exception.resolved`, `exception.muted`, `exception.ignored`, `alert.triggered`, `alert.acknowledged`, `deployment.detected`, `uptime.check_failed`, `uptime.check_recovered`, `backup.completed`, `backup.failed`
  - Fire-and-forget: dispatch `DispatchWebhookAction` job to queue
  - Retry failed deliveries up to 3 times with exponential backoff
- Create `app/Jobs/DispatchWebhookAction.php`:
  - Receives event type + payload
  - Posts to configured webhook URL
  - Logs delivery status

**Step 4 — Config & DB Changes:**
- Add to `config/services.php`:
  ```php
  'n8n' => [
      'default_webhook_url' => env('N8N_WEBHOOK_URL'),
      'webhook_headers' => [
          'Authorization' => env('N8N_WEBHOOK_AUTH_HEADER'),
      ],
  ],
  ```
- Add migration for `webhook_subscriptions` table:
  - `id` (uuid)
  - `project_id` (fk)
  - `name` (string)
  - `url` (string)
  - `secret` (nullable string, for HMAC signature verification)
  - `events` (json) — selected event types
  - `headers` (json) — custom headers to send
  - `is_active` (boolean)
  - `last_delivered_at` (nullable timestamp)
  - `failed_count` (int, default 0)
  - `timestamps`
- Add to `.env.example`:
  ```
  N8N_WEBHOOK_URL=
  N8N_WEBHOOK_AUTH_HEADER=
  ```

**Step 5 — Settings UI:**
- Add "N8N Workflow Automation" section to Settings → Integrations:
  - Enable/disable toggle
  - Default n8n webhook URL field
  - Auth header field (optional, for n8n webhook auth)
  - "Test Connection" button that sends a test ping to n8n

**Acceptance Criteria:**
- [ ] Alert rules can select `n8n` as a notification channel
- [ ] N8N receives structured JSON payloads when alerts fire
- [ ] `n8n` channel appears alongside mail/slack/discord/webhook in alert create/edit forms
- [ ] Outbound webhooks can be configured per-project with event filtering
- [ ] Webhook deliveries are queued (non-blocking)
- [ ] Failed deliveries are retried up to 3 times

---

### Task W1-003: Render Breadcrumbs Timeline in Exception Detail
**Priority:** MEDIUM | **Effort:** 1 hour | **Status:** 🔴 Not Started

**Problem:** Exceptions store `breadcrumbs` as JSON (logs, queries, requests leading up to the crash) but the detail view never renders them.

**Files to modify:**
- `resources/views/exceptions/show.blade.php`

**Implementation Details:**
1. Add a new Blade section after the stack trace, before "Similar Exceptions":
   ```blade
   <div class="mt-8">
       <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">📋 Breadcrumbs Timeline</h3>
       @if($exception->breadcrumbs && count(json_decode($exception->breadcrumbs, true)) > 0)
           <div class="relative">
               <!-- Vertical timeline line -->
               <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
               <div class="space-y-4">
                   @foreach(json_decode($exception->breadcrumbs, true) as $breadcrumb)
                       <div class="relative pl-10">
                           <!-- Timeline dot -->
                           <div class="absolute left-2.5 top-1.5 w-3 h-3 rounded-full border-2 
                               @if($breadcrumb['type'] === 'log' && $breadcrumb['level'] === 'error') bg-red-500 border-red-300
                               @elseif($breadcrumb['type'] === 'query') bg-blue-500 border-blue-300
                               @elseif($breadcrumb['type'] === 'request') bg-green-500 border-green-300
                               @else bg-gray-400 border-gray-200 @endif">
                           </div>
                           <!-- Breadcrumb content -->
                           <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                               <div class="flex items-center justify-between mb-1">
                                   <span class="text-xs font-mono px-2 py-0.5 rounded-full 
                                       @if($breadcrumb['type'] === 'log') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                       @elseif($breadcrumb['type'] === 'query') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                       @elseif($breadcrumb['type'] === 'request') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                       @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @endif">
                                       {{ strtoupper($breadcrumb['type']) }}
                                   </span>
                                   <span class="text-xs text-gray-400">{{ $breadcrumb['timestamp'] ?? '' }}</span>
                               </div>
                               <p class="text-sm text-gray-700 dark:text-gray-300 font-mono">{{ $breadcrumb['message'] ?? $breadcrumb['sql'] ?? $breadcrumb['url'] ?? '' }}</p>
                               @if(isset($breadcrumb['duration_ms']))
                                   <span class="text-xs text-gray-500 mt-1">{{ number_format($breadcrumb['duration_ms'], 1) }}ms</span>
                               @endif
                           </div>
                       </div>
                   @endforeach
               </div>
           </div>
       @else
           <p class="text-sm text-gray-400 italic">No breadcrumbs captured for this exception.</p>
       @endif
   </div>
   ```

2. Breadcrumb data structure (from `ExceptionCollector.php` or ingestion API):
   ```json
   [
     {"type": "log", "level": "info", "message": "User authenticated", "timestamp": "2026-06-04T18:00:00Z"},
     {"type": "query", "sql": "SELECT * FROM users WHERE id = ?", "duration_ms": 12.5, "timestamp": "..."},
     {"type": "request", "url": "/api/checkout", "method": "POST", "timestamp": "..."},
     {"type": "log", "level": "error", "message": "Payment processing failed: timeout", "timestamp": "..."}
   ]
   ```

**Acceptance Criteria:**
- [ ] Breadcrumbs render as a vertical timeline with dot indicators
- [ ] Different breadcrumb types (log, query, request) have distinct colors
- [ ] Timestamps are displayed for each breadcrumb
- [ ] Query breadcrumbs show duration
- [ ] Empty state shows when no breadcrumbs exist
- [ ] Timeline works in both light and dark mode

---

### Task W1-004: Add Occurrence History Sparkline Chart
**Priority:** MEDIUM | **Effort:** 1.5 hours | **Status:** 🔴 Not Started

**Problem:** Exception detail page shows occurrence count but no visual history of when occurrences happened over time.

**Files to modify:**
- `app/Http/Controllers/Web/ExceptionsController.php`
- `resources/views/exceptions/show.blade.php`

**Implementation Details:**

**Step 1 — Backend:**
- In `ExceptionsController::show()`, add a query for occurrence history:
  ```php
  $occurrenceHistory = DB::table('exceptions')
      ->where('fingerprint', $exception->fingerprint)
      ->where('project_id', $exception->project_id)
      ->selectRaw('DATE(last_seen_at) as date, SUM(occurrence_count) as count')
      ->groupBy('date')
      ->orderBy('date')
      ->limit(30)
      ->get();
  
  $chartLabels = $occurrenceHistory->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M j'));
  $chartData = $occurrenceHistory->pluck('count');
  ```

**Step 2 — Frontend:**
- Add a `<canvas>` element in the exception detail sidebar or above the breadcrumbs:
  ```blade
  <div class="mt-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📈 Occurrence History (30 days)</h4>
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
          <canvas id="occurrenceChart" height="80"></canvas>
      </div>
  </div>
  ```
- Add Chart.js initialization script at bottom of the page:
  ```javascript
  new Chart(document.getElementById('occurrenceChart'), {
      type: 'bar',
      data: {
          labels: @json($chartLabels),
          datasets: [{
              label: 'Occurrences',
              data: @json($chartData),
              backgroundColor: 'rgba(239, 68, 68, 0.5)',
              borderColor: 'rgb(239, 68, 68)',
              borderWidth: 1,
              borderRadius: 2,
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
              x: { grid: { display: false }, ticks: { maxTicksLimit: 7, font: { size: 10 } } },
              y: { beginAtZero: true, ticks: { font: { size: 10 } } }
          }
      }
  });
  ```

**Acceptance Criteria:**
- [ ] Bar chart shows daily occurrence counts for the last 30 days
- [ ] Chart is responsive and fits within the exception detail layout
- [ ] Empty state when only one occurrence (single bar)
- [ ] Chart respects dark mode (colors adapt or use CSS variables)

---

### Task W1-005: Fix Dark Mode Toggle Persistence
**Priority:** MEDIUM | **Effort:** 30 min | **Status:** 🔴 Not Started

**Problem:** Dark mode toggle works but preference is lost on page reload because it's not persisted to localStorage.

**Files to modify:**
- `resources/js/app.js`
- `resources/views/layouts/app.blade.php`

**Implementation Details:**

**Step 1 — In `resources/js/app.js`, add:**
```javascript
// Dark mode persistence
const darkModeToggle = document.getElementById('dark-mode-toggle');
const html = document.documentElement;

// Check saved preference or system preference
const savedTheme = localStorage.getItem('appswatch-theme');
const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
    html.classList.add('dark');
} else {
    html.classList.remove('dark');
}

// Toggle handler
if (darkModeToggle) {
    darkModeToggle.addEventListener('click', () => {
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('appswatch-theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('appswatch-theme', 'dark');
        }
    });

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('appswatch-theme')) {
            if (e.matches) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }
    });
}
```

**Step 2 — In `app.blade.php`, ensure dark mode toggle button has `id="dark-mode-toggle"`**

**Acceptance Criteria:**
- [ ] Dark mode preference persists across page reloads
- [ ] First visit respects system preference (OS dark mode setting)
- [ ] Explicit user toggle overrides system preference
- [ ] "Reset to system" option clears localStorage and falls back to OS preference

---

## ⚡ PHASE 2 — Integration Data Collectors (Week 2-3)

### Task INT-001: Google Analytics 4 Data Collector
**Priority:** HIGH | **Effort:** 3-4 hours | **Status:** 🔴 Not Started

**Problem:** Project settings have GA4 config fields (Measurement ID, Property ID, API Secret) but no code collects or displays GA4 data.

**Files to create:**
- `app/Services/Integrations/GoogleAnalyticsService.php`
- `app/Console/Commands/Integrations/PollGoogleAnalyticsCommand.php`

**Files to modify:**
- `routes/console.php` (register schedule)
- `config/services.php` (add GA4 defaults)

**Implementation Details:**

**Step 1 — GoogleAnalyticsService:**
- Constructor reads config from `$project->integrations_config['google_analytics']`
- Method `fetchMetrics($project, $days = 1)`:
  - Use Google Analytics Data API v1 (REST)
  - Authenticate using service account credentials stored in `integrations_config`
  - Fetch metrics:
    - `activeUsers` (real-time or daily)
    - `screenPageViews` (page views)
    - `sessions`
    - `bounceRate`
    - `averageSessionDuration`
    - `eventCount` (total events)
    - `conversions` (if configured)
  - Fetch dimensions: `pagePath`, `country`, `deviceCategory`
  - Fetch top 10 pages by page views
  - Return structured array ready for `IntegrationMetric` storage
- Method `storeMetrics($project, $metrics)`:
  - Loop through metrics, insert into `integration_metrics`:
    - `integration`: 'google_analytics'
    - `metric_name`: e.g., 'page_views', 'active_users', 'sessions'
    - `metric_value`: float
    - `unit`: 'count', 'percent', 'seconds'
    - `dimensions`: JSON with page, country, device, etc.
    - `recorded_at`: now
- Use Guzzle for API calls
- Handle API errors gracefully (log, don't throw)
- Respect rate limits (GA4 Data API: 50,000 requests/day for standard properties)
- Cache credentials in memory during command execution

**Step 2 — PollGoogleAnalyticsCommand:**
- Signature: `appswatch:integrations:poll-ga4`
- Description: "Poll Google Analytics 4 metrics for all enabled projects"
- Logic:
  1. Query `Project::where('integrations_config->google_analytics->enabled', true)->where('is_active', true)`
  2. For each project, instantiate `GoogleAnalyticsService`
  3. Call `fetchMetrics()` then `storeMetrics()`
  4. Log summary: "GA4: Project {name} — {n} metrics stored"
  5. Handle projects with invalid/missing credentials (log warning, skip)

**Step 3 — Dashboard Integration:**
- In `DashboardController.php`, GA4 metrics are already queried from `IntegrationMetric`:
  ```php
  $ga4Metrics = IntegrationMetric::where('project_id', $project->id)
      ->where('integration', 'google_analytics')
      ->where('recorded_at', '>=', now()->subDay())
      ->get();
  ```
- Add to dashboard blade: GA4 card showing page views, active users, top pages table
  - Only render if project has GA4 enabled in integrations_config

**Step 4 — Schedule:**
- Add to `routes/console.php`:
  ```php
  Schedule::command('appswatch:integrations:poll-ga4')->everyThirtyMinutes();
  ```

**Acceptance Criteria:**
- [ ] GA4 service authenticates using service account JSON credentials
- [ ] Command polls all projects with GA4 enabled
- [ ] Metrics stored in `integration_metrics` table
- [ ] Dashboard shows GA4 card with page views / active users
- [ ] Command logs errors for projects with invalid credentials
- [ ] No API calls made for projects with GA4 disabled

---

### Task INT-002: Google Search Console Data Collector
**Priority:** MEDIUM | **Effort:** 3-4 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/Integrations/GoogleSearchConsoleService.php`
- `app/Console/Commands/Integrations/PollGoogleSearchConsoleCommand.php`

**Files to modify:**
- `routes/console.php`

**Implementation Details:**

**Step 1 — GoogleSearchConsoleService:**
- Uses Google Search Console API v1
- Authenticate via OAuth 2.0 service account or OAuth web flow (stored in `integrations_config`)
- Method `fetchSearchAnalytics($project, $days = 28)`:
  - Fetch:
    - `clicks`
    - `impressions`
    - `ctr` (click-through rate)
    - `position` (average search position)
  - Dimensions: `query` (search terms), `page` (landing pages), `country`, `device`
  - Top 20 queries by clicks
  - Top 20 pages by clicks
- Method `fetchSitemapStatus($project)`:
  - List sitemaps, check last submitted/downloaded dates
  - Report any errors
- Method `fetchCoverageIssues($project)`:
  - Pages with errors, warnings, excluded
  - Group by issue type

**Step 2 — PollGoogleSearchConsoleCommand:**
- Signature: `appswatch:integrations:poll-gsc`
- Schedule: every 6 hours
- Iterates enabled projects, fetches metrics, stores in `integration_metrics`

**Step 3 — Dashboard Integration:**
- GSC card: Total clicks, impressions, CTR, avg position
- Top queries table
- Coverage issues count

**Acceptance Criteria:**
- [ ] GSC data polled every 6 hours for enabled projects
- [ ] Metrics stored with dimensions (query, page, country, device)
- [ ] Dashboard shows GSC metrics when integration enabled

---

### Task INT-003: Cloudflare Analytics Data Collector
**Priority:** MEDIUM | **Effort:** 3 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/Integrations/CloudflareService.php`
- `app/Console/Commands/Integrations/PollCloudflareCommand.php`

**Files to modify:**
- `routes/console.php`

**Implementation Details:**

**Step 1 — CloudflareService:**
- Uses Cloudflare GraphQL Analytics API
- Requires: API Token, Zone ID, (optionally Account ID)
- Method `fetchZoneAnalytics($project, $hours = 24)`:
  - GraphQL query:
    ```graphql
    {
      viewer {
        zones(filter: {zoneTag: $zoneTag}) {
          httpRequests1hGroups(limit: 24, filter: {datetime_geq: $since}) {
            dimensions { datetime }
            sum { requests, pageViews, bytes, threats, cachedBytes, cachedRequests }
            uniq { uniques }
          }
        }
      }
    }
    ```
  - Fetch also: bandwidth, cache hit ratio, threat count, WAF events
- Method `fetchSecurityEvents($project)`:
  - Recent WAF events, firewall rules triggered, DDoS alerts
- Use Cloudflare API v4 for non-GraphQL endpoints

**Step 2 — PollCloudflareCommand:**
- Signature: `appswatch:integrations:poll-cloudflare`
- Schedule: every 15 minutes

**Acceptance Criteria:**
- [ ] Cloudflare metrics polled every 15 minutes
- [ ] Dashboard shows: requests, bandwidth, cache ratio, threats blocked
- [ ] Security events tracked

---

### Task INT-004: Microsoft Clarity Data Collector
**Priority:** LOW | **Effort:** 3 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/Integrations/MicrosoftClarityService.php`
- `app/Console/Commands/Integrations/PollClarityCommand.php`

**Implementation Details:**
- Uses Microsoft Clarity API (REST)
- Requires: Project ID, API Token
- Fetch: total sessions, rage clicks, dead clicks, excessive scrolling, JS errors, session recordings count
- Schedule: every 60 minutes

**Acceptance Criteria:**
- [ ] Clarity metrics polled every 60 minutes
- [ ] Dashboard shows: rage clicks, dead clicks, JS errors count
- [ ] Correlate Clarity JS errors with Appswatch exceptions

---

### Task INT-005: Stripe Data Collector
**Priority:** HIGH | **Effort:** 4 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/Integrations/StripeService.php`
- `app/Console/Commands/Integrations/PollStripeCommand.php`
- `app/Http/Controllers/Api/StripeWebhookController.php`

**Files to modify:**
- `routes/api.php`
- `routes/console.php`

**Implementation Details:**

**Step 1 — StripeService:**
- Uses Stripe PHP SDK (`stripe/stripe-php` — add to composer.json)
- Requires: Secret Key
- Method `fetchRevenueMetrics($project)`:
  - MRR (Monthly Recurring Revenue) from subscriptions
  - ARR (Annual Recurring Revenue)
  - Successful charges count + total amount (today)
  - Failed charges count
  - Refunds count + total amount
  - Disputes (open count)
  - Subscription churn rate (canceled / active)
  - Active subscriptions count
- Method `fetchRecentCharges($project, $limit = 50)`:
  - Recent charge list for activity feed

**Step 2 — PollStripeCommand:**
- Signature: `appswatch:integrations:poll-stripe`
- Schedule: every 30 minutes

**Step 3 — StripeWebhookController:**
- Endpoint: `POST /api/stripe/webhook`
- Verify webhook signature using Stripe SDK
- Handle events:
  - `charge.succeeded` / `charge.failed` → update metrics in real-time
  - `invoice.payment_succeeded` / `invoice.payment_failed`
  - `customer.subscription.created` / `customer.subscription.deleted`
  - `charge.dispute.created` / `charge.dispute.closed`
  - `charge.refunded`
- Store events as `integration_metrics` with type=stripe
- Optionally trigger alerts on `charge.failed` or `dispute.created`

**Acceptance Criteria:**
- [ ] Stripe metrics polled every 30 minutes
- [ ] Webhook endpoint receives real-time events
- [ ] Dashboard shows: MRR, successful/failed charges, refunds, disputes, churn
- [ ] Revenue vs error correlation possible (overlay charts)

---

### Task INT-006: GitHub/GitLab Integration Collector
**Priority:** MEDIUM | **Effort:** 4 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/Integrations/GitHubService.php`
- `app/Services/Integrations/GitLabService.php`
- `app/Http/Controllers/Api/GitHubWebhookController.php`
- `app/Http/Controllers/Api/GitLabWebhookController.php`
- `app/Console/Commands/Integrations/PollGitHubCommand.php`

**Files to modify:**
- `routes/api.php`
- `routes/console.php`

**Implementation Details:**

**Step 1 — GitHubService:**
- Uses GitHub REST API v3
- Requires: Personal Access Token, Repository (owner/repo)
- Method `fetchDeployments($project)`:
  - List recent deployments (GitHub Environments)
  - Track: ref, environment, status, creator, timestamp
  - This enables deployment markers on exception/performance charts
- Method `fetchReleases($project)`:
  - Latest releases with tag names, commit hashes
- Method `fetchWorkflowRuns($project)`:
  - GitHub Actions runs: status (success/failure/in_progress), conclusion, duration
  - Branch, commit, actor
- Method `fetchCommits($project, $since)`:
  - Recent commits (for linking to deployments)
- Webhook events received:
  - `deployment` — new deployment detected
  - `deployment_status` — deployment succeeded/failed
  - `workflow_run` — CI pipeline completed
  - `release` — new release published
  - `push` — new commits (filter to default branch)

**Step 2 — GitLabService:**
- Uses GitLab API v4
- Requires: Host URL (gitlab.com or self-hosted), Project ID, Access Token
- Method `fetchDeployments($project)`:
  - GitLab Environments + Deployments
- Method `fetchPipelines($project)`:
  - GitLab CI pipeline status: running/success/failed/canceled
  - Duration, ref, user
- Method `fetchMergeRequests($project)`:
  - Open/merged MRs
- Webhook events:
  - `Deployment Hook` — deployment started/succeeded/failed
  - `Pipeline Hook` — pipeline status changes
  - `Release Hook` — new release
  - `Push Hook` — commits pushed

**Step 3 — Webhook Controllers:**
- `GitHubWebhookController`: `POST /api/github/webhook`
  - Verify X-Hub-Signature-256 (HMAC-SHA256)
  - Route event to correct handler
  - Store deployment/release/pipeline events
- `GitLabWebhookController`: `POST /api/gitlab/webhook`
  - Verify X-Gitlab-Token header
  - Route event to correct handler

**Step 4 — Deployment Markers on Charts:**
- Store deployments in `integration_metrics` with type=github/gitlab, metric_name=deployment
- In Chart.js: add vertical line annotations at deployment timestamps
- Hover tooltip: "Deploy v2.3.1 by @user at 14:32"

**Acceptance Criteria:**
- [ ] GitHub webhook receives deployment, workflow_run, release events
- [ ] GitLab webhook receives pipeline, deployment events
- [ ] Deployment markers appear as vertical lines on exception/performance charts
- [ ] Quick correlation: "Deploy at 14:32 → Error spike at 14:35"
- [ ] CI/CD status visible on dashboard (passing/failing)

---

### Task INT-007: Email Provider Data Collector (Mailgun/Postmark/SES)
**Priority:** LOW | **Effort:** 3 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/Integrations/MailgunService.php`
- `app/Services/Integrations/PostmarkService.php`
- `app/Services/Integrations/SesService.php`
- `app/Console/Commands/Integrations/PollEmailProviderCommand.php`
- `app/Http/Controllers/Api/MailgunWebhookController.php`

**Files to modify:**
- `routes/api.php`
- `routes/console.php`

**Implementation Details:**

**Step 1 — MailgunService:**
- Uses Mailgun Events API
- Requires: Domain, API Key
- Method `fetchMetrics($project, $days = 1)`:
  - Accepted, Delivered, Failed (permanent/temporary), Opened, Clicked, Complained (spam), Unsubscribed
  - Aggregate counts + rates
- Webhook events: delivered, failed, opened, clicked, complained, unsubscribed

**Step 2 — PostmarkService + SesService:**
- Similar structure, provider-specific API endpoints
- Fetch deliverability metrics: sent, delivered, bounced, opened, clicked, spam complaints

**Step 3 — PollEmailProviderCommand:**
- Signature: `appswatch:integrations:poll-email`
- Schedule: every 30 minutes
- Determines provider from `integrations_config.mail_provider.provider` (mailgun/postmark/ses)
- Instantiates correct service class

**Acceptance Criteria:**
- [ ] Email provider metrics collected based on configured provider
- [ ] Dashboard shows: delivery rate, bounce rate, spam complaint rate
- [ ] Webhooks update metrics in real-time
- [ ] Alert can be created for high bounce rate or delivery failures

---

### Task PKG-001: Publish Client Package to Packagist
**Priority:** HIGH | **Effort:** 2-3 hours | **Status:** 🔴 Not Started

**Files to modify:**
- `packages/appswatch/composer.json`
- Create GitHub repository
- Publish to Packagist

**Implementation Details:**

**Step 1 — Finalize `composer.json`:**
```json
{
    "name": "baklysystems/appswatch",
    "description": "Self-hosted Laravel monitoring agent — auto-collects exceptions, logs, queries, queues, schedules, and HTTP requests",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Mostafa Elbakly",
            "email": "dev@baklysystems.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "illuminate/support": "^11.0",
        "illuminate/queue": "^11.0",
        "illuminate/database": "^11.0",
        "illuminate/http": "^11.0",
        "illuminate/log": "^11.0",
        "illuminate/console": "^11.0",
        "illuminate/events": "^11.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "phpunit/phpunit": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "BaklySystems\\Appswatch\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "BaklySystems\\Appswatch\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "BaklySystems\\Appswatch\\AppswatchServiceProvider"
            ],
            "aliases": {
                "Appswatch": "BaklySystems\\Appswatch\\Facades\\Appswatch"
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

**Step 2 — Package Files Checklist (verify all exist):**
- [x] `src/AppswatchServiceProvider.php` — auto-registers collectors, middleware, commands
- [x] `src/Appswatch.php` — main facade class
- [x] `src/Facades/Appswatch.php` — Laravel facade
- [x] `src/Collectors/ExceptionCollector.php` — hooks into exception handler
- [x] `src/Collectors/LogCollector.php` — Monolog handler for log capture
- [x] `src/Collectors/QueryCollector.php` — DB::listen() for query profiling
- [x] `src/Collectors/QueueCollector.php` — job Processing/Processed/Failed events
- [x] `src/Collectors/ScheduleCollector.php` — scheduled task events
- [x] `src/Collectors/MetricCollector.php` — **MISSING — needs to be created**
- [x] `src/Collectors/MySqlHealthCollector.php` — **MISSING — needs to be created**
- [x] `src/Transport/HttpTransport.php` — sends data to central server
- [x] `src/Transport/AsyncTransport.php` — **MISSING — needs to be created**
- [x] `src/Transport/BufferedTransport.php` — **MISSING — needs to be created**
- [x] `src/Middleware/AppswatchRequestMiddleware.php` — captures HTTP requests
- [x] `src/Middleware/AppswatchJobMiddleware.php` — **MISSING — needs to be created**
- [x] `config/appswatch.php` — configuration file
- [x] `database/migrations/2025_01_01_000000_create_appswatch_buffer_table.php` — buffer table
- [x] `src/Commands/FlushBufferCommand.php` — flushes buffered data

**Step 3 — Create Missing Package Files:**

**`src/Collectors/MetricCollector.php`:**
- Facade method: `Appswatch::metric('name', $value, $unit, $tags)`
- Collects custom gauge/counter/histogram metrics
- Queues for async delivery to ingestion API

**`src/Collectors/MySqlHealthCollector.php`:**
- Runs on schedule (every 5 min via Laravel scheduler in the client app)
- Executes `SHOW STATUS`, `SHOW VARIABLES`, `SHOW PROCESSLIST`
- Collects:
  - `Threads_connected` / `max_connections` → connection saturation %
  - `Threads_running`
  - `Slow_queries` count
  - `Innodb_buffer_pool_read_requests` / `Innodb_buffer_pool_reads` → buffer pool hit rate
  - `Questions` / `Uptime` → QPS (queries per second)
  - `Innodb_rows_read/written/deleted`
  - Replication lag: `SHOW SLAVE STATUS` → `Seconds_Behind_Master`
  - `Key_reads` / `Key_read_requests` → key cache hit rate
- Sends to central server as custom metrics

**`src/Transport/AsyncTransport.php`:**
- Dispatches data to Laravel queue for async delivery
- Non-blocking: returns immediately
- Configurable queue connection/name

**`src/Transport/BufferedTransport.php`:**
- Stores data in local `appswatch_buffer` table
- `FlushBufferCommand` reads buffer and sends to central server
- Retries failed deliveries with exponential backoff

**`src/Middleware/AppswatchJobMiddleware.php`:**
- Wraps queued jobs
- Captures job start/end time, attempts, payload size
- Links failed jobs to exceptions

**Step 4 — Publish:**
1. Create GitHub repo: `baklysystems/appswatch`
2. Push package code to repo
3. Tag version: `git tag v1.0.0 && git push --tags`
4. Submit to Packagist (auto-detected from GitHub)
5. Update README with installation instructions:
   ```bash
   composer require baklysystems/appswatch
   php artisan appswatch:install
   ```
6. Create `appswatch:install` command in the package that:
   - Publishes config file
   - Runs buffer table migration
   - Prompts for: server URL, API key, environment name
   - Verifies connection to central server

**Acceptance Criteria:**
- [ ] Package installable via `composer require baklysystems/appswatch`
- [ ] `php artisan appswatch:install` wizard works
- [ ] All collectors auto-register via ServiceProvider
- [ ] Buffer table migration runs
- [ ] Transport strategies (sync/async/buffered) all work
- [ ] Package sends real data to central server ingestion API
- [ ] Packagist badge shows version + downloads

---

## 📅 PHASE 3 — Advanced Features (Sprint 2)

### Task ADV-001: Statistical Anomaly Detection Engine
**Priority:** HIGH | **Effort:** 5-6 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/AnomalyDetectionService.php`
- `app/Console/Commands/DetectAnomaliesCommand.php`

**Files to modify:**
- `routes/console.php`
- `app/Services/AlertService.php`

**Implementation Details:**

**Step 1 — AnomalyDetectionService:**

**Algorithm — Z-Score Method:**
```php
class AnomalyDetectionService
{
    /**
     * Detect anomalies using Z-score with configurable threshold.
     * 
     * @param array $values Array of timestamp => value pairs (last N data points)
     * @param float $threshold Z-score above which to flag (default: 2.5)
     * @return array Anomalous data points with their Z-scores
     */
    public function detectAnomalies(array $values, float $threshold = 2.5): array
    {
        $dataPoints = array_values($values);
        $count = count($dataPoints);
        
        if ($count < 10) {
            return []; // Not enough data for meaningful detection
        }
        
        // Exclude the latest point, calculate mean + stddev from historical data
        $historical = array_slice($dataPoints, 0, -1);
        $latest = end($dataPoints);
        $latestTimestamp = array_key_last($values);
        
        $mean = array_sum($historical) / count($historical);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $historical)) / count($historical);
        $stdDev = sqrt($variance);
        
        if ($stdDev == 0) {
            return []; // No variation, nothing anomalous
        }
        
        $zScore = abs(($latest - $mean) / $stdDev);
        
        if ($zScore >= $threshold) {
            return [[
                'timestamp' => $latestTimestamp,
                'value' => $latest,
                'mean' => round($mean, 2),
                'stddev' => round($stdDev, 2),
                'z_score' => round($zScore, 2),
                'direction' => $latest > $mean ? 'spike' : 'drop',
                'deviation_pct' => round((($latest - $mean) / $mean) * 100, 1),
            ]];
        }
        
        return [];
    }
    
    /**
     * Check exception rate anomaly for a project.
     */
    public function checkExceptionRate($project): array
    {
        // Get exception counts per 5-minute bucket for last 24 hours
        $buckets = DB::table('exceptions')
            ->where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subHours(24))
            ->selectRaw("DATE_FORMAT(last_seen_at, '%Y-%m-%d %H:%i') as bucket, COUNT(*) as count")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('count', 'bucket')
            ->toArray();
        
        $anomalies = $this->detectAnomalies($buckets, 2.5);
        
        if (!empty($anomalies)) {
            // Store anomaly event
            IntegrationMetric::create([
                'project_id' => $project->id,
                'integration' => 'anomaly_detection',
                'metric_name' => 'exception_rate_anomaly',
                'metric_value' => $anomalies[0]['z_score'],
                'unit' => 'z_score',
                'dimensions' => $anomalies[0],
                'recorded_at' => now(),
            ]);
            
            // Fire anomaly alert
            AlertService::triggerAnomalyAlert($project, 'exception_rate', $anomalies[0]);
        }
        
        return $anomalies;
    }
    
    /**
     * Check response time anomaly.
     */
    public function checkResponseTime($project): array { /* same pattern */ }
    
    /**
     * Check queue failure anomaly.
     */
    public function checkQueueFailures($project): array { /* same pattern */ }
    
    /**
     * Moving average deviation — alternative algorithm.
     * Compares current value to 7-day moving average at same hour.
     */
    public function detectSeasonalAnomaly(array $values, int $lookbackDays = 7): array { /* ... */ }
}
```

**Step 2 — DetectAnomaliesCommand:**
- Signature: `appswatch:detect-anomalies`
- Schedule: every 5 minutes
- For each active project:
  1. `checkExceptionRate()`
  2. `checkResponseTime()`
  3. `checkQueueFailures()`
- Log summary: "Anomalies detected: Project X — 2, Project Y — 0"

**Step 3 — New Alert Type:**
- Add `anomaly_detected` to alert types in `alerts` table
- Alert conditions: `{ "metric": "exception_rate", "z_score_threshold": 2.5, "min_data_points": 10 }`
- Auto-create anomaly alert for each project (can be toggled off)

**Acceptance Criteria:**
- [ ] Anomaly detection runs every 5 minutes
- [ ] Z-score threshold is configurable (default: 2.5)
- [ ] Alerts fire when anomalies detected
- [ ] Dashboard shows anomaly events with severity
- [ ] No false positives with small datasets (< 10 points)
- [ ] Anomaly history stored in integration_metrics for charting

---

### Task ADV-002: Telegram Bot Command System
**Priority:** HIGH | **Effort:** 4-5 hours | **Status:** 🔴 Not Started

**Depends on:** W1-001 (Telegram notification channel)

**Files to create:**
- `app/Services/TelegramCommandHandler.php`

**Files to modify:**
- `app/Http/Controllers/Api/TelegramWebhookController.php`

**Implementation Details:**

**Step 1 — TelegramCommandHandler:**
```php
class TelegramCommandHandler
{
    protected array $commands = [
        '/start' => 'handleStart',
        '/status' => 'handleStatus',
        '/exceptions' => 'handleExceptions',
        '/resolve' => 'handleResolve',
        '/mute' => 'handleMute',
        '/backup' => 'handleBackup',
        '/uptime' => 'handleUptime',
        '/metrics' => 'handleMetrics',
        '/help' => 'handleHelp',
        '/subscribe' => 'handleSubscribe',
        '/unsubscribe' => 'handleUnsubscribe',
        '/projects' => 'handleProjects',
    ];
    
    // handleStart: Welcome message with command list, auto-subscribe chat
    // handleStatus: Project health dashboard via Telegram message
    // handleExceptions: Paginated list of unresolved exceptions
    // handleResolve: /resolve <fingerprint_id> — resolves exception
    // handleMute: /mute <fingerprint_id> [hours] — mutes exception
    // handleBackup: Triggers database backup
    // handleUptime: Current uptime % + last check status
    // handleMetrics: Latest custom metrics
    // handleHelp: List all commands with descriptions
    // handleSubscribe: Subscribe to alerts/digests
    // handleUnsubscribe: Remove subscription
    // handleProjects: List accessible projects (if multi-project user)
}
```

**Step 2 — Inline Keyboard Rich Responses:**
- `/status` response:
  ```
  📊 *My App* Health Report
  ━━━━━━━━━━━━━━━━━━━━
  🔴 Exceptions: 23 today (5 new)
  🟢 Uptime: 99.97% (last 24h)
  🟡 Avg Response: 245ms
  🔴 Queue Failures: 3
  ━━━━━━━━━━━━━━━━━━━━
  [🔄 Refresh] [📋 Exceptions] [⚙️ Settings]
  ```
- `/exceptions` response (paginated, 5 per page):
  ```
  🔴 QueryException × 47
  /api/checkout — SQLSTATE[42S22]
  First seen: 2h ago | Last seen: 5m ago
  [✅ Resolve] [🔇 Mute 1h] [🔇 Mute 24h] [📊 Details]
  
  🟡 ValidationException × 12
  /api/register — email required
  [✅ Resolve] [🔇 Mute 1h] [🔇 Mute 24h] [📊 Details]
  ━━━━━━━━━━━━━━━━━━━━
  Page 1/5 [◀️ Previous] [Next ▶️]
  ```

**Step 3 — Authentication & Authorization:**
- Map Telegram `chat_id` to authorized users/projects
- `telegram_subscriptions` table links chat_id → project_id
- Only subscribed chats can execute commands
- `/start` auto-subscribes to default project
- Admin users can manage subscriptions via web UI

**Acceptance Criteria:**
- [ ] All 12 commands work with appropriate responses
- [ ] Inline keyboards provide quick actions
- [ ] Pagination works for long lists
- [ ] Unauthorized chats get "Not authorized" message
- [ ] Commands respect project boundaries (multi-project isolation)
- [ ] Error handling for invalid inputs (e.g., `/resolve invalid_id`)

---

### Task ADV-003: Incident Timeline View
**Priority:** MEDIUM | **Effort:** 3-4 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Models/IncidentEvent.php` (or use existing models)
- `resources/views/incidents/timeline.blade.php`

**Files to modify:**
- `app/Http/Controllers/Web/IncidentController.php`
- `routes/web.php`

**Implementation Details:**

**Step 1 — Data Aggregation:**
- Merge events from multiple sources into a single timeline:
  - Exceptions (created, status changes)
  - Alerts (triggered, acknowledged)
  - Deployments (from GitHub/GitLab integration)
  - Uptime checks (failed, recovered)
  - Queue jobs (failed)
  - Backups (completed, failed)
- Each event has: `type`, `timestamp`, `severity`, `project_id`, `summary`, `link`

**Step 2 — Timeline View:**
- Filterable by: date range, event types, severity
- Auto-scroll to "now"
- Color-coded: 🔴 errors, 🟡 warnings, 🟢 recoveries, 🔵 deployments, ⚪ info
- Group by hour/day depending on density
- Click event → expand details or navigate to detail page

**Step 3 — Incident Controller:**
- `IncidentController@timeline($projectId)`:
  ```php
  $exceptions = Exception::where('project_id', $projectId)
      ->where('last_seen_at', '>=', $startDate)
      ->get()
      ->map(fn($e) => ['type' => 'exception', ...]);
  
  $alerts = Alert::where('project_id', $projectId)
      ->whereNotNull('last_triggered_at')
      ->where('last_triggered_at', '>=', $startDate)
      ->get()
      ->map(fn($a) => ['type' => 'alert', ...]);
  
  $deployments = IntegrationMetric::where('project_id', $projectId)
      ->where('integration', 'github')
      ->where('metric_name', 'deployment')
      ->where('recorded_at', '>=', $startDate)
      ->get()
      ->map(fn($d) => ['type' => 'deployment', ...]);
  
  $events = collect([...$exceptions, ...$alerts, ...$deployments])
      ->sortByDesc('timestamp')
      ->values();
  ```
- Web route: `GET /projects/{project}/incidents`

**Acceptance Criteria:**
- [ ] Single merged timeline of all event types
- [ ] Filterable by event type and date range
- [ ] Color-coded severity indicators
- [ ] Click events to view details
- [ ] Deployment markers visible alongside error spikes
- [ ] Responsive on mobile

---

### Task ADV-004: Project Health Score
**Priority:** MEDIUM | **Effort:** 3 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/HealthScoreService.php`

**Files to modify:**
- `app/Http/Controllers/DashboardController.php`
- `resources/views/dashboard.blade.php`

**Implementation Details:**

**Health Score Algorithm (0-100):**
```php
class HealthScoreService
{
    /**
     * Calculate composite health score.
     * Components (weighted):
     * - Error rate: 30 points (fewer errors = higher score)
     * - Uptime: 25 points (higher uptime % = higher score)
     * - Response time: 20 points (faster = higher score)
     * - Queue health: 15 points (fewer failures = higher score)
     * - Recent alerts: 10 points (no recent alerts = higher score)
     */
    public function calculate($project): int
    {
        $scores = [
            'error_rate' => $this->scoreErrorRate($project) * 0.30,
            'uptime' => $this->scoreUptime($project) * 0.25,
            'response_time' => $this->scoreResponseTime($project) * 0.20,
            'queue_health' => $this->scoreQueueHealth($project) * 0.15,
            'recent_alerts' => $this->scoreRecentAlerts($project) * 0.10,
        ];
        
        $total = round(array_sum($scores));
        $grade = $total >= 90 ? 'A' : ($total >= 75 ? 'B' : ($total >= 60 ? 'C' : ($total >= 40 ? 'D' : 'F')));
        $color = $total >= 75 ? 'green' : ($total >= 50 ? 'yellow' : 'red');
        
        return compact('total', 'grade', 'color', 'scores', 'project_id');
    }
    
    private function scoreErrorRate($project): float
    {
        // Get exceptions in last 24h, compare to 7-day average
        // Fewer errors today = higher score (up to 100)
        $today = Exception::where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subDay())->count();
        $avg = Exception::where('project_id', $project->id)
            ->where('last_seen_at', '>=', now()->subDays(7))
            ->where('last_seen_at', '<', now()->subDay())->count() / 6;
        
        if ($avg == 0) return 100;
        $ratio = $today / max($avg, 1);
        return max(0, min(100, 100 - ($ratio - 1) * 50));
    }
    
    // Similar methods for uptime, response_time, queue_health, recent_alerts
}
```

**Dashboard Integration:**
- Large health score badge at top of dashboard
- Circular gauge visualization
- Historical score trend (last 30 days)
- Click to see score breakdown by component

**Acceptance Criteria:**
- [ ] Health score calculated for each project
- [ ] Color-coded badge (green/yellow/red) on dashboard
- [ ] Score breakdown shows component scores
- [ ] Historical trend chart
- [ ] Score recalculated on every dashboard load (or cached for 5 min)

---

### Task ADV-005: Saved Filters & Custom Views
**Priority:** LOW | **Effort:** 4 hours | **Status:** 🔴 Not Started

**Files to create:**
- Migration: `create_saved_filters_table.php`
- `app/Models/SavedFilter.php`
- `app/Http/Controllers/Web/SavedFilterController.php`

**Files to modify:**
- `resources/views/exceptions/index.blade.php`
- `resources/views/logs/index.blade.php`
- `routes/web.php`

**Implementation Details:**

**SavedFilter Model:**
- `id` (uuid)
- `project_id` (fk)
- `user_id` (fk, nullable — null = project-wide shared)
- `name` (string)
- `type` (enum: exceptions/logs/queries/requests)
- `filters` (json) — the filter parameters
- `is_default` (boolean) — auto-apply on page load
- `timestamps`

**Controller:**
- `GET /projects/{project}/filters` — list saved filters
- `POST /projects/{project}/filters` — save new filter
- `PUT /filters/{filter}` — update
- `DELETE /filters/{filter}` — delete

**UI:**
- Dropdown next to filter bar: "Saved Views ▼"
- "Save Current Filters" button → modal to name the view
- Shareable URL: `?filter_id=uuid` auto-applies the saved filter
- "Set as Default" option

**Acceptance Criteria:**
- [ ] Users can save filter combinations with custom names
- [ ] Saved filters appear in dropdown on applicable pages
- [ ] Filters can be shared via URL
- [ ] Default filter auto-applied on page load
- [ ] Filters are project-scoped

---

### Task ADV-006: Audit Log
**Priority:** MEDIUM | **Effort:** 3-4 hours | **Status:** 🔴 Not Started

**Files to create:**
- Migration: `create_audit_logs_table.php`
- `app/Models/AuditLog.php`
- `app/Services/AuditService.php`
- `app/Http/Controllers/Web/AuditLogController.php`

**Files to modify:**
- All controllers that perform write operations (ExceptionsController, AlertsController, SettingsController, etc.)

**Implementation Details:**

**AuditLog Model:**
- `id` (uuid)
- `project_id` (fk)
- `user_id` (fk, nullable — null for system actions)
- `action` (string) — e.g., 'exception.resolved', 'alert.created', 'api_key.rotated', 'project.deleted'
- `entity_type` (string) — 'Exception', 'Alert', 'Project', etc.
- `entity_id` (uuid)
- `old_values` (json, nullable) — previous state
- `new_values` (json, nullable) — new state
- `ip_address` (nullable)
- `user_agent` (nullable)
- `timestamp`

**AuditService:**
```php
class AuditService
{
    public static function log($projectId, $userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null)
    {
        AuditLog::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

**Integration Points (add to existing controllers):**
- `ExceptionsController@updateStatus`: log status change
- `AlertsController@store`: log alert creation
- `AlertsController@update`: log alert modification
- `AlertsController@destroy`: log alert deletion
- `SettingsController@update`: log settings change
- `SettingsController@rotateApiKey`: log key rotation
- `ProjectController@destroy`: log project deletion
- `AlertsController@toggle`: log enable/disable

**Audit Log Viewer:**
- Route: `GET /projects/{project}/audit-log`
- Filterable by: action type, user, date range, entity type
- Search by entity ID
- Export to CSV
- Immutable (no delete/edit)

**Acceptance Criteria:**
- [ ] All write operations are logged with user, action, and timestamp
- [ ] Old/new values captured for state changes
- [ ] Audit log viewable by project admins
- [ ] Filterable and searchable
- [ ] Exportable to CSV
- [ ] Audit logs are immutable (no edit/delete in UI)

---

### Task ADV-007: Auto-Resolution Rules Engine
**Priority:** MEDIUM | **Effort:** 4-5 hours | **Status:** 🔴 Not Started

**Files to create:**
- Migration: `create_auto_resolution_rules_table.php`
- `app/Models/AutoResolutionRule.php`
- `app/Services/AutoResolutionService.php`
- `app/Console/Commands/EvaluateAutoResolutionRulesCommand.php`

**Files to modify:**
- `routes/console.php`
- `resources/views/settings/index.blade.php`

**Implementation Details:**

**AutoResolutionRule Model:**
- `id` (uuid)
- `project_id` (fk)
- `name` (string)
- `rule_type` (enum: auto_resolve/auto_mute/auto_ignore)
- `conditions` (json):
  ```json
  {
    "field": "exception_class",
    "operator": "equals|contains|matches_regex|in",
    "value": "ValidationException",
    "additional": {
      "environment": ["staging"],
      "min_occurrence_count": null,
      "max_occurrence_count": 5,
      "days_unresolved": 7,
      "file_pattern": null,
      "message_pattern": null
    }
  }
  ```
- `is_active` (boolean)
- `last_evaluated_at` (nullable)
- `execution_count` (int, default 0) — how many times matched
- `timestamps`

**AutoResolutionService:**
```php
class AutoResolutionService
{
    public function evaluate(): void
    {
        $rules = AutoResolutionRule::with('project')
            ->where('is_active', true)
            ->get()
            ->groupBy('project_id');
        
        foreach ($rules as $projectId => $projectRules) {
            $exceptions = Exception::where('project_id', $projectId)
                ->whereIn('status', ['unresolved', 'muted'])
                ->get();
            
            foreach ($projectRules as $rule) {
                $matches = $exceptions->filter(fn($e) => $this->matchesRule($e, $rule));
                
                foreach ($matches as $exception) {
                    $newStatus = match($rule->rule_type) {
                        'auto_resolve' => 'resolved',
                        'auto_mute' => 'muted',
                        'auto_ignore' => 'ignored',
                    };
                    
                    $exception->update(['status' => $newStatus]);
                    AuditService::log($projectId, null, "auto_{$rule->rule_type}", 'Exception', $exception->id, ['status' => 'unresolved'], ['status' => $newStatus]);
                    $rule->increment('execution_count');
                }
            }
            
            $rule->update(['last_evaluated_at' => now()]);
        }
    }
    
    private function matchesRule($exception, $rule): bool
    {
        $conditions = $rule->conditions;
        
        // Match exception class
        if (!$this->compareValue($exception->class, $conditions['field_operator'] ?? 'equals', $conditions['class'] ?? null)) {
            return false;
        }
        
        // Match environment
        if (!empty($conditions['additional']['environment'])) {
            if (!in_array($exception->environment, $conditions['additional']['environment'])) {
                return false;
            }
        }
        
        // Match days unresolved
        if (!empty($conditions['additional']['days_unresolved'])) {
            if ($exception->last_seen_at->diffInDays(now()) < $conditions['additional']['days_unresolved']) {
                return false;
            }
        }
        
        // Match occurrence count range
        if (isset($conditions['additional']['max_occurrence_count'])) {
            if ($exception->occurrence_count > $conditions['additional']['max_occurrence_count']) {
                return false;
            }
        }
        
        // Match message pattern (regex)
        if (!empty($conditions['additional']['message_pattern'])) {
            if (!preg_match($conditions['additional']['message_pattern'], $exception->message)) {
                return false;
            }
        }
        
        return true;
    }
}
```

**Command:**
- Signature: `appswatch:evaluate-auto-resolution-rules`
- Schedule: every 5 minutes
- Runs `AutoResolutionService::evaluate()`

**Settings UI:**
- "Auto-Resolution Rules" section under Settings
- Table of rules with enable/disable toggle
- Create/edit rule form:
  - Rule type: auto-resolve / auto-mute / auto-ignore
  - Exception class (text input)
  - Environment (checkbox: production, staging, local)
  - Days unresolved threshold
  - Max occurrence count threshold
  - Message regex pattern (advanced)
  - File path pattern (advanced)

**Acceptance Criteria:**
- [ ] Rules engine evaluates every 5 minutes
- [ ] Auto-resolve rules change exception status automatically
- [ ] Auto-mute for staging environment exceptions works
- [ ] Rule conditions support: class, environment, age, count, message regex, file pattern
- [ ] Audit log captures auto-resolution actions
- [ ] UI for creating/managing rules with preview of matching exceptions

---

## 🎯 PHASE 4 — Ecosystem Features (Sprint 3+)

### Task ECO-001: N8N Community Node (n8n-nodes-appswatch)
**Priority:** MEDIUM | **Effort:** 8-10 hours | **Status:** 🔴 Not Started
**Depends on:** W1-002 (N8N webhook channel), W1-001 (Telegram)

**Implementation Details:**
- Create separate GitHub repo: `baklysystems/n8n-nodes-appswatch`
- Implement N8N node with:
  - **Trigger node:** `Appswatch Trigger` — webhook receiver for Appswatch events
  - **Action nodes:**
    - `Resolve Exception` — call Appswatch API to resolve exception by fingerprint ID
    - `Mute Exception` — mute exception for configurable duration
    - `Get Exception Details` — fetch full exception info
    - `Trigger Backup` — kick off database backup
    - `Get Project Status` — fetch health score and metrics
  - **Credentials:** Appswatch API Key + Server URL
- Publish to NPM: `n8n-nodes-appswatch`
- Submit to N8N community node registry

**Workflow Templates (include in docs):**
- "New critical exception → Create Jira ticket + Slack message"
- "Deployment detected → Monitor error rate for 30 min → Alert if spike"
- "Stripe dispute created → Create PagerDuty incident + Email finance"
- "Uptime check failed 3 times in a row → SMS on-call engineer"

**Acceptance Criteria:**
- [ ] NPM package published
- [ ] Trigger node receives Appswatch webhook events
- [ ] Action nodes successfully call Appswatch API
- [ ] Authenticated via API key
- [ ] Example workflow templates included in README
- [ ] Node appears in N8N community node list

---

### Task ECO-002: Prometheus Exporter Endpoint
**Priority:** LOW | **Effort:** 3-4 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Http/Controllers/Api/PrometheusMetricsController.php`

**Files to modify:**
- `routes/api.php`

**Implementation Details:**
- Endpoint: `GET /api/metrics/prometheus`
- Authenticated via API key query param or bearer token
- Expose metrics in Prometheus text format:
  ```
  # HELP appswatch_exceptions_total Total exceptions by project and status
  # TYPE appswatch_exceptions_total gauge
  appswatch_exceptions_total{project="my-app",status="unresolved"} 47
  appswatch_exceptions_total{project="my-app",status="resolved"} 230
  
  # HELP appswatch_uptime_percent Uptime percentage last 24h
  # TYPE appswatch_uptime_percent gauge
  appswatch_uptime_percent{project="my-app",url="https://example.com"} 99.97
  
  # HELP appswatch_avg_response_time_ms Average response time last hour
  # TYPE appswatch_avg_response_time_ms gauge
  appswatch_avg_response_time_ms{project="my-app"} 145
  
  # HELP appswatch_queue_failures_total Failed queue jobs by queue name
  # TYPE appswatch_queue_failures_total counter
  appswatch_queue_failures_total{project="my-app",queue="default"} 12
  ```
- Optional: add to `config/services.php`
  ```php
  'prometheus' => [
      'enabled' => env('PROMETHEUS_EXPORTER_ENABLED', false),
      'api_key' => env('PROMETHEUS_EXPORTER_API_KEY'),
  ],
  ```

**Acceptance Criteria:**
- [ ] `/api/metrics/prometheus` returns valid Prometheus format
- [ ] Authenticated via API key
- [ ] All core metrics exposed with correct labels
- [ ] Grafana can add Appswatch as Prometheus data source

---

### Task ECO-003: Scheduled Reports (PDF + Email)
**Priority:** LOW | **Effort:** 5-6 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Services/ReportService.php`
- `app/Console/Commands/SendWeeklyReportCommand.php`
- `app/Jobs/SendReportEmail.php`
- `resources/views/reports/weekly.blade.php`

**Implementation Details:**
- Generate PDF using `barryvdh/laravel-dompdf`
- Weekly report contents:
  - Project name + date range
  - Health score trend chart
  - Exception summary (total new, resolved, top 5 by occurrence)
  - Uptime percentage
  - Avg response time trend
  - Queue health (total processed, failed, avg duration)
  - Top slow queries
  - Alerts triggered this week
  - Revenue metrics (if Stripe integrated)
  - Traffic metrics (if GA4 integrated)
- Generation: `ReportService::generateWeekly($project)`
- Email: `SendReportEmail` job attaches PDF and sends to configured recipients
- Slack/Discord digest: plain text summary posted to channel
- Schedule: every Monday at 8 AM (configurable per project)

**Acceptance Criteria:**
- [ ] PDF report generated with all sections
- [ ] Charts rendered in PDF
- [ ] Email delivery works
- [ ] Slack/Discord digest posted
- [ ] Configurable schedule per project
- [ ] Recipients configurable (multiple email + channels)

---

### Task ECO-004: PWA with Push Notifications
**Priority:** LOW | **Effort:** 5-6 hours | **Status:** 🔴 Not Started

**Files to create:**
- `public/service-worker.js`
- `public/manifest.json`
- `resources/js/pwa.js`

**Implementation Details:**
- Service worker caches dashboard assets for offline access
- Push notifications via Web Push API
- Manifest for "Add to Home Screen"
- Push notification trigger: when alert fires, push to subscribed browsers
- Backend: `app/Services/PushNotificationService.php` using `web-push-php` library
- Migration: `create_push_subscriptions_table.php`

**Acceptance Criteria:**
- [ ] "Install App" prompt on mobile
- [ ] Dashboard loads offline (cached)
- [ ] Push notifications received for critical alerts
- [ ] Push subscription management in settings

---

### Task ECO-005: RBAC — Team Roles & Permissions
**Priority:** MEDIUM | **Effort:** 6-8 hours | **Status:** 🔴 Not Started

**Files to create:**
- Migration: `create_roles_table.php`
- Migration: `create_project_user_table.php`
- `app/Models/Role.php`
- `app/Policies/ExceptionPolicy.php`
- `app/Policies/ProjectPolicy.php`
- `app/Policies/AlertPolicy.php`

**Implementation Details:**
- Roles: `owner`, `admin`, `developer`, `viewer`
- Permissions matrix:
  | Permission | Owner | Admin | Developer | Viewer |
  |---|---|---|---|---|
  | View exceptions/logs | ✅ | ✅ | ✅ | ✅ |
  | Resolve/mute exceptions | ✅ | ✅ | ✅ | ❌ |
  | Delete exceptions | ✅ | ✅ | ❌ | ❌ |
  | Create/edit alerts | ✅ | ✅ | ❌ | ❌ |
  | Change project settings | ✅ | ✅ | ❌ | ❌ |
  | Manage API keys | ✅ | ❌ | ❌ | ❌ |
  | Delete project | ✅ | ❌ | ❌ | ❌ |
  | Manage team members | ✅ | ❌ | ❌ | ❌ |
  | View audit log | ✅ | ✅ | ❌ | ❌ |
- `ProjectUser` pivot table: `project_id`, `user_id`, `role_id`
- Middleware: `EnsureProjectAccess` — checks auth + role before project-scoped routes
- Policies for granular authorization
- UI: "Team" tab in project settings — invite users, manage roles

**Acceptance Criteria:**
- [ ] Users can be invited to projects with specific roles
- [ ] Role-based access enforced on all controller actions
- [ ] UI elements hidden based on permissions
- [ ] Role management UI in project settings
- [ ] Super admin can see/manage all projects

---

### Task ECO-006: Swagger/Scramble API Documentation
**Priority:** LOW | **Effort:** 2-3 hours | **Status:** 🔴 Not Started

**Files to modify:**
- `composer.json` (add `dedoc/scramble`)
- All API controllers (add PHPDoc annotations)

**Implementation Details:**
- Install Scramble: `composer require dedoc/scramble`
- Generate docs automatically from controllers
- Accessible at `/docs/api`
- Include all 7 ingestion endpoints + health check + Prometheus endpoint
- Add example request/response bodies

**Acceptance Criteria:**
- [ ] `/docs/api` accessible after login
- [ ] All ingestion endpoints documented with request/response examples
- [ ] Authentication documented (Bearer token)
- [ ] Rate limiting documented

---

### Task ECO-007: Meilisearch Full-Text Search
**Priority:** LOW | **Effort:** 4-5 hours | **Status:** 🔴 Not Started

**Implementation Details:**
- Install Meilisearch (or use Laravel Scout with Meilisearch driver)
- Index: `exceptions` (message, class, file, stack_trace, breadcrumbs)
- Index: `logs` (message, context, channel)
- Search UI: search bar with real-time results dropdown
- Filters combinable with search (status, severity, date range)
- Highlighting of matched terms

**Acceptance Criteria:**
- [ ] Real-time search across exceptions and logs
- [ ] Search results show highlighted matching terms
- [ ] Combined with existing filters
- [ ] Results load within 200ms
- [ ] Meilisearch instance optionally configurable (or built-in via Laravel Scout)

---

### Task ECO-008: Laravel Reverb Real-Time Updates
**Priority:** MEDIUM | **Effort:** 5-6 hours | **Status:** 🔴 Not Started

**Files to create:**
- `app/Events/ExceptionCreated.php`
- `app/Events/AlertTriggered.php`
- `app/Events/UptimeCheckFailed.php`
- `resources/js/reverb.js`

**Implementation Details:**
- Install Laravel Reverb
- Configure WebSocket server
- Events:
  - `ExceptionCreated` — broadcast new exception to project channel
  - `ExceptionStatusChanged` — broadcast status update
  - `AlertTriggered` — broadcast alert fire
  - `UptimeCheckFailed` / `UptimeCheckRecovered`
  - `DeploymentDetected`
  - `BackupCompleted` / `BackupFailed`
- Frontend: listen on private channel `project.{id}`
- Real-time updates on:
  - Dashboard stats cards (auto-increment when new data arrives)
  - Log tail (stream new log entries)
  - Exception list (new items appear at top without refresh)
  - Alert notifications (toast popups)

**Acceptance Criteria:**
- [ ] Dashboard stats auto-update within 2 seconds of new data
- [ ] Log tail streams real-time entries
- [ ] Toast notifications for new critical exceptions and alert fires
- [ ] WebSocket connection state indicator in UI
- [ ] Reconnects automatically on disconnect

---

## 📋 SUMMARY: Task Counts by Phase

| Phase | Tasks | Total Est. Hours | Priority |
|---|---|---|---|
| Phase 0 — Bug Fixes | 2 | 0.5h | CRITICAL |
| Phase 1 — Week 1 Quick Wins | 5 | 8h | HIGH |
| Phase 2 — Integration Collectors | 8 | 27h | HIGH |
| Phase 3 — Advanced Features (Sprint 2) | 7 | 30h | MEDIUM |
| Phase 4 — Ecosystem (Sprint 3+) | 8 | 46h | LOW |
| **Total** | **30** | **~111.5h** | |

---

*Generated from `phase2.md` gap analysis — Last updated: 2026-06-04*