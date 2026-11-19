@extends('layouts.app')

@section('title', 'Rekapitulasi Laporan Matriks — ' . config('app.name', 'SIGMA'))

@section('content')
    @php
        $tabs = [
            'mutasi' => 'Mutasi Stok',
            'matriks' => 'Pemakaian per Divisi',
            'pemasukan' => 'Rekap Pemasukan',
            'selisih' => 'Selisih & Barang Rusak',
        ];
        $queryFilters = array_filter($filters, static fn(mixed $value): bool => $value !== null && $value !== '');
        $formatQty = static fn(mixed $value): string => rtrim(
            rtrim(number_format((float) $value, 3, ',', '.'), '0'),
            ',',
        );
        $formatMoney = static fn(mixed $value): string => ((float) $value < 0 ? '-Rp ' : 'Rp ') .
            number_format(abs((float) $value), 0, ',', '.');
        $numberClass = static fn(mixed $value): string => 'tabular-nums text-right ' .
            ((float) $value === 0.0 ? 'text-slate-400' : 'text-slate-700');
    @endphp

    <style>
        [x-cloak] {
            display: none !important;
        }

        .report-table th,
        .report-table td {
            white-space: nowrap;
        }

        .report-dashboard {
            --report-helper: 12px;
            min-width: 0;
        }

        .report-surface {
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: none;
        }

        .report-page-header {
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
        }

        .report-page-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
        }

        .report-page-header p {
            color: var(--text-muted);
        }

        .report-page-header .report-location {
            font-size: var(--report-helper);
            font-weight: 600;
        }

        .report-page-header .report-description {
            max-width: 680px;
            font-size: var(--report-helper);
            line-height: 1.4;
        }

        .report-context-badge,
        .report-status-badge {
            padding: 4px 7px;
            border-radius: var(--radius);
            font-size: var(--report-helper);
            font-weight: 800;
        }

        .report-context-badge {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .report-status-badge {
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
        }

        .report-actions {
            align-items: center;
        }

        .report-action {
            min-height: 38px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            font-size: var(--report-helper);
            font-weight: 800;
        }

        .report-action-secondary {
            background: var(--surface-card);
            color: var(--brand-primary);
        }

        .report-action-secondary:hover {
            border-color: var(--brand-primary);
            background: var(--surface-base);
        }

        .report-action-primary {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .report-action-primary:hover {
            border-color: var(--brand-primary-d);
            background: var(--brand-primary-d);
        }

        .report-filter-panel {
            padding: 12px 14px;
        }

        .report-filter-form {
            gap: 8px;
        }

        .report-field-label {
            display: block;
            margin-bottom: 4px;
            color: var(--text-muted);
            font-size: var(--report-helper);
            font-weight: 700;
        }

        .report-control {
            min-height: 38px;
            border-color: var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
            font-size: var(--report-helper);
        }

        .report-control::placeholder {
            color: var(--text-subtle);
        }

        .report-control:focus-visible,
        .report-filter-submit:focus-visible,
        .report-tab:focus-visible,
        .report-matrix-mode button:focus-visible,
        .report-matrix-pagination a:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .report-filter-submit {
            min-height: 38px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--brand-primary);
            font-size: var(--report-helper);
            font-weight: 800;
        }

        .report-filter-submit:hover {
            border-color: var(--brand-primary);
            background: var(--surface-base);
        }

        .report-tabs {
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: none;
        }

        .report-tabs-list {
            gap: 3px;
            padding: 6px;
        }

        .report-tab {
            min-height: 38px;
            border-radius: var(--radius);
            color: var(--text-muted);
            font-size: var(--report-helper);
            font-weight: 800;
        }

        .report-tab:hover {
            background: var(--surface-base);
            color: var(--brand-primary);
        }

        .report-tab[aria-selected="true"] {
            background: var(--brand-primary);
            color: #fff;
        }

        .report-matrix-panel {
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: none;
        }

        .report-matrix-heading {
            min-height: 68px;
            align-items: center;
            padding: 14px 16px;
            border-color: var(--surface-border);
            background: var(--surface-card);
        }

        .report-matrix-heading h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 800;
            line-height: 1.2;
        }

        .report-matrix-heading p {
            margin: 3px 0 0;
            color: var(--text-muted);
            font-size: var(--report-helper);
            line-height: 1.4;
        }

        .report-matrix-mode {
            min-height: 38px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
        }

        .report-matrix-mode button {
            min-height: 32px;
            border-radius: var(--radius);
            color: var(--text-muted);
            font-size: var(--report-helper);
            font-weight: 800;
        }

        .report-matrix-mode button[aria-pressed="true"] {
            background: var(--surface-card);
            color: var(--brand-primary);
            box-shadow: 0 1px 3px rgba(31, 37, 35, .12);
        }

        .report-matrix-scroll {
            width: 100%;
            overflow: auto;
            scrollbar-color: #b6c4bf var(--surface-base);
            scrollbar-width: thin;
        }

        .report-matrix-table {
            min-width: max-content;
            border-collapse: separate;
            border-spacing: 0;
            color: var(--text-primary);
            font-size: var(--report-helper);
        }

        .report-matrix-table thead th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--report-helper);
            font-weight: 800;
            letter-spacing: .035em;
            vertical-align: middle;
        }

        .report-matrix-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #edf2f0;
            background: var(--surface-card);
            color: var(--text-primary);
            vertical-align: middle;
        }

        .report-matrix-table tbody tr:hover td {
            background: #f7faf8;
        }

        .report-matrix-table tfoot td {
            padding: 10px 12px;
            border-top: 2px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--text-primary);
            font-weight: 800;
            vertical-align: middle;
        }

        .report-matrix-table th:first-child,
        .report-matrix-table tfoot td:first-child {
            background: var(--surface-base);
        }

        .report-matrix-table tbody td:first-child {
            background: var(--surface-card);
        }

        .report-matrix-table tbody tr:hover td:first-child {
            background: #f7faf8;
        }

        .report-matrix-empty {
            padding: 32px 16px !important;
            color: var(--text-muted) !important;
            text-align: center;
        }

        .report-matrix-pagination {
            border-top: 1px solid var(--surface-border);
            padding: 8px 12px;
        }

        .report-matrix-pagination nav {
            font-size: var(--report-helper);
        }

        @media (max-width: 820px) {
            .report-page-header {
                flex-wrap: wrap;
            }

            .report-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .report-filter-submit {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 600px) {
            .report-page-header {
                padding: 12px;
            }

            .report-page-header h1 {
                font-size: 20px;
            }

            .report-actions,
            .report-actions a {
                flex: 1 1 100%;
            }

            .report-actions a {
                min-height: 44px;
            }

            .report-filter-panel {
                padding: 10px;
            }

            .report-filter-form {
                grid-template-columns: minmax(0, 1fr);
            }

            .report-filter-submit {
                grid-column: auto;
                min-height: 44px;
            }

            .report-control {
                min-height: 44px;
                font-size: 14px;
            }

            .report-tab {
                min-height: 44px;
            }

            .report-matrix-heading {
                align-items: flex-start;
                padding: 12px;
            }

            .report-matrix-heading h2 {
                font-size: 18px;
            }

            .report-matrix-mode {
                min-height: 44px;
            }

            .report-matrix-mode button {
                min-height: 36px;
            }
        }
    </style>

    <div class="report-dashboard mx-auto max-w-[1600px] space-y-5 pb-10">
        <header
            class="report-surface report-page-header flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span
                        class="report-context-badge rounded-full bg-[#145C54]/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-[#145C54]">Menu
                        9 · Admin GU</span>
                    <span
                        class="report-status-badge rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">{{ $reportStatus }}</span>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Rekapitulasi Laporan Matriks</h1>
                <p class="report-location mt-1 text-sm text-slate-600">
                    {{ $activeGudang?->nama ?? 'Gudang belum dipilih' }} <span class="mx-1 text-slate-300">·</span>
                    {{ $periodLabel }}
                </p>
                <p class="report-description mt-1 max-w-3xl text-xs leading-relaxed text-slate-500">
                    Ringkasan hanya membaca transaksi terverifikasi dan penyesuaian opname yang sudah diterapkan.
                </p>
            </div>
            <div class="report-actions flex flex-wrap gap-2">
                <a href="{{ route('unit-bisnis.admin-gu.laporan.export-excel', $queryFilters) }}"
                    class="report-action report-action-secondary inline-flex min-h-11 items-center justify-center rounded-lg border border-[#145C54] bg-white px-4 text-sm font-semibold text-[#145C54] transition hover:bg-[#145C54]/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/40">
                    Unduh Excel
                </a>
                <a href="{{ route('unit-bisnis.admin-gu.laporan.export-pdf', $queryFilters) }}"
                    class="report-action report-action-primary inline-flex min-h-11 items-center justify-center rounded-lg bg-[#145C54] px-4 text-sm font-semibold text-white transition hover:bg-[#0F4740] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/40">
                    Cetak PDF Resmi
                </a>
            </div>
        </header>

        @if ($activeGudang === null)
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="status">
                Gudang Umum aktif belum tersedia untuk akun Anda. Hubungi Admin Sistem untuk memeriksa penugasan gudang.
            </section>
        @else
            <section
                class="report-surface report-filter-panel rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <form method="GET" action="{{ route('unit-bisnis.admin-gu.laporan.index') }}"
                    class="report-filter-form grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[170px_minmax(180px,1fr)_minmax(220px,1.3fr)_auto] xl:items-end">
                    <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                    <input type="hidden" name="matrix_mode" value="{{ $filters['matrix_mode'] }}">
                    @if ($filters['source_type'])
                        <input type="hidden" name="source_type" value="{{ $filters['source_type'] }}">
                    @endif
                    <label class="report-field block">
                        <span class="report-field-label mb-1.5 block text-xs font-semibold text-slate-700">Periode
                            laporan</span>
                        <input type="month" name="period" value="{{ $filters['period'] }}" required
                            class="report-control min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15">
                    </label>
                    <label class="report-field block">
                        <span class="report-field-label mb-1.5 block text-xs font-semibold text-slate-700">Kategori
                            barang</span>
                        <select name="category_id"
                            class="report-control min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($filters['category_id'] === (int) $category->id)>{{ $category->nama }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="report-field block">
                        <span class="report-field-label mb-1.5 block text-xs font-semibold text-slate-700">Cari kode / nama
                            SKU</span>
                        <input type="search" name="search" value="{{ $filters['search'] }}" maxlength="255"
                            placeholder="Contoh: BRG-001 atau tepung"
                            class="report-control min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15">
                    </label>
                    <button type="submit"
                        class="report-filter-submit inline-flex min-h-11 items-center justify-center rounded-lg bg-[#0F4740] px-5 text-sm font-semibold text-white transition hover:bg-[#145C54] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/40">
                        Terapkan Filter
                    </button>
                </form>
            </section>

            <nav class="report-tabs overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm"
                aria-label="Tab laporan">
                <div class="report-tabs-list flex min-w-max gap-1 p-1.5" role="tablist">
                    @foreach ($tabs as $tab => $label)
                        <a href="{{ route('unit-bisnis.admin-gu.laporan.index', [...$queryFilters, 'tab' => $tab]) }}"
                            role="tab" aria-selected="{{ $filters['tab'] === $tab ? 'true' : 'false' }}"
                            class="report-tab inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/40 {{ $filters['tab'] === $tab ? 'bg-[#145C54] text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-[#145C54]' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </nav>

            <main class="report-main min-w-0">
                @switch($filters['tab'])
                    @case('matriks')
                        @include('unit-bisnis.admin-gudang-umum.reports._tab-matriks')
                    @break

                    @case('pemasukan')
                        @include('unit-bisnis.admin-gudang-umum.reports._tab-pemasukan')
                    @break

                    @case('selisih')
                        @include('unit-bisnis.admin-gudang-umum.reports._tab-selisih')
                    @break

                    @default
                        @include('unit-bisnis.admin-gudang-umum.reports._tab-mutasi')
                @endswitch
            </main>
        @endif
    </div>
@endsection
