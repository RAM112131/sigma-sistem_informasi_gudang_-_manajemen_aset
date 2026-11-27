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

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/admin-gudang-umum/reports/index.css')
@endpush

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
