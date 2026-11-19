@extends('layouts.app')

@section('title', 'Stok Opname Bulanan — ' . config('app.name', 'SIGMA'))

@section('content')
    @php
        $formatQty = static fn(float $quantity): string => rtrim(
            rtrim(number_format($quantity, 3, ',', '.'), '0'),
            ',',
        );
        $formatMoney = static fn(float $amount): string => ($amount < 0 ? '-Rp ' : 'Rp ') .
            number_format(abs($amount), 0, ',', '.');
        $isApplied = $opname?->status === 'diterapkan';
        $complete = $metrics['total'] > 0 && $metrics['belum'] === 0;
        $cards = [
            [
                'label' => 'Total item diaudit',
                'value' => number_format($metrics['total']),
                'foot' =>
                    number_format($metrics['dihitung']) . ' dari ' . number_format($metrics['total']) . ' SKU dihitung',
                'tone' => 'text-[#1F2523]',
                'icon' => 'M4 4h16v16H4zM8 8h8M8 12h8M8 16h4',
            ],
            [
                'label' => 'Item sesuai',
                'value' => number_format($metrics['sesuai']),
                'foot' => 'Akurasi ' . number_format($metrics['akurasi'], 1, ',', '.') . '% dari item terhitung',
                'tone' => 'text-[#145C54]',
                'icon' => 'm5 12 4 4L19 6',
            ],
            [
                'label' => 'Selisih kurang',
                'value' => number_format($metrics['kurang']),
                'foot' => $formatMoney((float) $metrics['nilai_kurang']),
                'tone' => 'text-[#B42318]',
                'icon' => 'm4 7 6 6 4-4 6 6',
            ],
            [
                'label' => 'Selisih lebih',
                'value' => number_format($metrics['lebih']),
                'foot' => $formatMoney((float) $metrics['nilai_lebih']),
                'tone' => 'text-[#1D5B52]',
                'icon' => 'm4 17 6-6 4 4 6-6',
            ],
            [
                'label' => 'Nilai selisih bersih',
                'value' => $formatMoney((float) $metrics['nilai_bersih']),
                'foot' => 'Berdasarkan harga satuan saat opname',
                'tone' => $metrics['nilai_bersih'] < 0 ? 'text-[#B42318]' : 'text-[#1F2523]',
                'icon' => 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
            ],
        ];
        $statusFilters = [
            'semua' => ['Semua', $metrics['total']],
            'kurang' => ['Kurang', $metrics['kurang']],
            'sesuai' => ['Sesuai', $metrics['sesuai']],
            'lebih' => ['Lebih', $metrics['lebih']],
            'belum' => ['Belum dihitung', $metrics['belum']],
        ];
    @endphp

    <div class="stocktake-dashboard space-y-5 pb-28">
        <header class="stocktake-page-header flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <h1 class="stocktake-page-title text-[22px] font-bold leading-snug tracking-tight text-[#1F2523]">Stok Opname
                    Bulanan</h1>
                <p class="stocktake-page-description mt-1 max-w-3xl text-xs leading-relaxed text-[#5B6B79] sm:text-[13px]">
                    Hitung stok fisik setiap barang. Patokan stok sistem diambil saat hitungan barang disimpan.
                </p>
            </div>

            <div class="stocktake-page-controls flex flex-wrap items-end gap-2">
                <form method="GET" action="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}"
                    class="stocktake-period-form flex flex-wrap items-end gap-2">
                    <label class="block">
                        <span class="mb-1 block text-[11px] font-semibold text-[#5B6B79]">Periode</span>
                        <select name="periode"
                            class="stocktake-control min-h-11 rounded-lg border border-[#D7DDD9] bg-white px-3 text-xs font-semibold text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15 lg:min-h-9 lg:px-2.5"
                            onchange="this.form.requestSubmit()">
                            @foreach ($periodOptions as $value => $label)
                                <option value="{{ $value }}" @selected($period === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    @if ($gudangUnits->count() > 1)
                        <label class="block">
                            <span class="mb-1 block text-[11px] font-semibold text-[#5B6B79]">Gudang</span>
                            <select name="gudang_id"
                                class="stocktake-control min-h-11 max-w-56 rounded-lg border border-[#D7DDD9] bg-white px-3 text-xs font-semibold text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15 lg:min-h-9 lg:px-2.5"
                                onchange="this.form.requestSubmit()">
                                @foreach ($gudangUnits as $gudang)
                                    <option value="{{ $gudang->getKey() }}" @selected((int) $activeGudang?->getKey() === (int) $gudang->getKey())>{{ $gudang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="gudang_id" value="{{ $activeGudang?->getKey() }}">
                    @endif
                </form>

                @if ($isApplied)
                    <a href="{{ route('unit-bisnis.admin-gu.stok-opname.pdf', ['periode' => $period, 'gudang_id' => $activeGudang?->getKey()]) }}"
                        class="stocktake-print-action inline-flex min-h-11 items-center gap-2 rounded-lg border border-[#D7DDD9] bg-white px-3.5 text-xs font-semibold text-[#1F2523] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 lg:min-h-9 lg:px-3">
                        <svg aria-hidden="true" class="h-[18px] w-[18px] text-[#145C54]" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path d="M7 3h7l5 5v13H7z" />
                            <path d="M14 3v5h5M9 15h8M9 18h6" />
                        </svg>
                        Cetak Berita Acara
                    </a>
                @endif
            </div>
        </header>

        @include('unit-bisnis.admin-gudang-umum.stok-opname._tabs', ['activeTab' => 'process'])

        @if ($errors->any())
            <section class="stocktake-feedback is-error rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                role="alert">
                <h2 class="font-bold">Opname belum dapat diproses</h2>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!$activeGudang)
            <section
                class="stocktake-feedback is-warning rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                role="alert">
                <h2 class="font-bold">Gudang aktif belum tersedia</h2>
                <p class="mt-1">Akun ini belum memiliki penugasan Gudang Umum aktif.</p>
            </section>
        @else
            @if ($metrics['needs_snapshot'] > 0)
                <div class="stocktake-feedback is-warning rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                    role="alert">
                    {{ number_format($metrics['needs_snapshot']) }} hitungan draf lama belum memiliki waktu snapshot. Hitung
                    ulang barang tersebut lalu simpan per baris sebelum finalisasi.
                </div>
            @endif

            <section class="stocktake-kpis grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5"
                aria-label="Ringkasan stok opname">
                @foreach ($cards as $card)
                    <article
                        class="stocktake-kpi flex min-h-32 flex-col justify-between rounded-xl border border-[#E4E2DC] bg-white p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="stocktake-kpi-heading text-[11px] font-bold uppercase tracking-wider text-[#5B6B79]">
                                {{ $card['label'] }}</h2>
                            <span
                                class="stocktake-kpi-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#E8F1EF] text-[#145C54]">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="{{ $card['icon'] }}" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-3">
                            <strong
                                class="stocktake-kpi-value block truncate text-[23px] font-bold leading-tight tabular-nums {{ $card['tone'] }}">{{ $card['value'] }}</strong>
                            <span
                                class="stocktake-kpi-foot mt-1 block truncate text-[11px] text-[#5B6B79]">{{ $card['foot'] }}</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="stocktake-panel overflow-hidden rounded-xl border border-[#E4E2DC] bg-white">
                <div
                    class="stocktake-filter-bar flex flex-col gap-3 border-b border-[#E4E2DC] p-4 sm:flex-row sm:items-center sm:justify-between">
                    <nav class="stocktake-status-filters flex flex-wrap gap-1.5" aria-label="Filter status opname">
                        @foreach ($statusFilters as $statusKey => [$statusLabel, $count])
                            <a href="{{ route('unit-bisnis.admin-gu.stok-opname.index', array_filter(['periode' => $period, 'gudang_id' => $activeGudang->getKey(), 'status' => $statusKey, 'q' => $filters['q'], 'per_page' => $filters['per_page']])) }}"
                                @if ($filters['status'] === $statusKey) aria-current="page" @endif
                                class="stocktake-status-tab inline-flex min-h-9 items-center gap-1.5 rounded-full px-3 text-[11px] font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 {{ $filters['status'] === $statusKey ? 'bg-[#145C54] text-white' : 'bg-white text-[#52615D] hover:bg-[#E8F1EF]' }}">
                                {{ $statusLabel }} <span
                                    class="tabular-nums {{ $filters['status'] === $statusKey ? 'text-white/80' : 'text-[#77827E]' }}">{{ number_format($count) }}</span>
                            </a>
                        @endforeach
                    </nav>

                    <form method="GET" action="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}"
                        class="stocktake-search-form flex w-full gap-2 sm:w-auto">
                        <input type="hidden" name="periode" value="{{ $period }}">
                        <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                        <input type="hidden" name="status" value="{{ $filters['status'] }}">
                        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                        <label class="stocktake-search relative block w-full sm:w-72">
                            <span class="sr-only">Cari kode SKU atau nama barang</span>
                            <svg aria-hidden="true" class="absolute left-3 top-3 h-4 w-4 text-[#71807B]" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-4-4" />
                            </svg>
                            <input type="search" name="q" value="{{ $filters['q'] }}"
                                placeholder="Cari SKU atau nama barang..."
                                class="stocktake-search-input min-h-10 w-full rounded-lg border border-[#D7DDD9] bg-white py-2 pl-9 pr-3 text-xs outline-none transition focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15 lg:min-h-9 lg:py-1.5">
                        </label>
                        <button type="submit"
                            class="stocktake-search-submit min-h-10 rounded-lg bg-[#145C54] px-3 text-xs font-semibold text-white transition hover:bg-[#0F4740] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/40 lg:min-h-9 lg:px-2.5">Cari</button>
                    </form>
                </div>
                <p class="stocktake-lock-notice px-4 py-2 text-xs text-[#5B6B79]">
                    @if ($isApplied)
                        Periode sudah dikunci pada {{ $opname->diterapkan_at?->translatedFormat('d M Y, H:i') }}.
                    @else
                        Simpan hitungan per barang untuk mengambil snapshot stok sistem saat itu. Catatan wajib diisi jika
                        ada selisih.
                    @endif
                </p>

                <form id="opname-form" method="POST" action="{{ route('unit-bisnis.admin-gu.stok-opname.draft') }}"
                    x-data="{
                        prepareSubmit(event) {
                            const itemId = event.submitter?.dataset.saveRowId;
                            if (!itemId) return;
                    
                            this.$el.querySelectorAll('[data-opname-row]').forEach((field) => {
                                field.disabled = field.dataset.opnameRow !== itemId;
                            });
                        }
                    }" @submit="prepareSubmit($event)">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $period }}">
                    <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                    <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                    <input type="hidden" name="page" value="{{ $items->currentPage() }}">

                    <div class="w-full">
                        <table class="stocktake-table block w-full table-fixed border-collapse text-left text-xs lg:table">
                            <colgroup class="hidden lg:table-column-group">
                                <col class="w-[5%]">
                                <col class="w-[10%]">
                                <col class="w-[18%]">
                                <col class="w-[11%]">
                                <col class="w-[21%]">
                                <col class="w-[13%]">
                                <col class="w-[8%]">
                                <col class="w-[14%]">
                            </colgroup>
                            <thead
                                class="hidden border-b border-[#E4E2DC] bg-[#F7F6F3] text-[10px] font-bold uppercase tracking-wider text-[#5B6B79] lg:table-header-group">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-center lg:px-2 lg:py-2">No.</th>
                                    <th scope="col" class="px-4 py-3 lg:px-2 lg:py-2">Kode Barang</th>
                                    <th scope="col" class="px-4 py-3 lg:px-2 lg:py-2">Nama barang &amp; satuan</th>
                                    <th scope="col" class="px-4 py-3 text-right lg:px-2 lg:py-2">Stok Sistem</th>
                                    <th scope="col" class="px-4 py-3 text-center lg:px-2 lg:py-2">Stok Fisik</th>
                                    <th scope="col" class="px-4 py-3 text-right lg:px-2 lg:py-2">Selisih &amp; Harga
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center lg:px-2 lg:py-2">Status</th>
                                    <th scope="col" class="px-4 py-3 lg:px-2 lg:py-2">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="block space-y-1 lg:table-row-group lg:space-y-0 lg:divide-y lg:divide-[#ECEFEB]">
                                @forelse($items as $index => $item)
                                    @php
                                        $barang = $item['barang'];
                                    @endphp
                                    <tr x-data="stocktakeRow(
                                        @js($item['qty_fisik'] === null ? '' : (string) $item['qty_fisik']),
                                        @js((float) $item['saldo_sistem']),
                                        @js((float) ($barang->harga_satuan ?? 0)),
                                        @js($barang->satuan?->nama ?? ''),
                                        @js($item['catatan']),
                                        @js($item['snapshot_fresh'])
                                    )"
                                        class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-1 rounded-xl border border-[#E4E2DC] bg-white p-2 transition-colors hover:bg-[#F8FAF8] lg:table-row lg:gap-y-2 lg:rounded-none lg:border-0 lg:bg-transparent lg:p-0">
                                        <td
                                            class="hidden px-4 py-3 text-center font-mono tabular-nums text-[#71807B] lg:table-cell lg:px-2 lg:py-2">
                                            {{ ($items->firstItem() ?? 1) + $index }}</td>
                                        <td class="col-span-2 flex min-w-0 items-center justify-between gap-3 px-0 py-0 font-mono text-[11px] text-[#52615D] lg:table-cell lg:truncate lg:px-2 lg:py-2 lg:font-semibold lg:text-[#34403C]"
                                            title="{{ $barang->kode }}">
                                            <span class="min-w-0 truncate">
                                                {{ $barang->kode }}
                                                @if ($barang->lokasi_rak)
                                                    <span class="text-[#71807B] lg:hidden"> ·
                                                        {{ $barang->lokasi_rak }}</span>
                                                @endif
                                            </span>
                                            <span :class="statusTextClass" class="shrink-0 font-semibold lg:hidden"
                                                x-text="{ kurang: 'Kurang', lebih: 'Lebih', sesuai: 'Sesuai', belum: 'Belum' }[status]">
                                                {{ match ($item['status']) {'kurang' => 'Kurang','lebih' => 'Lebih','sesuai' => 'Sesuai',default => 'Belum'} }}
                                            </span>
                                        </td>
                                        <td class="col-span-2 min-w-0 px-0 py-0 lg:table-cell lg:px-2 lg:py-2">
                                            <span class="block min-w-0 truncate font-semibold text-[#1F2523]"
                                                title="{{ $barang->nama }}">{{ $barang->nama }}</span>
                                            <span
                                                class="mt-0 block text-[11px] leading-3 text-[#71807B] lg:mt-0.5 lg:leading-normal">{{ $barang->satuan?->nama ?? '—' }}</span>
                                            <input type="hidden" name="items[{{ $index }}][barang_id]"
                                                value="{{ $barang->getKey() }}"
                                                data-opname-row="{{ $barang->getKey() }}">
                                            <input type="hidden" name="items[{{ $index }}][qty_fisik]"
                                                value="{{ $item['qty_fisik'] }}" x-model="quantity"
                                                data-opname-row="{{ $barang->getKey() }}">
                                            <input type="hidden" name="items[{{ $index }}][catatan]"
                                                value="{{ $item['catatan'] }}" x-model="note"
                                                data-opname-row="{{ $barang->getKey() }}">
                                        </td>
                                        <td
                                            class="col-span-1 px-0 py-0 text-left font-mono font-medium tabular-nums text-[#34403C] lg:table-cell lg:px-2 lg:py-2 lg:text-right">
                                            <span class="font-sans text-[11px] text-[#71807B] lg:hidden">Sis:</span>
                                            {{ $formatQty($item['saldo_sistem']) }} <span
                                                class="font-sans text-[#71807B]">{{ $barang->satuan?->nama }}</span>
                                            @if ($item['qty_sistem_at'])
                                                <span
                                                    class="mt-1 block whitespace-nowrap font-sans text-[10px] font-normal text-[#71807B]">
                                                    Patokan
                                                    {{ $item['qty_sistem_at']->locale('id')->translatedFormat('d M, H:i') }}
                                                </span>
                                            @elseif($item['needs_snapshot'])
                                                <span
                                                    class="mt-1 block whitespace-nowrap font-sans text-[10px] font-semibold text-amber-800">Hitungan
                                                    lama, simpan ulang</span>
                                            @else
                                                <span
                                                    class="mt-1 block whitespace-nowrap font-sans text-[10px] font-normal text-[#71807B]">Patokan
                                                    saat disimpan</span>
                                            @endif
                                        </td>
                                        <td class="col-start-2 row-start-3 px-0 py-0 lg:table-cell lg:px-2 lg:py-2">
                                            <div class="flex items-center justify-end gap-1 lg:justify-center lg:gap-1">
                                                <div
                                                    class="order-2 inline-flex items-center overflow-hidden rounded-lg border border-[#D7DDD9] bg-white lg:order-1">
                                                    <button type="button"
                                                        class="grid h-9 w-8 place-items-center text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#145C54]/30 disabled:cursor-not-allowed disabled:opacity-40 lg:h-8 lg:w-8"
                                                        aria-label="Kurangi jumlah fisik {{ $barang->nama }}"
                                                        :disabled="{{ $isApplied ? 'true' : 'false' }}"
                                                        @click="quantity = Math.max(0, (parseFloat(quantity) || 0) - 1).toString()">
                                                        <svg aria-hidden="true" class="h-4 w-4 lg:h-3.5 lg:w-3.5"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path d="M5 12h14" />
                                                        </svg>
                                                    </button>
                                                    <input x-model="quantity" type="number" min="0"
                                                        step="0.001" inputmode="decimal"
                                                        value="{{ $item['qty_fisik'] }}" placeholder="—"
                                                        @disabled($isApplied)
                                                        aria-label="Stok fisik {{ $barang->nama }}"
                                                        class="h-9 w-14 border-x border-[#D7DDD9] bg-white px-1 text-center font-mono text-xs font-semibold tabular-nums text-[#1F2523] outline-none focus:bg-[#F7FBF8] focus:ring-2 focus:ring-inset focus:ring-[#145C54]/30 disabled:bg-[#F4F5F4] lg:h-8 lg:w-16">
                                                    <button type="button"
                                                        class="grid h-9 w-8 place-items-center text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#145C54]/30 disabled:cursor-not-allowed disabled:opacity-40 lg:h-8 lg:w-8"
                                                        aria-label="Tambah jumlah fisik {{ $barang->nama }}"
                                                        :disabled="{{ $isApplied ? 'true' : 'false' }}"
                                                        @click="quantity = ((parseFloat(quantity) || 0) + 1).toString()">
                                                        <svg aria-hidden="true" class="h-4 w-4 lg:h-3.5 lg:w-3.5"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path d="M12 5v14M5 12h14" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <button type="submit" name="snapshot_barang_id"
                                                    value="{{ $barang->getKey() }}"
                                                    aria-label="Simpan hitungan fisik {{ $barang->nama }}"
                                                    title="Simpan hitungan ini"
                                                    data-save-row-id="{{ $barang->getKey() }}"
                                                    @disabled($isApplied)
                                                    :class="isSaved ? 'border-[#145C54] bg-[#145C54] text-white' :
                                                        'border-[#D7DDD9] bg-white text-[#52615D] hover:bg-[#F2F5F3]'"
                                                    class="order-first inline-flex h-9 min-w-[72px] shrink-0 items-center justify-center gap-1 rounded-lg border px-2 text-[11px] font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 disabled:cursor-not-allowed disabled:opacity-50 lg:order-3 lg:grid lg:h-8 lg:w-8 lg:min-w-0 lg:px-0">
                                                    <svg aria-hidden="true" class="h-4 w-4 shrink-0 lg:h-3.5 lg:w-3.5"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2.2">
                                                        <path d="m5 12 4 4L19 6" />
                                                    </svg>
                                                    <span class="lg:hidden"
                                                        x-text="{ kurang: 'Kurang', lebih: 'Lebih', sesuai: 'Sesuai', belum: 'Simpan' }[status]">
                                                        {{ match ($item['status']) {'kurang' => 'Kurang','lebih' => 'Lebih','sesuai' => 'Sesuai',default => 'Simpan'} }}
                                                    </span>
                                                </button>
                                                <button type="button"
                                                    class="order-3 grid h-9 w-8 shrink-0 place-items-center rounded-lg border border-[#D7DDD9] bg-white text-[#7890A8] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 lg:hidden"
                                                    aria-label="Buka catatan {{ $barang->nama }}"
                                                    :aria-expanded="showNote.toString()" @click="showNote = !showNote">
                                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="1.7">
                                                        <path
                                                            d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="hidden px-4 py-3 text-right font-mono tabular-nums lg:table-cell lg:px-2 lg:py-2"
                                            :class="differenceClass">
                                            <span
                                                x-text="difference === null ? '—' : (difference > 0 ? '+' : '') + formatQuantity(difference) + (unit ? ' ' + unit : '')">
                                                @if ($item['qty_selisih'] === null)
                                                    —
                                                @else
                                                    {{ $item['qty_selisih'] > 0 ? '+' : '' }}{{ $formatQty($item['qty_selisih']) }}
                                                    {{ $barang->satuan?->nama }}
                                                @endif
                                            </span>
                                            <span x-show="difference !== null && difference !== 0"
                                                class="mt-0.5 block font-sans text-[10px]"
                                                x-text="'(' + formatMoney(difference * unitPrice) + ')'">
                                                @if ($item['qty_selisih'] !== null && $item['qty_selisih'] !== 0.0)
                                                    ({{ $formatMoney((float) $item['nilai_selisih']) }})
                                                @endif
                                            </span>
                                        </td>
                                        <td class="hidden px-4 py-3 text-center lg:table-cell lg:px-2 lg:py-2">
                                            <span :class="statusBadgeClass"
                                                class="inline-flex rounded px-2 py-1 text-[10px] font-bold"
                                                x-text="{ kurang: 'Kurang', lebih: 'Lebih', sesuai: 'Sesuai', belum: 'Belum' }[status]">
                                                {{ match ($item['status']) {'kurang' => 'Kurang','lebih' => 'Lebih','sesuai' => 'Sesuai',default => 'Belum'} }}
                                            </span>
                                        </td>
                                        <td x-show="showNote" x-cloak class="col-span-2 min-w-0 lg:hidden">
                                            <input type="text" x-model="note" @disabled($isApplied)
                                                placeholder="Catatan selisih..."
                                                aria-label="Catatan selisih {{ $barang->nama }}"
                                                class="min-h-10 w-full min-w-0 rounded-lg border border-[#D7DDD9] bg-white px-2.5 text-xs text-[#1F2523] outline-none transition placeholder:text-[#87928E] focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15 disabled:bg-[#F4F5F4]">
                                        </td>
                                        <td class="hidden px-4 py-3 lg:table-cell lg:px-2 lg:py-2">
                                            <input type="text" x-model="note" @disabled($isApplied)
                                                placeholder="Catatan..." aria-label="Catatan selisih {{ $barang->nama }}"
                                                class="min-h-10 w-full min-w-0 rounded-lg border border-[#D7DDD9] bg-white px-2.5 text-xs text-[#1F2523] outline-none transition placeholder:text-[#87928E] focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15 disabled:bg-[#F4F5F4] lg:min-h-8 lg:px-2">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            class="col-span-2 px-4 py-12 text-center text-sm text-[#71807B]">
                                            @if ($filters['q'] !== '')
                                                Tidak ada barang yang cocok dengan pencarian.
                                            @else
                                                Belum ada barang aktif pada gudang ini.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

                <div
                    class="stocktake-pagination flex flex-col gap-3 border-t border-[#E4E2DC] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <form method="GET" action="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}"
                            class="flex items-center gap-2">
                            <input type="hidden" name="periode" value="{{ $period }}">
                            <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                            <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            <input type="hidden" name="q" value="{{ $filters['q'] }}">
                            <label for="stok-opname-per-page" class="text-xs font-medium text-[#52615D]">Tampil:</label>
                            <select id="stok-opname-per-page" name="per_page"
                                class="min-h-9 rounded-lg border border-[#D7DDD9] bg-white px-2.5 text-xs font-semibold text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15"
                                onchange="this.form.requestSubmit()">
                                @foreach ($pageSizes as $pageSize)
                                    <option value="{{ $pageSize }}" @selected($filters['per_page'] === $pageSize)>{{ $pageSize }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <span class="text-xs font-mono text-[#52615D]">
                            {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} /
                            {{ number_format($items->total()) }} SKU
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-2 sm:justify-end">
                        <div class="flex items-center gap-1">
                            <a href="{{ $items->url(1) }}" aria-label="Halaman pertama"
                                @if ($items->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                                class="grid h-8 w-8 place-items-center rounded-lg border border-[#D7DDD9] text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 {{ $items->onFirstPage() ? 'pointer-events-none opacity-40' : '' }}">
                                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="m18 18-6-6 6-6M8 6v12" />
                                </svg>
                            </a>
                            <a href="{{ $items->previousPageUrl() ?? '#' }}" aria-label="Halaman sebelumnya"
                                @if ($items->onFirstPage()) aria-disabled="true" tabindex="-1" @endif
                                class="grid h-8 w-8 place-items-center rounded-lg border border-[#D7DDD9] text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 {{ $items->onFirstPage() ? 'pointer-events-none opacity-40' : '' }}">
                                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                            </a>
                        </div>

                        <form method="GET" action="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}"
                            class="flex items-center gap-2">
                            <input type="hidden" name="periode" value="{{ $period }}">
                            <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                            <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            <input type="hidden" name="q" value="{{ $filters['q'] }}">
                            <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                            <label for="stok-opname-page" class="sr-only">Pilih halaman</label>
                            <select id="stok-opname-page" name="page"
                                class="min-h-9 rounded-lg border border-[#D7DDD9] bg-white px-2.5 text-xs font-semibold text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15"
                                onchange="this.form.requestSubmit()">
                                @for ($page = 1; $page <= $items->lastPage(); $page++)
                                    <option value="{{ $page }}" @selected($items->currentPage() === $page)>Hal
                                        {{ $page }}</option>
                                @endfor
                            </select>
                            <span class="text-xs text-[#52615D]">/ {{ $items->lastPage() }}</span>
                        </form>

                        <div class="flex items-center gap-1">
                            <a href="{{ $items->nextPageUrl() ?? '#' }}" aria-label="Halaman berikutnya"
                                @if (!$items->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                                class="grid h-8 w-8 place-items-center rounded-lg border border-[#D7DDD9] text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 {{ !$items->hasMorePages() ? 'pointer-events-none opacity-40' : '' }}">
                                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </a>
                            <a href="{{ $items->url($items->lastPage()) }}" aria-label="Halaman terakhir"
                                @if (!$items->hasMorePages()) aria-disabled="true" tabindex="-1" @endif
                                class="grid h-8 w-8 place-items-center rounded-lg border border-[#D7DDD9] text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 {{ !$items->hasMorePages() ? 'pointer-events-none opacity-40' : '' }}">
                                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="m6 18 6-6-6-6m10 12V6" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="stocktake-note flex items-start gap-3 rounded-xl border border-[#145C54]/20 bg-[#E8F1EF]/70 p-4">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#145C54]/10 text-[#145C54]">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8">
                        <path d="M12 3v18M5 8h14M5 16h14" />
                        <path d="m8 5-3 3 3 3m8 2 3 3-3 3" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-xs font-bold text-[#1F2523]">Dampak ke saldo dan kartu stok</h2>
                    <p class="mt-1 text-xs leading-relaxed text-[#52615D]">
                        Draf tidak mengubah saldo. Setiap barang memakai stok sistem saat hitungannya disimpan; pergerakan
                        setelahnya tetap tercatat sebagai mutasi terpisah.
                    </p>
                </div>
            </aside>

            <section
                class="stocktake-actions sticky bottom-3 z-20 flex flex-col gap-3 rounded-xl border border-[#D7DDD9] bg-white/95 p-3.5 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between"
                aria-label="Aksi stok opname">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#E8F1EF] text-[#145C54]">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <path d="M9 11 12 14 22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-[#1F2523]">Progress hitung:
                            {{ number_format($metrics['dihitung']) }} dari {{ number_format($metrics['total']) }} SKU</p>
                        <p class="mt-0.5 text-[11px] {{ $metrics['belum'] > 0 ? 'text-amber-800' : 'text-[#145C54]' }}">
                            @if ($isApplied)
                                Sudah diterapkan pada {{ $opname->diterapkan_at?->translatedFormat('d M Y, H:i') }}
                            @elseif($metrics['belum'] > 0)
                                {{ number_format($metrics['belum']) }} SKU belum memiliki hitungan tersimpan dengan
                                snapshot yang valid.
                            @else
                                Semua SKU telah dihitung. Pastikan catatan selisih sudah diisi.
                            @endif
                        </p>
                    </div>
                </div>

                @unless ($isApplied)
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <form method="POST" action="{{ route('unit-bisnis.admin-gu.stok-opname.reset') }}"
                            onsubmit="return confirm('Kosongkan seluruh hitungan draf periode ini?')">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $period }}">
                            <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                            <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            <input type="hidden" name="q" value="{{ $filters['q'] }}">
                            <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                            <input type="hidden" name="page" value="{{ $items->currentPage() }}">
                            <button type="submit"
                                class="min-h-11 rounded-lg border border-[#D7DDD9] px-3.5 text-xs font-semibold text-[#52615D] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 lg:min-h-9 lg:px-3">Reset</button>
                        </form>
                        <button form="opname-form" type="submit"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-[#D7DDD9] bg-white px-4 text-xs font-semibold text-[#1F2523] transition hover:bg-[#F2F5F3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/30 lg:min-h-9 lg:px-3">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="M5 3h12l4 4v14H3V3z" />
                                <path d="M7 3v6h10V3M7 21v-7h10v7" />
                            </svg>
                            Simpan draf
                        </button>
                        <button form="opname-form" formaction="{{ route('unit-bisnis.admin-gu.stok-opname.apply') }}"
                            type="submit" @disabled(!$complete)
                            onclick="return confirm('Terapkan penyesuaian stok? Setelah berhasil, opname periode ini akan dikunci.')"
                            class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#145C54] px-4 text-xs font-bold text-white transition hover:bg-[#0F4740] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#145C54]/40 active:scale-[.98] disabled:cursor-not-allowed disabled:bg-[#9AA9A4] lg:min-h-9 lg:px-3">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="m5 12 4 4L19 6" />
                                <path d="M21 12a9 9 0 1 1-5.3-8.2" />
                            </svg>
                            Terapkan penyesuaian
                        </button>
                    </div>
                @endunless
            </section>
        @endif
    </div>
    <style>
        .stocktake-dashboard {
            --stocktake-helper: 12px;
            min-width: 0;
            color: var(--text-primary);
        }

        .stocktake-page-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
        }

        .stocktake-page-description {
            color: var(--text-muted);
            font-size: var(--stocktake-helper);
            line-height: 1.4;
        }

        .stocktake-period-badge,
        .stocktake-state-badge {
            border-radius: var(--radius);
            font-size: var(--stocktake-helper);
            font-weight: 800;
        }

        .stocktake-state-badge.is-applied {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .stocktake-state-badge.is-draft {
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
        }

        .stocktake-control,
        .stocktake-print-action {
            min-height: 38px;
            border-color: var(--surface-border);
            border-radius: var(--radius);
            font-size: var(--stocktake-helper);
        }

        .stocktake-control:focus-visible,
        .stocktake-print-action:focus-visible,
        .stocktake-search-form :focus-visible,
        .stocktake-status-tab:focus-visible,
        .stocktake-pagination a:focus-visible,
        .stocktake-pagination select:focus-visible,
        .stocktake-actions button:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .stocktake-print-action {
            background: var(--surface-card);
            color: var(--text-primary);
            font-weight: 700;
        }

        .stocktake-print-action:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .stocktake-print-action.is-disabled {
            background: var(--surface-base);
            color: var(--text-muted);
        }

        .stocktake-feedback {
            border-radius: var(--radius-lg);
        }

        .stocktake-kpis {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 16px;
        }

        .stocktake-kpi {
            --kpi-accent: var(--brand-primary);
            position: relative;
            min-width: 0;
            min-height: 108px;
            gap: 4px;
            overflow: hidden;
            padding: 12px 14px 10px;
            border: 1px solid var(--surface-border);
            border-top: 3px solid var(--kpi-accent);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
        }

        .stocktake-kpi:nth-child(1) {
            --kpi-accent: var(--status-neutral);
        }

        .stocktake-kpi:nth-child(2) {
            --kpi-accent: var(--status-success);
        }

        .stocktake-kpi:nth-child(3) {
            --kpi-accent: var(--status-critical);
        }

        .stocktake-kpi:nth-child(4) {
            --kpi-accent: var(--brand-primary);
        }

        .stocktake-kpi:nth-child(5) {
            --kpi-accent: var(--brand-accent);
        }

        .stocktake-kpi-heading {
            padding-right: 26px;
            color: var(--text-muted);
            font-size: var(--stocktake-helper);
            font-weight: 800;
            letter-spacing: .055em;
            line-height: 1.2;
        }

        .stocktake-kpi-icon {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 24px;
            height: 24px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--kpi-accent);
        }

        .stocktake-kpi-icon svg {
            width: 14px;
            height: 14px;
        }

        .stocktake-kpi-value {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 850;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
        }

        .stocktake-kpi-foot {
            width: fit-content;
            max-width: 100%;
            padding: 3px 5px;
            border-radius: 3px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--stocktake-helper);
            font-weight: 700;
            line-height: 1.2;
        }

        .stocktake-kpi:nth-child(2) .stocktake-kpi-foot {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .stocktake-kpi:nth-child(3) .stocktake-kpi-foot {
            background: rgba(193, 68, 60, .12);
            color: var(--status-critical);
        }

        .stocktake-kpi:nth-child(4) .stocktake-kpi-foot {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .stocktake-panel {
            border-color: var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .stocktake-status-filters {
            min-width: 0;
            flex: 1 1 auto;
            flex-wrap: nowrap;
            gap: 3px;
        }

        .stocktake-status-tab {
            min-height: 36px;
            flex: 0 0 auto;
            padding: 0 8px;
            border: 1px solid transparent;
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--stocktake-helper);
            white-space: nowrap;
        }

        .stocktake-status-tab:hover {
            border-color: var(--surface-border);
            color: var(--brand-primary);
        }

        .stocktake-status-tab[aria-current="page"] {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .stocktake-lock-notice {
            margin: 0;
            color: var(--text-muted);
            font-size: var(--stocktake-helper);
            line-height: 1.4;
        }

        .stocktake-filter-bar {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 14px;
            border-color: var(--surface-border);
        }

        .stocktake-search-form {
            min-width: 220px;
            flex: 0 1 320px;
            align-items: center;
            gap: 8px;
        }

        .stocktake-search {
            width: auto;
            min-width: 0;
            flex: 1 1 auto;
            height: 38px;
            padding-left: 36px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
        }

        .stocktake-search svg {
            top: 11px;
            left: 10px;
            width: 16px;
            height: 16px;
            color: var(--text-muted);
        }

        .stocktake-search-input {
            height: 100%;
            min-height: 0;
            padding: 0 10px 0 0;
            border: 0;
            background: transparent;
            color: var(--text-primary);
            font-size: var(--stocktake-helper);
        }

        .stocktake-search-input::placeholder {
            color: var(--text-subtle);
        }

        .stocktake-search-input:focus {
            border: 0;
            outline: 0;
            box-shadow: none;
        }

        .stocktake-search-submit {
            min-height: 38px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--brand-primary);
            font-size: var(--stocktake-helper);
            font-weight: 800;
        }

        .stocktake-search-submit:hover {
            border-color: var(--brand-primary);
            background: var(--surface-base);
        }

        .stocktake-table {
            border-collapse: collapse;
            text-align: left;
        }

        .stocktake-table thead {
            background: var(--surface-base);
            color: var(--text-muted);
        }

        .stocktake-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--surface-border);
            font-size: var(--stocktake-helper);
            font-weight: 800;
            letter-spacing: .035em;
        }

        .stocktake-table td {
            border-bottom: 1px solid #edf2f0;
            font-size: var(--stocktake-helper);
        }

        .stocktake-pagination {
            min-height: 46px;
            padding: 8px 12px;
            border-color: var(--surface-border);
            color: var(--text-muted);
        }

        .stocktake-pagination a,
        .stocktake-pagination select {
            border-radius: var(--radius);
            font-size: var(--stocktake-helper);
        }

        .stocktake-note {
            border-color: var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
        }

        .stocktake-actions {
            border-color: var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 2px 8px rgba(31, 37, 35, .08);
        }

        @media (max-width: 1200px) {
            .stocktake-kpis {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .stocktake-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .stocktake-kpi {
                min-height: 100px;
                padding: 10px;
            }

            .stocktake-kpi-value {
                font-size: 22px;
            }

            .stocktake-filter-bar {
                flex-wrap: wrap;
            }

            .stocktake-status-filters {
                width: 100%;
                flex: 1 1 100%;
                flex-wrap: wrap;
            }

            .stocktake-search-form {
                width: 100%;
                min-width: 0;
                flex: 1 1 100%;
            }
        }

        @media (max-width: 600px) {

            .stocktake-page-controls,
            .stocktake-period-form {
                width: 100%;
            }

            .stocktake-control {
                min-height: 44px;
            }

            .stocktake-print-action,
            .stocktake-status-tab {
                min-height: 44px;
            }

            .stocktake-page-header {
                align-items: stretch;
            }

            .stocktake-status-filters {
                width: 100%;
                flex-wrap: nowrap;
                justify-content: flex-start;
                overflow-x: auto;
            }

            .stocktake-filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .stocktake-search-form {
                width: 100%;
            }

            .stocktake-search {
                width: 100%;
            }

            .stocktake-search-submit {
                min-height: 44px;
            }

            .stocktake-table button {
                min-width: 44px;
                min-height: 44px;
            }

            .stocktake-table input:not([type="hidden"]) {
                min-height: 44px;
            }

            .stocktake-pagination a {
                min-width: 44px;
                height: 44px;
            }

            .stocktake-pagination select {
                min-height: 44px;
            }

            .stocktake-actions button {
                min-height: 44px;
            }

            .stocktake-actions {
                bottom: 8px;
            }
        }
    </style>
@endsection
