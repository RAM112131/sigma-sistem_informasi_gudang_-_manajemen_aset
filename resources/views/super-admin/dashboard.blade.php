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

    <style>
        .super-dashboard {
            --dash-text-helper: 12px;
            --dash-text-body: 14px;
            --dash-text-section: 20px;
            --dash-text-metric: 24px;
            min-width: 0;
        }

        .super-dashboard-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin: 0 0 16px;
        }

        .super-kpi {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 108px;
            flex-direction: column;
            justify-content: space-between;
            gap: 4px;
            overflow: hidden;
            padding: 12px 14px 10px;
            border: 1px solid var(--surface-border);
            border-top: 3px solid var(--kpi-accent, var(--brand-primary));
            border-radius: var(--radius);
            background: #fff;
            color: var(--text-primary);
        }

        .super-kpi:hover {
            border-color: var(--brand-primary);
        }

        .super-kpi-users {
            --kpi-accent: var(--status-success);
        }

        .super-kpi-roles {
            --kpi-accent: var(--brand-accent);
        }

        .super-kpi-warehouses {
            --kpi-accent: var(--brand-primary);
        }

        .super-kpi-activity {
            --kpi-accent: var(--status-neutral);
        }

        .super-kpi:focus-visible,
        .super-panel-action:focus-visible,
        .super-user-filters :focus-visible,
        .super-users-pagination a:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .super-kpi-heading {
            padding-right: 24px;
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            font-weight: 800;
            letter-spacing: .055em;
            line-height: 1.2;
        }

        .super-kpi-value {
            color: var(--text-primary);
            font-size: var(--dash-text-metric);
            font-weight: 850;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
        }

        .super-kpi-value small {
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            font-weight: 600;
        }

        .super-kpi-icon {
            position: absolute;
            top: 8px;
            right: 8px;
            display: grid;
            width: 24px;
            height: 24px;
            place-items: center;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--brand-primary);
        }

        .super-kpi-icon svg,
        .super-search svg,
        .super-filter-submit svg,
        .super-activity-icon svg,
        .super-audit-note svg {
            width: 14px;
            height: 14px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .super-kpi-tag {
            width: fit-content;
            max-width: 100%;
            overflow: hidden;
            padding: 3px 5px;
            border-radius: 3px;
            font-size: var(--dash-text-helper);
            font-weight: 800;
            letter-spacing: .025em;
            line-height: 1.15;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tag-green {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .tag-navy {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .tag-teal {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .super-dashboard-panels {
            display: grid;
            grid-template-columns: minmax(0, 2.15fr) minmax(275px, 1fr);
            align-items: start;
            gap: 16px;
        }

        .super-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .super-panel-header {
            display: flex;
            min-height: 68px;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-panel-header h1,
        .super-panel-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: var(--dash-text-section);
            font-weight: 800;
            line-height: 1.2;
        }

        .super-activity-count {
            display: inline-grid;
            width: 20px;
            height: 20px;
            place-items: center;
            border-radius: 50%;
            background: rgba(193, 68, 60, .12);
            color: var(--status-critical);
            font-size: var(--dash-text-helper);
            vertical-align: 1px;
        }

        .super-panel-header p {
            margin: 3px 0 0;
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.3;
        }

        .super-panel-action {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 5px;
            color: var(--brand-primary);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .super-panel-action span {
            font-size: 18px;
            line-height: 1;
        }

        .super-user-filters {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(130px, .72fr) minmax(120px, .62fr) 38px;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-search,
        .super-filter-select {
            display: flex;
            min-width: 0;
            height: 38px;
            align-items: center;
            gap: 7px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-muted);
        }

        .super-search {
            padding: 0 10px;
        }

        .super-search svg {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            color: var(--text-muted);
        }

        .super-search input,
        .super-filter-select {
            font: inherit;
            font-size: var(--dash-text-helper);
        }

        .super-search input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            color: var(--text-primary);
        }

        .super-search input::placeholder {
            color: var(--text-subtle);
        }

        .super-filter-select {
            padding: 0 9px;
        }

        .super-filter-submit {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--brand-primary);
            cursor: pointer;
        }

        .super-filter-submit svg {
            width: 18px;
            height: 18px;
        }

        .super-users-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .super-users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        .super-users-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            font-weight: 800;
            letter-spacing: .035em;
        }

        .super-users-table td {
            height: 48px;
            padding: 7px 12px;
            border-bottom: 1px solid #edf2f0;
            color: var(--text-primary);
            font-size: var(--dash-text-body);
        }

        .super-users-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .super-user-cell {
            display: flex;
            min-width: 150px;
            align-items: center;
            gap: 6px;
        }

        .super-user-avatar {
            display: grid;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            place-items: center;
            border: 1px solid #d6e3df;
            border-radius: 50%;
            background: #edf4f1;
            color: var(--brand-primary);
            font-size: var(--dash-text-helper);
            font-weight: 800;
        }

        .super-user-details {
            display: flex;
            min-width: 0;
            flex-direction: column;
            line-height: 1.25;
        }

        .super-user-details strong {
            overflow: hidden;
            color: var(--text-primary);
            font-size: var(--dash-text-body);
            font-weight: 700;
            text-overflow: ellipsis;
        }

        .super-role-badge,
        .super-warehouse-chip,
        .super-status {
            display: inline-block;
            max-width: 160px;
            overflow: hidden;
            padding: 4px 7px;
            border-radius: var(--radius);
            font-size: var(--dash-text-helper);
            font-weight: 800;
            line-height: 1.1;
            text-transform: uppercase;
            text-overflow: ellipsis;
            vertical-align: middle;
        }

        .role-super {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .role-store {
            background: rgba(201, 122, 43, .12);
            color: var(--brand-accent-d);
        }

        .role-admin {
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
        }

        .role-recording {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .role-custom {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .super-warehouse-chip {
            background: var(--surface-base);
            color: var(--text-muted);
            font-weight: 600;
        }

        .super-warehouse-more {
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
        }

        .super-status.status-active {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .super-status.status-inactive {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .super-muted {
            color: var(--text-subtle);
            font-size: var(--dash-text-helper);
        }

        .super-table-empty {
            padding: 24px 8px !important;
            color: var(--text-muted) !important;
            text-align: center;
        }

        .super-users-pagination {
            display: flex;
            min-height: 46px;
            align-items: center;
            justify-content: space-between;
            gap: 7px;
            padding: 8px 12px;
            border-top: 1px solid var(--surface-border);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
        }

        .super-users-pagination nav {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .super-users-pagination a {
            display: inline-flex;
            min-width: 30px;
            height: 30px;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            border: 1px solid transparent;
            border-radius: var(--radius);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            text-decoration: none;
        }

        .super-users-pagination a.page-control {
            border-color: var(--surface-border);
            background: var(--surface-card);
        }

        .super-users-pagination a.is-current {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .super-users-pagination a.is-disabled {
            color: var(--text-subtle);
            pointer-events: none;
        }

        .super-activity-list {
            max-height: 500px;
            overflow-y: auto;
            margin: 0;
            padding: 4px 14px;
            list-style: none;
        }

        .super-activity-item {
            display: flex;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-activity-item:last-child {
            border-bottom: 0;
        }

        .super-activity-icon {
            display: grid;
            width: 32px;
            height: 32px;
            flex: 0 0 32px;
            place-items: center;
            border-radius: var(--radius);
        }

        .super-activity-icon svg {
            width: 18px;
            height: 18px;
        }

        .activity-success {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .activity-warning {
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
        }

        .activity-danger {
            background: rgba(193, 68, 60, .12);
            color: var(--status-critical);
        }

        .activity-info {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .super-activity-body {
            min-width: 0;
            flex: 1;
        }

        .super-activity-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
        }

        .super-activity-title-row strong {
            color: var(--text-primary);
            font-size: var(--dash-text-body);
            font-weight: 850;
        }

        .super-activity-title-row time {
            flex: 0 0 auto;
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
        }

        .super-activity-body p {
            margin: 3px 0;
            color: var(--text-primary);
            font-size: var(--dash-text-body);
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .super-activity-body small {
            display: block;
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .super-activity-empty {
            padding: 24px 8px;
            color: var(--text-muted);
            font-size: var(--dash-text-body);
            text-align: center;
        }

        .super-audit-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 0 12px 12px;
            padding: 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.35;
        }

        .super-audit-note svg {
            flex: 0 0 13px;
            color: var(--brand-primary);
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            clip-path: inset(50%);
        }

        @media (max-width: 1100px) {
            .super-dashboard-panels {
                grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr);
            }

            .super-user-filters {
                grid-template-columns: minmax(145px, 1fr) minmax(105px, .7fr) minmax(95px, .6fr) 38px;
            }

        }

        @media (max-width: 820px) {
            .super-dashboard-panels {
                grid-template-columns: minmax(0, 1fr);
            }

            .super-activity-list {
                max-height: none;
            }
        }

        @media (max-width: 600px) {
            .super-dashboard-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .super-kpi {
                min-height: 100px;
                padding: 10px;
            }

            .super-kpi-heading {
                font-size: var(--dash-text-helper);
            }

            .super-kpi-value {
                font-size: 22px;
            }

            .super-panel-header {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .super-panel-header h1,
            .super-panel-header h2 {
                font-size: 20px;
            }

            .super-user-filters {
                grid-template-columns: minmax(0, 1fr) 38px;
                gap: 8px;
            }

            .super-search {
                grid-column: 1 / 2;
            }

            .super-filter-select {
                grid-column: 1 / 2;
            }

            .super-filter-submit {
                grid-column: 2;
                grid-row: 1;
                width: 38px;
            }

            .super-users-table {
                min-width: 680px;
            }

            .super-users-pagination {
                align-items: flex-start;
                flex-direction: column;
            }

            .super-users-pagination nav {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
@endsection
