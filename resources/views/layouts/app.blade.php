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

<body x-data="{ sidebarOpen: false }" :class="{ 'sidebar-open': sidebarOpen }"
    @keydown.escape.window="sidebarOpen = false">

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

            @php
                $mobileUser = Auth::user();
                $mobileItems = [];
                $hasRole = static fn (array $roles): bool => $mobileUser
                    && method_exists($mobileUser, 'hasRole')
                    && collect($roles)->contains(fn (string $role): bool => $mobileUser->hasRole($role));

                if ($hasRole(['Super Admin', 'super_admin'])) {
                    $mobileItems = [
                        ['label' => 'Dashboard', 'icon' => 'grid', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
                        ['label' => 'Pengguna', 'icon' => 'users', 'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
                        ['label' => 'Master', 'icon' => 'box', 'url' => route('master.barang.index'), 'active' => request()->routeIs('master.*')],
                        ['label' => 'Gudang', 'icon' => 'warehouse', 'url' => route('admin.gudang.index'), 'active' => request()->routeIs('admin.gudang.*')],
                    ];
                } elseif ($hasRole(['Admin Gudang Store', 'admin_gudang_store'])) {
                    $mobileItems = [
                        ['label' => 'Dashboard', 'icon' => 'grid', 'url' => route('store.dashboard'), 'active' => request()->routeIs('store.dashboard')],
                        ['label' => 'Stok', 'icon' => 'box', 'url' => route('store.stok.index'), 'active' => request()->routeIs('store.stok.*')],
                        ['label' => 'Terima', 'icon' => 'receive', 'url' => route('store.penerimaan.index'), 'active' => request()->routeIs('store.penerimaan.*')],
                        ['label' => 'Permintaan', 'icon' => 'transfer', 'url' => route('store.permintaan.index'), 'active' => request()->routeIs('store.permintaan.*')],
                    ];
                } elseif ($hasRole(['Admin Gudang Umum', 'admin_gudang_umum'])) {
                    $mobileItems = [
                        ['label' => 'Dashboard', 'icon' => 'grid', 'url' => route('unit-bisnis.admin-gu.dashboard'), 'active' => request()->routeIs('unit-bisnis.admin-gu.dashboard')],
                        ['label' => 'Permintaan', 'icon' => 'transfer', 'url' => route('unit-bisnis.admin-gu.permintaan.index'), 'active' => request()->routeIs('unit-bisnis.admin-gu.permintaan.*')],
                        ['label' => 'Opname', 'icon' => 'box', 'url' => route('unit-bisnis.admin-gu.stok-opname.index'), 'active' => request()->routeIs('unit-bisnis.admin-gu.stok-opname.*')],
                        ['label' => 'Laporan', 'icon' => 'report', 'url' => route('unit-bisnis.admin-gu.laporan.index'), 'active' => request()->routeIs('unit-bisnis.admin-gu.laporan.*')],
                    ];
                } elseif ($hasRole(['Recording Gudang Umum', 'recording_gudang_umum'])) {
                    $mobileItems = [
                        ['label' => 'Beranda', 'icon' => 'grid', 'url' => route('unit-bisnis.recording-gu.dashboard'), 'active' => request()->routeIs('unit-bisnis.recording-gu.dashboard')],
                        ['label' => 'Penerimaan', 'icon' => 'receive', 'url' => route('unit-bisnis.recording-gu.penerimaan.index'), 'active' => request()->routeIs('unit-bisnis.recording-gu.penerimaan.*')],
                        ['label' => 'Pengeluaran', 'icon' => 'transfer', 'url' => route('unit-bisnis.recording-gu.pengeluaran.index'), 'active' => request()->routeIs('unit-bisnis.recording-gu.pengeluaran.*')],
                        ['label' => 'Stok', 'icon' => 'box', 'url' => route('unit-bisnis.stok.index'), 'active' => request()->routeIs('unit-bisnis.stok.*')],
                    ];
                }
            @endphp

            @if ($mobileItems)
                <nav class="mobile-bottom-nav" aria-label="Navigasi cepat mobile">
                    @foreach ($mobileItems as $mobileItem)
                        <a href="{{ $mobileItem['url'] }}"
                            class="mobile-bottom-nav-item {{ $mobileItem['active'] ? 'active' : '' }}"
                            @if ($mobileItem['active']) aria-current="page" @endif>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" aria-hidden="true">
                                @if ($mobileItem['icon'] === 'users')
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                @elseif ($mobileItem['icon'] === 'receive')
                                    <path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" />
                                @elseif ($mobileItem['icon'] === 'transfer')
                                    <path d="M5 7h14" /><path d="m15 3 4 4-4 4" /><path d="M19 17H5" /><path d="m9 13-4 4 4 4" />
                                @elseif ($mobileItem['icon'] === 'report')
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6" />
                                @elseif ($mobileItem['icon'] === 'warehouse')
                                    <path d="M3 21V9l9-7 9 7v12" /><path d="M9 21v-8h6v8" />
                                @elseif ($mobileItem['icon'] === 'box')
                                    <path d="M21 8l-9-5-9 5 9 5 9-5z" /><path d="m3 8 9 5 9-5" /><path d="M3 8v8l9 5 9-5V8" />
                                @else
                                    <rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />
                                @endif
                            </svg>
                            <span>{{ $mobileItem['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>

    </div>

    @stack('scripts')
</body>

</html>
