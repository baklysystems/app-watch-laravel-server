<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Settings</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
            <div class="flex items-center gap-2 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 text-sm font-medium" data-flash>
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
            @endif

            <!-- Project Settings -->
            <form method="POST" action="{{ route('settings.update-project', $project->id) }}" class="card">
                @csrf
                @method('PATCH')
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-5">Project Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Project Name</label>
                            <input type="text" name="name" value="{{ old('name', $project->name) }}" class="input-premium">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Data Retention (Days)</label>
                            <input type="number" name="retention_days" min="1" max="365" value="{{ old('retention_days', $project->retention_days) }}" class="input-premium">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Rate Limit (requests/min)</label>
                            <input type="number" name="rate_limit" min="1" value="{{ old('rate_limit', $project->rate_limit) }}" class="input-premium">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                            <select name="is_active" class="select-premium">
                                <option value="1" {{ $project->is_active ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !$project->is_active ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-span-2 pt-2">
                            <button type="submit" class="btn-primary">Save Project Settings</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Integrations Configuration -->
            <form method="POST" action="{{ route('settings.update-integrations', $project->id) }}" class="card">
                @csrf
                @method('PATCH')
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Integrations</h3>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Configure automated health checks, monitoring integrations, and third-party analytics.</p>
                        </div>
                        <button type="submit" class="btn-primary">Save Integrations</button>
                    </div>

                    @php $cfg = $project->integrations_config ?? []; @endphp

                    <div class="space-y-4">
                        <!-- Uptime Monitoring -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Uptime Monitoring</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">HTTP GET checks every minute. Status reflected on dashboard.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[uptime][enabled]" value="0">
                                    <input type="checkbox" name="integrations[uptime][enabled]" value="1" class="sr-only peer" {{ ($cfg['uptime']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">URL to Monitor</label>
                                <input type="url" name="integrations[uptime][url]" value="{{ old('integrations.uptime.url', $cfg['uptime']['url'] ?? '') }}" placeholder="https://example.com" class="input-premium text-sm">
                            </div>
                        </div>

                        <!-- SSL Certificate Check -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">SSL Certificate Check</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Daily TLS certificate validation. Expiry days shown on dashboard.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[ssl_check][enabled]" value="0">
                                    <input type="checkbox" name="integrations[ssl_check][enabled]" value="1" class="sr-only peer" {{ ($cfg['ssl_check']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Domain</label>
                                <input type="text" name="integrations[ssl_check][domain]" value="{{ old('integrations.ssl_check.domain', $cfg['ssl_check']['domain'] ?? '') }}" placeholder="example.com" class="input-premium text-sm">
                            </div>
                        </div>

                        <!-- Domain WHOIS Expiry -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Domain WHOIS Expiry</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Daily WHOIS lookup. Alerts when domain renewal is due.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[domain_expiry][enabled]" value="0">
                                    <input type="checkbox" name="integrations[domain_expiry][enabled]" value="1" class="sr-only peer" {{ ($cfg['domain_expiry']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Domain Name</label>
                                <input type="text" name="integrations[domain_expiry][domain]" value="{{ old('integrations.domain_expiry.domain', $cfg['domain_expiry']['domain'] ?? '') }}" placeholder="example.com" class="input-premium text-sm">
                            </div>
                        </div>

                        <!-- Server Resource Monitoring -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Server Resource Monitoring</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">CPU load, memory %, disk % every 5 min. Shown on dashboard.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[server_monitor][enabled]" value="0">
                                    <input type="checkbox" name="integrations[server_monitor][enabled]" value="1" class="sr-only peer" {{ ($cfg['server_monitor']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">Server resources are collected from the host running the Appswatch instance. No additional configuration needed.</p>
                            </div>
                        </div>

                        <!-- Database Backups -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11V7m3 4V7m3 4V7"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Database Backups</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Daily mysqldump/pg_dump + gzip with configurable retention.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[database_backup][enabled]" value="0">
                                    <input type="checkbox" name="integrations[database_backup][enabled]" value="1" class="sr-only peer" {{ ($cfg['database_backup']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Backup Retention (Days)</label>
                                <input type="number" name="integrations[database_backup][retention_days]" min="1" max="365" value="{{ old('integrations.database_backup.retention_days', $cfg['database_backup']['retention_days'] ?? 7) }}" class="input-premium text-sm w-32">
                            </div>
                        </div>

                        <!-- MySQL Health Monitoring -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">MySQL Health Monitoring</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Connection saturation, replication lag, and slow query detection.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[mysql_health][enabled]" value="0">
                                    <input type="checkbox" name="integrations[mysql_health][enabled]" value="1" class="sr-only peer" {{ ($cfg['mysql_health']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">MySQL Host</label><input type="text" name="integrations[mysql_health][host]" value="{{ old('integrations.mysql_health.host', $cfg['mysql_health']['host'] ?? '127.0.0.1') }}" class="input-premium text-sm"></div>
                                    <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Port</label><input type="number" name="integrations[mysql_health][port]" value="{{ old('integrations.mysql_health.port', $cfg['mysql_health']['port'] ?? 3306) }}" class="input-premium text-sm"></div>
                                    <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Username</label><input type="text" name="integrations[mysql_health][user]" value="{{ old('integrations.mysql_health.user', $cfg['mysql_health']['user'] ?? '') }}" class="input-premium text-sm"></div>
                                    <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Password</label><input type="password" name="integrations[mysql_health][password]" value="{{ old('integrations.mysql_health.password', $cfg['mysql_health']['password'] ?? '') }}" class="input-premium text-sm" placeholder="••••••••"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Service Vitals Monitoring -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Service Vitals Monitoring</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Mail, Queue, Notifications, Redis, Reverb — every 5 min. Dashboard badges.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[service_vitals][enabled]" value="0">
                                    <input type="checkbox" name="integrations[service_vitals][enabled]" value="1" class="sr-only peer" {{ ($cfg['service_vitals']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 flex items-center gap-4">
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic flex-1">Auto-detected from your Laravel configuration. No additional config needed.</p>
                                <button type="button" onclick="testServiceVitals({{ $project->id }})" class="btn-secondary btn-sm shrink-0">🔍 Test Now</button>
                            </div>
                        </div>

                        <!-- Log Retention -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Log Retention & Rotation</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Auto-delete logs older than N days. Cap total log storage.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Retain (Days)</label><input type="number" name="integrations[log_retention][days]" min="1" max="365" value="{{ old('integrations.log_retention.days', $cfg['log_retention']['days'] ?? 30) }}" class="input-premium text-sm w-32"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Max Size (MB)</label><input type="number" name="integrations[log_retention][max_size_mb]" min="1" max="10000" value="{{ old('integrations.log_retention.max_size_mb', $cfg['log_retention']['max_size_mb'] ?? 500) }}" class="input-premium text-sm w-32"></div>
                            </div>
                        </div>

                        <!-- Google Analytics 4 -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 font-bold text-sm">GA</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Google Analytics 4</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Track page views, events, and conversions from GA4 property.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[google_analytics][enabled]" value="0">
                                    <input type="checkbox" name="integrations[google_analytics][enabled]" value="1" class="sr-only peer" {{ ($cfg['google_analytics']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Measurement ID (G-XXXXXXXXXX)</label><input type="text" name="integrations[google_analytics][measurement_id]" value="{{ old('integrations.google_analytics.measurement_id', $cfg['google_analytics']['measurement_id'] ?? '') }}" placeholder="G-XXXXXXXXXX" class="input-premium text-sm"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Property ID</label><input type="text" name="integrations[google_analytics][property_id]" value="{{ old('integrations.google_analytics.property_id', $cfg['google_analytics']['property_id'] ?? '') }}" placeholder="123456789" class="input-premium text-sm"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">API Secret</label><input type="password" name="integrations[google_analytics][api_secret]" value="{{ old('integrations.google_analytics.api_secret', $cfg['google_analytics']['api_secret'] ?? '') }}" class="input-premium text-sm" placeholder="••••••••"></div>
                            </div>
                        </div>

                        <!-- Google Search Console -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm">GSC</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Google Search Console</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Search performance: clicks, impressions, CTR, and average position.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[google_search_console][enabled]" value="0">
                                    <input type="checkbox" name="integrations[google_search_console][enabled]" value="1" class="sr-only peer" {{ ($cfg['google_search_console']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Site URL</label><input type="url" name="integrations[google_search_console][site_url]" value="{{ old('integrations.google_search_console.site_url', $cfg['google_search_console']['site_url'] ?? '') }}" placeholder="https://example.com" class="input-premium text-sm"></div>
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">OAuth JSON Key (paste contents)</label><textarea name="integrations[google_search_console][oauth_key]" rows="3" class="input-premium text-sm font-mono text-xs" placeholder='{"type":"service_account",...}'>{{ old('integrations.google_search_console.oauth_key', $cfg['google_search_console']['oauth_key'] ?? '') }}</textarea></div>
                            </div>
                        </div>

                        <!-- Cloudflare Analytics -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 font-bold text-sm">CF</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cloudflare Analytics</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Traffic, security events, and performance metrics via GraphQL API.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[cloudflare][enabled]" value="0">
                                    <input type="checkbox" name="integrations[cloudflare][enabled]" value="1" class="sr-only peer" {{ ($cfg['cloudflare']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">API Token</label><input type="password" name="integrations[cloudflare][api_token]" value="{{ old('integrations.cloudflare.api_token', $cfg['cloudflare']['api_token'] ?? '') }}" class="input-premium text-sm" placeholder="••••••••"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Zone ID</label><input type="text" name="integrations[cloudflare][zone_id]" value="{{ old('integrations.cloudflare.zone_id', $cfg['cloudflare']['zone_id'] ?? '') }}" placeholder="abc123..." class="input-premium text-sm"></div>
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Account ID</label><input type="text" name="integrations[cloudflare][account_id]" value="{{ old('integrations.cloudflare.account_id', $cfg['cloudflare']['account_id'] ?? '') }}" placeholder="abc123..." class="input-premium text-sm"></div>
                            </div>
                        </div>

                        <!-- Microsoft Clarity -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center text-cyan-600 dark:text-cyan-400 font-bold text-sm">MC</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Microsoft Clarity</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Heatmaps, session recordings, and user behavior analytics.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[microsoft_clarity][enabled]" value="0">
                                    <input type="checkbox" name="integrations[microsoft_clarity][enabled]" value="1" class="sr-only peer" {{ ($cfg['microsoft_clarity']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50">
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Project ID</label>
                                <input type="text" name="integrations[microsoft_clarity][project_id]" value="{{ old('integrations.microsoft_clarity.project_id', $cfg['microsoft_clarity']['project_id'] ?? '') }}" placeholder="abc123..." class="input-premium text-sm">
                            </div>
                        </div>

                        <!-- Stripe -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 font-bold text-sm">ST</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Stripe</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Revenue, MRR, churn, and payment metrics via Stripe API.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[stripe][enabled]" value="0">
                                    <input type="checkbox" name="integrations[stripe][enabled]" value="1" class="sr-only peer" {{ ($cfg['stripe']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Secret Key</label><input type="password" name="integrations[stripe][secret_key]" value="{{ old('integrations.stripe.secret_key', $cfg['stripe']['secret_key'] ?? '') }}" class="input-premium text-sm" placeholder="sk_live_..."></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Webhook Secret</label><input type="password" name="integrations[stripe][webhook_secret]" value="{{ old('integrations.stripe.webhook_secret', $cfg['stripe']['webhook_secret'] ?? '') }}" class="input-premium text-sm" placeholder="whsec_..."></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Account ID</label><input type="text" name="integrations[stripe][account_id]" value="{{ old('integrations.stripe.account_id', $cfg['stripe']['account_id'] ?? '') }}" placeholder="acct_..." class="input-premium text-sm"></div>
                            </div>
                        </div>

                        <!-- GitHub/GitLab -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-700 dark:text-gray-300 font-bold text-sm">GH</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">GitHub / GitLab</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Commits, PRs, issues, and CI/CD pipeline status.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[github][enabled]" value="0">
                                    <input type="checkbox" name="integrations[github][enabled]" value="1" class="sr-only peer" {{ ($cfg['github']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Provider</label><select name="integrations[github][provider]" class="select-premium text-sm"><option value="github" {{ ($cfg['github']['provider'] ?? 'github') == 'github' ? 'selected' : '' }}>GitHub</option><option value="gitlab" {{ ($cfg['github']['provider'] ?? '') == 'gitlab' ? 'selected' : '' }}>GitLab</option></select></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Access Token</label><input type="password" name="integrations[github][access_token]" value="{{ old('integrations.github.access_token', $cfg['github']['access_token'] ?? '') }}" class="input-premium text-sm" placeholder="ghp_..."></div>
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Repository (owner/repo)</label><input type="text" name="integrations[github][repository]" value="{{ old('integrations.github.repository', $cfg['github']['repository'] ?? '') }}" placeholder="owner/repo" class="input-premium text-sm"></div>
                            </div>
                        </div>

                        <!-- Mailgun / Postmark / SES -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400 font-bold text-sm">@</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Mailgun / Postmark / SES</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Email delivery metrics: sent, delivered, opened, bounced, complaints.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[email_service][enabled]" value="0">
                                    <input type="checkbox" name="integrations[email_service][enabled]" value="1" class="sr-only peer" {{ ($cfg['email_service']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Provider</label>
                                    <select name="integrations[email_service][provider]" class="select-premium text-sm">
                                        <option value="mailgun" {{ ($cfg['email_service']['provider'] ?? 'mailgun') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                        <option value="postmark" {{ ($cfg['email_service']['provider'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</option>
                                        <option value="ses" {{ ($cfg['email_service']['provider'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                    </select>
                                </div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">API Key</label><input type="password" name="integrations[email_service][api_key]" value="{{ old('integrations.email_service.api_key', $cfg['email_service']['api_key'] ?? '') }}" class="input-premium text-sm" placeholder="••••••••"></div>
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Domain</label><input type="text" name="integrations[email_service][domain]" value="{{ old('integrations.email_service.domain', $cfg['email_service']['domain'] ?? '') }}" placeholder="mg.example.com" class="input-premium text-sm"></div>
                            </div>
                        </div>

                        <!-- Telegram Notifications -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 font-bold text-sm">TG</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Telegram Notifications</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Send alerts, status reports, and resolve exceptions via Telegram bot with inline buttons.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[telegram][enabled]" value="0">
                                    <input type="checkbox" name="integrations[telegram][enabled]" value="1" class="sr-only peer" {{ ($cfg['telegram']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Bot Token</label><input type="password" name="integrations[telegram][bot_token]" value="{{ old('integrations.telegram.bot_token', $cfg['telegram']['bot_token'] ?? '') }}" class="input-premium text-sm" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"></div>
                                    <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Default Chat ID</label><input type="text" name="integrations[telegram][default_chat_id]" value="{{ old('integrations.telegram.default_chat_id', $cfg['telegram']['default_chat_id'] ?? '') }}" class="input-premium text-sm" placeholder="-1001234567890"></div>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">
                                    <strong>Webhook URL:</strong> <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ url('/api/telegram/webhook') }}</code>
                                    — Set this in BotFather with <code>/setwebhook</code>. Active subscribers receive alerts automatically.
                                </p>
                            </div>
                        </div>

                        <!-- N8N Webhook -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 font-bold text-sm">N8</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">N8N Webhook</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Forward alerts to N8N automation workflows. Trigger custom actions on alert events.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[n8n][enabled]" value="0">
                                    <input type="checkbox" name="integrations[n8n][enabled]" value="1" class="sr-only peer" {{ ($cfg['n8n']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 space-y-3">
                                <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Webhook URL</label><input type="url" name="integrations[n8n][webhook_url]" value="{{ old('integrations.n8n.webhook_url', $cfg['n8n']['webhook_url'] ?? '') }}" placeholder="https://n8n.example.com/webhook/..." class="input-premium text-sm"></div>
                                <div><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Auth Header (optional)</label><input type="text" name="integrations[n8n][auth_header]" value="{{ old('integrations.n8n.auth_header', $cfg['n8n']['auth_header'] ?? '') }}" placeholder="Bearer abc123..." class="input-premium text-sm"></div>
                            </div>
                        </div>

                        <!-- IFTTT Webhook -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm">IF</div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">IFTTT Webhook</h4>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Trigger IFTTT applets on alert events. Control smart devices, send notifications, log to spreadsheets.</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="integrations[ifttt][enabled]" value="0">
                                    <input type="checkbox" name="integrations[ifttt][enabled]" value="1" class="sr-only peer" {{ ($cfg['ifttt']['enabled'] ?? false) ? 'checked' : '' }}>
                                    <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </div>
                            <div class="p-4 border-t border-gray-100 dark:border-gray-700/50 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Webhook Key</label><input type="password" name="integrations[ifttt][webhook_key]" value="{{ old('integrations.ifttt.webhook_key', $cfg['ifttt']['webhook_key'] ?? '') }}" class="input-premium text-sm" placeholder="Your IFTTT webhook key from maker.ifttt.com"></div>
                                    <div class="col-span-2"><label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Event Name</label><input type="text" name="integrations[ifttt][event_name]" value="{{ old('integrations.ifttt.event_name', $cfg['ifttt']['event_name'] ?? 'appswatch_alert') }}" class="input-premium text-sm" placeholder="appswatch_alert"></div>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">
                                    Get your key at <a href="https://ifttt.com/maker_webhooks" target="_blank" class="text-brand-500 hover:underline">IFTTT Webhooks</a>. Value1=alert name, Value2=project+type, Value3=details+URL.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- API Keys -->
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">API Keys</h3>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Used by the <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">baklysystems/appswatch</code> package to send data.</p>
                        </div>
                        <form method="POST" action="{{ route('settings.generate-api-key', $project->id) }}">
                            @csrf
                            <button type="submit" class="btn-secondary btn-sm">Generate New Key</button>
                        </form>
                    </div>
                    @if($project->apiKeys->isEmpty())
                    <div class="text-center py-8"><p class="text-sm text-gray-400 dark:text-gray-500">No API keys yet. Generate one to start receiving data.</p></div>
                    @else
                    <div class="space-y-3">
                        @foreach($project->apiKeys as $key)
                        <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50">
                            <div>
                                <div class="text-sm font-mono text-gray-900 dark:text-gray-100">{{ $key->key_prefix }}••••••••••</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $key->name }} &middot; Last used: {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'never' }}</div>
                            </div>
                            <form method="POST" action="{{ route('settings.delete-api-key', $key->id) }}" onsubmit="return confirm('Revoke this API key?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Revoke</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card border-2 border-red-200 dark:border-red-800/50">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-red-700 dark:text-red-400 mb-2">Danger Zone</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Deleting a project is irreversible. All data including exceptions, logs, metrics, and settings will be permanently removed.</p>
                    <form method="POST" action="{{ route('settings.delete-project', $project->id) }}" onsubmit="return confirm('Are you ABSOLUTELY sure? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger">Delete Project</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>