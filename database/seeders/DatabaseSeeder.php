<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\ApiKey;
use App\Models\AppException;
use App\Models\LogEntry;
use App\Models\QueueJob;
use App\Models\HttpRequest;
use App\Models\ScheduledTask;
use App\Models\DatabaseQuery;
use App\Models\Alert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@appswatch.dev',
            'password' => bcrypt('password'),
            'role' => \App\Models\User::ROLE_SUPER_ADMIN,
        ]);

        // ---------- Project 1: Demo Laravel App ----------
        $this->createProject($user, [
            'name' => 'Demo Laravel App',
            'slug' => 'demo-laravel-app',
            'environment' => 'production',
            'metadata' => ['framework' => 'Laravel 12.x', 'php_version' => '8.2'],
        ], true);

        // ---------- Project 2: Staging API ----------
        $this->createProject($user, [
            'name' => 'Staging API',
            'slug' => 'staging-api',
            'environment' => 'staging',
            'metadata' => ['framework' => 'Laravel 11.x', 'php_version' => '8.3'],
        ], false);

        // ---------- Project 3: E-Commerce Platform ----------
        $this->createProject($user, [
            'name' => 'E-Commerce Platform',
            'slug' => 'ecommerce-platform',
            'environment' => 'production',
            'metadata' => ['framework' => 'Laravel 12.x', 'php_version' => '8.2', 'database' => 'MySQL 8.0'],
        ], true);

        // ---------- Project 4: Mobile Backend ----------
        $this->createProject($user, [
            'name' => 'Mobile Backend',
            'slug' => 'mobile-backend',
            'environment' => 'development',
            'metadata' => ['framework' => 'Laravel 11.x', 'php_version' => '8.3'],
        ], false);

        $this->command->info('Demo data created successfully!');
        $this->command->info('User: admin@appswatch.dev / password');
    }

    private function createProject(User $user, array $config, bool $verbose): void
    {
        $rawApiKey = 'asw_' . Str::random(40);
        $prefix = substr($rawApiKey, 0, 8);

        $project = Project::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => $config['name'],
            'api_key' => password_hash($rawApiKey, PASSWORD_BCRYPT),
            'api_key_prefix' => $prefix,
            'environment' => $config['environment'],
            'slug' => $config['slug'],
            'retention_days' => 30,
            'rate_limit' => 600,
            'is_active' => true,
            'metadata' => $config['metadata'],
        ]);

        ApiKey::create([
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'key' => password_hash($rawApiKey, PASSWORD_BCRYPT),
            'key_prefix' => $prefix,
            'name' => ucfirst($config['environment']) . ' Server',
        ]);

        $this->createExceptions($project);
        $this->createLogEntries($project);
        $this->createQueueJobs($project);
        $this->createHttpRequests($project);
        $this->createScheduledTasks($project);
        $this->createDatabaseQueries($project);
        $this->createAlerts($project);

        if ($verbose) {
            $this->command->info("Project: {$config['name']} | API Key: {$rawApiKey}");
        }
    }

    // ==================== EXCEPTIONS ====================

    private function createExceptions(Project $project): void
    {
        $exceptionClasses = [
            'App\\Exceptions\\PaymentFailedException',
            'App\\Exceptions\\ValidationException',
            'App\\Exceptions\\ThirdPartyApiException',
            'App\\Exceptions\\DatabaseConnectionException',
            'App\\Exceptions\\RateLimitExceededException',
            'App\\Exceptions\\TimeoutException',
            'App\\Exceptions\\AuthenticationException',
        ];

        $exceptionMessages = [
            'Payment processing failed for order #%d: insufficient funds',
            'Validation failed for field "%s": value is required',
            'Third-party API returned 500 error: upstream service unavailable',
            'Database connection to replica-01 timed out after %d seconds',
            'Rate limit of %d requests per minute exceeded for IP %s',
            'Request to external service timed out after %d ms',
            'Invalid API token provided for user #%d',
        ];

        $exceptionFiles = [
            'app/Services/PaymentService.php',
            'app/Http/Controllers/Api/OrderController.php',
            'app/Services/Integrations/ThirdPartyService.php',
            'app/Providers/DatabaseServiceProvider.php',
            'app/Http/Middleware/RateLimiter.php',
            'app/Services/HttpClient.php',
            'app/Http/Middleware/Authenticate.php',
        ];

        $severities = ['critical', 'error', 'error', 'warning', 'warning', 'error', 'critical'];
        $statuses = ['unresolved', 'unresolved', 'resolved', 'unresolved', 'ignored', 'unresolved', 'resolved'];
        $lines = [142, 89, 256, 73, 45, 112, 31];

        $requestBase = [
            'production' => 'https://api.example.com',
            'staging' => 'https://staging.example.com',
            'development' => 'https://dev.example.com',
        ];

        $baseUrl = $requestBase[$project->environment] ?? 'https://api.example.com';

        for ($i = 0; $i < 7; $i++) {
            $class = $exceptionClasses[$i];
            $file = $exceptionFiles[$i];

            AppException::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'fingerprint' => md5($class . '|' . $file . '|' . $lines[$i]),
                'class' => $class,
                'message' => $exceptionMessages[$i],
                'file' => $file,
                'line' => $lines[$i],
                'code_snippet' => $this->generateCodeSnippet($lines[$i]),
                'stack_trace' => $this->generateStackTrace($file, $lines[$i]),
                'request_data' => [
                    'url' => $baseUrl . '/api/orders',
                    'method' => $i % 2 === 0 ? 'POST' : 'GET',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'ip' => '192.168.1.' . ($i + 10),
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Appswatch/' . rand(1, 99),
                ],
                'user_data' => [
                    'id' => 42 + $i,
                    'email' => 'customer' . ($i + 1) . '@example.com',
                ],
                'environment' => $project->environment,
                'release' => 'v2.5.' . ($i + 1),
                'severity' => $severities[$i],
                'status' => $statuses[$i],
                'occurrence_count' => rand(1, 120),
                'first_seen_at' => now()->subDays(rand(1, 30)),
                'last_seen_at' => now()->subMinutes(rand(1, 1440)),
            ]);
        }
    }

    // ==================== LOG ENTRIES ====================

    private function createLogEntries(Project $project): void
    {
        $levels = ['debug', 'info', 'info', 'info', 'warning', 'warning', 'error', 'error', 'critical'];
        $channels = ['laravel', 'laravel', 'laravel', 'security', 'slack', 'daily'];
        $files = [
            'app/Http/Controllers/Api/OrderController.php',
            'app/Services/PaymentService.php',
            'app/Services/NotificationService.php',
            'app/Jobs/ProcessOrder.php',
            'app/Listeners/SendOrderConfirmation.php',
            'app/Console/Commands/CleanOldSessions.php',
        ];

        $messages = [
            'debug' => [
                'SQL query executed in %dms: %s',
                'Cache hit for key: %s',
                'Starting batch processing for %d records',
            ],
            'info' => [
                'Order #%d created successfully',
                'User #%d logged in',
                'Payment of $%.2f processed for order #%d',
                'Email sent to %s for order confirmation',
                'Scheduled job completed: %s',
            ],
            'warning' => [
                'Slow query detected: %dms for %s',
                'Cache miss for key: %s — regenerating',
                'Memory usage exceeded %dMB on %s',
                'API rate limit approaching: %d/%d requests used',
            ],
            'error' => [
                'Unable to connect to payment gateway: %s',
                'Failed to send email to %s after %d attempts',
                'Queue job failed after %d retries: %s',
            ],
            'critical' => [
                'Database connection lost to %s — failing over to replica',
                'Out of memory: %dMB allocated, %dMB available',
            ],
        ];

        $total = 20;
        for ($i = 0; $i < $total; $i++) {
            $level = $levels[array_rand($levels)];
            $msgTemplates = $messages[$level];
            $msg = $this->fillMessageTemplate($msgTemplates[array_rand($msgTemplates)]);
            $file = $files[array_rand($files)];
            $line = rand(20, 500);

            LogEntry::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'batch_id' => rand(1, 3) === 1 ? (string) Str::uuid() : null,
                'level' => $level,
                'message' => $msg,
                'context' => $this->randomLogContext(),
                'channel' => $channels[array_rand($channels)],
                'file' => $file,
                'line' => $line,
                'trace_id' => (string) Str::uuid(),
                'occurred_at' => now()->subMinutes(rand(1, 4320)),
            ]);
        }
    }

    // ==================== QUEUE JOBS ====================

    private function createQueueJobs(Project $project): void
    {
        $jobs = [
            ['job_name' => 'App\\Jobs\\ProcessOrder', 'queue' => 'orders'],
            ['job_name' => 'App\\Jobs\\SendOrderConfirmation', 'queue' => 'emails'],
            ['job_name' => 'App\\Jobs\\GenerateInvoice', 'queue' => 'invoices'],
            ['job_name' => 'App\\Jobs\\SyncWithThirdParty', 'queue' => 'integrations'],
            ['job_name' => 'App\\Jobs\\ResizeImage', 'queue' => 'media'],
            ['job_name' => 'App\\Jobs\\UpdateSearchIndex', 'queue' => 'search'],
        ];

        $statuses = ['pending', 'processing', 'completed', 'completed', 'completed', 'failed'];
        $connections = ['database', 'redis'];

        for ($i = 0; $i < 12; $i++) {
            $j = $jobs[array_rand($jobs)];
            $status = $statuses[array_rand($statuses)];
            $queuedAt = now()->subMinutes(rand(5, 2880));
            $startedAt = $status !== 'pending' ? $queuedAt->copy()->addSeconds(rand(1, 30)) : null;
            $finishedAt = in_array($status, ['completed', 'failed']) ? $startedAt?->copy()->addSeconds(rand(50, 300000)) : null;
            $durationMs = $finishedAt && $startedAt ? $startedAt->diffInMilliseconds($finishedAt) : null;

            QueueJob::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'connection' => $connections[array_rand($connections)],
                'queue' => $j['queue'],
                'job_name' => $j['job_name'],
                'payload' => ['order_id' => rand(1000, 9999), 'user_id' => rand(1, 500)],
                'attempt' => $status === 'failed' ? rand(1, 3) : 0,
                'max_attempts' => 3,
                'status' => $status,
                'exception_id' => null,
                'queued_at' => $queuedAt,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'duration_ms' => $durationMs,
            ]);
        }
    }

    // ==================== HTTP REQUESTS ====================

    private function createHttpRequests(Project $project): void
    {
        $routes = [
            ['url' => '/api/orders', 'method' => 'GET', 'controller_action' => 'OrderController@index', 'route_name' => 'api.orders.index'],
            ['url' => '/api/orders', 'method' => 'POST', 'controller_action' => 'OrderController@store', 'route_name' => 'api.orders.store'],
            ['url' => '/api/products', 'method' => 'GET', 'controller_action' => 'ProductController@index', 'route_name' => 'api.products.index'],
            ['url' => '/api/products/123', 'method' => 'GET', 'controller_action' => 'ProductController@show', 'route_name' => 'api.products.show'],
            ['url' => '/api/auth/login', 'method' => 'POST', 'controller_action' => 'AuthController@login', 'route_name' => 'api.auth.login'],
            ['url' => '/api/user/profile', 'method' => 'GET', 'controller_action' => 'UserController@profile', 'route_name' => 'api.user.profile'],
            ['url' => '/api/checkout', 'method' => 'POST', 'controller_action' => 'CheckoutController@process', 'route_name' => 'api.checkout.process'],
            ['url' => '/api/search', 'method' => 'GET', 'controller_action' => 'SearchController@query', 'route_name' => 'api.search'],
        ];

        $statusCodes = [200, 200, 200, 200, 200, 201, 204, 301, 302, 400, 401, 403, 404, 422, 500];

        for ($i = 0; $i < 15; $i++) {
            $route = $routes[array_rand($routes)];
            $traceId = (string) Str::uuid();
            $statusCode = $statusCodes[array_rand($statusCodes)];
            $durationMs = $statusCode >= 500 ? rand(500, 5000) : rand(10, 500);

            HttpRequest::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'trace_id' => $traceId,
                'method' => $route['method'],
                'url' => 'https://' . ($project->environment === 'production' ? 'api' : $project->environment) . '.example.com' . $route['url'],
                'route_name' => $route['route_name'],
                'controller_action' => $route['controller_action'],
                'status_code' => $statusCode,
                'duration_ms' => $durationMs,
                'memory_usage_mb' => round(rand(80, 250) / 10, 1),
                'request_headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . Str::random(40),
                ],
                'request_body' => $route['method'] === 'POST' ? ['items' => [['id' => 1, 'qty' => 2]]] : null,
                'response_headers' => ['Content-Type' => 'application/json'],
                'response_body' => $statusCode === 200 ? ['data' => ['id' => rand(1000, 9999)]] : ['error' => 'Resource not found'],
                'ip_address' => '203.0.113.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0) AppleWebKit/605.1.15',
                'user_id' => null,
                'occurred_at' => now()->subMinutes(rand(1, 4320)),
            ]);
        }
    }

    // ==================== SCHEDULED TASKS ====================

    private function createScheduledTasks(Project $project): void
    {
        $tasks = [
            ['command' => 'orders:clean-pending', 'description' => 'Clean pending orders older than 24h', 'expression' => '0 * * * *'],
            ['command' => 'invoices:generate', 'description' => 'Generate monthly invoices', 'expression' => '0 0 1 * *'],
            ['command' => 'cache:prune-stale', 'description' => 'Prune stale cache entries', 'expression' => '*/30 * * * *'],
            ['command' => 'backup:database', 'description' => 'Daily database backup', 'expression' => '0 3 * * *'],
            ['command' => 'emails:send-reminders', 'description' => 'Send payment reminders', 'expression' => '0 10 * * *'],
            ['command' => 'analytics:aggregate', 'description' => 'Aggregate daily analytics', 'expression' => '0 2 * * *'],
        ];

        for ($i = 0; $i < 10; $i++) {
            $task = $tasks[array_rand($tasks)];
            $statuses = ['completed', 'completed', 'completed', 'completed', 'failed', 'started', 'skipped'];
            $status = $statuses[array_rand($statuses)];
            $startedAt = now()->subMinutes(rand(10, 4320));
            $durationMs = rand(200, 120000);
            $finishedAt = in_array($status, ['completed', 'failed']) ? $startedAt->copy()->addMilliseconds($durationMs) : null;

            ScheduledTask::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'command' => $task['command'],
                'description' => $task['description'],
                'expression' => $task['expression'],
                'status' => $status,
                'exception_id' => null,
                'output' => $status === 'completed' ? "Processed " . rand(10, 5000) . " records successfully." : ($status === 'failed' ? 'Error: connection timeout' : null),
                'duration_ms' => $durationMs,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
            ]);
        }
    }

    // ==================== DATABASE QUERIES ====================

    private function createDatabaseQueries(Project $project): void
    {
        $sqlTemplates = [
            ['sql' => 'SELECT * FROM "orders" WHERE "user_id" = ? AND "status" = ?', 'is_slow' => false],
            ['sql' => 'SELECT "products".*, "categories"."name" FROM "products" INNER JOIN "categories" ON "products"."category_id" = "categories"."id" WHERE "products"."is_active" = ?', 'is_slow' => false],
            ['sql' => 'SELECT COUNT(*) AS aggregate FROM "orders" WHERE "created_at" >= ? AND "created_at" <= ?', 'is_slow' => true],
            ['sql' => 'UPDATE "inventory" SET "stock" = "stock" - ? WHERE "product_id" = ? AND "stock" >= ?', 'is_slow' => false],
            ['sql' => 'INSERT INTO "sessions" ("id", "user_id", "ip_address", "payload", "last_activity") VALUES (?, ?, ?, ?, ?)', 'is_slow' => false],
            ['sql' => 'SELECT DISTINCT "users".* FROM "users" LEFT JOIN "orders" ON "users"."id" = "orders"."user_id" WHERE "orders"."total" > ?', 'is_slow' => true],
        ];

        $files = [
            'app/Http/Controllers/Api/OrderController.php',
            'app/Services/PaymentService.php',
            'app/Models/Order.php',
            'app/Repositories/ProductRepository.php',
        ];

        for ($i = 0; $i < 15; $i++) {
            $tpl = $sqlTemplates[array_rand($sqlTemplates)];
            if ($tpl['is_slow']) {
                $durationMs = rand(200, 3000);
            } else {
                $durationMs = rand(1, 150);
            }
            $file = $files[array_rand($files)];

            DatabaseQuery::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'batch_id' => rand(1, 3) === 1 ? (string) Str::uuid() : null,
                'sql' => $tpl['sql'],
                'bindings' => $this->randomBindingsForSql($tpl['sql']),
                'duration_ms' => $durationMs,
                'connection_name' => 'pgsql',
                'file' => $file,
                'line' => rand(15, 400),
                'is_slow' => $tpl['is_slow'] || $durationMs > 200,
                'trace_id' => (string) Str::uuid(),
                'occurred_at' => now()->subMinutes(rand(1, 4320)),
            ]);
        }
    }

    // ==================== ALERTS ====================

    private function createAlerts(Project $project): void
    {
        $alertDefs = [
            [
                'name' => 'High exception rate',
                'type' => 'exception_rate',
                'conditions' => ['threshold' => 10, 'window_minutes' => 5, 'severity' => ['critical', 'error']],
                'channels' => ['mail', 'slack'],
            ],
            [
                'name' => 'Critical log entries',
                'type' => 'log_level',
                'conditions' => ['level' => 'critical', 'min_count' => 1, 'window_minutes' => 1],
                'channels' => ['mail', 'slack', 'webhook'],
            ],
            [
                'name' => 'Queue failure spike',
                'type' => 'queue_failure',
                'conditions' => ['threshold' => 5, 'window_minutes' => 10],
                'channels' => ['slack'],
            ],
            [
                'name' => 'Slow database queries',
                'type' => 'query_slow',
                'conditions' => ['threshold_ms' => 500, 'min_count' => 3, 'window_minutes' => 5],
                'channels' => ['mail'],
            ],
            [
                'name' => 'Memory usage alert',
                'type' => 'metric_threshold',
                'conditions' => ['metric' => 'memory_usage_mb', 'operator' => '>', 'value' => 128, 'window_minutes' => 5],
                'channels' => ['slack'],
            ],
        ];

        foreach ($alertDefs as $def) {
            Alert::create([
                'id' => (string) Str::uuid(),
                'project_id' => $project->id,
                'name' => $def['name'],
                'type' => $def['type'],
                'conditions' => $def['conditions'],
                'channels' => $def['channels'],
                'cooldown_minutes' => rand(5, 30),
                'is_active' => true,
                'last_triggered_at' => rand(0, 1) ? now()->subMinutes(rand(10, 1440)) : null,
            ]);
        }
    }

    // ==================== HELPERS ====================

    private function fillMessageTemplate(string $template): string
    {
        $placeholders = [];
        // Match %d, %s, %.2f patterns
        preg_match_all('/%[dshlucf\.\d]*/', $template, $matches);
        foreach ($matches[0] as $fmt) {
            if (str_starts_with($fmt, '%d')) {
                $placeholders[] = rand(1, 9999);
            } elseif (str_starts_with($fmt, '%s') || str_starts_with($fmt, '%S')) {
                $placeholders[] = $this->randomString();
            } elseif (str_starts_with($fmt, '%f') || str_contains($fmt, '.2f')) {
                $placeholders[] = round(rand(1000, 49999) / 100, 2);
            } else {
                $placeholders[] = rand(1, 9999);
            }
        }
        return vsprintf($template, $placeholders);
    }

    private function randomString(): string
    {
        $words = ['user', 'order', 'payment', 'invoice', 'product', 'cache', 'session', 'token', 'email', 'notification'];
        return $words[array_rand($words)];
    }

    private function randomLogContext(): array
    {
        return [
            'ip' => '203.0.113.' . rand(1, 254),
            'user_id' => rand(1, 500),
            'session_id' => Str::random(40),
            'request_id' => (string) Str::uuid(),
        ];
    }

    private function randomBindingsForSql(string $sql): array
    {
        // Crude: count ? placeholders and fill with random values
        $count = substr_count($sql, '?');
        $bindings = [];
        for ($i = 0; $i < $count; $i++) {
            $bindings[] = match (rand(1, 3)) {
                1 => rand(1, 9999),
                2 => ['pending', 'completed', 'canceled'][rand(0, 2)],
                3 => (string) Str::uuid(),
            };
        }
        return $bindings;
    }

    private function generateCodeSnippet(int $line): array
    {
        $snippet = [];
        for ($i = $line - 5; $i <= $line + 5; $i++) {
            $snippet[$i] = $this->randomCodeLine();
        }
        return $snippet;
    }

    private function randomCodeLine(): string
    {
        $lines = [
            '    public function process(Request $request)',
            '    {',
            '        $data = $request->validate([',
            "            'amount' => 'required|numeric|min:1',",
            "            'currency' => 'required|string|size:3',",
            '        ]);',
            '',
            '        try {',
            '            $result = $this->gateway->charge($data);',
            '            return response()->json($result);',
            '        } catch (GatewayException $e) {',
            '            Log::error("Payment failed", ["error" => $e->getMessage()]);',
            '            throw new PaymentFailedException($e->getMessage());',
            '        }',
            '    }',
            '}',
        ];
        return $lines[array_rand($lines)];
    }

    private function generateStackTrace(string $file, int $line): array
    {
        return [
            [
                'file' => $file,
                'line' => $line,
                'function' => 'process',
                'class' => 'App\\Services\\PaymentService',
                'type' => '->',
            ],
            [
                'file' => 'app/Http/Controllers/Api/OrderController.php',
                'line' => 89,
                'function' => 'store',
                'class' => 'App\\Http\\Controllers\\Api\\OrderController',
                'type' => '->',
            ],
            [
                'file' => 'vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php',
                'line' => 48,
                'function' => 'dispatch',
                'class' => 'Illuminate\\Routing\\ControllerDispatcher',
                'type' => '->',
            ],
            [
                'file' => 'vendor/laravel/framework/src/Illuminate/Routing/Route.php',
                'line' => 254,
                'function' => 'run',
                'class' => 'Illuminate\\Routing\\Route',
                'type' => '->',
            ],
            [
                'file' => 'vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php',
                'line' => 173,
                'function' => 'handle',
                'class' => 'Illuminate\\Foundation\\Http\\Kernel',
                'type' => '->',
            ],
        ];
    }
}