<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Appswatch') }} — {{ $title ?? 'Self-Hosted Laravel Monitoring' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%236366f1'/><path d='M8 22L16 8l8 14H8z' fill='white' opacity='0.9'/></svg>">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100" x-data="{ sidebarOpen: false }">
    <div class="flex h-full">
        <!-- Sidebar -->
        @include('layouts.navigation')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 lg:ml-64">
            <!-- Mobile Header -->
            <header class="sticky top-0 z-30 lg:hidden bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between h-14 px-4">
                    <button @click="sidebarOpen = true" class="p-2 -ml-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="font-extrabold text-lg text-gray-900 dark:text-gray-100">Appswatch</span>
                    <div class="w-8"></div>
                </div>
            </header>

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-950/50">
                    <div class="px-4 sm:px-6 lg:px-8 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 page-enter">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-950/50 py-4 text-center">
                <div class="px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-2">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        <span class="font-semibold text-gray-500 dark:text-gray-400">Appswatch</span> &mdash; Self-Hosted Laravel Monitoring
                    </p>
                    <p class="text-xs text-gray-300 dark:text-gray-600">
                        Made with <span class="text-red-400">&hearts;</span> for Laravel developers
                    </p>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>