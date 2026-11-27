@extends('layouts.app')

@section('title', 'Master Lokasi Gudang — ' . config('app.name', 'SIGMA'))

@section('content')
    @php
        $totalGudang = \App\Models\Gudang::count();
        $totalStore = \App\Models\Gudang::whereIn('jenis', ['holding', 'global_master', 'gudang_store'])->count();
        $totalUnit = \App\Models\Gudang::whereIn('jenis', ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'])->count();
        $totalActive = \App\Models\Gudang::where('is_aktif', true)->count();
    @endphp

    <div class="super-dashboard">
        {{-- Header Halaman --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <span>Master Lokasi Gudang</span>
                </nav>
                <h1 class="super-page-title">Master Lokasi Gudang</h1>
                <p class="super-page-subtitle">Kelola fasilitas fisik, tipe operasional gudang, penugasan staf, serta
                    arsitektur sentral dan unit bisnis.</p>
            </div>
            <div class="super-header-actions">
                <a class="super-btn-primary" href="{{ route('admin.gudang.create') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    <span>Tambah Lokasi Gudang</span>
                </a>
            </div>
        </header>

        {{-- 4 KPI Stat Cards (Identik dengan Dashboard) --}}
        <section class="super-dashboard-kpis" aria-label="Ringkasan master lokasi gudang">
            <div class="super-kpi super-kpi-roles">
                <span class="super-kpi-heading">TOTAL FASILITAS GUDANG</span>
                <span class="super-kpi-value">{{ $totalGudang }}<small> Fasilitas</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-navy">ENTITAS MASTER FASILITAS</span>
            </div>

            <div class="super-kpi super-kpi-users">
                <span class="super-kpi-heading">GUDANG UNIT BISNIS</span>
                <span class="super-kpi-value">{{ $totalUnit }}<small> Unit</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">OPERASIONAL CABANG</span>
            </div>

            <div class="super-kpi super-kpi-warehouses">
                <span class="super-kpi-heading">GUDANG STORE / HOLDING</span>
                <span class="super-kpi-value">{{ $totalStore }}<small> Sentral</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-teal">KATALOG PUSAT &amp; HOLDING</span>
            </div>

            <div class="super-kpi super-kpi-activity">
                <span class="super-kpi-heading">FASILITAS AKTIF</span>
                <span class="super-kpi-value">{{ $totalActive }}<small> Fasilitas</small></span>
                <span class="super-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </span>
                <span class="super-kpi-tag tag-green">SIAP TRANSAKSI OPERASIONAL</span>
            </div>
        </section>

        {{-- Filter Toolbar --}}
        <form class="super-filter-card" action="{{ route('admin.gudang.index') }}" method="GET">
            <div class="super-search-wrap">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
                <input class="super-search-input" type="search" name="search" value="{{ $filters['search'] }}"
                    placeholder="Cari berdasarkan kode atau nama gudang..." aria-label="Cari gudang">
            </div>
            <div class="super-filters-row">
                <select class="super-filter-select" name="jenis" aria-label="Filter tipe gudang">
                    <option value="">Semua tipe gudang</option>
                    <option value="gudang_umum" @selected($filters['jenis'] === 'gudang_umum')>Gudang Umum</option>
                    <option value="unit_bisnis" @selected($filters['jenis'] === 'unit_bisnis')>Unit Bisnis</option>
                    <option value="gudang_sukahati" @selected($filters['jenis'] === 'gudang_sukahati')>Gudang Sukahati</option>
                    <option value="gudang_store" @selected($filters['jenis'] === 'gudang_store')>Gudang Store</option>
                    <option value="global_master" @selected($filters['jenis'] === 'global_master')>Master Global</option>
                    <option value="holding" @selected($filters['jenis'] === 'holding')>Holding</option>
                </select>
                <select class="super-filter-select" name="status" aria-label="Filter status">
                    <option value="">Semua status</option>
                    <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                </select>
                <button class="super-btn-filter" type="submit">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                    </svg>
                    <span>Terapkan</span>
                </button>
                @if ($filters['search'] !== '' || $filters['jenis'] !== '' || $filters['status'] !== '')
                    <a class="super-btn-reset" href="{{ route('admin.gudang.index') }}">Reset</a>
                @endif
            </div>
        </form>

        {{-- Konten Daftar Gudang --}}
        <div class="super-results-card">
            <div class="super-results-header">
                <div class="super-results-title-group">
                    <h2>Daftar Fasilitas</h2>
                    <span class="super-results-subtitle">Fasilitas logistik terdaftar pada sistem inventaris SIGMA</span>
                </div>
                <div class="super-results-counter">
                    Menampilkan {{ $gudangs->firstItem() ?? 0 }}–{{ $gudangs->lastItem() ?? 0 }}
                    dari {{ $gudangs->total() }} gudang
                </div>
            </div>

            @if ($gudangs->isEmpty())
                <div class="super-empty-state">
                    <div class="super-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <path d="M9 22V12h6v10" />
                        </svg>
                    </div>
                    <h3 class="super-empty-title">Gudang tidak ditemukan</h3>
                    <p class="super-empty-desc">Coba ubah kata pencarian atau filter yang digunakan.</p>
                    <a class="super-btn-empty" href="{{ route('admin.gudang.create') }}">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        <span>Tambah lokasi gudang</span>
                    </a>
                </div>
            @else
                <div class="super-warehouse-grid">
                    @foreach ($gudangs as $gudang)
                        @php
                            $isStoreType = in_array(
                                $gudang['jenis'],
                                ['holding', 'global_master', 'gudang_store'],
                                true,
                            );
                        @endphp
                        <article class="super-wh-card">
                            {{-- Top identity --}}
                            <div class="super-wh-top">
                                <div class="super-wh-identity">
                                    <div class="super-wh-icon {{ $isStoreType ? 'icon-store' : 'icon-unit' }}"
                                        aria-hidden="true">
                                        <svg viewBox="0 0 24 24">
                                            @if ($isStoreType)
                                                <ellipse cx="12" cy="5" rx="9" ry="3" />
                                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                                            @else
                                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                                <polyline points="9 22 9 12 15 12 15 22" />
                                            @endif
                                        </svg>
                                    </div>
                                    <div class="super-wh-meta">
                                        <div class="super-wh-title-line">
                                            <h3 class="super-wh-name">{{ $gudang['nama'] }}</h3>
                                            <code class="super-wh-code">{{ $gudang['kode'] }}</code>
                                        </div>
                                        <div>
                                            <span
                                                class="super-role-badge {{ $isStoreType ? 'role-store' : 'role-super' }}">
                                                {{ $gudang['jenis_label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="super-status {{ $gudang['is_aktif'] ? 'status-active' : 'status-inactive' }}">
                                    {{ $gudang['is_aktif'] ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            {{-- Metrics & Stats --}}
                            <div class="super-wh-stats">
                                <div class="super-wh-stat-box">
                                    <span class="super-wh-stat-num">{{ number_format($gudang['items_count']) }}</span>
                                    <span class="super-wh-stat-label">Barang</span>
                                </div>
                                <div class="super-wh-stat-box">
                                    <span class="super-wh-stat-num">{{ number_format($gudang['users_count']) }}</span>
                                    <span class="super-wh-stat-label">Pegawai</span>
                                </div>
                                <div class="super-wh-stat-box">
                                    <span class="super-wh-stat-num"
                                        style="font-size: 0.75rem; font-family: monospace; text-transform: uppercase;">{{ $gudang['jenis'] }}</span>
                                    <span class="super-wh-stat-label">Tipe Sistem</span>
                                </div>
                            </div>

                            {{-- Footer actions --}}
                            <div class="super-wh-footer">
                                <span class="super-wh-tag-arch">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                    </svg>
                                    <span>{{ $isStoreType ? 'Sentral Catalog' : 'Unit Logistik' }}</span>
                                </span>
                                <div class="super-wh-actions">
                                    <a class="super-btn-action" href="{{ route('admin.gudang.edit', $gudang['id']) }}"
                                        title="Edit lokasi gudang">
                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>
                                    <form action="{{ route('admin.gudang.destroy', $gudang['id']) }}" method="POST"
                                        onsubmit="return confirm('Hapus lokasi gudang ini? Pastikan tidak ada pegawai yang ditugaskan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="super-btn-action action-delete" type="submit"
                                            title="Hapus lokasi gudang">
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                <line x1="10" y1="11" x2="10" y2="17" />
                                                <line x1="14" y1="11" x2="14" y2="17" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="super-pagination-wrap">
                    {{ $gudangs->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('styles')
    @vite('resources/css/pages/super-admin/gudang/index.css')
@endpush
@endsection
