@extends('layouts.app')

@section('title', 'Permintaan Barang — Admin Gudang Umum')

@section('content')
    @php
        $submittedItems = old('items', [['barang_id' => '', 'qty_diminta' => 1, 'catatan' => '']]);
        $oldItems = collect(is_array($submittedItems) ? $submittedItems : [])
            ->filter(static fn($item): bool => is_array($item))
            ->map(function (array $item) use ($barangs, $stokStoreTersedia): array {
                $barang = $barangs->firstWhere('id', $item['barang_id'] ?? null);

                return [
                    ...$item,
                    'query' => $barang ? "{$barang->kode} — {$barang->nama}" : '',
                    'stok_store_tersedia' => $barang ? (int) ($stokStoreTersedia[$barang->id] ?? 0) : 0,
                    'stok_satuan' => $barang?->satuan?->nama ?? 'unit',
                ];
            })
            ->values()
            ->all();
        if ($oldItems === []) {
            $oldItems[] = [
                'barang_id' => '',
                'qty_diminta' => 1,
                'catatan' => '',
                'query' => '',
                'stok_store_tersedia' => 0,
                'stok_satuan' => 'unit',
            ];
        }
        $activeTab = request()->query('tab') === 'riwayat_permintaan' ? 'riwayat_permintaan' : 'permintaan_baru';

        if (!$canCreate) {
            $activeTab = 'riwayat_permintaan';
        }
    @endphp

    <main class="gu-request-page" x-data="{ currentTab: @js($activeTab) }">
        <header class="gu-page-heading">
            <div>
                <h1 class="page-title">Permintaan Barang</h1>
                <p class="page-subtitle">Ajukan kebutuhan gudang ke Store dan pantau pemenuhan barang.</p>
            </div>
            <div class="gu-request-location">
                <span class="gu-request-location-code">{{ $gudang->kode }}</span>
                <span>{{ $gudang->nama }}</span>
                <span class="gu-request-location-divider" aria-hidden="true"></span>
                <span>Tujuan: {{ $gudangStore->nama }}</span>
            </div>
        </header>

        @if ($errors->any())
            <div class="gu-request-feedback is-error" role="alert">
                <strong>Periksa kembali data permintaan:</strong>
                <ul>
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="receipt-tabs" role="tablist" aria-label="Menu permintaan barang">
            @if ($canCreate)
                <button type="button" role="tab" id="request-create-tab"
                    :aria-selected="currentTab === 'permintaan_baru'" aria-controls="request-create-panel" class="btn"
                    :class="currentTab === 'permintaan_baru' ? 'btn-primary' : 'btn-outline'"
                    @click="currentTab = 'permintaan_baru'">
                    <span aria-hidden="true">＋</span> Permintaan Baru
                </button>
            @endif
            <button type="button" role="tab" id="request-history-tab"
                :aria-selected="currentTab === 'riwayat_permintaan'" aria-controls="request-history-panel" class="btn"
                :class="currentTab === 'riwayat_permintaan' ? 'btn-primary' : 'btn-outline'"
                @click="currentTab = 'riwayat_permintaan'">
                <span aria-hidden="true">◷</span> Riwayat Permintaan
            </button>
        </div>

        @if ($canCreate)
            <section id="request-create-panel" role="tabpanel" aria-labelledby="request-create-tab"
                x-show="currentTab === 'permintaan_baru'" x-cloak @scroll.window.capture="updateSearchPosition()"
                @resize.window="updateSearchPosition()"
                @click.window="if (!$event.target.closest('#request-create-panel') && !$event.target.closest('#gu-request-search-results')) closeBarangSearch()"
                x-data="{
                    searchUrl: @js(route('unit-bisnis.admin-gu.permintaan.barang-search')),
                    items: @js($oldItems),
                    activeItemIndex: null,
                    searchAnchor: null,
                    searchStyle: {},
                    init() {
                        this.items.forEach(item => {
                            item.query = item.query || '';
                            item.barang_id = item.barang_id || '';
                            item.stok_store_tersedia = Number(item.stok_store_tersedia || 0);
                            item.stok_satuan = item.stok_satuan || 'unit';
                            item.results = [];
                            item.loading = false;
                            item.hasSearched = false;
                            item.searchError = '';
                            item.searchRequestId = 0;
                            item.searchTimer = null;
                        });
                    },
                    addItem() {
                        if (this.items.length < 50) {
                            this.items.push({
                                barang_id: '',
                                qty_diminta: 1,
                                catatan: '',
                                query: '',
                                stok_store_tersedia: 0,
                                stok_satuan: 'unit',
                                results: [],
                                loading: false,
                                hasSearched: false,
                                searchError: '',
                                searchRequestId: 0,
                                searchTimer: null
                            });
                        }
                    },
                    openBarangSearch(index, event) {
                        this.activeItemIndex = index;
                        this.searchAnchor = event.currentTarget;
                        this.updateSearchPosition();
                
                        const item = this.items[index];
                        if (item.query.trim() !== '' && !item.barang_id && !item.hasSearched) {
                            this.searchBarang(index);
                        }
                    },
                    updateBarangSearch(index, event) {
                        const item = this.items[index];
                        clearTimeout(item.searchTimer);
                        this.activeItemIndex = index;
                        this.searchAnchor = event.currentTarget;
                        this.updateSearchPosition();
                        item.barang_id = '';
                        item.stok_store_tersedia = 0;
                        item.stok_satuan = 'unit';
                        item.results = [];
                        item.loading = false;
                        item.hasSearched = false;
                        item.searchError = '';
                        item.searchRequestId += 1;
                
                        if (item.query.trim() !== '') {
                            item.searchTimer = setTimeout(() => this.searchBarang(index), 200);
                        }
                    },
                    closeBarangSearch() {
                        this.activeItemIndex = null;
                        this.searchAnchor = null;
                    },
                    updateSearchPosition() {
                        if (this.activeItemIndex === null || !this.searchAnchor?.isConnected) return;
                
                        const rect = this.searchAnchor.getBoundingClientRect();
                        const viewportPadding = 8;
                        const gap = 4;
                        const width = Math.min(rect.width, window.innerWidth - viewportPadding * 2);
                        const left = Math.max(viewportPadding, Math.min(rect.left, window.innerWidth - width - viewportPadding));
                        const roomBelow = window.innerHeight - rect.bottom - viewportPadding;
                        const roomAbove = rect.top - viewportPadding;
                        const opensAbove = roomBelow < 220 && roomAbove > roomBelow;
                        const availableHeight = opensAbove ? roomAbove : roomBelow;
                
                        this.searchStyle = {
                            position: 'fixed',
                            left: `${left}px`,
                            width: `${width}px`,
                            top: opensAbove ? 'auto' : `${rect.bottom + gap}px`,
                            bottom: opensAbove ? `${window.innerHeight - rect.top + gap}px` : 'auto',
                            maxHeight: `${Math.max(80, Math.min(238, availableHeight))}px`,
                            zIndex: 100000,
                            backgroundColor: '#FFFFFF'
                        };
                    },
                    async searchBarang(index) {
                        const item = this.items[index];
                        const query = String(item?.query || '').trim();
                        if (!item) return;
                        if (query.length < 1) {
                            item.results = [];
                            item.hasSearched = false;
                            return;
                        }
                
                        const requestId = ++item.searchRequestId;
                        item.searchTimer = null;
                        item.loading = true;
                        item.hasSearched = true;
                        item.searchError = '';
                
                        const selectedIds = this.items
                            .filter((selectedItem, itemIndex) => itemIndex !== index && selectedItem.barang_id)
                            .map(selectedItem => String(selectedItem.barang_id));
                
                        try {
                            const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(query)}`, {
                                headers: { Accept: 'application/json' }
                            });
                            const payload = await response.json();
                
                            if (!response.ok) {
                                throw new Error(payload.errors?.q?.[0] || payload.message || 'Pencarian barang gagal.');
                            }
                            if (requestId !== item.searchRequestId || query !== item.query.trim()) return;
                
                            item.results = (Array.isArray(payload.data) ? payload.data : [])
                                .filter(barang => !selectedIds.includes(String(barang.id)));
                        } catch (error) {
                            if (requestId !== item.searchRequestId) return;
                
                            item.results = [];
                            item.searchError = error instanceof Error ?
                                error.message :
                                'Terjadi kesalahan saat mencari barang. Silakan coba lagi.';
                        } finally {
                            if (requestId === item.searchRequestId) {
                                item.loading = false;
                            }
                        }
                    },
                    highlightBarangMatch(value, query) {
                        const text = String(value ?? '');
                        const terms = String(query ?? '').trim().toLocaleLowerCase('id').split(/\s+/).filter(Boolean);
                        const escapeHtml = value => String(value).replace(/[&<>'\x22]/g, character => {
                            const entities = {
                                '&': 'amp',
                                '<': 'lt',
                                '>': 'gt',
                                '\x22': 'quot',
                                '\x27': '#039'
                            };
                
                            return String.fromCharCode(38) + entities[character] + ';';
                        });
                
                        if (terms.length === 0) return escapeHtml(text);
                
                        const escapeRegExp = value => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const matcher = new RegExp(terms.sort((first, second) => second.length - first.length).map(escapeRegExp).join('|'), 'gi');
                        let html = '';
                        let lastIndex = 0;
                        let match;
                
                        while ((match = matcher.exec(text)) !== null) {
                            html += escapeHtml(text.slice(lastIndex, match.index));
                            html += `<mark class='gu-request-match'>${escapeHtml(match[0])}</mark>`;
                            lastIndex = match.index + match[0].length;
                        }
                
                        return html + escapeHtml(text.slice(lastIndex));
                    },
                    selectBarang(index, barang) {
                        clearTimeout(this.items[index].searchTimer);
                        this.items[index].searchRequestId += 1;
                        this.items[index].barang_id = barang.id;
                        this.items[index].query = `${barang.kode} — ${barang.nama}`;
                        this.items[index].stok_store_tersedia = Number(barang.stok_tersedia || 0);
                        this.items[index].stok_satuan = barang.satuan || 'unit';
                        this.items[index].results = [];
                        this.items[index].hasSearched = false;
                        this.items[index].searchError = '';
                        this.closeBarangSearch();
                    }
                }">
                <form class="card gu-request-form" action="{{ route('unit-bisnis.admin-gu.permintaan.store') }}"
                    method="POST">
                    @csrf
                    <div class="card-header gu-request-card-heading">
                        <div>
                            <h2 class="card-title">Form Permintaan Baru</h2>
                            <p class="gu-request-description">Pilih barang dari katalog Store dan masukkan jumlah yang
                                dibutuhkan.</p>
                        </div>
                        <span class="badge badge-neutral">Status awal: Menunggu Store</span>
                    </div>

                    <div class="gu-request-doc-strip">
                        <div class="gu-request-doc-strip-main">
                            <span class="gu-doc-strip-label">
                                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <polyline points="10 9 9 9 8 9" />
                                </svg>
                                No. Permintaan / Request:
                            </span>
                            <span class="gu-doc-strip-number">{{ $nextNomorPr ?? '' }}</span>
                            <span class="gu-doc-strip-badge">Otomatis</span>
                        </div>
                        <div class="gu-request-doc-strip-date">
                            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            <span>{{ now()->translatedFormat('d M Y') }}</span>
                        </div>
                        <input type="hidden" name="nomor_pr" value="{{ old('nomor_pr', $nextNomorPr ?? '') }}">
                    </div>

                    <div class="gu-request-form-table-wrap">
                        <div class="gu-request-table-card">
                            <table class="data-table gu-request-form-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Barang</th>
                                        <th scope="col">Stok Store tersedia</th>
                                        <th scope="col">Jumlah</th>
                                        <th scope="col">Catatan barang <span>(opsional)</span></th>
                                        <th scope="col"><span class="sr-only">Aksi</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td data-label="Barang">
                                        <label class="sr-only" for="gu-request-item-0">Barang baris 1</label>
                                        <input id="gu-request-item-0" class="form-control" type="search"
                                            x-model="items[0].query" @focus="openBarangSearch(0, $event)"
                                            @input="updateBarangSearch(0, $event)"
                                            @keydown.escape.stop="closeBarangSearch()" placeholder="Cari nama/kode barang"
                                            autocomplete="off" role="combobox" aria-autocomplete="list"
                                            aria-haspopup="listbox"
                                            :aria-expanded="activeItemIndex === 0 && items[0].hasSearched && (items[0].loading || items[
                                                0].searchError || items[0].results.length > 0)"
                                            aria-controls="gu-request-search-results" required>
                                        <input type="hidden" name="items[0][barang_id]" x-model="items[0].barang_id">
                                    </td>
                                    <td class="gu-request-stock-cell" data-label="Stok Store tersedia">
                                        <template x-if="items[0].barang_id">
                                            <span class="gu-request-stock"
                                                :class="{ 'is-empty': items[0].stok_store_tersedia < 1 }">
                                                <strong x-text="items[0].stok_store_tersedia"></strong>
                                                <span x-text="items[0].stok_satuan"></span>
                                            </span>
                                        </template>
                                        <template x-if="!items[0].barang_id">
                                            <span class="gu-request-stock-placeholder">Pilih barang</span>
                                        </template>
                                    </td>
                                    <td data-label="Jumlah">
                                        <label class="sr-only" for="gu-request-qty-0">Jumlah barang baris 1</label>
                                        <input id="gu-request-qty-0" class="form-control gu-request-quantity" type="number"
                                            name="items[0][qty_diminta]" min="1" max="2147483647"
                                            value="{{ old('items.0.qty_diminta', 1) }}" x-model="items[0].qty_diminta"
                                            required>
                                    </td>
                                    <td data-label="Catatan barang">
                                        <label class="sr-only" for="gu-request-note-0">Catatan barang baris 1</label>
                                        <input id="gu-request-note-0" class="form-control" type="text"
                                            name="items[0][catatan]" value="{{ old('items.0.catatan') }}"
                                            x-model="items[0].catatan" maxlength="2000"
                                            placeholder="Tambahkan keterangan bila diperlukan">
                                    </td>
                                    <td class="gu-request-action-cell"></td>
                                </tr>
                                <template x-for="(item, index) in items.slice(1)" :key="index + 1">
                                    <tr>
                                        <td data-label="Barang">
                                            <label class="sr-only" :for="`gu-request-item-${index + 1}`"
                                                x-text="`Barang baris ${index + 2}`"></label>
                                            <input class="form-control" type="search"
                                                :id="`gu-request-item-${index + 1}`" x-model="item.query"
                                                @focus="openBarangSearch(index + 1, $event)"
                                                @input="updateBarangSearch(index + 1, $event)"
                                                @keydown.escape.stop="closeBarangSearch()"
                                                placeholder="Cari nama/kode barang" autocomplete="off" role="combobox"
                                                aria-autocomplete="list" aria-haspopup="listbox"
                                                :aria-expanded="activeItemIndex === index + 1 && item.hasSearched && (item.loading ||
                                                    item.searchError || item.results.length > 0)"
                                                aria-controls="gu-request-search-results" required>
                                            <input type="hidden" :name="`items[${index + 1}][barang_id]`"
                                                x-model="item.barang_id">
                                        </td>
                                        <td class="gu-request-stock-cell" data-label="Stok Store tersedia">
                                            <template x-if="item.barang_id">
                                                <span class="gu-request-stock"
                                                    :class="{ 'is-empty': item.stok_store_tersedia < 1 }">
                                                    <strong x-text="item.stok_store_tersedia"></strong>
                                                    <span x-text="item.stok_satuan"></span>
                                                </span>
                                            </template>
                                            <template x-if="!item.barang_id">
                                                <span class="gu-request-stock-placeholder">Pilih barang</span>
                                            </template>
                                        </td>
                                        <td data-label="Jumlah">
                                            <label class="sr-only" :for="`gu-request-qty-${index + 1}`"
                                                x-text="`Jumlah barang baris ${index + 2}`"></label>
                                            <input class="form-control gu-request-quantity" type="number"
                                                :id="`gu-request-qty-${index + 1}`"
                                                :name="`items[${index + 1}][qty_diminta]`" min="1"
                                                max="2147483647" x-model="items[index + 1].qty_diminta" required>
                                        </td>
                                        <td data-label="Catatan barang">
                                            <label class="sr-only" :for="`gu-request-note-${index + 1}`"
                                                x-text="`Catatan barang baris ${index + 2}`"></label>
                                            <input class="form-control" type="text"
                                                :id="`gu-request-note-${index + 1}`"
                                                :name="`items[${index + 1}][catatan]`" x-model="items[index + 1].catatan"
                                                maxlength="2000" placeholder="Tambahkan keterangan bila diperlukan">
                                        </td>
                                        <td class="gu-request-action-cell" data-label="Aksi">
                                            <button class="btn btn-outline gu-request-remove" type="button"
                                                @click="items.splice(index + 1, 1); closeBarangSearch()"
                                                :aria-label="`Hapus barang baris ${index + 2}`">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <div class="gu-request-form-actions">
                        <button class="btn btn-outline" type="button" @click="addItem()"
                            :disabled="items.length >= 50 || {{ $barangs->isEmpty() ? 'true' : 'false' }}">
                            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                            Tambah Barang
                        </button>
                        <span class="gu-request-form-hint">Maksimal 50 barang dalam satu permintaan.</span>
                    </div>

                    <template x-teleport="body">
                        <div id="gu-request-search-results" class="gu-request-search-results"
                            x-show="activeItemIndex !== null && items[activeItemIndex].hasSearched" x-cloak
                            :style="searchStyle" role="listbox" aria-label="Hasil pencarian barang">
                            <div class="gu-request-search-state"
                                x-show="activeItemIndex !== null && items[activeItemIndex].loading" role="status">
                                Sedang mencari barang...
                            </div>
                            <div class="gu-request-search-state is-error"
                                x-show="activeItemIndex !== null && !items[activeItemIndex].loading && items[activeItemIndex].searchError"
                                x-text="activeItemIndex !== null ? items[activeItemIndex].searchError : ''"
                                role="alert"></div>
                            <template x-if="activeItemIndex !== null">
                                <template x-for="barang in items[activeItemIndex].results" :key="barang.id">
                                    <button class="gu-request-search-option" type="button" role="option"
                                        @mousedown.prevent @click="selectBarang(activeItemIndex, barang)">
                                        <strong
                                            x-html="highlightBarangMatch(barang.nama, items[activeItemIndex].query)"></strong>
                                        <span>
                                            Kode: <span
                                                x-html="highlightBarangMatch(barang.kode, items[activeItemIndex].query)"></span>
                                            <span x-show="barang.kategori">
                                                · <span
                                                    x-html="highlightBarangMatch(barang.kategori, items[activeItemIndex].query)"></span>
                                            </span>
                                            · Stok: <strong :class="{ 'is-empty': barang.stok_tersedia < 1 }"
                                                x-text="barang.stok_tersedia"></strong>
                                            <span x-text="barang.satuan || ''"></span>
                                        </span>
                                    </button>
                                </template>
                            </template>
                            <div class="gu-request-search-empty"
                                x-show="activeItemIndex !== null && !items[activeItemIndex].loading && !items[activeItemIndex].searchError && items[activeItemIndex].results.length === 0"
                                role="status">
                                Tidak ada barang yang cocok dengan kata kunci pencarian.
                            </div>
                        </div>
                    </template>

                    <div class="gu-request-notes">
                        <label class="form-label" for="gu-request-general-note">Catatan permintaan
                            <span>(opsional)</span></label>
                        <textarea id="gu-request-general-note" class="form-control" name="catatan" rows="3" maxlength="2000"
                            placeholder="Contoh: kebutuhan untuk operasional bulan ini">{{ old('catatan') }}</textarea>
                        <p>Store akan memeriksa ketersediaan sebelum memproses permintaan. Pengajuan ini tidak langsung
                            mengurangi stok.</p>
                    </div>

                    @if ($barangs->isEmpty())
                        <p class="gu-request-catalog-empty" role="status">Katalog barang Store belum memiliki barang
                            aktif untuk diminta.</p>
                    @endif

                    <div class="gu-request-submit-row">
                        <button class="btn btn-primary gu-request-submit" type="submit" @disabled($barangs->isEmpty())>
                            Ajukan Permintaan
                        </button>
                    </div>
                </form>
            </section>
        @endif

        <section id="request-history-panel" role="tabpanel" aria-labelledby="request-history-tab"
            x-show="currentTab === 'riwayat_permintaan'" x-cloak x-data="{ expandedRequestId: null }">
            <div class="receipt-dashboard">
                <div class="receipt-dashboard-heading">
                    <div>
                        <h2>Riwayat Permintaan</h2>
                        <p>Cari permintaan dan lihat perkembangan pemenuhan dari Gudang Store.</p>
                    </div>
                </div>

                <form class="receipt-filter-bar gu-request-filter" method="GET"
                    action="{{ route('unit-bisnis.admin-gu.permintaan.index') }}">
                    <input type="hidden" name="tab" value="riwayat_permintaan">
                    @if (request()->filled('gudang_id'))
                        <input type="hidden" name="gudang_id" value="{{ request('gudang_id') }}">
                    @endif
                    <label class="search-box receipt-filter-search">
                        <svg class="search-icon icon-inline" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input class="search-input" type="search" id="gu-request-search" name="search"
                            value="{{ $search }}" placeholder="Cari nomor permintaan..."
                            aria-label="Cari nomor permintaan">
                    </label>
                    <label class="sr-only" for="gu-request-status">Filter berdasarkan status</label>
                    <select class="form-control" id="gu-request-status" name="status">
                        <option value="">Semua status</option>
                        <option value="baru" @selected($status === 'baru')>Menunggu Store</option>
                        <option value="diproses" @selected($status === 'diproses')>Dalam Pengiriman</option>
                        <option value="selesai" @selected($status === 'selesai')>Selesai</option>
                        <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
                    </select>
                    <div class="receipt-filter-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('unit-bisnis.admin-gu.permintaan.index', ['tab' => 'riwayat_permintaan']) }}"
                            class="btn btn-outline">Reset</a>
                    </div>
                </form>

                <div class="receipt-history-section-title">
                    <h3>Daftar Permintaan</h3>
                    <span>{{ number_format($permintaans->total(), 0, ',', '.') }} permintaan</span>
                </div>

                @if ($permintaans->count())
                    @php
                        $barangsById = $barangs->keyBy('id');
                    @endphp
                    <div class="gu-request-history-list">
                        @foreach ($permintaans as $permintaan)
                            @php
                                [$statusLabel, $statusClass] = match ($permintaan->status) {
                                    'baru' => ['Menunggu Store', 'badge-warning'],
                                    'diproses' => ['Dalam Pengiriman', 'badge-brand'],
                                    'selesai' => ['Selesai', 'badge-success'],
                                    'ditolak' => ['Ditolak', 'badge-critical'],
                                    default => [$permintaan->status, 'badge-neutral'],
                                };
                                $detailsPanelId = "gu-request-details-{$permintaan->id}";
                            @endphp
                            <article class="gu-request-history-card">
                                <div class="gu-request-history-card-header">
                                    <div class="gu-request-history-summary">
                                        <h3>Rincian Permintaan <span>{{ $permintaan->nomor_pr }}</span></h3>
                                        <p>
                                            Diajukan dari <strong>{{ $gudang->nama }}</strong>
                                            pada {{ $permintaan->created_at->translatedFormat('d M Y, H:i') }}
                                        </p>
                                    </div>
                                    <div class="gu-request-history-card-controls">
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                        <button type="button" class="btn btn-outline gu-request-detail-toggle"
                                            @click="expandedRequestId = expandedRequestId === {{ $permintaan->id }} ? null : {{ $permintaan->id }}"
                                            :aria-expanded="expandedRequestId === {{ $permintaan->id }}"
                                            aria-controls="{{ $detailsPanelId }}">
                                            <span>Detail Barang</span>
                                            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                :class="{ 'is-expanded': expandedRequestId === {{ $permintaan->id }} }">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div id="{{ $detailsPanelId }}" class="gu-request-history-details"
                                    x-show="expandedRequestId === {{ $permintaan->id }}" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 transform -translate-y-1"
                                    x-transition:enter-end="opacity-100 transform translate-y-0">
                                    <div class="gu-request-details-table-wrap" tabindex="0" role="region"
                                        aria-label="Rincian barang permintaan {{ $permintaan->nomor_pr }}">
                                        <table class="gu-request-details-table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Nama barang</th>
                                                    <th scope="col">Kategori</th>
                                                    <th scope="col">Satuan</th>
                                                    <th scope="col">Qty diminta</th>
                                                    <th scope="col">Stok Store tersedia</th>
                                                    <th scope="col">Status item</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($permintaan->details as $detail)
                                                    @php
                                                        $catalogBarang = $barangsById->get($detail->barang_id);
                                                        $qtyDiminta = (int) $detail->qty_diminta;
                                                        $qtyDiproses = (int) $detail->qty_diproses;
                                                        [$itemStatusLabel, $itemStatusClass] = match (true) {
                                                            $permintaan->status === 'ditolak' => [
                                                                'Ditolak',
                                                                'badge-critical',
                                                            ],
                                                            $qtyDiproses >= $qtyDiminta => [
                                                                'Dikirim Store',
                                                                'badge-success',
                                                            ],
                                                            $qtyDiproses > 0 => ['Dikirim sebagian', 'badge-warning'],
                                                            default => ['Menunggu Store', 'badge-neutral'],
                                                        };
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $detail->barang?->nama ?? 'Barang tidak tersedia' }}</strong>
                                                            @if ($detail->barang?->kode)
                                                                <span
                                                                    class="gu-request-item-code">{{ $detail->barang->kode }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($catalogBarang?->kategori?->nama)
                                                                <span
                                                                    class="gu-request-category">{{ $catalogBarang->kategori->nama }}</span>
                                                            @else
                                                                <span class="gu-request-time">—</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="gu-request-unit">
                                                                {{ $detail->satuan?->nama ?? ($detail->barang?->satuan?->nama ?? '—') }}
                                                            </span>
                                                        </td>
                                                        <td class="tabular">{{ number_format($qtyDiminta, 0, ',', '.') }}
                                                        </td>
                                                        <td class="tabular">
                                                            @if ($catalogBarang)
                                                                <strong class="gu-request-stock-value">
                                                                    {{ number_format((int) ($stokStoreTersedia[$detail->barang_id] ?? 0), 0, ',', '.') }}
                                                                </strong>
                                                            @else
                                                                <span class="gu-request-time">—</span>
                                                            @endif
                                                        </td>
                                                        <td><span
                                                                class="badge {{ $itemStatusClass }}">{{ $itemStatusLabel }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if ($permintaan->catatan || ($permintaan->status === 'ditolak' && $permintaan->alasan_penolakan))
                                        <div class="gu-request-history-card-footer">
                                            @if ($permintaan->catatan)
                                                <p><strong>Catatan:</strong> {{ $permintaan->catatan }}</p>
                                            @endif
                                            @if ($permintaan->status === 'ditolak' && $permintaan->alasan_penolakan)
                                                <p class="gu-request-rejection">
                                                    <strong>Alasan penolakan:</strong> {{ $permintaan->alasan_penolakan }}
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="card receipt-history-empty">
                        <strong>Belum ada permintaan barang.</strong>
                        <span>Permintaan yang Anda ajukan akan tampil di sini.</span>
                        @if ($canCreate)
                            <button type="button" class="btn btn-primary gu-request-empty-action"
                                @click="currentTab = 'permintaan_baru'">
                                Buat permintaan
                            </button>
                        @endif
                    </div>
                @endif

                @if ($permintaans->hasPages())
                    <div class="gu-request-pagination">{{ $permintaans->links() }}</div>
                @endif
            </div>
        </section>
    </main>

    <style>
        .gu-request-page {
            --gu-ink: var(--text-primary);
            --gu-muted: var(--text-muted);
            --gu-border: var(--surface-border);
            --gu-surface: var(--surface-card);
            --gu-background: var(--surface-base);
            --gu-primary: var(--brand-primary);
            display: grid;
            min-width: 0;
            gap: 16px;
            color: var(--gu-ink);
        }

        .gu-request-page>* {
            min-width: 0;
        }

        .gu-request-page .gu-page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin: 0;
        }

        .gu-request-page .gu-page-heading>div:first-child {
            min-width: 0;
        }

        .gu-request-page .gu-page-heading h1 {
            margin: 0;
            color: var(--gu-ink);
            font-size: 24px;
            font-weight: 850;
            line-height: 1.2;
        }

        .gu-request-page .gu-page-heading p {
            margin: 4px 0 0;
            color: var(--gu-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .gu-request-page .receipt-dashboard {
            display: grid;
            min-width: 0;
            gap: 16px;
        }

        .gu-request-page .receipt-dashboard-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
        }

        .gu-request-page .receipt-dashboard-heading h2 {
            margin: 0;
            color: var(--gu-ink);
            font-size: 20px;
            font-weight: 850;
        }

        .gu-request-page .receipt-dashboard-heading p {
            margin: 4px 0 0;
            color: var(--gu-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .gu-request-page .receipt-filter-bar {
            display: grid;
            grid-template-columns: minmax(190px, 1.5fr) minmax(150px, 1fr) auto;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius-lg);
            background: var(--gu-surface);
        }

        .gu-request-page .receipt-filter-bar .form-control {
            min-width: 0;
            min-height: 44px;
            padding: 8px 11px;
            border-radius: var(--radius);
            font-size: .82rem;
        }

        .gu-request-page .receipt-filter-bar .receipt-filter-search {
            width: 100%;
            min-width: 0;
        }

        .gu-request-page .receipt-filter-bar .receipt-filter-search .search-input {
            width: 100%;
            min-width: 0;
            height: 44px;
            font-size: .82rem;
        }

        .gu-request-page .receipt-filter-actions {
            display: flex;
            gap: 8px;
        }

        .gu-request-page .receipt-history-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 4px 0 0;
        }

        .gu-request-page .receipt-history-section-title h3 {
            margin: 0;
            color: var(--gu-ink);
            font-size: 16px;
            font-weight: 850;
        }

        .gu-request-page .receipt-history-section-title>span {
            color: var(--text-muted);
            font-size: .75rem;
        }

        .gu-request-page .receipt-history-table-wrap {
            max-width: 100%;
            overflow-x: auto;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--gu-surface);
        }

        .gu-request-page .receipt-history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .gu-request-page .receipt-history-table th,
        .gu-request-page .receipt-history-table td {
            padding: 10px 11px;
            border-bottom: 1px solid var(--gu-border);
            font-size: .8rem;
            vertical-align: top;
            text-align: left;
        }

        .gu-request-page .receipt-history-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            color: var(--gu-muted);
            background: var(--gu-background);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .gu-request-page .receipt-history-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-request-page .receipt-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            border-bottom: 2px solid var(--gu-border);
        }

        .gu-request-page .receipt-tabs .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            flex: 0 0 auto;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .gu-request-location {
            display: flex;
            min-width: 0;
            min-height: 40px;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 6px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--gu-surface);
            color: var(--gu-muted);
            font-size: .78rem;
        }

        .gu-request-location>span:not(.gu-request-location-divider) {
            overflow-wrap: anywhere;
        }

        .gu-request-location-code {
            color: var(--brand-primary);
            font-weight: 800;
        }

        .gu-request-location-divider {
            width: 1px;
            height: 16px;
            background: var(--surface-border);
        }

        .gu-request-feedback {
            margin: 0;
            padding: 12px 16px;
            border: 1px solid;
            border-radius: var(--radius-lg);
            font-size: .84rem;
            line-height: 1.5;
        }

        .gu-request-feedback ul {
            margin: 6px 0 0 20px;
            list-style: disc;
        }

        .gu-request-feedback.is-success {
            border-color: var(--status-success);
            background: rgba(63, 122, 82, .08);
            color: var(--status-success);
        }

        .gu-request-feedback.is-error {
            border-color: var(--status-critical);
            background: rgba(193, 68, 60, .08);
            color: var(--status-critical);
        }

        .gu-request-card-heading {
            display: flex;
            min-height: 68px;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin: 0;
            padding: 14px 16px;
            border-bottom: 1px solid var(--gu-border);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            background: var(--gu-surface);
        }

        .gu-request-doc-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 10px 16px;
            margin: 16px 16px 16px 16px;
            background: #F8FAFC;
            border: 1px solid var(--gu-border, #E2E8F0);
            border-radius: var(--radius-md, 8px);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }

        .gu-request-doc-strip-main {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .gu-doc-strip-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .gu-doc-strip-number {
            display: inline-block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--brand-primary, #145C54);
            background: #FFFFFF;
            padding: 3px 8px;
            border-radius: var(--radius, 4px);
            border: 1px solid #CBD5E1;
            letter-spacing: 0.03em;
        }

        .gu-doc-strip-badge {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 4px;
            background: rgba(20, 92, 84, 0.08);
            color: var(--brand-primary, #145C54);
            border: 1px solid rgba(20, 92, 84, 0.2);
            letter-spacing: 0.03em;
        }

        .gu-request-doc-strip-date {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748B;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .gu-request-form {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
            padding: 0;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius-lg);
            background: var(--gu-surface);
            box-shadow: none;
        }

        .gu-request-description {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: .875rem;
            line-height: 1.5;
        }

        .gu-request-form-table-wrap {
            width: 100%;
            max-width: 100%;
            margin-top: 0;
            padding: 0 16px;
        }

        .gu-request-table-card {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--gu-border, #E2E8F0);
            border-radius: var(--radius-md, 8px);
            background: #FFFFFF;
        }

        .gu-request-form-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .gu-request-form-table th:nth-child(1) {
            width: 35%;
        }

        .gu-request-form-table th:nth-child(2) {
            width: 17%;
        }

        .gu-request-form-table th:nth-child(3) {
            width: 12%;
        }

        .gu-request-form-table th:nth-child(4) {
            width: 30%;
        }

        .gu-request-form-table th:nth-child(5) {
            width: 6%;
        }

        .gu-request-form-table th {
            padding: 10px 10px;
            border-bottom: 1px solid var(--gu-border);
            background: #F8FAFC;
            color: var(--gu-muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .035em;
            text-transform: uppercase;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .gu-request-form-table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--gu-border);
            vertical-align: top;
        }

        .gu-request-form-table tr:last-child td {
            border-bottom: none;
        }

        .gu-request-form-table th span {
            color: var(--text-muted);
            font-weight: 400;
            text-transform: none;
        }

        .gu-request-form-table .form-control {
            width: 100%;
            min-width: 0;
            min-height: 44px;
            padding: 8px 10px;
            border-radius: var(--radius);
            font-size: .84rem;
        }

        .gu-request-form-table .gu-request-quantity {
            min-width: 0;
        }

        .gu-request-form-table td:first-child .form-control {
            min-width: 0;
        }

        .gu-request-stock-cell {
            text-align: center;
            overflow-wrap: anywhere;
        }

        .gu-request-action-cell {
            text-align: center;
        }

        .gu-request-action-cell .gu-request-remove {
            width: 100%;
            min-width: 0;
            padding-right: 5px;
            padding-left: 5px;
        }

        .gu-request-stock {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            color: var(--status-success);
            font-size: .82rem;
        }

        .gu-request-stock.is-empty,
        .gu-request-search-stock strong.is-empty {
            color: var(--status-critical);
        }

        .gu-request-stock-placeholder {
            color: var(--text-muted);
            font-size: .78rem;
        }

        .gu-request-search-results {
            box-sizing: border-box;
            max-height: 238px;
            overflow-y: auto;
            border: 1px solid var(--surface-border, #CBD5E1);
            border-radius: var(--radius, 8px);
            background: #FFFFFF !important;
            background-color: #FFFFFF !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .15), 0 8px 10px -6px rgba(0, 0, 0, .1);
            scrollbar-color: var(--surface-border, #CBD5E1) #F8FAFC;
            scrollbar-width: thin;
        }

        .gu-request-search-results::-webkit-scrollbar {
            width: 10px;
        }

        .gu-request-search-results::-webkit-scrollbar-track {
            background: #F8FAFC;
        }

        .gu-request-search-results::-webkit-scrollbar-thumb {
            border: 2px solid #F8FAFC;
            border-radius: var(--radius-lg, 8px);
            background: var(--surface-border, #CBD5E1);
        }

        .gu-request-search-option {
            display: block;
            width: 100%;
            min-height: 56px;
            padding: 8px 12px;
            border: 0;
            border-bottom: 1px solid var(--surface-border, #E2E8F0);
            background: #FFFFFF !important;
            background-color: #FFFFFF !important;
            color: var(--text-primary, #0F172A);
            cursor: pointer;
            text-align: left;
        }

        .gu-request-search-option:hover,
        .gu-request-search-option:focus-visible {
            background: #F8FAFC !important;
            background-color: #F8FAFC !important;
        }

        .gu-request-search-option strong,
        .gu-request-search-option>span {
            display: block;
        }

        .gu-request-search-option strong {
            font-size: .82rem;
            line-height: 1.4;
        }

        .gu-request-search-option>span {
            margin-top: 2px;
            color: var(--text-muted);
            font-size: .72rem;
            line-height: 1.35;
        }

        .gu-request-search-option .gu-request-search-stock {
            color: var(--text-muted);
        }

        .gu-request-search-stock strong {
            color: var(--status-success);
        }

        .gu-request-match {
            padding: 0 1px;
            border-radius: 2px;
            background: color-mix(in srgb, var(--brand-accent) 22%, var(--surface-card));
            color: var(--text-primary);
            font-weight: 700;
        }

        .gu-request-search-empty {
            padding: 16px 14px;
            background: #FFFFFF !important;
            background-color: #FFFFFF !important;
            color: var(--text-muted, #64748B);
            font-size: .84rem;
            text-align: center;
        }

        .gu-request-search-state {
            padding: 16px 14px;
            background: #FFFFFF !important;
            background-color: #FFFFFF !important;
            color: var(--text-muted, #64748B);
            font-size: .84rem;
            text-align: center;
        }

        .gu-request-search-state.is-error {
            color: var(--status-critical);
        }

        .gu-request-remove {
            min-height: 44px;
            color: var(--status-critical);
        }

        .gu-request-form-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            padding: 12px 16px 0;
        }

        .gu-request-form-actions .btn,
        .gu-request-submit {
            min-height: 44px;
        }

        .gu-request-form-actions .btn {
            min-width: 152px;
        }

        .gu-request-form-hint,
        .gu-request-notes p,
        .gu-request-time {
            color: var(--text-muted);
            font-size: .75rem;
        }

        .gu-request-notes {
            display: grid;
            gap: 7px;
            margin: 18px 16px 0;
        }

        .gu-request-notes .form-label span {
            color: var(--text-muted);
            font-weight: 400;
        }

        .gu-request-notes p {
            margin: 0;
        }

        .gu-request-catalog-empty {
            margin: 14px 0 0;
            color: var(--status-critical);
        }

        .gu-request-submit-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--gu-border);
            padding-bottom: 16px;
        }

        .gu-request-filter {
            grid-template-columns: minmax(190px, 1.5fr) minmax(150px, 1fr) auto;
        }

        .gu-request-filter .form-control {
            min-width: 0;
            min-height: 44px;
            font-size: .82rem;
        }

        .gu-request-history-list {
            display: grid;
            gap: 10px;
        }

        .gu-request-history-card {
            min-width: 0;
            padding: 14px 16px;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius-lg);
            background: var(--gu-surface);
        }

        .gu-request-history-card-header,
        .gu-request-history-card-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .gu-request-history-card-controls {
            flex-shrink: 0;
            justify-content: flex-end;
        }

        .gu-request-history-summary {
            min-width: 0;
        }

        .gu-request-history-summary h3 {
            margin: 0 0 3px;
            color: var(--text-primary);
            font-size: .95rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .gu-request-history-summary h3 span {
            color: var(--brand-primary);
        }

        .gu-request-history-summary p {
            margin: 0;
            color: var(--text-muted);
            font-size: .76rem;
        }

        .gu-request-detail-toggle {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            font-size: .78rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .gu-request-detail-toggle svg {
            transition: transform .18s ease;
        }

        .gu-request-detail-toggle svg.is-expanded {
            transform: rotate(180deg);
        }

        .gu-request-history-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--surface-border);
        }

        .gu-request-details-table-wrap {
            max-width: 100%;
            overflow-x: auto;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--gu-surface);
            scrollbar-width: thin;
        }

        .gu-request-details-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        .gu-request-details-table th {
            padding: 10px 11px;
            border-bottom: 1px solid var(--gu-border);
            background: var(--gu-background);
            color: var(--gu-muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .045em;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .gu-request-details-table th:nth-child(n + 3),
        .gu-request-details-table td:nth-child(n + 3) {
            text-align: center;
        }

        .gu-request-details-table td {
            padding: 10px 11px;
            border-bottom: 1px solid var(--gu-border);
            color: var(--gu-ink);
            font-size: .8rem;
            vertical-align: middle;
        }

        .gu-request-details-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-request-details-table td:first-child strong {
            display: block;
        }

        .gu-request-category,
        .gu-request-unit {
            display: inline-block;
            padding: 3px 9px;
            border-radius: var(--radius);
            background: var(--gu-background);
            color: var(--text-muted);
            font-size: .7rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .gu-request-category {
            background: color-mix(in srgb, var(--brand-primary) 9%, var(--surface-card));
            color: var(--brand-primary);
        }

        .gu-request-stock-value {
            color: var(--status-success);
        }

        .gu-request-history-card-footer {
            display: grid;
            gap: 6px;
            margin-top: 9px;
        }

        .gu-request-history-card-footer p {
            width: fit-content;
            max-width: 100%;
            margin: 0;
            padding: 7px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--gu-background);
            color: var(--text-muted);
            font-size: .76rem;
            overflow-wrap: anywhere;
        }

        .gu-request-history-card-footer .gu-request-rejection {
            color: var(--status-critical);
        }

        .gu-request-item-note,
        .gu-request-rejection,
        .gu-request-item-name,
        .gu-request-item-code,
        .gu-request-quantity-line {
            display: block;
        }

        .gu-request-item-note,
        .gu-request-rejection,
        .gu-request-item-code {
            margin-top: 3px;
            font-size: .72rem;
            overflow-wrap: anywhere;
        }

        .gu-request-rejection {
            color: var(--status-critical);
        }

        .gu-request-item-code,
        .gu-request-time {
            color: var(--text-muted);
        }

        .gu-request-quantity-line+.gu-request-quantity-line {
            margin-top: 5px;
        }

        .gu-request-empty-action {
            margin: 12px auto 0;
        }

        .gu-request-page .receipt-history-empty {
            display: grid;
            justify-items: center;
            gap: 6px;
            padding: 28px 16px;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius-lg);
            background: var(--gu-surface);
            color: var(--gu-ink);
            text-align: center;
            box-shadow: none;
        }

        .gu-request-page .receipt-history-empty>span {
            color: var(--gu-muted);
            font-size: .84rem;
            line-height: 1.5;
        }

        .gu-request-pagination {
            max-width: 100%;
            min-width: 0;
            overflow-x: auto;
        }

        .gu-request-page [role="tab"]:focus-visible,
        .gu-request-page button:focus-visible,
        .gu-request-page a:focus-visible,
        .gu-request-page input:focus-visible,
        .gu-request-page select:focus-visible,
        .gu-request-page textarea:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        @media (max-width: 900px) {
            .gu-request-filter {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .gu-request-page .gu-page-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .gu-request-location {
                width: 100%;
            }

            .gu-request-history-card-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .gu-request-history-card-controls {
                width: 100%;
                justify-content: space-between;
            }

            .gu-request-details-table {
                min-width: 680px;
            }

            .gu-request-table-card {
                border: none;
                border-radius: 0;
                background: transparent;
            }

            .gu-request-form-table,
            .gu-request-form-table tbody {
                display: block;
                width: 100%;
            }

            .gu-request-form-table thead {
                position: absolute;
                width: 1px;
                height: 1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                clip-path: inset(50%);
            }

            .gu-request-form-table tbody {
                display: grid;
                gap: 10px;
            }

            .gu-request-form-table tbody tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                padding: 10px;
                border: 1px solid var(--surface-border);
                border-radius: var(--radius-lg);
                background: var(--gu-surface);
            }

            .gu-request-form-table td {
                display: grid;
                min-width: 0;
                gap: 4px;
                padding: 0;
                border: 0;
                text-align: left;
            }

            .gu-request-form-table td::before {
                content: attr(data-label);
                color: var(--text-muted);
                font-size: .7rem;
                font-weight: 700;
            }

            .gu-request-form-table td:first-child,
            .gu-request-form-table td:nth-child(4),
            .gu-request-form-table .gu-request-action-cell {
                grid-column: 1 / -1;
            }

            .gu-request-form-table .gu-request-stock-cell {
                justify-items: start;
            }

            .gu-request-form-table .gu-request-action-cell:empty {
                display: none;
            }

            .gu-request-form-table .gu-request-remove {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .gu-request-filter {
                grid-template-columns: minmax(0, 1fr);
            }

            .gu-request-filter .receipt-filter-search,
            .gu-request-filter .receipt-filter-actions {
                grid-column: 1 / -1;
            }

            .gu-request-location {
                width: 100%;
            }

            .gu-request-filter .receipt-filter-search {
                width: 100%;
            }

            .gu-request-filter .receipt-filter-actions {
                display: flex;
            }

            .gu-request-filter .receipt-filter-actions .btn {
                flex: 1;
                min-height: 44px;
            }

            .gu-request-page .receipt-filter-bar .receipt-filter-search .search-input,
            .gu-request-page .receipt-filter-bar .form-control {
                height: 44px;
                min-height: 44px;
                font-size: 16px;
            }

            .gu-request-form-table .form-control {
                width: 100%;
                min-height: 44px;
                font-size: 16px;
            }

            .gu-request-notes .form-control {
                min-height: 44px;
                font-size: 16px;
            }

            .gu-request-notes textarea.form-control {
                min-height: 96px;
            }

            .gu-request-form-actions {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .gu-request-form-actions .btn {
                width: 100%;
            }

            .gu-request-card-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .gu-request-submit-row .btn {
                width: 100%;
            }
        }

        @media (max-width: 520px) {
            .gu-request-page .receipt-tabs .btn {
                flex: 1 1 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .gu-request-page [x-transition],
            .gu-request-page .gu-request-detail-toggle svg {
                transition-duration: .01ms !important;
            }
        }
    </style>
@endsection
