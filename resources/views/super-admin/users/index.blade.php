@extends('layouts.app')

@section('title', 'Manajemen Pengguna — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="super-dashboard">
        {{-- Header Halaman --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <span>Manajemen Pengguna</span>
                </nav>
                <h1 class="super-page-title">Manajemen Pengguna</h1>
                <p class="super-page-subtitle">Kelola akun pengguna, role otoritas Spatie, status aktif, dan penugasan akses
                    gudang.</p>
            </div>
            <a class="super-header-btn" href="{{ route('admin.users.create') }}">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M12 5v14m-7-7h14" />
                </svg>
                <span>Tambah Pengguna</span>
            </a>
        </header>

        {{-- 4 KPI Stat Cards (Identik dengan Dashboard) --}}
        <section class="super-dashboard-kpis" aria-label="Ringkasan akun pengguna">
            <div class="super-kpi super-kpi-users">
                <span class="super-kpi-heading">TOTAL PENGGUNA</span>
                <span class="super-kpi-value">{{ number_format($stats['total_users']) }}<small> Akun</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="4" />
                        <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">{{ number_format($stats['active_users']) }} AKTIF,
                    {{ number_format($stats['inactive_users']) }} NONAKTIF</span>
            </div>

            <div class="super-kpi super-kpi-roles">
                <span class="super-kpi-heading">MULTI-ROLE SPATIE</span>
                <span class="super-kpi-value">{{ number_format($stats['multi_role_users']) }}<small> Akun</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-navy">LEBIH DARI 1 PERAN</span>
            </div>

            <div class="super-kpi super-kpi-warehouses">
                <span class="super-kpi-heading">GUDANG TERBANYAK</span>
                <span
                    class="super-kpi-value super-kpi-name-truncate">{{ $stats['top_gudang']?->nama ?? 'Belum ada gudang' }}</span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <path d="M9 22V12h6v10" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-teal">{{ number_format($stats['top_gudang']?->users_count ?? 0) }} PENGGUNA
                    DITUGASKAN</span>
            </div>

            <div class="super-kpi super-kpi-activity">
                <span class="super-kpi-heading">SESI AKTIF (ONLINE)</span>
                <span
                    class="super-kpi-value">{{ $stats['active_sessions'] === null ? '—' : number_format($stats['active_sessions']) }}<small>
                        Sesi</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 12a8 8 0 1 0 2.34-5.66L4 8.5" />
                        <path d="M4 4v4.5h4.5M12 8v4l3 2" />
                    </svg>
                </span>
                <span
                    class="super-kpi-tag tag-green">{{ $stats['active_sessions'] === null ? 'DRIVER SESI STATIK' : 'SESI AKTIF DATABASE' }}</span>
            </div>
        </section>

        {{-- Tabel Pengguna Utama (Super Panel) --}}
        <article class="super-panel">
            {{-- Filter Toolbar --}}
            <form action="{{ route('admin.users.index') }}" method="GET" class="super-user-filters">
                <label class="super-search">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>
                    <span class="sr-only">Cari nama, username, atau semantik pengguna</span>
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Cari nama, username, atau semantik...">
                </label>

                <label class="sr-only" for="users-role-filter">Filter role</label>
                <select name="role" id="users-role-filter" class="super-filter-select">
                    <option value="">Semua Role</option>
                    @foreach ($availableRoles as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                    @endforeach
                </select>

                <label class="sr-only" for="users-gudang-filter">Filter gudang</label>
                <select name="gudang" id="users-gudang-filter" class="super-filter-select">
                    <option value="">Semua Gudang</option>
                    @foreach ($availableGudangs as $gudang)
                        <option value="{{ $gudang->id }}" @selected((string) ($filters['gudang'] ?? '') === (string) $gudang->id)>
                            {{ $gudang->nama }}
                        </option>
                    @endforeach
                </select>

                <label class="sr-only" for="users-status-filter">Filter status</label>
                <select name="status" id="users-status-filter" class="super-filter-select">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                </select>

                <button type="submit" class="super-filter-submit" aria-label="Terapkan filter" title="Terapkan filter">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M20 7v5h-5M4 17v-5h5" />
                        <path d="M5.5 9a7 7 0 0 1 11.6-2L20 12M4 12l2.9 5a7 7 0 0 0 11.6-2" />
                    </svg>
                </button>

                @if (collect($filters)->filter(fn($value): bool => filled($value))->isNotEmpty())
                    <a href="{{ route('admin.users.index') }}" class="super-filter-reset" title="Reset filter">Reset</a>
                @endif
            </form>

            {{-- Tabel Responsif --}}
            <div class="super-users-table-wrap">
                <table class="super-users-table">
                    <thead>
                        <tr>
                            <th scope="col">PENGGUNA</th>
                            <th scope="col">USERNAME</th>
                            <th scope="col">ROLE OTORITAS</th>
                            <th scope="col">PENUGASAN GUDANG</th>
                            <th scope="col">STATUS</th>
                            <th scope="col">TANGGAL DIBUAT</th>
                            <th scope="col" style="text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $initials = collect(explode(' ', trim($user->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn(string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                                if ($initials === '') {
                                    $initials = mb_strtoupper(mb_substr($user->username, 0, 2));
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="super-user-cell">
                                        <span class="super-user-avatar">{{ $initials }}</span>
                                        <span class="super-user-details">
                                            <strong>{{ $user->name }}</strong>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="super-username-code">{{ $user->username }}</span>
                                </td>
                                <td>
                                    <div class="super-role-wrap">
                                        @forelse ($user->roles as $role)
                                            @php
                                                $roleClass = match ($role->name) {
                                                    'Super Admin', 'super_admin' => 'role-super',
                                                    'Admin Gudang Store', 'admin_gudang_store' => 'role-store',
                                                    'Admin Gudang Umum', 'admin_gudang_umum' => 'role-admin',
                                                    'Recording Gudang Umum',
                                                    'recording_gudang_umum'
                                                        => 'role-recording',
                                                    default => 'role-custom',
                                                };
                                            @endphp
                                            <span class="super-role-badge {{ $roleClass }}">{{ $role->name }}</span>
                                        @empty
                                            <span class="super-muted">Belum ada role</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if ($user->gudang->isNotEmpty())
                                        <span class="super-warehouse-chip">{{ $user->gudang->first()->nama }}</span>
                                        @if ($user->gudang->count() > 1)
                                            <span class="super-warehouse-more"
                                                title="{{ $user->gudang->pluck('nama')->join(', ') }}">+{{ $user->gudang->count() - 1 }}</span>
                                        @endif
                                    @else
                                        <span class="super-muted">Belum ditugaskan</span>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="super-status {{ $user->is_aktif ? 'status-active' : 'status-inactive' }}">
                                        {{ $user->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="super-date-cell">
                                    {{ $user->created_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td style="text-align: right;">
                                    <div class="super-users-actions">
                                        <a class="super-action-btn btn-edit"
                                            href="{{ route('admin.users.edit', $user) }}"
                                            title="Edit {{ $user->name }}" aria-label="Edit {{ $user->name }}">
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="m14 5 5 5M4 20l4.5-1 10.8-10.8a2.1 2.1 0 0 0-3-3L5.5 16 4 20Z" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus pengguna ini? Riwayat transaksinya akan tetap tersimpan.');"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="super-action-btn btn-delete" type="submit"
                                                title="Hapus {{ $user->name }}" aria-label="Hapus {{ $user->name }}"
                                                @if ($user->id === auth()->id()) disabled title="Anda tidak dapat menghapus akun Anda sendiri" @endif>
                                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                                    <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                                                </svg>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="super-table-empty">
                                    <strong>Pengguna tidak ditemukan</strong>
                                    <p style="margin:4px 0 0; color:var(--text-subtle);">Coba ubah kata kunci pencarian
                                        atau filter yang dipilih.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer Paginasi & Baris --}}
            <footer class="super-users-pagination">
                <div class="super-pagination-left">
                    <span>
                        Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari
                        {{ number_format($users->total()) }} pengguna
                    </span>
                    <form action="{{ route('admin.users.index') }}" method="GET" class="super-page-size-form">
                        @foreach ($filters as $key => $value)
                            @if ($key !== 'per_page' && filled($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <label for="users-per-page">Baris:</label>
                        <select id="users-per-page" name="per_page" onchange="this.form.submit()">
                            @foreach ([10, 25, 50] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if ($users->lastPage() > 1)
                    @php
                        $firstPage = max(1, $users->currentPage() - 2);
                        $lastPage = min($users->lastPage(), $users->currentPage() + 2);
                    @endphp
                    <nav class="super-pagination-nav" aria-label="Navigasi halaman pengguna">
                        @if ($users->onFirstPage())
                            <span class="page-control is-disabled" aria-disabled="true">Sebelumnya</span>
                        @else
                            <a class="page-control" href="{{ $users->previousPageUrl() }}">Sebelumnya</a>
                        @endif

                        @for ($page = $firstPage; $page <= $lastPage; $page++)
                            @if ($page === $users->currentPage())
                                <span class="page-number is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="page-number" href="{{ $users->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if ($users->hasMorePages())
                            <a class="page-control" href="{{ $users->nextPageUrl() }}">Selanjutnya</a>
                        @else
                            <span class="page-control is-disabled" aria-disabled="true">Selanjutnya</span>
                        @endif
                    </nav>
                @endif
            </footer>
        </article>

        <p class="super-audit-note">
            <svg aria-hidden="true" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 11v5m0-8h.01" />
            </svg>
            Data yang ditampilkan mengikuti akun pengguna, role Spatie, penugasan gudang, dan sesi database yang aktif.
        </p>
    </div>

    @push('styles')
    @vite('resources/css/pages/super-admin/users/index.css')
@endpush
@endsection
