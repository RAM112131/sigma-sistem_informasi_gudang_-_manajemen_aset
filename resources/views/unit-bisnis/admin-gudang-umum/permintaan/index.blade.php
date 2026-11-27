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

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/admin-gudang-umum/permintaan/index.css')
@endpush
@endsection
