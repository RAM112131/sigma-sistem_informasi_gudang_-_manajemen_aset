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

        .super-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            background: var(--brand-primary);
            color: #ffffff;
            font-size: var(--dash-text-body);
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: all 0.15s ease;
        }

        .super-btn-primary:hover {
            background: var(--brand-primary-d);
        }

        .super-btn-primary svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* 4 KPI Stat Cards */
        .super-dashboard-kpis {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .super-kpi {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 110px;
            padding: 16px;
            border-radius: var(--radius-md);
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-top: 3px solid var(--kpi-accent, var(--brand-primary));
            box-sizing: border-box;
        }

        .super-kpi:hover {
            border-color: var(--brand-primary);
        }

        .super-kpi-roles {
            --kpi-accent: #35536C;
        }

        .super-kpi-users {
            --kpi-accent: var(--status-success);
        }

        .super-kpi-warehouses {
            --kpi-accent: var(--brand-primary);
        }

        .super-kpi-activity {
            --kpi-accent: var(--brand-accent);
        }

        .super-kpi-heading {
            font-size: var(--dash-text-helper);
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .super-kpi-value {
            font-size: var(--dash-text-metric);
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
            margin: 8px 0;
        }

        .super-kpi-value small {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .super-kpi-icon {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--surface-base);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
        }

        .super-kpi-icon svg {
            width: 17px;
            height: 17px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-kpi-tag {
            align-self: flex-start;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .tag-navy {
            background: #EEF2F6;
            color: #35536C;
        }

        .tag-green {
            background: var(--status-success-bg);
            color: var(--status-success);
        }

        .tag-teal {
            background: rgba(20, 92, 84, 0.1);
            color: var(--brand-primary);
        }

        /* Alert Banners */
        .super-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: var(--dash-text-body);
            margin-bottom: 16px;
        }

        .super-alert svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
        }

        .super-alert-success {
            background: var(--status-success-bg);
            border: 1px solid rgba(63, 122, 82, 0.25);
            color: var(--status-success);
        }

        .super-alert-error {
            background: var(--status-critical-bg);
            border: 1px solid rgba(193, 68, 60, 0.25);
            color: var(--status-critical);
        }

        /* Filter Card */
        .super-filter-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .super-search-wrap {
            position: relative;
            flex: 1;
            min-width: 240px;
        }

        .super-search-wrap svg {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            stroke: var(--text-subtle);
            stroke-width: 2;
            fill: none;
            pointer-events: none;
        }

        .super-search-input {
            width: 100%;
            padding: 8px 12px 8px 34px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--surface-border);
            background: var(--surface-card);
            font-size: var(--dash-text-body);
            color: var(--text-primary);
            box-sizing: border-box;
            transition: all 0.15s ease;
        }

        .super-search-input:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(20, 92, 84, 0.15);
        }

        .super-filters-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .super-filter-select {
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--surface-border);
            background: var(--surface-card);
            font-size: var(--dash-text-body);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-filter-select:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(20, 92, 84, 0.15);
        }

        .super-btn-filter {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            color: var(--text-primary);
            font-size: var(--dash-text-body);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-btn-filter:hover {
            background: #EAE7E1;
            border-color: #D4D0C8;
        }

        .super-btn-filter svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-btn-reset {
            font-size: var(--dash-text-body);
            font-weight: 600;
            color: var(--brand-primary);
            text-decoration: underline;
            padding: 0 6px;
            cursor: pointer;
        }

        /* Card Results Wrapper */
        .super-results-card {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .super-results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--surface-border);
            flex-wrap: wrap;
            gap: 10px;
        }

        .super-results-title-group h2 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .super-results-subtitle {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin-top: 2px;
            display: block;
        }

        .super-results-counter {
            font-size: var(--dash-text-helper);
            font-weight: 600;
            color: var(--text-muted);
            background: var(--surface-base);
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid var(--surface-border);
        }

        /* Empty state */
        .super-empty-state {
            padding: 48px 24px;
            text-align: center;
        }

        .super-empty-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--surface-base);
            color: var(--text-subtle);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .super-empty-icon svg {
            width: 26px;
            height: 26px;
            stroke: currentColor;
            stroke-width: 1.8;
            fill: none;
        }

        .super-empty-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 4px;
        }

        .super-empty-desc {
            font-size: var(--dash-text-body);
            color: var(--text-muted);
            margin: 0 0 16px;
        }

        .super-btn-empty {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            background: var(--brand-primary);
            color: #ffffff;
            font-size: var(--dash-text-body);
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.15s ease;
        }

        .super-btn-empty:hover {
            background: var(--brand-primary-d);
        }

        .super-btn-empty svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* Grid Warehouse Cards */
        .super-warehouse-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
            padding: 20px;
        }

        .super-wh-card {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-md);
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            transition: all 0.15s ease;
        }

        .super-wh-card:hover {
            border-color: var(--brand-primary);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .super-wh-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .super-wh-identity {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .super-wh-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .super-wh-icon.icon-unit {
            background: rgba(20, 92, 84, 0.08);
            color: var(--brand-primary);
        }

        .super-wh-icon.icon-store {
            background: #FEF3E8;
            color: var(--brand-accent);
        }

        .super-wh-icon svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 1.8;
            fill: none;
        }

        .super-wh-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .super-wh-title-line {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .super-wh-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.25;
        }

        .super-wh-code {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            background: var(--surface-base);
            color: var(--text-primary);
            font-family: monospace;
            font-size: 0.6875rem;
            font-weight: 700;
            border: 1px solid var(--surface-border);
        }

        /* Badges */
        .super-role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .role-super {
            background: rgba(20, 92, 84, 0.1);
            color: var(--brand-primary);
        }

        .role-store {
            background: #FEF3E8;
            color: #C97A2B;
        }

        .super-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .status-active {
            background: var(--status-success-bg);
            color: var(--status-success);
        }

        .status-active::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--status-success);
        }

        .status-inactive {
            background: #F0EDE8;
            color: var(--text-subtle);
        }

        .status-inactive::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--text-subtle);
        }

        /* Stat Box Metrics */
        .super-wh-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 10px 12px;
            background: var(--surface-base);
            border-radius: var(--radius-sm);
            border: 1px solid var(--surface-border);
        }

        .super-wh-stat-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .super-wh-stat-num {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .super-wh-stat-label {
            font-size: 0.6875rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 1px;
        }

        /* Footer Actions */
        .super-wh-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid var(--surface-border);
        }

        .super-wh-tag-arch {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.6875rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .super-wh-tag-arch svg {
            width: 12px;
            height: 12px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-wh-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .super-btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--surface-border);
            background: var(--surface-card);
            color: var(--text-primary);
            font-size: var(--dash-text-helper);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-btn-action:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            background: rgba(20, 92, 84, 0.04);
        }

        .super-btn-action svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-btn-action.action-delete:hover {
            border-color: var(--status-critical);
            color: var(--status-critical);
            background: var(--status-critical-bg);
        }

        .super-pagination-wrap {
            padding: 16px 20px;
            border-top: 1px solid var(--surface-border);
            display: flex;
            justify-content: flex-end;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1100px) {
            .super-dashboard-kpis {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 680px) {
            .super-users-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .super-dashboard-kpis {
                grid-template-columns: 1fr;
            }

            .super-filter-card {
                flex-direction: column;
                align-items: stretch;
            }

            .super-search-wrap {
                min-width: 100%;
            }

            .super-filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .super-warehouse-grid {
                grid-template-columns: 1fr;
            }

            .super-results-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endsection
