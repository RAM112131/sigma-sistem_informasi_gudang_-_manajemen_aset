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

    @push('styles')
    @vite('resources/css/pages/super-admin/roles/index.css')
@endpush
@endsection
