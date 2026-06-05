<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">Alerts</h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $project->name }} &mdash; {{ $project->environment }}</p>
            </div>
            <a href="{{ route('alerts.create', ['project_id' => $project->id]) }}" class="btn-primary btn-sm" data-ripple>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                New Alert
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="flex items-center gap-2 mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 text-sm font-medium" data-flash>
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
            @endif

            <!-- Alerts List -->
            <div class="card overflow-hidden">
                @forelse($alerts as $alert)
                <div class="p-5 border-b border-gray-50 dark:border-gray-800 last:border-0 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $alert->name }}</h3>
                            <span class="badge {{ $alert->is_active ? 'badge-green' : 'badge-gray' }} text-[10px]">{{ $alert->is_active ? 'Active' : 'Inactive' }}</span>
                            <span class="badge-blue text-[10px]">{{ str_replace('_', ' ', ucfirst($alert->type)) }}</span>
                        </div>
                        @if($alert->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $alert->description }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1.5">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Threshold: {{ $alert->threshold }}</span>
                            <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">Cooldown: {{ $alert->cooldown_minutes }}m</span>
                            <span class="text-[10px] text-gray-300 dark:text-gray-600">&middot;</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $alert->channels ? count($alert->channels) . ' channels' : 'No channels' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <form method="POST" action="{{ route('alerts.toggle', $alert->id) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-ghost btn-sm" title="{{ $alert->is_active ? 'Disable' : 'Enable' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </form>
                        <a href="{{ route('alerts.edit', $alert->id) }}" class="btn-ghost btn-sm">Edit</a>
                        <form method="POST" action="{{ route('alerts.destroy', $alert->id) }}" onsubmit="return confirm('Delete this alert?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ghost btn-sm text-red-500 hover:text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="empty-state py-12">
                    <div class="empty-state-icon">
                        <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <p class="empty-state-title">No alerts configured</p>
                    <p class="empty-state-desc">Create alert rules to get notified when things go wrong.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>