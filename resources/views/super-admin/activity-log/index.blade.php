@extends('layouts.app')

@section('title', 'Log Aktivitas — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="super-dashboard activity-page" x-data="{
        listLoading: false,
        detail: { open: false, loading: false, error: '', data: null, records: [], index: -1, position: 0, total: 0, trigger: null, currentUrl: '' },
        async openDetail(url, trigger) {
            this.detail.records = Array.from(this.$root.querySelectorAll('[data-detail-url]')).map((button) => ({
                url: button.href,
                position: Number(button.dataset.position)
            }));
            this.detail.index = this.detail.records.findIndex((record) => record.url === url);
            this.detail.position = Number(trigger.dataset.position);
            this.detail.total = Number(trigger.dataset.total);
            this.detail.trigger = trigger;
            this.detail.currentUrl = url;
            this.detail.open = true;
            await this.loadDetail(url);
            this.$nextTick(() => this.$refs.detailClose.focus());
        },
        async loadDetail(url) {
            this.detail.loading = true;
            this.detail.error = '';
            this.detail.data = null;
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) {
                    throw new Error('Detail aktivitas tidak dapat dimuat. Silakan coba kembali.');
                }
                this.detail.data = await response.json();
                return true;
            } catch (error) {
                this.detail.error = error.message || 'Terjadi kesalahan saat memuat detail aktivitas.';
                return false;
            } finally {
                this.detail.loading = false;
            }
        },
        closeDetail() {
            this.detail.open = false;
            if (this.detail.trigger?.isConnected) this.detail.trigger.focus();
        },
        canMoveDetail(offset) {
            const targetPosition = this.detail.position + offset;
            return targetPosition >= 1 && targetPosition <= this.detail.total;
        },
        async moveDetail(offset) {
            if (!this.canMoveDetail(offset)) return;
            const targetPosition = this.detail.position + offset;
            const recordIndex = this.detail.records.findIndex((item) => item.position === targetPosition);
            const record = this.detail.records[recordIndex];
    
            if (record) {
                if (await this.loadDetail(record.url)) {
                    this.detail.index = recordIndex;
                    this.detail.position = targetPosition;
                    this.detail.currentUrl = record.url;
                }
                return;
            }
    
            const requestUrl = new URL(this.detail.currentUrl, window.location.origin);
            requestUrl.searchParams.set('direction', offset < 0 ? 'previous' : 'next');
            if (await this.loadDetail(requestUrl.toString())) {
                const currentUrl = new URL(this.detail.currentUrl, window.location.origin);
                currentUrl.pathname = currentUrl.pathname.replace(/\/\d+\/?$/, '/' + this.detail.data.log.id);
                currentUrl.searchParams.delete('direction');
                this.detail.currentUrl = currentUrl.toString();
                this.detail.position = targetPosition;
                this.detail.index = -1;
            }
        },
        detailVariant() {
            const action = (this.detail.data?.log?.action || '').toLowerCase();
            if (this.detail.data?.log?.transaction_data_hidden) return 'transaction';
            if (['reset_password', 'password_reset', 'reset_user_password'].includes(action)) return 'password-reset';
            if (['login_failed', 'failed_login', 'login.failure'].includes(action)) return 'login-failed';
            if (['login', 'login_success', 'logout'].includes(action)) return 'login';
            if (['create_user', 'created'].includes(action) && this.detail.data?.log?.subject?.type === 'User') return 'user-created';
            return 'changes';
        },
        detailActionLabel() {
            const action = (this.detail.data?.log?.action || '').toLowerCase();
            const labels = {
                login: 'Login',
                login_success: 'Login',
                login_failed: 'Login Gagal',
                failed_login: 'Login Gagal',
                'login.failure': 'Login Gagal',
                logout: 'Logout',
                create_user: 'Buat Data',
                created: 'Buat Data',
                updated: 'Ubah Data',
                update_role: 'Ubah Role',
                update_role_matrix: 'Ubah Permission',
                reset_password: 'Reset Password',
                password_reset: 'Reset Password',
                reset_user_password: 'Reset Password',
                finalisasi: 'Finalisasi Opname'
            };
            return labels[action] || this.detail.data?.log?.action_label || action;
        },
        detailBadgeClass() {
            const action = (this.detail.data?.log?.action || '').toLowerCase();
            if (this.detailVariant() === 'login-failed') return 'super-badge-danger';
            if (['login', 'login_success', 'create_user', 'created'].includes(action)) return 'super-badge-success';
            if (this.detailVariant() === 'password-reset' || ['update_role', 'update_role_matrix', 'updated'].includes(action)) return 'super-badge-warning';
            if (action === 'finalisasi' || this.detailVariant() === 'transaction') return 'super-badge-neutral';
            return 'super-badge-neutral';
        },
        detailSubject() {
            const subject = this.detail.data?.log?.subject || {};
            const type = subject.type === 'User' ? 'Pengguna' : subject.type;
            const changes = this.detail.data?.log?.changes || {};
            const after = Object.fromEntries(this.flattenChanges(changes.sesudah || changes.after || {}));
            if (type === 'Pengguna' && after.name) {
                return 'Pengguna: ' + after.name + (after.username ? ' (' + after.username + ')' : '');
            }
            if (type === 'Role' && after.name) return 'Role: ' + after.name;
            return [type, subject.id ? 'ID ' + subject.id : ''].filter(Boolean).join(': ');
        },
        displayTimestamp(value) {
            if (!value) return 'Tidak tersedia';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return 'Tidak tersedia';
            const dateText = date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
            const timeText = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }).format(date);
            return dateText + ', ' + timeText;
        },
        displayDevice(value) {
            if (!value) return 'Tidak tersedia';
            const browser = value.match(/Edg\/([\d.]+)/) || value.match(/Chrome\/([\d.]+)/) || value.match(/Firefox\/([\d.]+)/) || value.match(/Version\/([\d.]+).*Safari/);
            const browserName = value.includes('Edg/') ? 'Edge' : value.includes('Chrome/') ? 'Chrome' : value.includes('Firefox/') ? 'Firefox' : value.includes('Safari/') ? 'Safari' : '';
            const browserVersion = browser ? browser[1].split('.')[0] : '';
            const platform = value.includes('Windows') ? 'Windows' : value.includes('Mac OS') ? 'macOS' : value.includes('Android') ? 'Android' : value.includes('iPhone') || value.includes('iPad') ? 'iOS' : value.includes('Linux') ? 'Linux' : '';
            return [browserName + (browserVersion ? ' ' + browserVersion : ''), platform].filter(Boolean).join(' di ') || value;
        },
        flattenChanges(value, prefix = '') {
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                return Object.entries(value).flatMap(([key, child]) => this.flattenChanges(child, prefix ? prefix + ' → ' + key : key));
            }
            return prefix ? [
                [prefix, value]
            ] : [];
        },
        changeRows() {
            const changes = this.detail.data?.log?.changes || {};
            const before = Object.fromEntries(this.flattenChanges(changes.sebelum || changes.before || {}));
            const after = Object.fromEntries(this.flattenChanges(changes.sesudah || changes.after || {}));
            const keys = this.detailVariant() === 'user-created' ? Object.keys(after) : [...new Set([...Object.keys(before), ...Object.keys(after)])];
            return keys.filter((key) => {
                const hasBefore = Object.prototype.hasOwnProperty.call(before, key);
                const hasAfter = Object.prototype.hasOwnProperty.call(after, key);
                return !hasBefore || !hasAfter || JSON.stringify(before[key]) !== JSON.stringify(after[key]);
            }).map((key) => ({
                key,
                label: this.changeLabel(key),
                oldValue: before[key],
                newValue: after[key]
            }));
        },
        changeLabel(key) {
            const labels = {
                name: 'Nama',
                username: 'Username',
                is_aktif: 'Status',
                roles: 'Role',
                gudang: 'Gudang',
                gudang_ids: 'Gudang',
                password: 'Password'
            };
            return key.split(' → ').map((part) => labels[part] || part.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())).join(' → ');
        },
        displayValue(value) {
            if (value === undefined || value === null || value === '') return '—';
            if (typeof value === 'boolean') return value ? 'Aktif' : 'Tidak aktif';
            if (Array.isArray(value)) return value.map((item) => typeof item === 'object' ? JSON.stringify(item) : String(item)).join(', ');
            return typeof value === 'object' ? JSON.stringify(value) : String(value);
        },
        eventValue(keys, fallback = 'Tidak tercatat') {
            const changes = this.detail.data?.log?.changes || {};
            const values = { ...(changes.sebelum || changes.before || {}), ...(changes.sesudah || changes.after || {}) };
            const key = Object.keys(values).find((candidate) => keys.includes(candidate.toLowerCase()));
            return key ? this.displayValue(values[key]) : fallback;
        }
    }" @keydown.escape.window="if (detail.open) closeDetail()">
        {{-- Header Halaman --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <span>Log Audit Aktivitas</span>
                </nav>
                <h1 class="super-page-title">Log Aktivitas Sistem</h1>
                <p class="super-page-subtitle">Jejak audit read-only untuk memantau aktivitas, mutasi data, dan keamanan
                    operasional.</p>
            </div>
            <div class="super-header-actions">
                <span class="super-status status-active">
                    Audit Trail Aktif
                </span>
                <details class="super-export-dropdown">
                    <summary class="super-btn-primary">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3" />
                        </svg>
                        <span>Ekspor Log</span>
                    </summary>
                    <div class="super-export-menu">
                        <a href="{{ route('admin.activity-log.export', array_merge($filters, ['format' => 'csv'])) }}"
                            data-turbo="false">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>
                            <span>Unduh CSV</span>
                        </a>
                        <a href="{{ route('admin.activity-log.export', array_merge($filters, ['format' => 'json'])) }}"
                            data-turbo="false">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <polyline points="16 18 22 12 16 6" />
                                <polyline points="8 6 2 12 8 18" />
                            </svg>
                            <span>Unduh JSON</span>
                        </a>
                    </div>
                </details>
                <form action="{{ route('admin.activity-log.destroy-all') }}" method="POST"
                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus SEMUA catatan log aktivitas sistem? Tindakan ini bersifat permanen dan tidak dapat dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="super-btn-danger" title="Hapus semua catatan log aktivitas">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <line x1="10" y1="11" x2="10" y2="17" />
                            <line x1="14" y1="11" x2="14" y2="17" />
                        </svg>
                        <span>Hapus Semua Log</span>
                    </button>
                </form>
            </div>
        </header>

        {{-- Alert Message --}}
        @if ($errors->any())
            <div class="super-alert super-alert-error" role="alert">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- 4 KPI Stat Cards (Identik dengan Dashboard) --}}
        <section class="super-dashboard-kpis" aria-label="Ringkasan aktivitas hari ini">
            <div class="super-kpi super-kpi-roles">
                <span class="super-kpi-heading">TOTAL LOG HARI INI</span>
                <span class="super-kpi-value">{{ number_format($stats['total_today']) }}<small> aktivitas</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M5 4h14v16H5zM8 8h8M8 12h5M8 16h3" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-navy">CATATAN AUDIT SEJAK AWAL HARI</span>
            </div>

            <div class="super-kpi super-kpi-activity">
                <span class="super-kpi-heading">AKTIVITAS AUTENTIKASI</span>
                <span class="super-kpi-value">{{ number_format($stats['authentication_events']) }}<small>
                        event</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M5 21v-2a7 7 0 0 1 14 0v2M12 4v4l2 2" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-orange">LOGIN &amp; LOGOUT HARI INI</span>
            </div>

            <div class="super-kpi super-kpi-users">
                <span class="super-kpi-heading">MUTASI DATA</span>
                <span class="super-kpi-value">{{ number_format($stats['data_mutations']) }}<small> perubahan</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 7v5h-5M4 17v-5h5" />
                        <path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">PEMBUATAN, UBAH &amp; HAPUS</span>
            </div>

            <div class="super-kpi super-kpi-warehouses">
                <span class="super-kpi-heading">AKTOR AKTIF HARI INI</span>
                <span class="super-kpi-value">{{ number_format($stats['active_actors']) }}<small> aktor</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <circle cx="12" cy="12" r="4" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-teal">PENGGUNA UNIK BERAKTIVITAS</span>
            </div>
        </section>

        {{-- Filter Toolbar --}}
        <form class="super-filter-card" action="{{ route('admin.activity-log.index') }}" method="GET"
            @submit="listLoading = true">
            <div class="super-activity-filter-grid">
                {{-- Periode Waktu --}}
                <div class="super-filter-group super-period-group">
                    <label class="super-filter-label">Periode Waktu</label>
                    <div class="super-period-inputs">
                        <input type="date" name="date_from" class="super-input super-date-input"
                            value="{{ $filters['date_from'] ?? '' }}" aria-label="Dari tanggal">
                        <span class="super-period-divider" aria-hidden="true">—</span>
                        <input type="date" name="date_to" class="super-input super-date-input"
                            value="{{ $filters['date_to'] ?? '' }}" aria-label="Sampai tanggal">
                    </div>
                </div>

                {{-- Operator --}}
                <div class="super-filter-group super-operator-group">
                    <label for="filter-operator" class="super-filter-label">Pengguna / Operator</label>
                    <select id="filter-operator" name="operator" class="super-input super-select">
                        <option value="">Semua pengguna</option>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator->id }}" @selected((string) ($filters['operator'] ?? '') === (string) $operator->id)>
                                {{ $operator->name }}
                                ({{ $operator->roles->pluck('name')->first() ?? $operator->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Aksi --}}
                <div class="super-filter-group super-action-group">
                    <label for="filter-action" class="super-filter-label">Aksi / Event</label>
                    <select id="filter-action" name="action" class="super-input super-select">
                        <option value="">Semua aksi</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>
                                {{ \App\Services\SuperAdmin\ActivityLogService::getActionLabel($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Subjek --}}
                <div class="super-filter-group super-subject-group">
                    <label for="filter-subject" class="super-filter-label">Subjek</label>
                    <select id="filter-subject" name="subject" class="super-input super-select">
                        <option value="">Semua subjek</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject }}" @selected(($filters['subject'] ?? '') === $subject)>
                                {{ class_basename($subject) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Pencarian --}}
                <div class="super-filter-group super-search-group">
                    <label for="filter-search" class="super-filter-label">Pencarian</label>
                    <div class="super-search-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-4-4" />
                        </svg>
                        <input id="filter-search" type="search" name="search" class="super-search-input"
                            value="{{ $filters['search'] }}" placeholder="Cari log, pengguna, atau ringkasan...">
                    </div>
                </div>
            </div>

            {{-- Filter Footer --}}
            <div class="super-filter-footer">
                <div class="super-filter-info">
                    Menampilkan <strong>{{ number_format($logs->count()) }}</strong> dari
                    <strong>{{ number_format($logs->total()) }}</strong> aktivitas
                    @if (collect($filters)->except('search')->filter()->isNotEmpty() || $filters['search'] !== '')
                        <span class="super-filter-summary">
                            (Filter aktif:
                            @if (!empty($filters['date_from']) || !empty($filters['date_to']))
                                periode {{ $filters['date_from'] ?? 'awal' }} – {{ $filters['date_to'] ?? 'hari ini' }}
                            @endif
                            @if (!empty($filters['operator']))
                                · operator
                                {{ $operators->firstWhere('id', (int) $filters['operator'])?->name ?? 'terpilih' }}
                            @endif
                            @if (!empty($filters['action']))
                                · aksi
                                {{ \App\Services\SuperAdmin\ActivityLogService::getActionLabel($filters['action']) }}
                            @endif
                            @if (!empty($filters['subject']))
                                · subjek {{ class_basename($filters['subject']) }}
                            @endif
                            @if ($filters['search'] !== '')
                                · pencarian “{{ $filters['search'] }}”
                            @endif)
                        </span>
                    @endif
                </div>
                <div class="super-filter-actions">
                    @if (collect($filters)->except('search')->filter()->isNotEmpty() || $filters['search'] !== '')
                        <a href="{{ route('admin.activity-log.index') }}" class="super-btn-reset">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M3 12a9 9 0 1 0 3-6.7L3 8m0-5v5h5" />
                            </svg>
                            <span>Reset Filter</span>
                        </a>
                    @endif
                    <button class="super-btn-filter" type="submit">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- Tabel Log Aktivitas --}}
        <div class="super-results-card">
            <div class="super-table-scroll">
                <table class="super-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th class="super-align-center">Aksi</th>
                            <th>Subjek</th>
                            <th>Ringkasan</th>
                            <th class="super-align-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="activity-skeleton-row" x-show="listLoading" aria-hidden="true">
                            <td><span></span><span></span></td>
                            <td><span></span><span></span></td>
                            <td><span></span></td>
                            <td><span></span><span></span></td>
                            <td><span></span><span></span></td>
                            <td><span></span></td>
                        </tr>
                        @forelse ($logs as $log)
                            @php
                                $actor = $log->causer;
                                $actorName =
                                    $actor?->name ??
                                    ($log->causer_type === \App\Models\User::class
                                        ? 'Pengguna tidak tersedia'
                                        : 'Sistem');
                                $actorRoles =
                                    $actor instanceof \App\Models\User
                                        ? $actor->roles->pluck('name')->implode(', ')
                                        : 'Sistem';
                                $actorInitials = mb_strtoupper(mb_substr($actorName, 0, 2));
                                $transactionDetailsHidden = \App\Services\SuperAdmin\ActivityLogService::shouldHideTransactionData(
                                    $log,
                                );
                            @endphp
                            <tr x-show="!listLoading">
                                <td class="super-time-cell">
                                    <time datetime="{{ $log->created_at?->toIso8601String() }}">
                                        {{ $log->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') }}
                                    </time>
                                    <span class="super-time-relative">{{ $log->created_at?->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <div class="super-actor-identity">
                                        <span class="super-actor-avatar">{{ $actorInitials }}</span>
                                        <div class="super-actor-copy">
                                            <strong class="super-actor-name">{{ $actorName }}</strong>
                                            <span
                                                class="super-actor-sub">{{ $actorRoles !== '' ? $actorRoles : 'Tanpa role' }}
                                                · {{ $actor?->username ?? 'sistem' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="super-align-center">
                                    <span
                                        class="super-activity-badge {{ \App\Services\SuperAdmin\ActivityLogService::getActionBadge($log->action) }}">
                                        {{ \App\Services\SuperAdmin\ActivityLogService::getActionLabel($log->action) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="super-subject-meta">
                                        <strong
                                            class="super-subject-type">{{ class_basename((string) $log->subject_type) }}</strong>
                                        <span class="super-subject-id">ID: {{ $log->subject_id }}</span>
                                    </div>
                                </td>
                                <td>
                                    <p class="super-log-desc"
                                        title="{{ $transactionDetailsHidden ? 'Data transaksi disembunyikan' : $log->description }}">
                                        {{ $transactionDetailsHidden ? 'Data transaksi disembunyikan' : ($log->description ?: 'Tidak ada ringkasan') }}
                                    </p>
                                </td>
                                <td class="super-align-right">
                                    <a class="super-btn-action activity-detail-button"
                                        href="{{ route('admin.activity-log.show', array_merge([$log], $filters)) }}"
                                        data-detail-url="{{ route('admin.activity-log.show', array_merge([$log], $filters)) }}"
                                        data-position="{{ ($logs->firstItem() ?? 1) + $loop->index }}"
                                        data-total="{{ $logs->total() }}"
                                        @click.prevent="openDetail($event.currentTarget.href, $event.currentTarget)"
                                        title="Lihat detail log">
                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <span>Detail</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr x-show="!listLoading">
                                <td class="super-empty-cell" colspan="6">
                                    <div class="super-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.35-4.35" />
                                        </svg>
                                    </div>
                                    <strong>Tidak ada aktivitas ditemukan</strong>
                                    <span>Ubah periode, operator, aksi, atau kata pencarian lalu coba lagi.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <footer class="super-pagination-wrap activity-pagination"
                @click="if ($event.target.closest('a')) listLoading = true">
                <div class="super-pagination-info">
                    Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}
                    dari {{ number_format($logs->total()) }} aktivitas
                </div>
                <div>
                    {{ $logs->links() }}
                </div>
            </footer>
        </div>

        {{-- Modal Dialog Detail Log Aktivitas --}}
        <div class="super-modal-overlay activity-modal-overlay" x-show="detail.open" x-cloak @click.self="closeDetail()"
            role="presentation">
            <section class="super-modal activity-modal" role="dialog" aria-modal="true"
                aria-labelledby="activity-modal-title">
                <header class="super-modal-header activity-modal-header">
                    <div class="super-modal-heading-group activity-modal-heading">
                        <h2 id="activity-modal-title" class="super-modal-title">Detail Log Aktivitas</h2>
                        <div class="super-modal-sub-row">
                            <span class="super-modal-log-id" x-show="detail.data"
                                x-text="'Log #LG-' + String(detail.data?.log?.id || '').padStart(6, '0')"></span>
                            <span class="super-role-badge activity-modal-badge" x-show="detail.data"
                                :class="detailBadgeClass()" x-text="detailActionLabel()"></span>
                        </div>
                    </div>
                    <button class="super-modal-close activity-modal-close" x-ref="detailClose" type="button"
                        aria-label="Tutup detail log" @click="closeDetail()">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </header>

                {{-- Loading State --}}
                <div class="super-modal-loading activity-modal-loading" x-show="detail.loading" role="status"
                    aria-live="polite">
                    <span></span><span></span><span></span><span></span>
                    <p>Memuat detail aktivitas…</p>
                </div>

                {{-- Error State --}}
                <div class="super-alert super-alert-error activity-modal-error" x-show="detail.error"
                    x-text="detail.error" role="alert"></div>

                {{-- Body Content --}}
                <template x-if="detail.data && !detail.loading">
                    <div class="super-modal-body activity-modal-body">
                        {{-- Section: Ringkasan --}}
                        <section class="super-modal-section activity-modal-section">
                            <h3 class="super-section-subheading">Ringkasan</h3>
                            <dl class="super-modal-dl activity-modal-summary">
                                <div>
                                    <dt>Waktu</dt>
                                    <dd x-text="displayTimestamp(detail.data.log.recorded_at)"></dd>
                                </div>
                                <div>
                                    <dt>Pengguna</dt>
                                    <dd>
                                        <strong
                                            x-text="detail.data.actor.name + (detail.data.actor.username ? ' (' + detail.data.actor.username + ')' : '')"></strong>
                                        <span class="super-tag tag-navy activity-role-chip"
                                            x-show="detail.data.actor.roles?.length"
                                            x-text="detail.data.actor.roles?.[0]"></span>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Aksi</dt>
                                    <dd x-text="detailActionLabel()"></dd>
                                </div>
                                <div>
                                    <dt>Subjek</dt>
                                    <dd x-text="detailSubject()"></dd>
                                </div>
                                <div>
                                    <dt>Gudang Aktif</dt>
                                    <dd x-text="detail.data.log.active_warehouse || '—'"></dd>
                                </div>
                                <div>
                                    <dt>Status</dt>
                                    <dd>
                                        <span class="super-status activity-status-chip"
                                            :class="detailVariant() === 'login-failed' ?
                                                'status-inactive activity-status-chip--danger' :
                                                'status-active activity-status-chip--success'"
                                            x-text="detailVariant() === 'login-failed' ? 'Gagal' : 'Berhasil'"></span>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        {{-- Section: Informasi Teknis --}}
                        <section class="super-modal-section activity-modal-section">
                            <h3 class="super-section-subheading">Informasi Teknis</h3>
                            <dl class="super-modal-technical-dl activity-modal-technical">
                                <div>
                                    <dt>Alamat IP</dt>
                                    <dd>
                                        <code class="super-code-inline"
                                            x-text="detail.data.log.ip_address || 'Tidak tersedia'"></code>
                                    </dd>
                                </div>
                                <div>
                                    <dt>Perangkat</dt>
                                    <dd x-text="displayDevice(detail.data.log.user_agent)"></dd>
                                </div>
                                <div class="super-technical-full">
                                    <dt>ID Sesi</dt>
                                    <dd class="super-text-muted">Tidak disimpan demi keamanan</dd>
                                </div>
                            </dl>
                        </section>

                        {{-- Section: Kejadian / Perubahan / Aksi --}}
                        <section class="super-modal-section activity-modal-section">
                            <h3 class="super-section-subheading"
                                x-text="detailVariant() === 'login' || detailVariant() === 'login-failed' ? 'Detail Kejadian' : detailVariant() === 'password-reset' ? 'Detail Aksi' : 'Perubahan'">
                            </h3>

                            {{-- Variant: Transaksi Rahasia --}}
                            <div class="super-panel-info-banner banner-locked activity-locked-card"
                                x-show="detailVariant() === 'transaction'">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <div>
                                    <strong style="display:block; margin-bottom: 2px;">Isi data transaksi
                                        disembunyikan</strong>
                                    <span>Super Admin hanya dapat melihat siapa, kapan, dan aksi apa. Isi transaksi gudang
                                        tidak ditampilkan.</span>
                                </div>
                            </div>

                            {{-- Variant: Password Reset --}}
                            <div class="super-panel-info-banner banner-warning activity-action-card"
                                x-show="detailVariant() === 'password-reset'">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4M12 16h.01" />
                                </svg>
                                <div style="width: 100%;">
                                    <strong x-text="'Password pengguna direset oleh ' + detail.data.actor.name"></strong>
                                    <p style="margin: 2px 0 8px; font-size: 11px;">Nilai password tidak pernah disimpan
                                        atau ditampilkan.</p>
                                    <div class="super-action-detail-row">
                                        <span>Wajib ganti password saat login berikutnya</span>
                                        <strong
                                            x-text="eventValue(['must_change_password', 'wajib_ganti_password'], 'Tidak tercatat')"></strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Variant: Login / Logout --}}
                            <div class="super-event-grid activity-event-card"
                                x-show="detailVariant() === 'login' || detailVariant() === 'login-failed'">
                                <div class="super-event-cell">
                                    <span>Username dicoba</span>
                                    <strong
                                        x-text="detail.data.actor.username || eventValue(['username', 'username_tried'], 'Tidak tersedia')"></strong>
                                </div>
                                <div class="super-event-cell">
                                    <span>Hasil</span>
                                    <strong
                                        x-text="detailVariant() === 'login-failed' ? (detail.data.log.description || 'Login gagal') : 'Berhasil'"></strong>
                                </div>
                                <div class="super-event-cell">
                                    <span>Percobaan ke</span>
                                    <strong
                                        x-text="eventValue(['attempt', 'attempt_number', 'percobaan_ke'], 'Tidak tercatat')"></strong>
                                </div>
                                <p class="super-login-warning activity-login-warning"
                                    x-show="detailVariant() === 'login-failed'">
                                    Akun akan terkunci sementara setelah 5 percobaan gagal.
                                </p>
                            </div>

                            {{-- Variant: Diff Mutasi Data --}}
                            <template x-if="detailVariant() === 'changes' || detailVariant() === 'user-created'">
                                <div>
                                    <div class="super-tag tag-navy activity-change-count" x-show="changeRows().length"
                                        x-text="changeRows().length + ' perubahan'" style="margin-bottom: 8px;"></div>
                                    <div class="super-diff-wrap activity-diff-scroll" x-show="changeRows().length">
                                        <table class="super-diff-table activity-diff-table">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th x-show="detailVariant() !== 'user-created'">Nilai Lama</th>
                                                    <th
                                                        x-text="detailVariant() === 'user-created' ? 'Nilai Baru' : 'Nilai Baru'">
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="row in changeRows()" :key="row.key">
                                                    <tr>
                                                        <td class="super-diff-label" x-text="row.label"></td>
                                                        <td class="super-diff-old activity-old-value"
                                                            x-show="detailVariant() !== 'user-created'"
                                                            x-text="displayValue(row.oldValue)"></td>
                                                        <td class="super-diff-new activity-new-value"
                                                            x-text="displayValue(row.newValue)"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="super-no-diff activity-no-diff" x-show="!changeRows().length">Data perubahan
                                        tidak tersedia pada log ini.</p>
                                </div>
                            </template>
                        </section>

                        <p class="super-readonly-note activity-readonly-note">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="16" x2="12" y2="12" />
                                <line x1="12" y1="8" x2="12.01" y2="8" />
                            </svg>
                            <span>Log bersifat read-only dan tidak dapat diubah atau dihapus.</span>
                        </p>
                    </div>
                </template>

                {{-- Modal Footer --}}
                <footer class="super-modal-footer activity-modal-footer">
                    <div class="super-modal-nav activity-modal-navigation">
                        <button type="button" class="super-btn-nav activity-modal-nav-button"
                            :disabled="!canMoveDetail(-1)" @click="moveDetail(-1)">
                            ‹ Sebelumnya
                        </button>
                        <button type="button" class="super-btn-nav activity-modal-nav-button"
                            :disabled="!canMoveDetail(1)" @click="moveDetail(1)">
                            Berikutnya ›
                        </button>
                        <span class="super-modal-pos" x-show="detail.position"
                            x-text="detail.position + ' dari ' + detail.total"></span>
                    </div>
                    <button type="button" class="super-btn-cancel activity-modal-dismiss" @click="closeDetail()">
                        Tutup
                    </button>
                </footer>
            </section>
        </div>
    </div>

    @push('styles')
    @vite('resources/css/pages/super-admin/activity-log/index.css')
@endpush
@endsection
