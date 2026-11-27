@extends('layouts.app')

@section('title', 'Dashboard Super Admin — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="super-dashboard">
        <section class="super-dashboard-kpis" aria-label="Ringkasan sistem">
            <a href="{{ route('admin.users.index') }}" class="super-kpi super-kpi-users">
                <span class="super-kpi-heading">TOTAL PENGGUNA AKTIF</span>
                <span class="super-kpi-value">{{ number_format($stats['active_users']) }}<small> /
                        {{ number_format($stats['total_users']) }} Akun</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">{{ number_format($stats['active_users']) }} AKTIF</span>
            </a>

            <a href="{{ route('admin.roles.index') }}" class="super-kpi super-kpi-roles">
                <span class="super-kpi-heading">TOTAL ROLE SISTEM</span>
                <span class="super-kpi-value">{{ number_format($stats['total_roles']) }}<small> Peran
                        Otoritas</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-navy">{{ number_format($stats['default_roles']) }} DEFAULT,
                    {{ number_format($stats['custom_roles']) }} KUSTOM</span>
            </a>

            <a href="{{ route('admin.gudang.index') }}" class="super-kpi super-kpi-warehouses">
                <span class="super-kpi-heading">TOTAL GUDANG AKTIF</span>
                <span class="super-kpi-value">{{ number_format($stats['active_gudangs']) }}<small> Fasilitas
                        Master</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <path d="M9 22V12h6v10" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-teal">{{ number_format($stats['active_store_gudangs']) }} STORE,
                    {{ number_format($stats['active_business_gudangs']) }} UNIT BISNIS</span>
            </a>

            <a href="{{ route('admin.activity-log.index') }}" class="super-kpi super-kpi-activity">
                <span class="super-kpi-heading">AKTIVITAS HARI INI</span>
                <span class="super-kpi-value">{{ number_format($stats['audit_logs_today']) }}<small> Audit
                        Log</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 12a8 8 0 1 0 2.34-5.66L4 8.5" />
                        <path d="M4 4v4.5h4.5M12 8v4l3 2" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">{{ number_format($stats['audit_logs_today']) }} LOG TERCATAT</span>
            </a>
        </section>

        <section class="super-dashboard-panels">
            <article class="super-panel super-users-panel">
                <header class="super-panel-header">
                    <div>
                        <h1>Pengguna Terbaru</h1>
                        <p>Daftar akun staf operasional, administrator gudang, dan kredensial sistem</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="super-panel-action">
                        Lihat Semua Pengguna <span aria-hidden="true">›</span>
                    </a>
                </header>

                <form action="{{ route('admin.dashboard') }}" method="GET" class="super-user-filters">
                    <label class="super-search">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-4-4" />
                        </svg>
                        <span class="sr-only">Cari nama atau username</span>
                        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari nama atau username...">
                    </label>
                    <label class="sr-only" for="dashboard-role">Filter role</label>
                    <select name="role" id="dashboard-role" class="super-filter-select">
                        <option value="">Semua Role</option>
                        @foreach ($availableRoles as $role)
                            <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="dashboard-status">Filter status</label>
                    <select name="status" id="dashboard-status" class="super-filter-select">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                    </select>
                    <button type="submit" class="super-filter-submit" aria-label="Terapkan filter">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M20 7v5h-5M4 17v-5h5" />
                            <path d="M5.5 9a7 7 0 0 1 11.6-2L20 12M4 12l2.9 5a7 7 0 0 0 11.6-2" />
                        </svg>
                    </button>
                </form>

                <div class="super-users-table-wrap">
                    <table class="super-users-table">
                        <thead>
                            <tr>
                                <th>NAMA LENGKAP</th>
                                <th>USERNAME</th>
                                <th>ROLE</th>
                                <th>GUDANG AKTIF</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentUsers as $account)
                                @php
                                    $initials = collect(explode(' ', trim($account->name)))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn(string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                        ->implode('');
                                    $roleName = $account->roles->first()?->name ?? 'Belum ada role';
                                    $roleClass = match ($roleName) {
                                        'Super Admin', 'super_admin' => 'role-super',
                                        'Admin Gudang Store', 'admin_gudang_store' => 'role-store',
                                        'Admin Gudang Umum', 'admin_gudang_umum' => 'role-admin',
                                        'Recording Gudang Umum', 'recording_gudang_umum' => 'role-recording',
                                        default => 'role-custom',
                                    };
                                    $warehouseNames = $account->gudang->pluck('nama');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="super-user-cell">
                                            <span class="super-user-avatar">{{ $initials }}</span>
                                            <span class="super-user-details">
                                                <strong>{{ $account->name }}</strong>
                                            </span>
                                        </div>
                                    </td>
                                    <td>{{ $account->username }}</td>
                                    <td><span class="super-role-badge {{ $roleClass }}">{{ $roleName }}</span></td>
                                    <td>
                                        @if ($warehouseNames->isNotEmpty())
                                            <span class="super-warehouse-chip">{{ $warehouseNames->first() }}</span>
                                            @if ($warehouseNames->count() > 1)
                                                <span
                                                    class="super-warehouse-more">+{{ $warehouseNames->count() - 1 }}</span>
                                            @endif
                                        @else
                                            <span class="super-muted">Belum ditetapkan</span>
                                        @endif
                                    </td>
                                    <td><span
                                            class="super-status {{ $account->is_aktif ? 'status-active' : 'status-inactive' }}">{{ $account->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="super-table-empty">Tidak ada pengguna yang sesuai dengan
                                        filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <footer class="super-users-pagination">
                    <span>
                        Menampilkan {{ $recentUsers->count() }} dari {{ number_format($recentUsers->total()) }} pengguna
                    </span>
                    <nav aria-label="Navigasi halaman pengguna">
                        <a href="{{ $recentUsers->previousPageUrl() ?: '#' }}" @class(['page-control', 'is-disabled' => $recentUsers->onFirstPage()])
                            @if ($recentUsers->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>Sebelumnya</a>
                        @php
                            $pageRangeStart = max(
                                1,
                                min($recentUsers->currentPage() - 1, $recentUsers->lastPage() - 2),
                            );
                            $pageRangeEnd = min($recentUsers->lastPage(), $pageRangeStart + 2);
                        @endphp
                        @foreach ($recentUsers->getUrlRange($pageRangeStart, $pageRangeEnd) as $page => $url)
                            <a href="{{ $url }}" @class([
                                'page-number',
                                'is-current' => $page === $recentUsers->currentPage(),
                            ])
                                @if ($page === $recentUsers->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                        @endforeach
                        <a href="{{ $recentUsers->nextPageUrl() ?: '#' }}" @class([
                            'page-control',
                            'is-disabled' => !$recentUsers->hasMorePages(),
                        ])
                            @if (!$recentUsers->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>Selanjutnya</a>
                    </nav>
                </footer>
            </article>

            <article class="super-panel super-activity-panel">
                <header class="super-panel-header">
                    <div>
                        <h2>Aktivitas Sensitif Terakhir <span
                                class="super-activity-count">{{ $recentSystemLogsCount }}</span></h2>
                        <p>Log audit masuk, perubahan kredensial, dan modul master</p>
                    </div>
                    <a href="{{ route('admin.activity-log.index') }}" class="super-panel-action">Semua Log <span
                            aria-hidden="true">→</span></a>
                </header>

                <ol class="super-activity-list">
                    @forelse ($recentSystemLogs as $log)
                        <li class="super-activity-item">
                            <span @class([
                                'super-activity-icon',
                                'activity-success' => $log['badge'] === 'success',
                                'activity-warning' => $log['badge'] === 'warning',
                                'activity-danger' => $log['badge'] === 'critical',
                                'activity-info' => in_array($log['badge'], ['brand', 'neutral'], true),
                            ]) aria-hidden="true">
                                @if ($log['badge'] === 'critical')
                                    <svg viewBox="0 0 24 24">
                                        <path
                                            d="M10.3 3.9 2.5 17.4A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                        <path d="M12 9v4m0 3h.01" />
                                    </svg>
                                @elseif ($log['badge'] === 'success')
                                    <svg viewBox="0 0 24 24">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="10" cy="7" r="4" />
                                        <path d="m16 11 2 2 4-4" />
                                    </svg>
                                @else
                                    <svg viewBox="0 0 24 24">
                                        <path d="M12 8v4l3 2" />
                                        <circle cx="12" cy="12" r="10" />
                                    </svg>
                                @endif
                            </span>
                            <div class="super-activity-body">
                                <div class="super-activity-title-row">
                                    <strong>{{ $log['action'] }}</strong>
                                    <time datetime="{{ $log['created_at']?->toIso8601String() }}">
                                        {{ $log['created_at']?->diffForHumans() ?? 'Waktu tidak tersedia' }}
                                        @if ($log['created_at'])
                                            ({{ $log['created_at']->format('H:i') }})
                                        @endif
                                    </time>
                                </div>
                                <p>{{ $log['description'] }}</p>
                                <small>
                                    Oleh: {{ $log['actor'] }} [{{ $log['role'] }}]
                                    @if ($log['subject'])
                                        · Target: {{ $log['subject'] }}
                                    @endif
                                    @if ($log['ip_address'])
                                        · IP: {{ $log['ip_address'] }}
                                    @endif
                                </small>
                            </div>
                        </li>
                    @empty
                        <li class="super-activity-empty">
                            Belum ada aktivitas akun atau gudang yang tercatat.
                        </li>
                    @endforelse
                </ol>

                <div class="super-audit-note">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 11v5m0-8h.01" />
                    </svg>
                    Feed ini menampilkan perubahan akun dan gudang serta aktivitas login dari audit log.
                </div>
            </article>
        </section>
    </div>

    @push('styles')
    @vite('resources/css/pages/super-admin/dashboard.css')
@endpush
@endsection
