<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-prefetch" content="true">
    <meta name="turbo-cache-control" content="cache">
    <title>@yield('title', 'Dashboard — ' . config('app.name', 'SIGMA'))</title>

    {{-- Favicon --}}
    <link rel="icon"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpolygon points='16,2 29,9 29,23 16,30 3,23 3,9' fill='%23145C54'/%3E%3Cpolygon points='16,2 29,9 16,16 3,9' fill='%23C97A2B'/%3E%3C/svg%3E">

    {{-- Local compiled assets & Alpine.js runtime --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body x-data="{ sidebarOpen: false }">

    <div class="app-shell">

        {{-- Mobile Backdrop Overlay --}}
        <div class="sidebar-overlay" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak x-transition.opacity>
        </div>

        {{-- Sidebar Component --}}
        @include('components.sidebar')

        {{-- Main Content Area --}}
        <div class="main-wrapper">

            {{-- Header Component --}}
            @include('components.header')

            {{-- Main View Container --}}
            <main class="main-content" id="main-content">

                {{-- Flash Message Session Alerts --}}
                @if (session('status') || session('success'))
                    <div class="alert-toast alert-toast-success" role="alert" x-data="{ show: true }" x-show="show">
                        <svg style="width: 20px; height: 20px;" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <div style="flex: 1;">{{ session('status') ?? session('success') }}</div>
                        <button type="button" @click="show = false"
                            style="background: none; border: none; cursor: pointer; color: inherit; opacity: .7;">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-toast alert-toast-danger" role="alert" x-data="{ show: true }" x-show="show">
                        <svg style="width: 20px; height: 20px;" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <div style="flex: 1;">{{ session('error') }}</div>
                        <button type="button" @click="show = false"
                            style="background: none; border: none; cursor: pointer; color: inherit; opacity: .7;">&times;</button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

    </div>

    @stack('scripts')
</body>

</html>
