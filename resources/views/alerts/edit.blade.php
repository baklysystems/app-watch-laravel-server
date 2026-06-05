<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Edit Alert</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
            <a href="{{ route('alerts.index', ['project_id' => $project->id]) }}" class="btn-ghost btn-sm">&larr; Back to Alerts</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card">
                <form method="POST" action="{{ route('alerts.update', $alert->id) }}" class="p-6">
                    @csrf
                    @method('PATCH')

                    <div class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Alert Name</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $alert->name) }}" required class="input-premium">
                            @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Alert Type</label>
                            <select id="type" name="type" class="select-premium">
                                @foreach(['exception_rate' => 'Exception Rate', 'log_level' => 'Log Level', 'queue_failure' => 'Queue Failure', 'query_slow' => 'Slow Query', 'metric_threshold' => 'Metric Threshold', 'mysql_connection_saturation' => 'MySQL Connection Saturation', 'mysql_replication_lag' => 'MySQL Replication Lag', 'backup_stale' => 'Stale Backup'] as $val => $lab)
                                <option value="{{ $val }}" {{ $alert->type == $val ? 'selected' : '' }}>{{ $lab }}</option>
                                @endforeach
                            </select>
                            @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Notification Channels</label>
                            <div class="flex flex-wrap gap-3">
                                @foreach(['mail' => 'Email', 'slack' => 'Slack', 'discord' => 'Discord', 'webhook' => 'Webhook', 'telegram' => 'Telegram', 'n8n' => 'N8N'] as $val => $label)
                                <label class="inline-flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-700 transition-colors">
                                    <input type="checkbox" name="channels[]" value="{{ $val }}" {{ in_array($val, (array)$alert->channels) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('channels') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="cooldown_minutes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Cooldown (minutes)</label>
                            <input type="number" id="cooldown_minutes" name="cooldown_minutes" min="1" max="1440" value="{{ old('cooldown_minutes', $alert->cooldown_minutes) }}" class="input-premium !w-32">
                            @error('cooldown_minutes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="is_active" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                            <select id="is_active" name="is_active" class="select-premium">
                                <option value="1" {{ $alert->is_active ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !$alert->is_active ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="btn-primary">Update Alert Rule</button>
                            <a href="{{ route('alerts.index', ['project_id' => $project->id]) }}" class="btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>