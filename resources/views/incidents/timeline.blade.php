<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-xl text-gray-900 dark:text-gray-100 tracking-tight">
                    Incident Timeline
                    <span class="ml-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">{{ $project->name }}</span>
                </h2>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ $startDate }} → {{ $endDate }} · {{ $events->count() }} events</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filters -->
            <form method="GET" class="card p-4 mb-6 flex flex-wrap gap-4 items-end">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="input-premium !w-auto">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="input-premium !w-auto">
                </div>
                <div class="flex gap-3 flex-wrap items-center">
                    @foreach(['exception' => 'Exceptions', 'alert' => 'Alerts', 'deployment' => 'Deployments', 'uptime' => 'Uptime', 'backup' => 'Backups'] as $val => $label)
                    <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="types[]" value="{{ $val }}" {{ in_array($val, $selectedTypes) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-brand-500 focus:ring-brand-500">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="btn-primary !py-2">Apply</button>
            </form>

            @if($events->isEmpty())
            <div class="card p-12 text-center">
                <p class="text-gray-400 dark:text-gray-500">No events found in the selected date range.</p>
            </div>
            @else
            <!-- Timeline -->
            <div class="relative">
                <!-- Vertical line -->
                <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                <div class="space-y-8">
                    @foreach($groupedEvents as $date => $dayEvents)
                    <div>
                        <!-- Date header -->
                        <div class="relative pl-12 mb-3">
                            <div class="absolute left-2 top-1.5 w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-900 ring-4 ring-white dark:ring-gray-900 flex items-center justify-center">
                                <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</h3>
                        </div>

                        @foreach($dayEvents as $event)
                        <div class="relative pl-12">
                            <!-- Dot -->
                            <div class="absolute left-3.5 top-2 w-3 h-3 rounded-full border-2 border-white dark:border-gray-900
                                @if($event['severity'] === 'error' || $event['severity'] === 'critical') bg-red-500
                                @elseif($event['severity'] === 'warning') bg-amber-500
                                @elseif($event['severity'] === 'success') bg-emerald-500
                                @elseif($event['severity'] === 'info') bg-blue-500
                                @else bg-gray-400 @endif">
                            </div>
                            <!-- Event card -->
                            <a href="{{ $event['link'] }}" class="block card p-3 hover:ring-2 hover:ring-brand-500/30 transition-all mb-2">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                        @if($event['type'] === 'exception') bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300
                                        @elseif($event['type'] === 'alert') bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300
                                        @elseif($event['type'] === 'deployment') bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300
                                        @elseif($event['type'] === 'uptime') {{ $event['severity'] === 'success' ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300' }}
                                        @elseif($event['type'] === 'backup') {{ $event['severity'] === 'success' ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300' }}
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @endif">
                                        {{ strtoupper($event['type']) }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($event['timestamp'])->format('H:i') }}</span>
                                </div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $event['title'] }}</p>
                                @if($event['summary'])
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $event['summary'] }}</p>
                                @endif
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>