@extends('layouts.app')

@section('title', 'Stok & Import Data Awal Gudang Store — SIGMA Armada')

@push('scripts')
    {{-- SheetJS: browser-side Excel/CSV parser for workbook preview --}}
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
@endpush

@push('styles')
    <style>
        .store-stock-page {
            --store-stock-helper: 12px;
            --store-stock-body: 14px;
            --store-stock-section: 18px;
            min-width: 0;
        }

        .store-stock-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 16px;
        }

        .store-stock-eyebrow {
            margin: 0 0 4px;
            color: var(--brand-primary);
            font-size: var(--store-stock-helper);
            font-weight: 800;
            letter-spacing: .07em;
        }

        .store-stock-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
        }

        .store-stock-description {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: var(--store-stock-body);
        }

        .store-stock-actions,
        .store-stock-tabs,
        .store-stock-toolbar,
        .store-stock-filters {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .store-stock-actions {
            flex: 0 0 auto;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .store-stock-actions .btn,
        .store-stock-tabs .btn {
            min-height: 40px;
        }

        .store-stock-tabs {
            gap: 4px;
            overflow-x: auto;
            border-bottom: 1px solid var(--surface-border);
            margin-bottom: 16px;
            scrollbar-width: thin;
        }

        .store-stock-tabs .btn {
            flex: 0 0 auto;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .store-stock-toolbar {
            align-items: stretch;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .store-stock-toolbar .search-box {
            min-width: min(100%, 280px);
        }

        .store-stock-filters {
            flex-wrap: wrap;
        }

        .store-stock-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .store-stock-panel-header {
            display: flex;
            min-height: 68px;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .store-stock-panel-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: var(--store-stock-section);
            font-weight: 800;
            line-height: 1.25;
        }

        .store-stock-panel-header p,
        .store-stock-result-count {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: var(--store-stock-helper);
        }

        .store-stock-panel .data-table th {
            font-size: var(--store-stock-helper);
            font-weight: 800;
            letter-spacing: .035em;
        }

        .store-stock-panel .data-table td {
            font-size: var(--store-stock-body);
        }

        .store-stock-panel .data-table th:last-child,
        .store-stock-panel .data-table td:last-child {
            text-align: right;
        }

        .store-stock-code,
        .store-stock-amount {
            font-variant-numeric: tabular-nums;
        }

        .store-stock-code {
            color: var(--brand-primary);
            font-weight: 800;
        }

        .store-stock-match {
            padding: 0 1px;
            border-radius: 3px;
            color: inherit;
            background: rgba(201, 122, 43, .2);
            font-weight: 800;
        }

        .store-stock-amount {
            font-weight: 700;
            white-space: nowrap;
        }

        .store-stock-current {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 800;
        }

        .store-stock-unit {
            color: var(--text-muted);
            font-size: var(--store-stock-helper);
        }

        .store-stock-pagination {
            padding: 12px 16px;
            border-top: 1px solid var(--surface-border);
        }

        .store-stock-message {
            min-height: 20px;
            margin: 4px 2px 8px;
            color: var(--text-muted);
            font-size: var(--store-stock-helper);
        }

        .store-stock-message [role="alert"] {
            color: var(--status-critical);
        }

        .store-stock-modal-backdrop {
            position: fixed;
            z-index: 2000;
            inset: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: rgba(18, 29, 27, .58);
        }

        .store-stock-modal {
            display: flex;
            width: min(1120px, 100%);
            max-height: min(86vh, 900px);
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        }

        .store-stock-modal:focus {
            outline: none;
        }

        .store-stock-modal-header,
        .store-stock-modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
        }

        .store-stock-modal-header {
            border-bottom: 1px solid var(--surface-border);
        }

        .store-stock-modal-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 800;
            line-height: 1.25;
        }

        .store-stock-modal-header .store-stock-description {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            font-size: var(--store-stock-helper);
        }

        .store-stock-modal-close {
            flex: 0 0 40px;
            width: 40px;
            padding: 0;
            font-size: 24px;
        }

        .store-stock-modal-content {
            min-height: 160px;
            overflow: auto;
        }

        .store-stock-modal-table {
            min-width: 980px;
        }

        .store-stock-modal-table th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .store-stock-modal-table td {
            font-size: var(--store-stock-body);
        }

        .store-stock-ref,
        .store-stock-balance,
        .store-stock-in,
        .store-stock-out {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .store-stock-ref,
        .store-stock-balance {
            color: var(--brand-primary);
            font-weight: 800;
        }

        .store-stock-in {
            color: var(--status-success);
            font-weight: 700;
        }

        .store-stock-out {
            color: var(--status-critical);
            font-weight: 700;
        }

        .store-stock-modal-feedback {
            padding: 40px 20px;
            color: var(--text-muted);
            text-align: center;
        }

        .store-stock-modal-error {
            color: var(--status-critical);
        }

        .store-stock-modal-footer {
            border-top: 1px solid var(--surface-border);
            color: var(--text-muted);
            font-size: var(--store-stock-helper);
        }

        .store-stock-actions .btn:focus-visible,
        .store-stock-tabs .btn:focus-visible,
        .store-stock-panel a:focus-visible,
        .store-stock-panel button:focus-visible,
        .store-stock-modal button:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        @media (max-width: 720px) {
            .store-stock-modal-backdrop {
                align-items: end;
                padding: 0;
            }

            .store-stock-modal {
                width: 100%;
                max-height: 92vh;
                border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            }

            .store-stock-modal-header,
            .store-stock-modal-footer {
                padding: 12px;
            }

            .store-stock-modal-header h2 {
                font-size: 18px;
            }

            .store-stock-modal-footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .store-stock-modal-footer .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media print {
            .store-stock-page> :not(.store-stock-modal-backdrop) {
                display: none !important;
            }

            .store-stock-modal-backdrop {
                position: static;
                display: block !important;
                padding: 0;
                background: #fff;
            }

            .store-stock-modal {
                width: 100%;
                max-height: none;
                overflow: visible;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .store-stock-modal-header,
            .store-stock-modal-footer {
                padding: 8px 0;
            }

            .store-stock-modal-close,
            .store-stock-modal-footer .btn {
                display: none !important;
            }

            .store-stock-modal-content,
            .store-stock-modal-content .table-responsive {
                overflow: visible;
            }

            .store-stock-modal-table {
                min-width: 0;
                font-size: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .store-stock-modal-backdrop *,
            .store-stock-modal-backdrop *::before,
            .store-stock-modal-backdrop *::after {
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }

        @media (max-width: 720px) {
            .store-stock-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .store-stock-header h1 {
                font-size: 22px;
            }

            .store-stock-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .store-stock-actions .btn {
                flex: 1 1 auto;
                justify-content: center;
            }

            .store-stock-toolbar,
            .store-stock-filters {
                width: 100%;
            }

            .store-stock-toolbar .search-box {
                width: 100%;
            }

            .store-stock-filters .select-filter {
                flex: 1 1 160px;
                min-width: 0;
            }

            .store-stock-panel-header {
                align-items: flex-start;
                flex-wrap: wrap;
                padding: 12px;
            }

            .store-stock-panel .data-table {
                min-width: 820px;
            }

            .store-stock-pagination {
                padding: 10px 12px;
            }
        }
    </style>
@endpush

@php
    $canReadStoreStok =
        auth()->user()?->hasRole('Super Admin') ||
        auth()->user()?->can('store_stok.read') ||
        auth()->user()?->can('store.read') ||
        auth()->user()?->can('store.view');
    $canImportStoreStok =
        auth()->user()?->hasRole('Super Admin') ||
        auth()->user()?->can('store_stok.create') ||
        auth()->user()?->can('stok_awal.import');
@endphp

@section('content')
    <div class="store-stock-page" x-data="{
        currentTab: '{{ !$canReadStoreStok && $canImportStoreStok ? 'import_data_awal' : $activeTab ?? 'stok_saat_ini' }}',
        stockSearchLoading: false,
        stockSearchMessage: '',
        stockSearchController: null,
        stockHistoryOpen: false,
        stockHistoryLoading: false,
        stockHistoryError: '',
        stockHistoryBarang: null,
        stockHistoryMutations: [],
        stockHistoryUrl: '',
        stockHistoryController: null,
        stockHistoryReturnFocus: null,
        async openStockHistory(url) {
            this.stockHistoryController?.abort();
            const controller = new AbortController();
            this.stockHistoryController = controller;
            this.stockHistoryOpen = true;
            this.stockHistoryUrl = url;
            this.stockHistoryLoading = true;
            this.stockHistoryError = '';
            this.stockHistoryBarang = null;
            this.stockHistoryMutations = [];
            this.stockHistoryUrl = '';
    
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('Riwayat kartu stok gagal dimuat.');
                const result = await response.json();
                this.stockHistoryBarang = result.barang;
                this.stockHistoryMutations = result.mutasi;
                this.$nextTick(() => this.$refs.stockHistoryClose?.focus());
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.stockHistoryError = error.message || 'Terjadi kesalahan saat memuat kartu stok.';
                }
            } finally {
                if (this.stockHistoryController === controller) {
                    this.stockHistoryLoading = false;
                    this.stockHistoryController = null;
                }
            }
        },
        closeStockHistory() {
            this.stockHistoryController?.abort();
            this.stockHistoryController = null;
            this.stockHistoryOpen = false;
            this.stockHistoryLoading = false;
            this.stockHistoryError = '';
            this.stockHistoryBarang = null;
            this.stockHistoryMutations = [];
            this.$nextTick(() => this.stockHistoryReturnFocus?.focus());
        },
        trapStockHistoryFocus(event) {
            const focusable = this.$refs.stockHistoryDialog?.querySelectorAll(
                'button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex=&quot;-1&quot;])'
            );
            if (!focusable?.length) return;
    
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
        formatStockHistoryDate(value) {
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ?
                value :
                new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
        },
        async searchStoreStock(searchTerm) {
            const normalizedSearch = searchTerm.trim();
            this.stockSearchController?.abort();
            if (normalizedSearch.length > 0 && normalizedSearch.length < 3) {
                this.stockSearchMessage = 'Masukkan minimal 3 karakter untuk mencari.';
                return;
            }
    
            const controller = new AbortController();
            this.stockSearchController = controller;
            const searchForm = this.$refs.stockSearchForm;
            const filterForm = this.$refs.stockFilterForm;
            const url = new URL(searchForm.action);
    
            for (const [key, value] of new FormData(filterForm).entries()) {
                if (typeof value === 'string' && value.trim() !== '') {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            }
            url.searchParams.set('tab', 'stok_saat_ini');
            if (normalizedSearch) url.searchParams.set('search', normalizedSearch);
            else url.searchParams.delete('search');
            url.searchParams.delete('page');
    
            this.stockSearchLoading = true;
            this.stockSearchMessage = '';
            try {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
                if (!response.ok) throw new Error('Pencarian stok gagal dimuat.');
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                const source = doc.querySelector('#store-stock-results');
                const target = document.querySelector('#store-stock-results');
                if (!source || !target) throw new Error('Hasil pencarian stok tidak ditemukan.');
                target.innerHTML = source.innerHTML;
                window.history.replaceState({}, '', url);
                if (window.Alpine) window.Alpine.initTree(target);
            } catch (error) {
                if (error.name !== 'AbortError') this.stockSearchMessage = error.message || 'Terjadi kesalahan saat mencari stok.';
            } finally {
                if (this.stockSearchController === controller) {
                    this.stockSearchLoading = false;
                    this.stockSearchController = null;
                }
            }
        }
    }">

        <header class="store-stock-header">
            <div>
                <p class="store-stock-eyebrow">INVENTARIS GUDANG</p>
                <h1>Stok Gudang Store</h1>
                <p class="store-stock-description">Pantau saldo persediaan, riwayat mutasi, dan data awal gudang.</p>
            </div>
            <div class="store-stock-actions">
                <a href="{{ route('store.laporan.index') }}" class="btn btn-outline">
                    <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="icon-inline">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Export Laporan
                </a>
            </div>
        </header>

        {{-- Tab Navigation --}}
        <div class="store-stock-tabs" role="tablist" aria-label="Bagian stok gudang">
            @if ($canReadStoreStok)
                <button id="store-stock-tab-current" type="button" class="btn" role="tab"
                    aria-controls="store-stock-panel-current" :aria-selected="currentTab === 'stok_saat_ini'"
                    :class="currentTab === 'stok_saat_ini' ? 'btn-primary' : 'btn-outline'"
                    @click="currentTab='stok_saat_ini'">
                    <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="icon-inline">
                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                    </svg>
                    Stok Real-Time
                </button>
            @endif
            @if ($canImportStoreStok)
                <button id="store-stock-tab-import" type="button" class="btn" role="tab"
                    aria-controls="store-stock-panel-import" :aria-selected="currentTab === 'import_data_awal'"
                    :class="currentTab === 'import_data_awal' ? 'btn-primary' : 'btn-outline'"
                    @click="currentTab='import_data_awal'">
                    <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="icon-inline">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    Import Data Awal
                </button>
            @endif
        </div>

        {{-- ═══════════════════════════════════════════ TAB 1: STOK REAL-TIME ═══ --}}
        <div id="store-stock-panel-current" role="tabpanel" aria-labelledby="store-stock-tab-current"
            x-show="currentTab === 'stok_saat_ini'">
            <div class="store-stock-toolbar">
                <form x-ref="stockSearchForm" action="{{ route('store.stok.index') }}" method="GET" class="search-box">
                    <input type="hidden" name="tab" value="stok_saat_ini">
                    <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="search-icon icon-inline">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="search" name="search" value="{{ $search }}" class="search-input"
                        aria-label="Cari stok barang" placeholder="Cari nama atau kode (min. 3 karakter)..."
                        autocomplete="off" @input.debounce.500ms="searchStoreStock($event.target.value)"
                        @keydown.enter.prevent="searchStoreStock($event.target.value)">
                </form>
                @error('search')
                    <p class="form-error" role="alert">{{ $message }}</p>
                @enderror
                @error('q')
                    <p class="form-error" role="alert">{{ $message }}</p>
                @enderror
                <form x-ref="stockFilterForm" action="{{ route('store.stok.index') }}" method="GET"
                    class="store-stock-filters">
                    <input type="hidden" name="tab" value="stok_saat_ini">
                    @if ($search)
                        <input type="hidden" name="search" value="{{ $search }}">
                    @endif
                    <select name="kategori_id" class="select-filter" aria-label="Filter kategori barang"
                        onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        @foreach ($kategoriList as $kat)
                            <option value="{{ $kat->id }}"
                                {{ (string) $kategoriId === (string) $kat->id ? 'selected' : '' }}>
                                {{ $kat->nama }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="select-filter" aria-label="Filter kondisi stok"
                        onchange="this.form.submit()">
                        <option value="">Semua Kondisi</option>
                        <option value="aman" {{ $statusFilter === 'aman' ? 'selected' : '' }}>Stok Aman</option>
                        <option value="warning" {{ $statusFilter === 'warning' ? 'selected' : '' }}>Mendekati Minimum
                        </option>
                        <option value="kritis" {{ $statusFilter === 'kritis' ? 'selected' : '' }}>Stok Habis / Kritis
                        </option>
                    </select>
                </form>
            </div>

            <div class="store-stock-message" aria-live="polite">
                <span x-show="stockSearchLoading" role="status">Mencari stok...</span>
                <span x-show="stockSearchMessage" x-text="stockSearchMessage" role="alert"></span>
            </div>

            <div id="store-stock-results">
                <section class="store-stock-panel" aria-labelledby="store-stock-results-title">
                    <header class="store-stock-panel-header">
                        <div>
                            <h2 id="store-stock-results-title">Persediaan Saat Ini</h2>
                            <p>Saldo aktual dan kondisi stok untuk gudang yang sedang dipilih.</p>
                        </div>
                        <span class="store-stock-result-count">{{ number_format($barangs->total()) }} barang</span>
                    </header>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Total Masuk</th>
                                    <th>Total Keluar</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Kondisi</th>
                                    <th style="text-align:right;">Mutasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $highlightStockSearch = static function (string $value) use (
                                        $search,
                                    ): \Illuminate\Support\HtmlString {
                                        if (mb_strlen($search) < 3) {
                                            return new \Illuminate\Support\HtmlString(e($value));
                                        }

                                        $parts = preg_split(
                                            '/(' . preg_quote($search, '/') . ')/iu',
                                            $value,
                                            -1,
                                            PREG_SPLIT_DELIM_CAPTURE,
                                        );
                                        $html = '';

                                        foreach ($parts ?: [$value] as $index => $part) {
                                            $escapedPart = e($part);
                                            $html .=
                                                $index % 2 === 1
                                                    ? '<mark class="store-stock-match">' . $escapedPart . '</mark>'
                                                    : $escapedPart;
                                        }

                                        return new \Illuminate\Support\HtmlString($html);
                                    };
                                @endphp
                                @forelse($barangs as $item)
                                    @php
                                        $brg = $item['model'];
                                        $stokAktual = $item['stok_aktual'];
                                        $badge = $item['badge'];
                                        $statusText = $item['status_text'];
                                        $totalMasuk = $item['total_masuk'];
                                        $totalKeluar = $item['total_keluar'];
                                    @endphp
                                    <tr>
                                        <td><span class="store-stock-code">{!! $highlightStockSearch($brg->kode) !!}</span></td>
                                        <td><strong>{!! $highlightStockSearch($brg->nama) !!}</strong></td>
                                        <td>{{ $brg->kategori?->nama ?? 'Umum' }}</td>
                                        <td><span class="store-stock-amount"
                                                style="color:var(--status-success);">+{{ $totalMasuk }}</span> <span
                                                class="store-stock-unit">{{ $brg->satuan?->nama }}</span></td>
                                        <td><span class="store-stock-amount"
                                                style="color:var(--status-critical);">-{{ $totalKeluar }}</span> <span
                                                class="store-stock-unit">{{ $brg->satuan?->nama }}</span></td>
                                        <td><span class="store-stock-current">{{ $stokAktual }}</span> <span
                                                class="store-stock-unit">{{ $brg->satuan?->nama }}</span></td>
                                        <td><span class="badge {{ $badge }}">{{ $statusText }}</span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline"
                                                data-history-url="{{ route('store.stok.kartu', $brg->id) }}"
                                                @click="stockHistoryReturnFocus=$event.currentTarget; openStockHistory($event.currentTarget.dataset.historyUrl)">
                                                Lihat Kartu Stok <span aria-hidden="true">→</span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            style="text-align:center;padding:32px 0;color:var(--text-muted);">Tidak ada
                                            data stok ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="store-stock-pagination">{{ $barangs->links() }}</div>
                </section>
            </div>
        </div>

        <div x-cloak x-show="stockHistoryOpen" x-transition.opacity class="store-stock-modal-backdrop"
            role="presentation" @click.self="closeStockHistory()"
            @keydown.escape.window="if (stockHistoryOpen) closeStockHistory()">
            <section x-show="stockHistoryOpen" x-transition class="store-stock-modal" role="dialog" aria-modal="true"
                aria-labelledby="store-stock-modal-title" x-ref="stockHistoryDialog" tabindex="-1"
                @keydown.tab="trapStockHistoryFocus($event)">
                <header class="store-stock-modal-header">
                    <div>
                        <p class="store-stock-eyebrow">KARTU STOK · AUDIT MUTASI</p>
                        <h2 id="store-stock-modal-title"
                            x-text="stockHistoryBarang ? stockHistoryBarang.nama : 'Riwayat Mutasi Barang'"></h2>
                        <p class="store-stock-description" x-show="stockHistoryBarang">
                            <span x-text="stockHistoryBarang?.kode"></span>
                            <span aria-hidden="true">·</span>
                            <span x-text="stockHistoryBarang?.kategori"></span>
                            <span aria-hidden="true">·</span>
                            Satuan: <span x-text="stockHistoryBarang?.satuan"></span>
                            <span aria-hidden="true">·</span>
                            Minimum: <span x-text="stockHistoryBarang?.minimum_stok"></span>
                        </p>
                    </div>
                    <button type="button" class="btn btn-outline store-stock-modal-close" x-ref="stockHistoryClose"
                        aria-label="Tutup kartu stok" @click="closeStockHistory()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </header>

                <div class="store-stock-modal-content">
                    <div x-show="stockHistoryLoading" class="store-stock-modal-feedback" role="status">
                        Memuat riwayat mutasi stok...
                    </div>
                    <div x-show="stockHistoryError" class="store-stock-modal-feedback store-stock-modal-error"
                        role="alert" x-text="stockHistoryError"></div>
                    <template
                        x-if="!stockHistoryLoading && !stockHistoryError && stockHistoryBarang && stockHistoryMutations.length === 0">
                        <div class="store-stock-modal-feedback">Belum ada mutasi stok yang tercatat untuk barang ini.</div>
                    </template>

                    <div class="table-responsive"
                        x-show="!stockHistoryLoading && !stockHistoryError && stockHistoryMutations.length > 0">
                        <table class="data-table store-stock-modal-table">
                            <thead>
                                <tr>
                                    <th>Waktu Mutasi</th>
                                    <th>Jenis Transaksi</th>
                                    <th>No. Dokumen</th>
                                    <th>Pihak Terkait</th>
                                    <th>Qty Masuk</th>
                                    <th>Qty Keluar</th>
                                    <th>Saldo</th>
                                    <th>Petugas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(mutation, index) in stockHistoryMutations"
                                    :key="`${mutation.ref}-${index}`">
                                    <tr>
                                        <td class="tabular" x-text="formatStockHistoryDate(mutation.tanggal)"></td>
                                        <td><span class="badge" :class="mutation.badge" x-text="mutation.jenis"></span>
                                        </td>
                                        <td><strong class="store-stock-ref" x-text="mutation.ref"></strong></td>
                                        <td x-text="mutation.pihak"></td>
                                        <td class="store-stock-in"
                                            x-text="mutation.masuk > 0 ? `+${mutation.masuk}` : '—'"></td>
                                        <td class="store-stock-out"
                                            x-text="mutation.keluar > 0 ? `-${mutation.keluar}` : '—'"></td>
                                        <td class="store-stock-balance"><span x-text="mutation.saldo"></span> <span
                                                x-text="stockHistoryBarang?.satuan"></span></td>
                                        <td x-text="mutation.petugas"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <footer class="store-stock-modal-footer">
                    <span>Riwayat ini untuk penelusuran dan audit; perubahan stok dilakukan melalui penerimaan atau
                        pengeluaran.</span>
                    <div class="store-stock-actions">
                        <button type="button" class="btn btn-outline" @click="window.print()">Cetak</button>
                        <button type="button" class="btn btn-primary" @click="closeStockHistory()">Selesai</button>
                    </div>
                </footer>
            </section>
        </div>

        {{-- ═══════════════════════════════════════════ TAB 2: IMPORT WIZARD ═══ --}}
        @if (auth()->user()?->can('store_stok.create') || auth()->user()?->can('stok_awal.import'))
            <div id="store-stock-panel-import" role="tabpanel" aria-labelledby="store-stock-tab-import"
                x-show="currentTab === 'import_data_awal'" x-cloak x-data="importGridFlow()" x-init="init()">

                {{-- ══════════════════════════ PHASE 0: UPLOAD OR PASTE DATA ══════════════════════════ --}}
                <div x-show="phase === 0" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Unggah file Excel atau CSV</h2>
                                <p style="font-size:.82rem;color:var(--text-muted);margin:2px 0 0;">Pilih file atau tempel
                                    data dari Excel pada grid di bawah. File maksimum <strong>100 MB</strong>.</p>
                            </div>
                            <a href="{{ route('store.stok.import-template') }}" class="btn btn-accent btn-sm"
                                data-turbo="false">
                                <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" class="icon-inline">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="7 10 12 15 17 10" />
                                    <line x1="12" y1="15" x2="12" y2="3" />
                                </svg>
                                Unduh Template
                            </a>
                        </div>

                        {{-- Drop zone --}}
                        <div id="imp-drop" @dragover.prevent="dragOver=true" @dragleave.prevent="dragOver=false"
                            @drop.prevent="onDrop($event)" @click="$refs.fileIn.click()"
                            :style="`margin-top:16px;border:2.5px dashed ${dragOver?'var(--brand-accent)':'var(--surface-border)'};border-radius:var(--radius);padding:${parsing?'28px':'52px'} 32px;text-align:center;cursor:pointer;transition:all .25s;background:${dragOver?'rgba(201,122,43,.05)':'transparent'}`">
                            {{-- Idle state --}}
                            <div x-show="!parsing && !fileInfo.name">
                                <svg style="width:44px;height:44px;color:var(--text-muted);margin-bottom:10px;"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                <p style="font-size:.95rem;font-weight:600;color:var(--text-primary);margin:0 0 4px;">Drag
                                    &amp; drop file di sini</p>
                                <p style="font-size:.8rem;color:var(--text-muted);margin:0;">atau klik untuk memilih
                                    &mdash; <code>.xlsx</code> <code>.xls</code> <code>.csv</code></p>
                            </div>

                            {{-- Parsing spinner (inline in drop zone) --}}
                            <div x-show="parsing"
                                style="display:flex;align-items:center;justify-content:center;gap:14px;">
                                <div
                                    style="width:28px;height:28px;border:3px solid var(--surface-border);border-top-color:var(--brand-accent);border-radius:50%;animation:spin .7s linear infinite;flex-shrink:0;">
                                </div>
                                <div style="text-align:left;">
                                    <p style="font-weight:600;font-size:.9rem;margin:0 0 2px;" x-text="parseMsg"></p>
                                    <p style="font-size:.75rem;color:var(--text-muted);margin:0;">Membaca struktur file di
                                        browser...</p>
                                </div>
                            </div>

                            {{-- File selected (ready) --}}
                            <div x-show="fileInfo.name && !parsing"
                                style="display:flex;align-items:center;gap:12px;justify-content:center;">
                                <svg style="width:32px;height:32px;color:var(--brand-primary);flex-shrink:0;"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg>
                                <div style="text-align:left;">
                                    <p style="font-weight:700;font-size:.9rem;margin:0 0 2px;" x-text="fileInfo.name"></p>
                                    <p style="font-size:.75rem;color:var(--text-muted);margin:0;"
                                        x-text="fileInfo.source === 'paste' ? fileInfo.sizeLabel : fileInfo.sizeLabel + ' · ' + fileInfo.sheetCount + ' sheet terdeteksi'">
                                    </p>
                                </div>
                                <span
                                    style="font-size:.72rem;padding:3px 10px;border-radius:100px;background:rgba(34,197,94,.12);color:#16a34a;font-weight:700;">✓
                                    Siap</span>
                            </div>

                            <input type="file" x-ref="fileIn" accept=".xlsx,.xls,.csv" style="display:none;"
                                @change="onFileInput($event)">
                        </div>

                        {{-- Error --}}
                        <div x-show="fileError" x-transition
                            style="margin-top:10px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.25);color:var(--status-critical);padding:10px 14px;border-radius:var(--radius);font-size:.83rem;"
                            x-text="fileError"></div>

                        {{-- Info strip --}}
                        <div
                            style="margin-top:14px;padding:12px 14px;background:rgba(20,92,84,.05);border:1px solid rgba(20,92,84,.15);border-radius:var(--radius);font-size:.79rem;color:var(--text-muted);">
                            <strong style="color:var(--brand-primary);">Kolom template:</strong>
                            <code style="margin-left:6px;">kode_barang · nama_barang · kategori · satuan · qty · kondisi ·
                                catatan</code>
                        </div>

                    </div>

                    <section class="card stock-paste-card" aria-labelledby="store-paste-heading">
                        <div class="stock-paste-heading">
                            <div>
                                <h2 id="store-paste-heading" class="card-title">Tempel dari Excel</h2>
                                <p class="stock-paste-help">File Excel/CSV dipetakan otomatis ke grid. Jika header tidak
                                    dikenali, gunakan urutan template atau edit data langsung di grid. Anda juga dapat
                                    menempel dengan urutan: kode, nama, kategori, satuan, qty, kondisi, catatan.</p>
                            </div>
                            <div class="stock-paste-tools">
                                <button type="button" class="btn btn-outline btn-sm" @click="addPasteRows()">Tambah 20
                                    baris</button>
                                <button type="button" class="btn btn-outline btn-sm" :disabled="parsing || extracting"
                                    @click="clearPasteGrid()">Kosongkan grid</button>
                            </div>
                        </div>

                        <div x-show="sheets.length > 1" class="stock-paste-help"
                            style="margin:12px 0;padding:10px 12px;border:1px solid var(--surface-border);border-radius:8px;">
                            <strong>Pilih sheet data:</strong>
                            <div class="sheets-scroll-container" style="margin-top:8px;">
                                <template x-for="sName in sheets" :key="sName">
                                    <button type="button" class="sheet-pill"
                                        :class="selectedSheet === sName ? 'sheet-pill--active' : ''"
                                        @click.prevent="selectSheet(sName)" x-text="sName"></button>
                                </template>
                            </div>
                        </div>

                        <datalist id="store-paste-categories">
                            @foreach ($kategoriList as $kategori)
                                <option value="{{ $kategori->nama }}"></option>
                            @endforeach
                        </datalist>
                        <datalist id="store-paste-units">
                            @foreach ($satuanList as $satuan)
                                <option value="{{ $satuan->nama }}"></option>
                            @endforeach
                        </datalist>

                        <div class="stock-paste-grid-wrap">
                            <table class="stock-paste-grid" aria-label="Grid tempel stok awal">
                                <thead>
                                    <tr>
                                        <th scope="col">Baris</th>
                                        <template x-for="field in systemFields" :key="field.key">
                                            <th scope="col">
                                                <span x-text="field.label"></span>
                                                <span x-show="field.required" class="stock-paste-required"
                                                    aria-label="wajib">*</span>
                                            </th>
                                        </template>
                                        <th scope="col">Review</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, visibleRowIndex) in visiblePasteRows"
                                        :key="pasteRowIndex(visibleRowIndex)">
                                        <tr>
                                            <td x-text="pasteRowIndex(visibleRowIndex) + 1"></td>
                                            <template x-for="(field, fieldIndex) in systemFields" :key="field.key">
                                                <td>
                                                    <input type="text"
                                                        :inputmode="field.key === 'qty' ? 'decimal' : 'text'"
                                                        :list="field.key === 'kategori' ? 'store-paste-categories' : (field
                                                            .key === 'satuan' ? 'store-paste-units' : null)"
                                                        :aria-label="`${field.label}, baris ${pasteRowIndex(visibleRowIndex) + 1}`"
                                                        :data-paste-row="pasteRowIndex(visibleRowIndex)"
                                                        :data-paste-column="fieldIndex"
                                                        x-model="pasteRows[pasteRowIndex(visibleRowIndex)][field.key]"
                                                        @input="scheduleGridValidation()"
                                                        @paste="pasteIntoGrid($event, pasteRowIndex(visibleRowIndex), fieldIndex)"
                                                        @keydown.enter.prevent="focusNextPasteCell($event, pasteRowIndex(visibleRowIndex), fieldIndex)">
                                                </td>
                                            </template>
                                            <td style="min-width:180px;max-width:280px;">
                                                <span x-show="!pasteRows[pasteRowIndex(visibleRowIndex)]._validation"
                                                    class="stock-paste-help">Belum divalidasi</span>
                                                <span
                                                    x-show="pasteRows[pasteRowIndex(visibleRowIndex)]._validation?.status === 'ok'"
                                                    style="color:#16a34a;font-weight:700;">✓ Valid</span>
                                                <div x-show="pasteRows[pasteRowIndex(visibleRowIndex)]._validation?.status === 'error'"
                                                    style="color:var(--status-critical);font-size:.75rem;">
                                                    <strong>Perlu diperbaiki</strong>
                                                    <div
                                                        x-text="pasteRows[pasteRowIndex(visibleRowIndex)]._validation?.issues?.join('; ')">
                                                    </div>
                                                </div>
                                                <div x-show="pasteRows[pasteRowIndex(visibleRowIndex)]._validation?.warnings?.length"
                                                    style="color:var(--text-muted);font-size:.75rem;"
                                                    x-text="pasteRows[pasteRowIndex(visibleRowIndex)]._validation?.warnings?.join('; ')">
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline btn-sm"
                                                    :aria-label="`Hapus baris ${pasteRowIndex(visibleRowIndex) + 1}`"
                                                    @click="removePasteRow(pasteRowIndex(visibleRowIndex))">Hapus</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p x-show="extracting" class="stock-paste-help" role="status" aria-live="polite"
                            style="margin-top:10px;">Membaca baris dari file untuk ditampilkan di grid...</p>
                        <p x-show="fileInfo.source === 'file' && !extracting && pasteRows.length > 0"
                            class="stock-paste-help" role="status" aria-live="polite" style="margin-top:10px;">
                            <span
                                x-text="`${pasteRows.length} baris dari file ditampilkan. Validasi berjalan otomatis dan diperbarui saat data diedit.`"></span>
                        </p>

                        <nav class="stock-paste-pagination" aria-label="Halaman grid tempel">
                            <button type="button" class="btn btn-outline btn-sm" :disabled="pastePage <= 1"
                                @click="pastePage--">Sebelumnya</button>
                            <span
                                x-text="`Baris ${(pastePage - 1) * pastePageSize + 1}–${Math.min(pastePage * pastePageSize, pasteRows.length)} dari ${pasteRows.length}`"></span>
                            <button type="button" class="btn btn-outline btn-sm" :disabled="pastePage >= pastePageCount"
                                @click="pastePage++">Berikutnya</button>
                        </nav>
                        <p class="stock-paste-help" role="note">Unggah Excel/CSV atau tempel langsung ke grid. Data
                            divalidasi otomatis setelah selesai dimuat atau diedit.</p>

                        <div x-show="validatedRows.length > 0" class="stock-paste-help" role="status"
                            aria-live="polite"
                            style="margin-top:12px;padding:10px 12px;border:1px solid var(--surface-border);border-radius:8px;">
                            <strong>Hasil validasi:</strong>
                            <span
                                x-text="`${validSummary.ok} valid · ${validSummary.error} perlu diperbaiki · ${validSummary.new_barang} barang baru`"></span>
                            <span x-show="validSummary.error > 0"> — Perbaiki baris bertanda merah; validasi akan berjalan
                                kembali otomatis.</span>
                        </div>
                        <div x-show="validationError" role="alert" class="stock-paste-help"
                            style="margin-top:10px;color:var(--status-critical);" x-text="validationError"></div>
                        <div x-show="validating" class="stock-paste-help" role="status" aria-live="polite"
                            style="margin-top:10px;">Validasi otomatis: memeriksa baris grid...</div>
                        <div x-show="validationError && pasteRows.some(row => systemFields.some(field => String(row[field.key] ?? '').trim() !== ''))"
                            style="margin-top:10px;">
                            <button type="button" class="btn btn-outline btn-sm" :disabled="validating"
                                @click="analyzeGrid()">Coba validasi lagi</button>
                        </div>

                        <div
                            style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:16px;">
                            <div class="form-group">
                                <label class="form-label">Tanggal Acuan <span
                                        style="color:var(--status-critical);">*</span></label>
                                <input type="date" class="form-control" x-model="tanggalAcuan" :max="todayStr"
                                    required>
                                <small class="stock-paste-help">Tanggal opname fisik yang menjadi dasar angka stok.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Catatan Umum <span
                                        style="color:var(--text-muted);font-weight:400;">(opsional)</span></label>
                                <input type="text" class="form-control" x-model="catatanUmum" maxlength="500"
                                    placeholder="Catatan untuk stok awal">
                            </div>
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:14px;">
                            <button type="button" class="btn btn-primary"
                                :disabled="validating || validatedRows.length === 0 || validSummary.ok === 0 || !tanggalAcuan"
                                @click="doImport()">
                                Simpan <span x-text="validSummary.ok"></span> baris valid
                            </button>
                        </div>
                    </section>
                </div>

                {{-- ══════════════════════════ PHASE 2: RESULT ══════════════════════════ --}}
                <div x-show="phase === 2" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100">

                    {{-- Importing progress --}}
                    <div x-show="importing" class="card" style="text-align:center;padding:60px 32px;">
                        <div
                            style="width:60px;height:60px;border:5px solid var(--surface-border);border-top-color:var(--brand-primary);border-radius:50%;animation:spin .65s linear infinite;margin:0 auto 18px;">
                        </div>
                        <p style="font-weight:700;font-size:1rem;">Menyimpan data ke database...</p>
                        <p style="color:var(--text-muted);font-size:.82rem;margin-top:6px;"
                            x-text="`Memproses ${validSummary.ok} baris stok awal...`"></p>
                        <p style="color:var(--text-muted);font-size:.75rem;margin-top:4px;">Harap tunggu, jangan tutup
                            halaman ini.</p>
                    </div>

                    {{-- Result --}}
                    <div x-show="!importing" class="card" style="text-align:center;padding:48px 32px;">

                        {{-- Success --}}
                        <div x-show="importResult.success">
                            <div
                                style="width:70px;height:70px;border-radius:50%;background:rgba(34,197,94,.13);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                                <svg style="width:34px;height:34px;color:#16a34a;" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                            <h2 style="font-size:1.35rem;font-weight:800;color:#16a34a;margin-bottom:6px;">Import Berhasil!
                                🎉</h2>
                            <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:24px;">Data stok awal telah
                                tersimpan ke database.</p>

                            <div
                                style="display:inline-grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:28px;">
                                <div
                                    style="padding:14px 18px;background:rgba(34,197,94,.07);border:1px solid rgba(34,197,94,.2);border-radius:10px;">
                                    <div style="font-size:2rem;font-weight:800;color:#16a34a;"
                                        x-text="importResult.success_count"></div>
                                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;">Stok awal tercatat
                                    </div>
                                </div>
                                <div
                                    style="padding:14px 18px;background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.2);border-radius:10px;">
                                    <div style="font-size:2rem;font-weight:800;color:#6366f1;"
                                        x-text="importResult.created_count"></div>
                                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;">Barang baru dibuat
                                    </div>
                                </div>
                                <div
                                    style="padding:14px 18px;background:var(--surface-base);border:1px solid var(--surface-border);border-radius:10px;">
                                    <div style="font-size:2rem;font-weight:800;color:var(--text-muted);"
                                        x-text="importResult.skipped_count"></div>
                                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;">Dilewati</div>
                                </div>
                            </div>

                            <div x-show="importResult.error_rows&&importResult.error_rows.length>0"
                                style="max-width:480px;margin:0 auto 20px;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:12px 14px;text-align:left;">
                                <p style="font-size:.78rem;font-weight:700;color:var(--status-critical);margin:0 0 6px;">
                                    Baris yang gagal diimport:</p>
                                <ul style="margin:0;padding-left:16px;font-size:.75rem;color:var(--text-muted);">
                                    <template x-for="e in importResult.error_rows" :key="e.row">
                                        <li x-text="`Baris ${e.row} (${e.kode}): ${e.error}`"></li>
                                    </template>
                                </ul>
                            </div>

                            <div style="display:flex;gap:10px;justify-content:center;">
                                <a href="{{ route('store.stok.index', ['tab' => 'stok_saat_ini']) }}"
                                    class="btn btn-primary">📦 Lihat Stok Real-Time →</a>
                                <button type="button" class="btn btn-outline" @click="resetImport()">🔄 Import File
                                    Lain</button>
                            </div>
                        </div>

                        {{-- Failed --}}
                        <div x-show="!importResult.success">
                            <div
                                style="width:70px;height:70px;border-radius:50%;background:rgba(239,68,68,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                                <svg style="width:34px;height:34px;color:var(--status-critical);" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="15" y1="9" x2="9" y2="15" />
                                    <line x1="9" y1="9" x2="15" y2="15" />
                                </svg>
                            </div>
                            <h2 style="font-size:1.2rem;font-weight:700;color:var(--status-critical);margin-bottom:8px;">
                                Import Gagal</h2>
                            <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:20px;"
                                x-text="importResult.errorMsg"></p>
                            <button type="button" class="btn btn-outline" @click="phase=0;importing=false;">← Kembali ke
                                grid</button>
                        </div>
                    </div>
                </div>

            </div>{{-- end Tab 2 wizard --}}
        @endif
    </div>{{-- end main tab wrapper --}}

    <style>
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        [x-cloak] {
            display: none !important;
        }

        /* Sheet selector pills */
        .sheet-pill {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--surface-border);
            background: transparent;
            color: var(--text-muted);
            transition: all .2s;
            line-height: 1.4;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sheet-pill:hover {
            border-color: var(--brand-accent);
            color: var(--brand-accent);
            background: rgba(201, 122, 43, .05);
        }

        .sheet-pill--active {
            border-color: var(--brand-accent) !important;
            background: rgba(201, 122, 43, .1) !important;
            color: var(--brand-accent) !important;
        }

        .stock-paste-card {
            margin-top: 14px;
        }

        .stock-paste-heading,
        .stock-paste-tools,
        .stock-paste-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .stock-paste-heading {
            margin-bottom: 12px;
        }

        .stock-paste-tools {
            flex-wrap: wrap;
        }

        .stock-paste-help {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: .78rem;
        }

        .stock-paste-grid-wrap {
            max-height: 430px;
            overflow: auto;
            border: 1px solid var(--surface-border);
            border-radius: 6px;
        }

        .stock-paste-grid {
            width: 100%;
            min-width: 1180px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: .75rem;
        }

        .stock-paste-grid th,
        .stock-paste-grid td {
            padding: 0;
            border-right: 1px solid var(--surface-border);
            border-bottom: 1px solid var(--surface-border);
        }

        .stock-paste-grid th {
            position: sticky;
            top: 0;
            z-index: 2;
            height: 40px;
            padding: 8px;
            background: var(--surface-base);
            color: var(--text-primary);
            text-align: left;
        }

        .stock-paste-grid th:first-child,
        .stock-paste-grid td:first-child {
            width: 54px;
            text-align: center;
        }

        .stock-paste-grid th:nth-child(2) {
            width: 145px;
        }

        .stock-paste-grid th:nth-child(3) {
            width: 220px;
        }

        .stock-paste-grid th:nth-child(4) {
            width: 155px;
        }

        .stock-paste-grid th:nth-child(5) {
            width: 105px;
        }

        .stock-paste-grid th:nth-child(6) {
            width: 90px;
        }

        .stock-paste-grid th:nth-child(7) {
            width: 130px;
        }

        .stock-paste-grid th:nth-child(8) {
            width: 230px;
        }

        .stock-paste-grid th:last-child,
        .stock-paste-grid td:last-child {
            width: 78px;
            text-align: center;
        }

        .stock-paste-grid td:first-child {
            background: var(--surface-base);
            color: var(--text-muted);
        }

        .stock-paste-grid input {
            width: 100%;
            height: 38px;
            padding: 7px 9px;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text-primary);
            font: inherit;
        }

        .stock-paste-grid input:focus {
            position: relative;
            z-index: 1;
            outline: 2px solid var(--brand-primary);
            outline-offset: -2px;
            background: var(--surface-card);
        }

        .stock-paste-grid td:last-child {
            padding: 4px;
        }

        .stock-paste-grid td:last-child .btn {
            padding: 5px 8px;
            font-size: .7rem;
        }

        .stock-paste-required {
            color: var(--status-critical);
        }

        .stock-paste-pagination {
            justify-content: flex-end;
            margin-top: 10px;
            color: var(--text-muted);
            font-size: .75rem;
        }

        .stock-paste-pagination button:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        @media (max-width: 720px) {
            .stock-paste-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .stock-paste-tools {
                width: 100%;
            }
        }

        /* Horizontal scrollable sheet selector container */
        .sheets-scroll-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 6px;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        .sheets-scroll-container::-webkit-scrollbar {
            height: 4px;
        }

        .sheets-scroll-container::-webkit-scrollbar-thumb {
            background: var(--surface-border);
            border-radius: 4px;
        }
    </style>

    <script>
        // ─────────────────────────────────────────────────────────────────────────────
        // Worker source: 2-phase persistent protocol
        //   msg {type:'parse', buffer: ArrayBuffer}  → reply {type:'sheets', sheets:[]}
        //   msg {type:'extract', sheet: 'Sheet1'}    → reply {type:'extracted', sheet, rows:[]}
        // The worker keeps the parsed workbook in memory between sheet extractions.
        // ─────────────────────────────────────────────────────────────────────────────
        var WORKER_SRC = (cdn) => `
importScripts("${cdn}");
var wb = null;
self.onmessage = function(msg) {
  var d = msg.data;
  if (d.type === 'parse') {
    try {
      wb = XLSX.read(new Uint8Array(d.buffer), {
        type: 'array',
        cellFormula: false,
        cellHTML:    false,
        cellText:    false,
        sheetStubs:  false
      });
      self.postMessage({ type: 'sheets', sheets: wb.SheetNames });
    } catch(e) {
      self.postMessage({ type: 'error', message: e.message });
    }
  } else if (d.type === 'extract') {
    try {
      var ws   = wb.Sheets[d.sheet];
      var rows = XLSX.utils.sheet_to_json(ws, {
        header:    1,
        defval:    '',
        raw:       true,
        blankrows: false
      });
      self.postMessage({ type: 'extracted', sheet: d.sheet, rows: rows });
    } catch(e) {
      self.postMessage({ type: 'error', message: e.message });
    }
  }
};
`;

        function importGridFlow() {
            return {
                phase: 0,
                dragOver: false,
                fileInfo: {},
                selectedFile: null,
                fileError: '',
                parsing: false,
                extracting: false, // true while worker extracts a specific sheet
                parseMsg: 'Membaca file...',
                sheetJsonData: {}, // cache: { sheetName: [[row],...] }
                _worker: null, // persistent Worker reference
                _workerUrl: null,

                pasteRows: [],
                pastePage: 1,
                pastePageSize: 20,
                pasteRowLimit: 50000,
                validationTimer: null,
                validationRun: 0,

                sheets: [],
                selectedSheet: '',
                detectedHeaders: [],
                allRawRows: [], // all rows from current sheet (including title rows)
                rawRows: [], // data rows only (after header)
                headerRowIndex: 0, // which row in allRawRows is the header
                columnAliases: {
                    kode_barang: ['kode_barang', 'kode', 'code', 'sku', 'item_code', 'kode barang'],
                    nama_barang: ['nama_barang', 'nama', 'name', 'item_name', 'nama barang', 'barang'],
                    kategori: ['kategori', 'category', 'kat', 'jenis'],
                    satuan: ['satuan', 'unit', 'uom', 'satuan dasar'],
                    qty: ['qty', 'quantity', 'jumlah', 'stok', 'stock', 'amount'],
                    kondisi: ['kondisi', 'condition', 'status'],
                    catatan: ['catatan', 'notes', 'note', 'keterangan', 'remark'],
                },
                systemFields: [{
                        key: 'kode_barang',
                        label: 'Kode Barang',
                        required: true
                    },
                    {
                        key: 'nama_barang',
                        label: 'Nama Barang',
                        required: true
                    },
                    {
                        key: 'kategori',
                        label: 'Kategori',
                        required: true
                    },
                    {
                        key: 'satuan',
                        label: 'Satuan',
                        required: true
                    },
                    {
                        key: 'qty',
                        label: 'Qty / Jumlah',
                        required: true
                    },
                    {
                        key: 'kondisi',
                        label: 'Kondisi',
                        required: false
                    },
                    {
                        key: 'catatan',
                        label: 'Catatan',
                        required: false
                    },
                ],
                columnMap: {},
                validating: false,
                validationError: '',
                validatedRows: [],
                validSummary: {
                    total: 0,
                    ok: 0,
                    duplicate: 0,
                    skip: 0,
                    error: 0,
                    new_barang: 0
                },
                duplicateAction: 'skip',
                duplicateConfirmed: false,
                pvFilter: 'all',
                tanggalAcuan: '',
                catatanUmum: '',
                todayStr: '',

                importing: false,
                importResult: {},

                init() {
                    this.todayStr = this.tanggalAcuan = new Date().toISOString().split('T')[0];
                    this.resetPasteGrid();
                },

                get visiblePasteRows() {
                    const start = (this.pastePage - 1) * this.pastePageSize;
                    return this.pasteRows.slice(start, start + this.pastePageSize);
                },

                get pastePageCount() {
                    return Math.max(1, Math.ceil(this.pasteRows.length / this.pastePageSize));
                },

                get filteredRows() {
                    if (this.pvFilter === 'all') return this.validatedRows;
                    return this.validatedRows.filter(r => r.status === this.pvFilter);
                },

                // ── Phase 0 ──────────────────────────────────────────────────────────────
                onDrop(e) {
                    this.dragOver = false;
                    const f = e.dataTransfer.files[0];
                    if (f) this.loadFile(f);
                },
                onFileInput(e) {
                    const f = e.target.files[0];
                    if (f) this.loadFile(f);
                },

                blankPasteRow() {
                    return Object.fromEntries(this.systemFields.map(field => [field.key, '']));
                },

                resetPasteGrid() {
                    this.pasteRows = Array.from({
                        length: this.pastePageSize
                    }, () => this.blankPasteRow());
                    this.pastePage = 1;
                    this.invalidateGridValidation();
                },

                clearPasteGrid() {
                    this.resetPasteGrid();
                    this.fileError = '';
                    this.fileInfo = {};
                    this.selectedFile = null;
                    this.sheets = [];
                    this.selectedSheet = '';
                    this.allRawRows = [];
                    this.rawRows = [];
                    this.columnMap = {};
                    this._killWorker();
                    this.parsing = false;
                    this.extracting = false;
                    if (this.$refs.fileIn) this.$refs.fileIn.value = '';
                },

                pasteRowIndex(visibleRowIndex) {
                    return (this.pastePage - 1) * this.pastePageSize + visibleRowIndex;
                },

                addPasteRows() {
                    this.invalidateGridValidation();
                    const rowsToAdd = Math.min(this.pastePageSize, this.pasteRowLimit - this.pasteRows.length);
                    if (rowsToAdd < 1) {
                        this.fileError = 'Grid tempel dibatasi 50.000 baris.';
                        return;
                    }
                    this.pasteRows.push(...Array.from({
                        length: rowsToAdd
                    }, () => this.blankPasteRow()));
                    this.pastePage = this.pastePageCount;
                    this.fileError = '';
                    this.scheduleGridValidation();
                },

                removePasteRow(rowIndex) {
                    this.pasteRows.splice(rowIndex, 1);
                    if (this.pasteRows.length === 0) this.resetPasteGrid();
                    else this.pastePage = Math.min(this.pastePage, this.pastePageCount);
                    this.scheduleGridValidation();
                },

                pasteIntoGrid(event, rowIndex, columnIndex) {
                    const text = event.clipboardData?.getData('text/plain') ?? '';
                    if (!/[\t\r\n]/.test(text)) return;

                    event.preventDefault();
                    this.invalidateGridValidation();
                    const rows = text.replace(/\r\n?/g, '\n').replace(/\n+$/, '').split('\n');
                    const availableRows = this.pasteRowLimit - rowIndex;
                    const rowsToPaste = rows.slice(0, availableRows);
                    const requiredLength = rowIndex + rowsToPaste.length;

                    while (this.pasteRows.length < requiredLength) {
                        this.pasteRows.push(this.blankPasteRow());
                    }

                    rowsToPaste.forEach((line, rowOffset) => {
                        line.split('\t').slice(0, this.systemFields.length - columnIndex).forEach((value,
                            columnOffset) => {
                            const field = this.systemFields[columnIndex + columnOffset];
                            this.pasteRows[rowIndex + rowOffset][field.key] = value;
                        });
                    });

                    this.pastePage = Math.floor(rowIndex / this.pastePageSize) + 1;
                    this.fileError = rowsToPaste.length < rows.length ?
                        'Grid tempel dibatasi 50.000 baris. Data setelah batas tersebut tidak ditempel.' :
                        '';
                    this.scheduleGridValidation();
                },

                focusNextPasteCell(event, rowIndex, columnIndex) {
                    const nextRow = rowIndex + 1;
                    if (nextRow >= this.pasteRows.length) this.addPasteRows();
                    this.pastePage = Math.floor(nextRow / this.pastePageSize) + 1;
                    this.$nextTick(() => {
                        this.$root.querySelector(
                            `[data-paste-row="${nextRow}"][data-paste-column="${columnIndex}"]`)?.focus();
                    });
                },

                invalidateGridValidation() {
                    clearTimeout(this.validationTimer);
                    this.validationTimer = null;
                    this.validationRun++;
                    this.validating = false;
                    this.validatedRows = [];
                    this.validSummary = {
                        total: 0,
                        ok: 0,
                        duplicate: 0,
                        skip: 0,
                        error: 0,
                        new_barang: 0
                    };
                    this.validationError = '';
                    this.pasteRows.forEach(row => {
                        delete row._validation;
                    });
                },

                scheduleGridValidation() {
                    this.invalidateGridValidation();
                    const hasRows = this.pasteRows.some(row =>
                        this.systemFields.some(field => String(row[field.key] ?? '').trim() !== '')
                    );
                    if (!hasRows) return;

                    this.validationTimer = setTimeout(() => {
                        this.validationTimer = null;
                        this.analyzeGrid();
                    }, 500);
                },

                async analyzeGrid() {
                    clearTimeout(this.validationTimer);
                    this.validationTimer = null;
                    const rows = this.pasteRows
                        .map((row, index) => ({
                            ...row,
                            row: index + 1
                        }))
                        .filter(row => this.systemFields.some(field => String(row[field.key] ?? '').trim() !== ''));

                    if (rows.length === 0) {
                        this.validationError = 'Isi atau tempel setidaknya satu baris data terlebih dahulu.';
                        return;
                    }

                    const validationRun = ++this.validationRun;
                    this.validating = true;
                    this.validationError = '';
                    this.validatedRows = [];
                    this.validSummary = {
                        total: 0,
                        ok: 0,
                        duplicate: 0,
                        skip: 0,
                        error: 0,
                        new_barang: 0
                    };
                    this.pasteRows.forEach(row => {
                        delete row._validation;
                    });

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const response = await fetch('{{ route('store.stok.import-analyze') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                rows
                            }),
                        });
                        const data = await response.json();

                        if (validationRun !== this.validationRun) return;
                        if (!response.ok) {
                            this.validationError = data.message || data.error || 'Validasi grid gagal.';
                            return;
                        }

                        this.validatedRows = data.rows || [];
                        this.validSummary = data.summary || {
                            total: 0,
                            ok: 0,
                            duplicate: 0,
                            skip: 0,
                            error: 0,
                            new_barang: 0
                        };
                        this.validatedRows.forEach(result => {
                            const gridRow = this.pasteRows[Number(result.row) - 1];
                            if (gridRow) gridRow._validation = result;
                        });
                    } catch (error) {
                        if (validationRun === this.validationRun) {
                            this.validationError = 'Koneksi gagal: ' + error.message;
                        }
                    } finally {
                        if (validationRun === this.validationRun) this.validating = false;
                    }
                },

                _killWorker() {
                    if (this._worker) {
                        this._worker.terminate();
                        this._worker = null;
                    }
                    if (this._workerUrl) {
                        URL.revokeObjectURL(this._workerUrl);
                        this._workerUrl = null;
                    }
                },

                loadFile(file) {
                    this.fileError = '';
                    this._killWorker();
                    this.parsing = false;
                    this.extracting = false;
                    this.selectedFile = null;
                    this.fileInfo = {};
                    this.sheets = [];
                    this.selectedSheet = '';
                    this.allRawRows = [];
                    this.rawRows = [];
                    this.resetPasteGrid();
                    this.columnMap = {};
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
                        this.fileError = 'Format tidak didukung. Gunakan .xlsx, .xls, atau .csv';
                        return;
                    }
                    if (file.size > 100 * 1024 * 1024) {
                        this.fileError = 'File terlalu besar. Maksimum 100 MB.';
                        return;
                    }
                    this.selectedFile = file;
                    this.parsing = true;
                    this.parseMsg = 'Membaca file...';
                    this.fileInfo = {};
                    this.sheetJsonData = {};
                    this.sheets = [];

                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        const buf = ev.target.result;
                        this.parseMsg = 'Memparse struktur file...';

                        if (typeof Worker === 'undefined') {
                            // No Worker support – synchronous fallback
                            this._syncParse(buf, file);
                            return;
                        }

                        const cdn = 'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js';
                        const src = WORKER_SRC(cdn);
                        this._workerUrl = URL.createObjectURL(new Blob([src], {
                            type: 'application/javascript'
                        }));
                        this._worker = new Worker(this._workerUrl);

                        this._worker.onmessage = (wev) => {
                            const msg = wev.data;
                            if (msg.type === 'sheets') {
                                // ── Phase 1 done: we have SheetNames, workbook stays in worker ──
                                this.sheets = msg.sheets;
                                this.selectedSheet = msg.sheets[0];
                                this.fileInfo = {
                                    name: file.name,
                                    sizeLabel: file.size >= 1048576 ?
                                        (file.size / 1048576).toFixed(1) + ' MB' : (file.size / 1024).toFixed(
                                            1) + ' KB',
                                    sheetCount: msg.sheets.length,
                                    source: 'file',
                                };
                                this.parsing = false;
                                // ── Phase 2: extract first sheet ──
                                this._extractSheet(msg.sheets[0]);

                            } else if (msg.type === 'extracted') {
                                // Cache the extracted sheet data
                                this.sheetJsonData[msg.sheet] = msg.rows;
                                this.extracting = false;
                                this._applySheetData(msg.sheet, msg.rows);

                            } else if (msg.type === 'error') {
                                this._killWorker();
                                this.fileError = 'Error: ' + msg.message;
                                this.parsing = false;
                                this.extracting = false;
                            }
                        };

                        this._worker.onerror = () => {
                            this._killWorker();
                            this._syncParse(buf, file);
                        };

                        // Transfer ArrayBuffer (zero-copy) — instant
                        this._worker.postMessage({
                            type: 'parse',
                            buffer: buf
                        }, [buf]);
                    };
                    reader.readAsArrayBuffer(file);
                },

                // Ask worker to extract one specific sheet (cached if already done)
                _extractSheet(sheetName) {
                    if (this.sheetJsonData[sheetName]) {
                        this._applySheetData(sheetName, this.sheetJsonData[sheetName]);
                        return;
                    }
                    if (!this._worker) return;
                    this.extracting = true;
                    this.parseMsg = 'Memuat sheet "' + sheetName + '"...';
                    this._worker.postMessage({
                        type: 'extract',
                        sheet: sheetName
                    });
                },

                _applySheetData(sheetName, rows) {
                    this.allRawRows = rows;
                    this.headerRowIndex = this._autoDetectHeaderRow(rows);
                    this._applyHeaderRow();
                    if (this.selectedSheet === sheetName) {
                        this.autoMap();
                        this.populatePasteGridFromFile();
                    }
                },

                // Heuristic: score first 25 rows; pick the one that looks most like a header
                // (many distinct non-numeric strings, not a single merged-title cell)
                _autoDetectHeaderRow(rows) {
                    const scanLimit = Math.min(25, rows.length);
                    const aliases = new Set(Object.values(this.columnAliases).flat().map(alias =>
                        alias.toLowerCase().trim().replace(/[\s-]+/g, '_')
                    ));
                    let bestHeaderScore = 0;
                    let bestHeaderIndex = -1;
                    for (let i = 0; i < scanLimit; i++) {
                        const score = (rows[i] || []).reduce((matches, cell) => {
                            const normalized = String(cell ?? '').toLowerCase().trim().replace(/[\s-]+/g, '_');
                            return matches + Number(aliases.has(normalized));
                        }, 0);
                        if (score > bestHeaderScore) {
                            bestHeaderScore = score;
                            bestHeaderIndex = i;
                        }
                    }
                    if (bestHeaderIndex >= 0) return bestHeaderIndex;

                    let bestScore = -1,
                        bestIdx = 0;
                    for (let i = 0; i < scanLimit; i++) {
                        const row = rows[i] || [];
                        const nonEmpty = row.filter(c => String(c ?? '').trim() !== '');
                        if (nonEmpty.length === 0) continue;
                        const strCells = nonEmpty.filter(c => isNaN(Number(String(c).trim())));
                        // Penalise single-cell rows (title rows) heavily
                        const score = strCells.length * 3 + nonEmpty.length - (nonEmpty.length <= 1 ? 8 : 0);
                        if (score > bestScore) {
                            bestScore = score;
                            bestIdx = i;
                        }
                    }
                    return bestIdx;
                },

                // Apply headerRowIndex → recompute detectedHeaders and rawRows
                _applyHeaderRow() {
                    const rows = this.allRawRows;
                    if (!rows || rows.length <= this.headerRowIndex) {
                        this.detectedHeaders = [];
                        this.rawRows = [];
                        return;
                    }
                    this.detectedHeaders = (rows[this.headerRowIndex] || [])
                        .map(h => String(h ?? '').trim())
                        .filter(h => h !== '');
                    this.rawRows = rows.slice(this.headerRowIndex + 1)
                        .filter(r => r.some(c => String(c ?? '').trim() !== ''));
                },

                populatePasteGridFromFile() {
                    this.invalidateGridValidation();
                    const headerCells = this.allRawRows[this.headerRowIndex] || [];
                    const mappedColumns = Object.fromEntries(
                        Object.entries(this.columnMap).map(([field, header]) => [
                            field,
                            headerCells.findIndex(cell => String(cell ?? '').trim() === header),
                        ])
                    );
                    const hasRequiredMapping = this.systemFields.some(field =>
                        field.required && this.columnMap[field.key]
                    );
                    if (!hasRequiredMapping) {
                        this.systemFields.forEach((field, index) => {
                            if (index < headerCells.length) mappedColumns[field.key] = index;
                        });
                    }
                    const rows = this.rawRows.slice(0, this.pasteRowLimit);

                    this.pasteRows = rows.map(rawRow => {
                        const mappedRow = this.blankPasteRow();
                        for (const field of this.systemFields) {
                            const columnIndex = mappedColumns[field.key];
                            if (columnIndex >= 0) {
                                mappedRow[field.key] = String(rawRow[columnIndex] ?? '');
                            }
                        }
                        return mappedRow;
                    });
                    if (this.pasteRows.length === 0) {
                        this.resetPasteGrid();
                    } else {
                        this.pastePage = 1;
                    }

                    this.fileError = this.rawRows.length > this.pasteRowLimit ?
                        'Grid menampilkan maksimal 50.000 baris. Baris setelah batas tersebut tidak disertakan.' :
                        '';
                    this.scheduleGridValidation();
                },


                _syncParse(buf, file) {
                    try {
                        this.parseMsg = 'Parsing langsung...';
                        const wb = XLSX.read(new Uint8Array(buf), {
                            type: 'array',
                            cellFormula: false,
                            cellHTML: false,
                            cellText: false,
                            sheetStubs: false
                        });
                        this.sheets = wb.SheetNames;
                        this.selectedSheet = wb.SheetNames[0];
                        this.fileInfo = {
                            name: file.name,
                            sizeLabel: file.size >= 1048576 ?
                                (file.size / 1048576).toFixed(1) + ' MB' : (file.size / 1024).toFixed(1) + ' KB',
                            sheetCount: wb.SheetNames.length,
                            source: 'file',
                        };
                        for (const n of wb.SheetNames) {
                            this.sheetJsonData[n] = XLSX.utils.sheet_to_json(wb.Sheets[n], {
                                header: 1,
                                defval: '',
                                raw: true,
                                blankrows: false
                            });
                        }
                        this._applySheetData(wb.SheetNames[0], this.sheetJsonData[wb.SheetNames[0]]);
                    } catch (e) {
                        this.fileError = 'Gagal membaca file: ' + e.message;
                    }
                    this.parsing = this.extracting = false;
                },

                // Called when user clicks a sheet pill
                selectSheet(sName) {
                    if (this.selectedSheet === sName && this.rawRows.length > 0) return;
                    this.selectedSheet = sName;
                    this.headerRowIndex = 0; // reset for new sheet, auto-detect will re-run
                    this._extractSheet(sName);
                },

                loadSheetData(sheetName) {
                    this._extractSheet(sheetName);
                },

                autoMap() {
                    const lower = this.detectedHeaders.map(h => h.toLowerCase().trim());
                    this.columnMap = {};
                    for (const [field, list] of Object.entries(this.columnAliases)) {
                        for (const a of list) {
                            const idx = lower.indexOf(a);
                            if (idx !== -1) {
                                this.columnMap[field] = this.detectedHeaders[idx];
                                break;
                            }
                        }
                    }
                },

                // ── Import reviewed grid rows ─────────────────────────────────────────────
                async doImport() {
                    if (!this.tanggalAcuan || this.validSummary.ok === 0) return;
                    if (!window.confirm(
                            `Simpan ${this.validSummary.ok} baris stok awal? Data yang tersimpan tidak dapat dibatalkan.`
                        )) return;
                    this.phase = 2;
                    this.importing = true;

                    // Strictly import only valid non-duplicate rows (status === 'ok')
                    const targetRows = this.validatedRows.filter(r => r.status === 'ok');

                    if (targetRows.length === 0) {
                        this.importing = false;
                        this.phase = 0;
                        alert('Tidak ada data baru/valid yang bisa diimport.');
                        return;
                    }

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch('{{ route('store.stok.import-process') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                tanggal_acuan: this.tanggalAcuan,
                                catatan_umum: this.catatanUmum,
                                rows: targetRows
                            }),
                        });
                        const data = await res.json();
                        this.importing = false;
                        if (!res.ok) {
                            this.importResult = {
                                success: false,
                                errorMsg: data.error || data.message || 'Import gagal.'
                            };
                        } else {
                            this.importResult = {
                                success: true,
                                success_count: data.success_count,
                                created_count: data.created_count,
                                skipped_count: (data.skipped_count || 0) + (this.validSummary.duplicate || 0),
                                error_rows: data.error_rows || [],
                            };
                        }
                    } catch (err) {
                        this.importing = false;
                        this.importResult = {
                            success: false,
                            errorMsg: 'Koneksi gagal: ' + err.message
                        };
                    }
                },

                resetImport() {
                    this._killWorker();
                    this.phase = 0;
                    this.fileInfo = {};
                    this.fileError = '';
                    this.parsing = false;
                    this.extracting = false;
                    this.selectedFile = null;
                    this.sheets = [];
                    this.selectedSheet = '';
                    this.detectedHeaders = [];
                    this.allRawRows = [];
                    this.rawRows = [];
                    this.sheetJsonData = {};
                    this.headerRowIndex = 0;
                    this.columnMap = {};
                    this.validatedRows = [];
                    this.validSummary = {
                        total: 0,
                        ok: 0,
                        duplicate: 0,
                        skip: 0,
                        error: 0,
                        new_barang: 0
                    };
                    this.validating = false;
                    this.validationError = '';
                    this.importing = false;
                    this.importResult = {};
                    this.tanggalAcuan = this.todayStr;
                    this.catatanUmum = '';
                    this.pvFilter = 'all';
                    this.resetPasteGrid();
                    if (this.$refs.fileIn) this.$refs.fileIn.value = '';
                },
            };
        }
    </script>
@endsection
