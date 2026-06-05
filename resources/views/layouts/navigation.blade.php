<!-- Mobile overlay -->
<div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" @click="sidebarOpen = false">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
</div>

<!-- Sidebar -->
@php
    $projects = \App\Models\Project::where('user_id', auth()->id())->orderBy('name')->get();
    $currentProjectId = request('project_id');
    $navItems = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
        ['route' => 'exceptions.index', 'label' => 'Exceptions', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'],
        ['route' => 'logs.index', 'label' => 'Logs', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
        ['route' => 'queues.index', 'label' => 'Queues', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
        ['route' => 'performance.queries', 'label' => 'Performance', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
        ['route' => 'schedules.index', 'label' => 'Schedules', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        ['route' => 'metrics.index', 'label' => 'Metrics', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'],
        ['route' => 'alerts.index', 'label' => 'Alerts', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>'],
        ['route' => 'incidents.timeline', 'label' => 'Timeline', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        ['route' => 'audit.index', 'label' => 'Audit Log', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>'],
    ];

    $integrationItems = [
        ['route' => 'integrations.google-analytics', 'label' => 'Google Analytics', 'icon' => '<text x="3" y="14" font-size="9" font-weight="bold" fill="currentColor">GA</text>'],
        ['route' => 'integrations.google-search-console', 'label' => 'Search Console', 'icon' => '<text x="2" y="14" font-size="9" font-weight="bold" fill="currentColor">GSC</text>'],
        ['route' => 'integrations.cloudflare', 'label' => 'Cloudflare', 'icon' => '<text x="3" y="14" font-size="9" font-weight="bold" fill="currentColor">CF</text>'],
        ['route' => 'integrations.microsoft-clarity', 'label' => 'MS Clarity', 'icon' => '<text x="3" y="14" font-size="9" font-weight="bold" fill="currentColor">MC</text>'],
        ['route' => 'integrations.stripe', 'label' => 'Stripe', 'icon' => '<text x="3" y="14" font-size="9" font-weight="bold" fill="currentColor">ST</text>'],
        ['route' => 'integrations.github', 'label' => 'GitHub/GitLab', 'icon' => '<text x="2" y="14" font-size="9" font-weight="bold" fill="currentColor">GH</text>'],
        ['route' => 'integrations.email', 'label' => 'Email', 'icon' => '<text x="4" y="14" font-size="9" font-weight="bold" fill="currentColor">@</text>'],
    ];
@endphp

<aside x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', newProjectOpen: false, projectOpen: false }" x-init="$watch('darkMode', v => { localStorage.setItem('darkMode', v); document.documentElement.classList.toggle('dark', v) }); document.documentElement.classList.toggle('dark', darkMode)"
x-show="sidebarOpen || (window.innerWidth >= 1024)" x-cloak
class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-white dark:bg-gray-950 border-r border-gray-100 dark:border-gray-800 lg:flex" style="display: none;">

    <!-- Logo & Close -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-sm">
                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <span class="font-extrabold text-lg text-gray-900 dark:text-gray-100 tracking-tight">Appswatch</span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>

    <!-- Project Selector -->
    @if($projects->isNotEmpty())
    <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-800 shrink-0">
        <button @click="projectOpen = !projectOpen" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <span class="truncate">{{ $projects->firstWhere('id', $currentProjectId)->name ?? 'Select Project' }}</span>
            <svg class="w-3.5 h-3.5 text-gray-400 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="projectOpen" @click.outside="projectOpen = false" x-cloak class="mt-1 rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg overflow-hidden" style="display: none;">
            @foreach($projects as $p)
                @php $params = array_merge(request()->except('project_id'), ['project_id' => $p->id]); @endphp
                <a href="{{ route('dashboard', $params) }}" class="flex items-center gap-2 px-3 py-2 text-sm {{ $p->id == $currentProjectId ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $p->id == $currentProjectId ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>{{ $p->name }}<span class="ml-auto text-xs text-gray-400">{{ $p->environment }}</span>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Navigation Items -->
    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-1">
        @foreach($navItems as $item)
            @php
                $routeParams = request()->has('project_id') ? ['project_id' => request('project_id')] : [];
                $isActive = request()->routeIs($item['route'] . '.*') || request()->routeIs($item['route']);
            @endphp
            <a href="{{ route($item['route'], $routeParams) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group {{ $isActive ? 'bg-brand-50 dark:bg-brand-950/50 text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200' }}">
                <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-brand-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $item['icon'] !!}</svg>
                <span>{{ $item['label'] }}</span>
                @if($isActive)<span class="ml-auto w-1 h-5 bg-brand-500 rounded-full"></span>@endif
            </a>
        @endforeach
        <!-- Integrations Section -->
        @php
            $anyIntegrationActive = false;
            foreach ($integrationItems as $item) {
                if (request()->routeIs($item['route'] . '.*') || request()->routeIs($item['route'])) {
                    $anyIntegrationActive = true;
                    break;
                }
            }
        @endphp
        <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200">
            <svg class="w-5 h-5 shrink-0 text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
            <span>Integrations</span>
            <svg class="w-3.5 h-3.5 text-gray-400 ml-auto shrink-0 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            @if($anyIntegrationActive)<span class="ml-auto w-1 h-5 bg-brand-500 rounded-full"></span>@endif
        </button>
        <div class="space-y-1 ml-2 {{ $anyIntegrationActive ? '' : 'hidden' }}">
            @foreach($integrationItems as $item)
                @php
                    $intRouteParams = request()->has('project_id') ? ['project_id' => request('project_id')] : [];
                    $intIsActive = request()->routeIs($item['route'] . '.*') || request()->routeIs($item['route']);
                @endphp
                <a href="{{ route($item['route'], $intRouteParams) }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium transition-all duration-150 group {{ $intIsActive ? 'bg-brand-50 dark:bg-brand-950/50 text-brand-600 dark:text-brand-400' : 'text-gray-500 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    <svg class="w-4 h-4 shrink-0 {{ $intIsActive ? 'text-brand-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $item['icon'] !!}</svg>
                    <span>{{ $item['label'] }}</span>
                    @if($intIsActive)<span class="ml-auto w-1 h-4 bg-brand-500 rounded-full"></span>@endif
                </a>
            @endforeach
        </div>

        @php $isActive = request()->routeIs('settings.index'); @endphp
        <a href="{{ route('settings.index', request()->has('project_id') ? ['project_id' => request('project_id')] : []) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150 group {{ $isActive ? 'bg-brand-50 dark:bg-brand-950/50 text-brand-600 dark:text-brand-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-200' }}">
            <svg class="w-5 h-5 shrink-0 {{ $isActive ? 'text-brand-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Settings</span>
            @if($isActive)<span class="ml-auto w-1 h-5 bg-brand-500 rounded-full"></span>@endif
        </a>
    </nav>

    <!-- Bottom Section -->
    <div class="border-t border-gray-100 dark:border-gray-800 shrink-0 px-3 py-3 space-y-2">
        <button @click="newProjectOpen = true" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm font-medium text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950/30 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>New Project
        </button>

        <button @click="darkMode = !darkMode" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors">
            <svg x-show="!darkMode" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <svg x-show="darkMode" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span x-show="!darkMode">Dark Mode</span><span x-show="darkMode">Light Mode</span>
        </button>

        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors group">
            <span class="h-8 w-8 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-xs font-bold shadow-sm shrink-0">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
            </div>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="px-2.5 pb-2">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Logout">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<!-- New Project Modal -->
<div x-show="newProjectOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="newProjectOpen = false"></div>
    <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-soft-lg border border-gray-100 dark:border-gray-800 max-w-md w-full p-6" @click.outside="newProjectOpen = false">
        <div class="flex justify-between items-center mb-5">
            <div><h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Create New Project</h3><p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Set up a new project to start monitoring</p></div>
            <button @click="newProjectOpen = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form method="POST" action="{{ route('projects.create') }}">
            @csrf
            <div class="space-y-4">
                <div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Project Name</label><input type="text" name="name" required class="input-premium" placeholder="e.g., My Laravel App" autofocus></div>
                <div><label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Environment</label><select name="environment" required class="select-premium"><option value="production">Production</option><option value="staging">Staging</option><option value="local">Local</option></select></div>
                <div class="flex gap-3 justify-end pt-2"><button type="button" @click="newProjectOpen = false" class="btn-secondary btn-sm">Cancel</button><button type="submit" class="btn-primary btn-sm">Create Project</button></div>
            </div>
        </form>
    </div>
</div>