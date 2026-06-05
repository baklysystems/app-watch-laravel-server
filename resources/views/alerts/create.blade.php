<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Create Alert</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
            <a href="{{ route('alerts.index', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">&larr; Back to Alerts</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <form method="POST" action="{{ route('alerts.store', ['project_id' => $project->id]) }}" class="p-6">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Alert Name</label>
                            <input type="text" id="name" name="name" required class="input-premium" placeholder="e.g., High Error Rate">
                            @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Alert Type</label>
                            <select id="type" name="type" required class="select-premium">
                                <option value="exception_rate">Exception Rate</option>
                                <option value="log_level">Log Level</option>
                                <option value="queue_failure">Queue Failure</option>
                                <option value="query_slow">Slow Query</option>
                                <option value="metric_threshold">Metric Threshold</option>
                                <option value="mysql_connection_saturation">MySQL Connection Saturation</option>
                                <option value="mysql_replication_lag">MySQL Replication Lag</option>
                                <option value="backup_stale">Stale Backup</option>
                            </select>
                            @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Notification Channels</label>
                            <div class="flex flex-wrap gap-3">
                                @foreach(['mail' => 'Email', 'slack' => 'Slack', 'discord' => 'Discord', 'webhook' => 'Webhook', 'telegram' => 'Telegram', 'n8n' => 'N8N'] as $val => $label)
                                <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-700 transition-colors">
                                    <input type="checkbox" name="channels[]" value="{{ $val }}" class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('channels') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="cooldown_minutes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Cooldown (minutes)</label>
                            <input type="number" id="cooldown_minutes" name="cooldown_minutes" min="1" max="1440" required value="5" class="input-premium !w-32">
                            <p class="text-xs text-gray-400 mt-1.5">Prevents alert spam. The alert will not fire again within this window.</p>
                            @error('cooldown_minutes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Conditions -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Conditions</h3>
                            <div class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-100 dark:border-gray-700/50">
                                <div>
                                    <label for="conditions[threshold]" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Threshold</label>
                                    <input type="number" id="conditions[threshold]" name="conditions[threshold]" required value="10" class="input-premium !py-2">
                                </div>
                                <div>
                                    <label for="conditions[window_minutes]" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Window (minutes)</label>
                                    <input type="number" id="conditions[window_minutes]" name="conditions[window_minutes]" required value="5" class="input-premium !py-2">
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">For exception_rate: "Max exceptions in window". For log_level: "Any matching log in window". For queue_failure: "Max failures in window".</p>
                        </div>

                        <div>
                            <button type="submit" class="btn-primary">Create Alert Rule</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>