@extends('layouts.app')

@section('title', 'Role & Permission Matrix — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="super-dashboard" x-data="{
        search: '',
        typeFilter: 'all',
        usageFilter: 'all',
        currentPage: 1,
        perPage: 10,
        roles: @js($roles->values()),
        get filteredRoleIndexes() {
            const searchTerm = this.search.trim().toLowerCase();
    
            return this.roles
                .map((role, index) => ({ role, index }))
                .filter(({ role }) => {
                    const roleText = [role.name, role.description, role.guard_name].join(' ').toLowerCase();
                    const matchesSearch = roleText.includes(searchTerm);
                    const matchesType = this.typeFilter === 'all' ||
                        (this.typeFilter === 'default' && role.is_default) ||
                        (this.typeFilter === 'custom' && !role.is_default);
                    const matchesUsage = this.usageFilter === 'all' ||
                        (this.usageFilter === 'used' && Number(role.users_count) > 0) ||
                        (this.usageFilter === 'unused' && Number(role.users_count) === 0);
    
                    return matchesSearch && matchesType && matchesUsage;
                })
                .map(({ index }) => index);
        },
        get visibleRoleIndexes() {
            const firstIndex = (this.currentPage - 1) * this.perPage;
    
            return this.filteredRoleIndexes.slice(firstIndex, firstIndex + this.perPage);
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredRoleIndexes.length / this.perPage));
        },
        get showingFrom() {
            return this.filteredRoleIndexes.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
        },
        get showingTo() {
            return Math.min(this.currentPage * this.perPage, this.filteredRoleIndexes.length);
        },
        resetFilters() {
            this.search = '';
            this.typeFilter = 'all';
            this.usageFilter = 'all';
            this.currentPage = 1;
        }
    }">
        {{-- Header Halaman --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <span>Role &amp; Permission Matrix</span>
                </nav>
                <h1 class="super-page-title">Role &amp; Permission</h1>
                <p class="super-page-subtitle">Kelola peran Spatie RBAC dinamis, alokasi matriks izin operasional gudang, dan
                    batasan hak akses pengguna.</p>
            </div>
            <div class="super-header-actions">
                <form action="{{ route('admin.roles.cache-sync') }}" method="POST">
                    @csrf
                    <button class="super-sync-btn" type="submit" title="Bersihkan dan sinkronkan cache Spatie RBAC">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M20 7v5h-5M4 17v-5h5" />
                            <path d="M5.5 9a7 7 0 0 1 11.6-2L20 12M4 12l2.9 5a7 7 0 0 0 11.6-2" />
                        </svg>
                        <span>Sinkronisasi Cache Spatie</span>
                        <span class="super-cache-pill">{{ ucfirst($stats['permission_cache_store']) }}</span>
                    </button>
                </form>
                <a class="super-header-btn" href="{{ route('admin.roles.create') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M12 5v14m-7-7h14" />
                    </svg>
                    <span>Buat Role Baru</span>
                </a>
            </div>
        </header>

        {{-- 4 KPI Stat Cards (Identik dengan Dashboard) --}}
        <section class="super-dashboard-kpis" aria-label="Ringkasan role dan permission">
            <div class="super-kpi super-kpi-roles">
                <span class="super-kpi-heading">TOTAL ROLE SISTEM</span>
                <span class="super-kpi-value">{{ $stats['total_roles'] }}<small> Role</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-navy">{{ $stats['default_roles'] }} BAWAAN, {{ $stats['custom_roles'] }}
                    KUSTOM</span>
            </div>

            <div class="super-kpi super-kpi-users">
                <span class="super-kpi-heading">TOTAL PERMISSION</span>
                <span class="super-kpi-value">{{ $stats['total_permissions'] }}<small> Izin</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="8" cy="15" r="5" />
                        <path d="m11.5 11.5 8-8 2 2-2 2 1.5 1.5-2 2L17.5 9l-2 2" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">SPATIE GUARD WEB</span>
            </div>

            <div class="super-kpi super-kpi-warehouses">
                <span class="super-kpi-heading">PENGGUNA TERALOKASI</span>
                <span class="super-kpi-value">{{ $stats['assigned_users'] }}<small> Pegawai</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-teal">AKUN AKTIF DENGAN PERAN</span>
            </div>

            <div class="super-kpi super-kpi-activity">
                <span class="super-kpi-heading">STATUS CACHE RBAC</span>
                <span class="super-kpi-value">{{ ucfirst($stats['permission_cache_store']) }}</span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path d="M7 8h10M7 12h10M7 16h5" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">INVALIDASI OTOMATIS</span>
            </div>
        </section>

        {{-- Banner Informasi Spatie --}}
        <div class="super-info-card">
            <svg aria-hidden="true" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                <path d="m9 12 2 2 4-4" />
            </svg>
            <p>
                <strong>Spatie RBAC Dinamis:</strong> Role bawaan sistem dilindungi dari penghapusan. Perubahan permission
                melalui aplikasi otomatis menginvalidasi cache; gunakan sinkronisasi cache jika diperlukan.
            </p>
        </div>

        {{-- Panel Daftar Role Utama --}}
        <article class="super-panel">
            {{-- Toolbar Filter & Pencarian --}}
            <div class="super-user-filters">
                <label class="super-search">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>
                    <span class="sr-only">Cari nama role, guard, atau fungsi</span>
                    <input type="search" placeholder="Cari nama role, guard, atau fungsi..."
                        aria-label="Cari nama role, guard, atau fungsi" x-model="search" x-on:input="currentPage = 1">
                </label>

                <select aria-label="Filter tipe role" class="super-filter-select" x-model="typeFilter"
                    x-on:change="currentPage = 1">
                    <option value="all">Semua Tipe Role</option>
                    <option value="default">Role Bawaan</option>
                    <option value="custom">Role Kustom</option>
                </select>

                <select aria-label="Filter penggunaan role" class="super-filter-select" x-model="usageFilter"
                    x-on:change="currentPage = 1">
                    <option value="all">Semua Status Penggunaan</option>
                    <option value="used">Aktif Dipakai</option>
                    <option value="unused">Kosong (0 Pengguna)</option>
                </select>

                <button class="super-filter-reset" type="button" title="Atur ulang pencarian dan filter"
                    aria-label="Atur ulang filter" @click="resetFilters()">
                    Reset
                </button>
            </div>

            {{-- Tabel Responsif --}}
            <div class="super-users-table-wrap">
                <table class="super-users-table">
                    <thead>
                        <tr>
                            <th scope="col">ROLE &amp; GUARD</th>
                            <th scope="col">DESKRIPSI WEWENANG</th>
                            <th scope="col" style="text-align: right;">JUMLAH PERMISSION</th>
                            <th scope="col" style="text-align: right;">PENGGUNA AKTIF</th>
                            <th scope="col" style="text-align: center;">TIPE</th>
                            <th scope="col" style="text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            @php
                                $roleClass = match ($role['name']) {
                                    'Super Admin', 'super_admin' => 'role-super',
                                    'Admin Gudang Store', 'admin_gudang_store' => 'role-store',
                                    'Admin Gudang Umum', 'admin_gudang_umum' => 'role-admin',
                                    'Recording Gudang Umum', 'recording_gudang_umum' => 'role-recording',
                                    default => 'role-custom',
                                };
                            @endphp
                            <tr x-cloak x-show="visibleRoleIndexes.includes({{ $loop->index }})">
                                <td>
                                    <div class="super-user-cell">
                                        <span
                                            class="super-role-avatar {{ $role['is_default'] ? 'is-default' : 'is-custom' }}">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                            </svg>
                                        </span>
                                        <div class="super-role-cell-details">
                                            <span class="super-role-badge {{ $roleClass }}">{{ $role['name'] }}</span>
                                            <span class="super-role-guard-code">{{ $role['guard_name'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="super-role-desc-cell">
                                    {{ $role['description'] ?: 'Tidak ada deskripsi khusus.' }}
                                </td>
                                <td style="text-align: right;">
                                    <div
                                        style="display: inline-flex; flex-direction: column; align-items: flex-end; gap: 3px;">
                                        <a class="super-count-link" href="{{ route('admin.roles.edit', $role['id']) }}"
                                            title="Atur permission role">
                                            <span>{{ $role['permissions_count'] }} Izin</span>
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </a>
                                        <span
                                            class="super-root-tag">{{ $role['is_super_admin'] ? 'Semua Root' : 'Permission terpilih' }}</span>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <span class="super-user-count-chip">{{ $role['active_users_count'] }} Pengguna</span>
                                </td>
                                <td style="text-align: center;">
                                    <span
                                        class="super-type-pill {{ $role['is_default'] ? 'pill-default' : 'pill-custom' }}">
                                        {{ $role['is_default'] ? 'Bawaan' : 'Kustom' }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="super-users-actions">
                                        <a class="super-action-btn btn-edit"
                                            href="{{ route('admin.roles.edit', $role['id']) }}"
                                            title="Atur role & permission">
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z" />
                                            </svg>
                                            <span>Atur</span>
                                        </a>
                                        @if ($role['is_default'] || $role['users_count'] > 0)
                                            <button class="super-action-btn btn-delete is-disabled" type="button"
                                                disabled
                                                title="{{ $role['is_default'] ? 'Role bawaan dilindungi dan tidak dapat dihapus' : 'Role masih digunakan oleh pengguna' }}"
                                                aria-label="Role {{ $role['name'] }} tidak dapat dihapus">
                                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                                    <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.roles.destroy', $role['id']) }}" method="POST"
                                                onsubmit="return confirm('Hapus role ini?')" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button class="super-action-btn btn-delete" type="submit"
                                                    title="Hapus role" aria-label="Hapus role {{ $role['name'] }}">
                                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                                        <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="super-table-empty">Belum ada role yang terdaftar.</td>
                            </tr>
                        @endforelse
                        <tr x-cloak x-show="roles.length > 0 && filteredRoleIndexes.length === 0">
                            <td colspan="6" class="super-table-empty">Tidak ada role yang cocok dengan pencarian atau
                                filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Footer Paginasi --}}
            <footer class="super-users-pagination">
                <div class="super-pagination-left">
                    <span>Menampilkan <strong x-text="showingFrom"></strong>–<strong x-text="showingTo"></strong> dari
                        <strong x-text="filteredRoleIndexes.length"></strong> role sistem</span>
                </div>
                <div class="super-pagination-right" x-show="filteredRoleIndexes.length > 0">
                    <button type="button" class="super-page-btn" :disabled="currentPage <= 1"
                        @click="currentPage = Math.max(1, currentPage - 1)">
                        ‹ Sebelumnya
                    </button>
                    <span class="super-page-indicator">Halaman <strong x-text="currentPage"></strong> dari <strong
                            x-text="totalPages"></strong></span>
                    <button type="button" class="super-page-btn" :disabled="currentPage >= totalPages"
                        @click="currentPage = Math.min(totalPages, currentPage + 1)">
                        Selanjutnya ›
                    </button>
                </div>
            </footer>
        </article>

        {{-- Catatan Arsitektur --}}
        <div class="super-panel-info-banner">
            <svg aria-hidden="true" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 16v-4m0-4h.01" />
            </svg>
            <span>Matriks permission dikonfigurasi secara granular per-guard (<code>web</code>). Penambahan role baru tidak
                memerlukan deploy kode ulang.</span>
        </div>
    </div>

    <style>
        .super-dashboard {
            --dash-text-helper: 12px;
            --dash-text-body: 14px;
            --dash-text-section: 20px;
            --dash-text-metric: 24px;
            min-width: 0;
        }

        /* Header Halaman */
        .super-users-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .super-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .super-breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s;
        }

        .super-breadcrumb a:hover {
            color: var(--brand-primary);
        }

        .super-breadcrumb span:last-child {
            color: var(--text-primary);
            font-weight: 700;
        }

        .super-page-title {
            font-size: var(--dash-text-section);
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .super-page-subtitle {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin: 3px 0 0;
            line-height: 1.35;
        }

        .super-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .super-sync-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 38px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            color: var(--text-primary);
            padding: 0 12px;
            border-radius: var(--radius);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-sync-btn:hover {
            background: var(--surface-base);
            border-color: var(--brand-primary);
        }

        .super-sync-btn svg {
            width: 14px;
            height: 14px;
            stroke: var(--brand-primary);
            stroke-width: 2;
            fill: none;
        }

        .super-cache-pill {
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 800;
        }

        .super-header-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 38px;
            background: var(--brand-primary);
            color: #ffffff;
            padding: 0 14px;
            border-radius: var(--radius);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .super-header-btn:hover {
            background: var(--brand-primary-d);
        }

        .super-header-btn svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* 4 KPI Cards (Identik dengan Dashboard Super Admin) */
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
            text-decoration: none;
        }

        .super-kpi:hover {
            border-color: var(--brand-primary);
        }

        .super-kpi-roles {
            --kpi-accent: var(--brand-accent);
        }

        .super-kpi-users {
            --kpi-accent: var(--status-success);
        }

        .super-kpi-warehouses {
            --kpi-accent: var(--brand-primary);
        }

        .super-kpi-activity {
            --kpi-accent: var(--status-neutral);
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

        .super-kpi-roles .super-kpi-icon {
            color: var(--brand-accent);
        }

        .super-kpi-users .super-kpi-icon {
            color: var(--status-success);
        }

        .super-kpi-warehouses .super-kpi-icon {
            color: var(--brand-primary);
        }

        .super-kpi-activity .super-kpi-icon {
            color: var(--status-neutral);
        }

        .super-kpi-icon svg {
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

        /* Info Card Banner */
        .super-info-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-left: 3px solid var(--brand-accent);
            border-radius: var(--radius);
            margin-bottom: 16px;
        }

        .super-info-card svg {
            width: 16px;
            height: 16px;
            stroke: var(--brand-accent);
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
        }

        .super-info-card p {
            margin: 0;
            font-size: var(--dash-text-helper);
            color: var(--text-primary);
            line-height: 1.4;
        }

        /* Super Panel Container */
        .super-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            margin-bottom: 16px;
        }

        /* Filter Toolbar */
        .super-user-filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-search {
            display: flex;
            min-width: 220px;
            flex: 1;
            height: 38px;
            align-items: center;
            gap: 7px;
            padding: 0 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-muted);
        }

        .super-search svg {
            width: 14px;
            height: 14px;
            flex: 0 0 14px;
            stroke: var(--text-muted);
            stroke-width: 2;
            fill: none;
        }

        .super-search input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            font: inherit;
            font-size: var(--dash-text-helper);
            color: var(--text-primary);
        }

        .super-search input::placeholder {
            color: var(--text-subtle);
        }

        .super-filter-select {
            display: flex;
            min-width: 140px;
            height: 38px;
            align-items: center;
            padding: 0 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
            font: inherit;
            font-size: var(--dash-text-helper);
            cursor: pointer;
        }

        .super-filter-select:focus,
        .super-search:focus-within {
            outline: 2px solid var(--brand-primary);
            outline-offset: 1px;
        }

        .super-filter-reset {
            height: 38px;
            padding: 0 14px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-muted);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-filter-reset:hover {
            background: var(--surface-card);
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        /* Table Styles */
        .super-users-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .super-users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        .super-users-table th {
            padding: 10px 14px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            font-weight: 800;
            letter-spacing: .035em;
        }

        .super-users-table td {
            height: 48px;
            padding: 8px 14px;
            border-bottom: 1px solid #edf2f0;
            color: var(--text-primary);
            font-size: var(--dash-text-body);
            vertical-align: middle;
        }

        .super-users-table tbody tr:hover {
            background: rgba(20, 92, 84, .02);
        }

        .super-user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 160px;
        }

        .super-role-avatar {
            width: 28px;
            height: 28px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--brand-primary);
        }

        .super-role-avatar.is-default {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
            border-color: rgba(20, 92, 84, .2);
        }

        .super-role-avatar.is-custom {
            background: rgba(201, 122, 43, .1);
            color: var(--brand-accent);
            border-color: rgba(201, 122, 43, .2);
        }

        .super-role-avatar svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-role-cell-details {
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        .super-role-guard-code {
            font-size: 11px;
            color: var(--text-subtle);
            font-family: monospace;
        }

        .super-role-desc-cell {
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            line-height: 1.35;
        }

        /* Permission Count Link */
        .super-count-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: var(--radius);
            background: rgba(20, 92, 84, .08);
            color: var(--brand-primary);
            font-size: var(--dash-text-helper);
            font-weight: 800;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .super-count-link:hover {
            background: var(--brand-primary);
            color: #fff;
        }

        .super-count-link svg {
            width: 12px;
            height: 12px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        .super-root-tag {
            font-size: 11px;
            color: var(--text-subtle);
        }

        .super-user-count-chip {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-primary);
            font-size: var(--dash-text-helper);
            font-weight: 700;
        }

        .super-type-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: var(--radius);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .pill-default {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .pill-custom {
            background: rgba(201, 122, 43, .12);
            color: var(--brand-accent-d);
        }

        /* Action Buttons */
        .super-users-actions {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .super-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 9px;
            border-radius: var(--radius);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            text-decoration: none;
            border: 1px solid var(--surface-border);
            cursor: pointer;
            transition: all 0.15s ease;
            background: #fff;
        }

        .super-action-btn svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .btn-edit {
            color: var(--brand-primary);
            border-color: rgba(20, 92, 84, .3);
            background: rgba(20, 92, 84, .04);
        }

        .btn-edit:hover {
            background: var(--brand-primary);
            color: #fff;
            border-color: var(--brand-primary);
        }

        .btn-delete {
            color: var(--status-critical);
            border-color: rgba(193, 68, 60, .3);
            background: rgba(193, 68, 60, .04);
        }

        .btn-delete:hover:not(:disabled) {
            background: var(--status-critical);
            color: #fff;
            border-color: var(--status-critical);
        }

        .btn-delete.is-disabled {
            opacity: 0.45;
            cursor: not-allowed;
            background: var(--surface-base);
            border-color: var(--surface-border);
            color: var(--text-subtle);
        }

        .super-table-empty {
            text-align: center;
            padding: 32px 16px !important;
            color: var(--text-muted) !important;
            font-size: var(--dash-text-body);
        }

        /* Pagination */
        .super-users-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 46px;
            padding: 8px 14px;
            background: #ffffff;
            border-top: 1px solid var(--surface-border);
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
        }

        .super-pagination-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .super-page-btn {
            padding: 4px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            cursor: pointer;
        }

        .super-page-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            color: var(--text-subtle);
        }

        .super-page-btn:not(:disabled):hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .super-page-indicator {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
        }

        /* Info Banner */
        .super-panel-info-banner {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: var(--radius);
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.35;
        }

        .super-panel-info-banner svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            stroke: var(--brand-primary);
            stroke-width: 2;
            fill: none;
        }

        /* Role Badges (Identik dengan Dashboard) */
        .super-role-badge {
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
            .super-dashboard-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .super-users-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .super-header-actions {
                justify-content: flex-start;
            }

            .super-dashboard-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .super-user-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .super-search,
            .super-filter-select,
            .super-filter-reset {
                width: 100%;
            }

            .super-users-pagination {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .super-pagination-right {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
@endsection
