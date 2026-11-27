<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-prefetch" content="true">
    <meta name="turbo-cache-control" content="cache">
    <title>@yield('title', 'Masuk — ' . config('app.name', 'SIGMA'))</title>

    {{-- Favicon --}}
    <link rel="icon"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpolygon points='16,2 29,9 29,23 16,30 3,23 3,9' fill='%23145C54'/%3E%3Cpolygon points='16,2 29,9 16,16 3,9' fill='%23C97A2B'/%3E%3C/svg%3E">

    {{-- Local compiled assets & Alpine.js runtime --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
</head>

<body>

    <main class="layout-container" id="main-content">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
