@extends('layouts.app')

@section('title', 'Stok Barang ' . ($activeGudang?->nama ?? 'Gudang Unit') . ' — ' . config('app.name', 'SIGMA'))

@php
    $u = auth()->user();
    $canReadStok =
        $u?->hasAnyRole([
            'Super Admin',
            'super_admin',
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Recording Gudang Umum',
            'recording_gudang_umum',
        ]) ||
        $u?->can('stokunitbisnis.read') ||
        $u?->can('stokunitbisnis.view') ||
        $u?->can('barang.read') ||
        $u?->can('barang.view');
    $canImportStok =
        $u?->hasAnyRole([
            'Super Admin',
            'super_admin',
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Recording Gudang Umum',
            'recording_gudang_umum',
        ]) ||
        $u?->can('stokunitbisnis.create') ||
        $u?->can('stok_awal.import');
    $canEditStok =
        $u?->hasAnyRole([
            'Super Admin',
            'super_admin',
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Recording Gudang Umum',
            'recording_gudang_umum',
        ]) ||
        $u?->can('stokunitbisnis.edit') ||
        $u?->can('stokunitbisnis.update');
    $canDeleteStok =
        $u?->hasAnyRole(['Super Admin', 'super_admin', 'Admin Gudang Umum', 'admin_gudang_umum']) ||
        $u?->can('stokunitbisnis.delete');

@endphp



@section('content')
    <div x-data="{
        currentTab: '{{ !$canReadStok && $canImportStok ? 'import_data_awal' : $activeTab ?? 'stok_saat_ini' }}',
        stockSearchLoading: false,
        stockSearchMessage: '',
        stockSearchController: null,
        stockCardOpen: false,
        stockCardLoading: false,
        stockCardError: '',
        stockCard: null,
        stockEditOpen: false,
        stockEditTrigger: null,
        stockDeleteOpen: false,
        stockDeleteTrigger: null,
        stockDeleteForm: {
            action: '',
            name: ''
        },
        stockEditForm: {
            id: '',
            action: '',
            kode: '',
            nama: '',
            kategori_id: '',
            satuan_id: '',
            minimum_stok: '',
            harga_satuan: '',
            deskripsi: ''
        },
        openStockEdit(source) {
            const data = source.dataset;
            this.stockEditForm = {
                id: data.id || '',
                action: data.action || '',
                kode: data.kode || '',
                nama: data.nama || '',
                kategori_id: data.kategoriId || '',
                satuan_id: data.satuanId || '',
                minimum_stok: data.minimumStok || '',
                harga_satuan: data.hargaSatuan || '',
                deskripsi: data.deskripsi || ''
            };
            this.stockEditTrigger = source.matches('.physical-stock-action') ? source : null;
            this.stockEditOpen = true;
            this.$nextTick(() => this.$refs.stockEditCode?.focus());
        },
        closeStockEdit() {
            const trigger = this.stockEditTrigger;
            this.stockEditOpen = false;
            this.stockEditTrigger = null;
            this.$nextTick(() => trigger?.focus());
        },
        openStockDelete(source) {
            this.stockDeleteForm = {
                action: source.dataset.action || '',
                name: source.dataset.name || 'barang'
            };
            this.stockDeleteTrigger = source;
            this.stockDeleteOpen = true;
            this.$nextTick(() => this.$refs.stockDeleteCancel?.focus());
        },
        closeStockDelete() {
            const trigger = this.stockDeleteTrigger;
            this.stockDeleteOpen = false;
            this.stockDeleteTrigger = null;
            this.$nextTick(() => trigger?.focus());
        },
        async searchUnitStock(searchTerm, allowShortSearch = false) {
            const normalizedSearch = searchTerm.trim();
            this.stockSearchController?.abort();
            if (!allowShortSearch && normalizedSearch.length > 0 && normalizedSearch.length < 3) {
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
                const source = doc.querySelector('#unit-stock-results');
                const target = document.querySelector('#unit-stock-results');
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
        },
        async openStockCard(url) {
            this.stockCardOpen = true;
            this.stockCardLoading = true;
            this.stockCardError = '';
            this.stockCard = null;
            await this.$nextTick();
            this.$refs.stockCardClose?.focus();
    
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error(`Kartu stok gagal dimuat (HTTP ${response.status}).`);
    
                const payload = await response.json();
                if (!payload.barang || !Array.isArray(payload.mutasi)) {
                    throw new Error('Data kartu stok tidak lengkap.');
                }
    
                this.stockCard = payload;
            } catch (error) {
                this.stockCardError = error.message || 'Kartu stok gagal dimuat. Silakan coba lagi.';
            } finally {
                this.stockCardLoading = false;
            }
        },
        closeStockCard() {
            this.stockCardOpen = false;
            this.stockCard = null;
            this.stockCardError = '';
        },
        formatStockCardDate(value) {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
    
            return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
        },
        formatStockCardQuantity(value) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value) || 0);
        }
    }">

        <header class="physical-stock-header">
            <div>
                <h1>Stok Barang Fisik</h1>
                <p>Kelola stok fisik dan data stok awal pada gudang aktif.</p>
            </div>
        </header>

        @if (isset($gudangUnits) && $gudangUnits->count() > 1)
            <div class="filter-actions"
                style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                <form action="{{ route('unit-bisnis.stok.index') }}" method="GET" style="display:inline-flex;">
                    <input type="hidden" name="tab" :value="currentTab">
                    <select name="gudang_id" class="select-filter" onchange="this.form.submit()" style="font-weight: 600;">
                        @foreach ($gudangUnits as $gu)
                            <option value="{{ $gu->id }}"
                                {{ (int) $activeGudang?->id === (int) $gu->id ? 'selected' : '' }}>
                                {{ $gu->nama }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endif

        {{-- Tab Navigation --}}
        <div style="display:flex;gap:8px;border-bottom:2px solid var(--surface-border);margin-bottom:20px;">
            @if ($canReadStok)
                <button type="button" class="btn"
                    :class="currentTab === 'stok_saat_ini' ? 'btn-primary' : 'btn-outline'"
                    @click="currentTab='stok_saat_ini'" style="border-bottom-left-radius:0;border-bottom-right-radius:0;">
                    <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" class="icon-inline">
                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                    </svg>
                    Stok Real-Time
                </button>
            @endif
            @if ($canImportStok)
                <button type="button" class="btn"
                    :class="currentTab === 'import_data_awal' ? 'btn-primary' : 'btn-outline'"
                    @click="currentTab='import_data_awal'"
                    style="border-bottom-left-radius:0;border-bottom-right-radius:0;">
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

        {{-- Tab Stok Real-Time --}}
        @include('unit-bisnis.stok.realtime')

        {{-- ═══════════════════════════════════════════ TAB 2: IMPORT WIZARD ═══ --}}
        @if ($canImportStok)
            <div x-show="currentTab === 'import_data_awal'" x-cloak x-data="importGridFlow(@js($importCatalogItems ?? []))" x-init="init()">

                {{-- PHASE 0: PASTE DATA --}}
                <div x-show="phase === 0" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Input Stok Awal</h2>
                                <p style="font-size:.82rem;color:var(--text-muted);margin:2px 0 0;">Import data barang sesuai template atau Isi manual di kolom yang telah disediakan</p>
                            </div>
                            <a href="{{ route('unit-bisnis.stok.import-template') }}" class="btn btn-accent btn-sm"
                                data-turbo="false">
                                <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" class="icon-inline">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="7 10 12 15 17 10" />
                                    <line x1="12" y1="15" x2="12" y2="3" />
                                </svg>
                                Unduh Template CSV
                            </a>
                        </div>

                        {{-- Info strip --}}
                        <div
                            style="margin-top:14px;padding:12px 14px;background:rgba(20,92,84,.05);border:1px solid rgba(20,92,84,.15);border-radius:var(--radius);font-size:.79rem;color:var(--text-muted);">
                            <strong style="color:var(--brand-primary);">Kolom template:</strong>
                            <code style="margin-left:6px;">kode_barang · nama_barang · kategori · satuan · qty · harga_satuan ·
                                minimum_stok · catatan</code>
                        </div>
                    </div>

                    <section class="card stock-paste-card" aria-labelledby="unit-paste-heading">
                        <div class="stock-paste-heading">
                            <div class="stock-paste-tools">
                                <button type="button" class="btn btn-outline btn-sm" @click="addPasteRows()">Tambah 20
                                    baris</button>
                                <button type="button" class="btn btn-outline btn-sm" @click="clearPasteGrid()">Kosongkan
                                    Tabel</button>
                            </div>
                        </div>

                        <datalist id="unit-paste-categories">
                            @foreach ($kategoriList as $kategori)
                                <option value="{{ $kategori->nama }}"></option>
                            @endforeach
                        </datalist>
                        <datalist id="unit-paste-units">
                            @foreach ($satuanList as $satuan)
                                <option value="{{ $satuan->nama }}"></option>
                            @endforeach
                        </datalist>

                        <div class="stock-paste-grid-wrap">
                            <table class="stock-paste-grid" aria-label="Tabel input stok awal">
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
                                            <td><input type="text" x-model="row.kode_barang" placeholder="SKH-001"
                                                    autocomplete="off"
                                                    @input="scheduleGridValidation(); scheduleCatalogSearch($event.target, pasteRowIndex(visibleRowIndex), 'kode_barang', row.kode_barang)"
                                                    @focus="handleCatalogFocus($event.target, pasteRowIndex(visibleRowIndex), 'kode_barang', row.kode_barang)"
                                                    @keydown="handleCatalogKeydown($event, pasteRowIndex(visibleRowIndex))"
                                                    @blur="handleCatalogBlur(pasteRowIndex(visibleRowIndex), 'kode_barang')"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 0)"></td>
                                            <td><input type="text" x-model="row.nama_barang" placeholder="Nama barang"
                                                    autocomplete="off"
                                                    @input="scheduleGridValidation(); scheduleCatalogSearch($event.target, pasteRowIndex(visibleRowIndex), 'nama_barang', row.nama_barang)"
                                                    @focus="handleCatalogFocus($event.target, pasteRowIndex(visibleRowIndex), 'nama_barang', row.nama_barang)"
                                                    @keydown="handleCatalogKeydown($event, pasteRowIndex(visibleRowIndex))"
                                                    @blur="handleCatalogBlur(pasteRowIndex(visibleRowIndex), 'nama_barang')"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 1)"></td>
                                            <td><input type="text" list="unit-paste-categories" x-model="row.kategori"
                                                    placeholder="Kategori" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 2)"></td>
                                            <td><input type="text" list="unit-paste-units" x-model="row.satuan"
                                                    placeholder="Satuan" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 3)"></td>
                                            <td><input type="number" min="0" step="1" x-model="row.qty"
                                                    placeholder="0" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 4)"></td>
                                            <td><input type="number" min="0" step="0.01" x-model="row.harga_satuan"
                                                    placeholder="0" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 5)"></td>
                                            <td><input type="number" min="0" step="1" x-model="row.minimum_stok"
                                                    placeholder="10" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 6)"></td>
                                            <td><input type="text" x-model="row.catatan"
                                                    placeholder="Catatan opsional" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 7)"></td>
                                            <td class="stock-review-cell">
                                                <template x-if="row._validation">
                                                    <div class="stock-review-inline">
                                                        <span class="badge" :class="validationBadgeClass(row._validation)"
                                                            x-text="validationStatusLabel(row._validation)"></span>
                                                        <button type="button" class="stock-review-info-btn"
                                                            :class="{
                                                                'is-error': row._validation.status === 'error',
                                                                'is-active': reviewPopoverRow === pasteRowIndex(visibleRowIndex)
                                                            }"
                                                            :aria-expanded="reviewPopoverRow === pasteRowIndex(visibleRowIndex)"
                                                            aria-label="Lihat keterangan review"
                                                            @click.stop="toggleReviewPopover($event.currentTarget, pasteRowIndex(visibleRowIndex))">
                                                            !
                                                        </button>
                                                    </div>
                                                </template>
                                                <span x-show="!row._validation && isRowFilled(row)"
                                                    style="font-size:.7rem;color:var(--text-muted);">Menunggu
                                                    validasi</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline"
                                                    @click="removePasteRow(pasteRowIndex(visibleRowIndex))"
                                                    aria-label="Hapus baris">&times;</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Floating Vector Search Autocomplete Dropdown --}}
                        <div x-show="searchActiveRow !== null" x-cloak class="stock-paste-autocomplete"
                            :style="searchDropdownStyle" role="listbox" aria-label="Saran barang dari vector search">
                            <div x-show="searchLoading" class="stock-paste-autocomplete-status">
                                Mencari barang...
                            </div>
                            <template x-for="(item, sIdx) in searchSuggestions" :key="item.kode_barang + '-' + sIdx">
                                <button type="button" class="stock-paste-autocomplete-item"
                                    :class="{ 'is-active': sIdx === searchHighlightedIndex }" role="option"
                                    :aria-selected="sIdx === searchHighlightedIndex"
                                    @mousedown.prevent="selectCatalogSuggestion(item, searchActiveRow)">
                                    <span class="stock-paste-autocomplete-line">
                                        <span class="stock-paste-autocomplete-code">
                                            <template x-for="(part, pIdx) in highlightParts(item.kode_barang, searchQuery)"
                                                :key="pIdx">
                                                <span :class="{ 'stock-paste-highlight': part.match }"
                                                    x-text="part.text"></span>
                                            </template>
                                        </span>
                                        <span> &mdash; </span>
                                        <span>
                                            <template x-for="(part, pIdx) in highlightParts(item.nama_barang, searchQuery)"
                                                :key="pIdx">
                                                <span :class="{ 'stock-paste-highlight': part.match }"
                                                    x-text="part.text"></span>
                                            </template>
                                        </span>
                                    </span>
                                </button>
                            </template>
                            <div x-show="!searchLoading && searchSuggestions.length === 0"
                                class="stock-paste-autocomplete-empty" role="alert">
                                <span class="stock-paste-autocomplete-empty-text">Barang belum ada. Tambahkan terlebih dahulu di Master Barang.</span>
                                <a href="{{ route('master.barang.index') }}"
                                    class="btn btn-primary btn-sm stock-paste-autocomplete-cta"
                                    @mousedown.stop>
                                    Tambah di Master Barang &rarr;
                                </a>
                            </div>
                        </div>

                        {{-- Floating Review Detail Popover --}}
                        <div x-show="reviewPopoverRow !== null" x-cloak class="stock-review-popover"
                            :style="reviewPopoverStyle" role="dialog" aria-label="Keterangan hasil review"
                            @click.outside="closeReviewPopover()"
                            @keydown.escape.window="if (reviewPopoverRow !== null) closeReviewPopover()">
                            <template x-if="reviewPopoverData">
                                <div>
                                    <div class="stock-review-popover-title"
                                        :class="{ 'is-error': reviewPopoverData.status === 'error' }"
                                        x-text="validationStatusLabel(reviewPopoverData)"></div>
                                    <template
                                        x-if="reviewPopoverData.status === 'error' && (reviewPopoverData.issues || []).length > 0">
                                        <ul class="stock-review-popover-list">
                                            <template x-for="(issue, iIdx) in reviewPopoverData.issues" :key="iIdx">
                                                <li x-text="formatHumanIssue(issue)"></li>
                                            </template>
                                        </ul>
                                    </template>
                                    <template
                                        x-if="reviewPopoverData.status !== 'error' || (reviewPopoverData.issues || []).length === 0">
                                        <div x-text="validationDetailText(reviewPopoverData)"></div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div class="stock-paste-pagination">
                            <span x-text="pastePaginationLabel"></span>
                            <div style="display:flex;gap:6px;">
                                <button type="button" class="btn btn-outline btn-sm" :disabled="pastePage <= 1"
                                    @click="pastePage--">Prev</button>
                                <button type="button" class="btn btn-outline btn-sm"
                                    :disabled="pastePage >= totalPastePages" @click="pastePage++">Next</button>
                            </div>
                        </div>

                        {{-- Validation summary strip --}}
                        <div
                            style="margin-top:14px;padding:12px 16px;background:var(--surface-base);border:1px solid var(--surface-border);border-radius:var(--radius);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:.82rem;">
                                <span>Total baris: <strong x-text="filledRowsCount"></strong></span>
                                <span style="color:var(--status-success);">✓ Siap diimpor: <strong
                                        x-text="validSummary.ok"></strong></span>
                                <span style="color:var(--status-warning);" x-show="validSummary.duplicate > 0">⚠
                                    Data ganda/Lewati: <strong x-text="validSummary.duplicate"></strong></span>
                                <span style="color:var(--status-critical);" x-show="validSummary.error > 0">✗ Perlu diperbaiki:
                                    <strong x-text="validSummary.error"></strong></span>
                                <span x-show="validating"
                                    style="font-size:.75rem;color:var(--text-muted);">Memvalidasi...</span>
                            </div>
                            <div>
                                <button x-ref="importConfirmTrigger" type="button" class="btn btn-primary"
                                    :disabled="validSummary.ok === 0 || validating" @click="openConfirm()">
                                    Lanjut ke Verifikasi &amp; Simpan &rarr;
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- PHASE 1: REVIEW & CONFIRM --}}
                <div x-show="phase === 1" x-cloak x-transition.opacity class="stock-card-backdrop" role="presentation"
                    @click.self="closeConfirm()" @keydown.escape.window="if (phase === 1) closeConfirm()">
                    <section class="stock-card-modal stock-import-modal" role="dialog" aria-modal="true"
                        aria-labelledby="unit-stock-import-confirm-title">
                        <header class="stock-card-modal-header">
                            <div>
                                <h2 id="unit-stock-import-confirm-title">Konfirmasi &amp; Simpan Stok Awal Unit</h2>
                                <div class="stock-card-modal-subtitle">Periksa kembali data untuk Gudang Unit
                                    {{ $activeGudang?->nama }}.</div>
                            </div>
                            <button class="stock-card-close" type="button" aria-label="Kembali ke data"
                                @click="closeConfirm()">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="m18 6-12 12M6 6l12 12" />
                                </svg>
                            </button>
                        </header>
                        <div class="stock-card-modal-body">
                            <div class="stock-import-details">
                                <div class="form-group">
                                    <label class="form-label" for="unit-stock-import-date">Tanggal Acuan Stok Awal <span
                                            style="color:var(--status-critical);">*</span></label>
                                    <input id="unit-stock-import-date" x-ref="importReferenceDate" type="date"
                                        class="form-control" x-model="tanggalAcuan" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="unit-stock-import-note">Catatan Tambahan
                                        (Opsional)</label>
                                    <input id="unit-stock-import-note" type="text" class="form-control"
                                        x-model="catatanUmum"
                                        placeholder="Misal: Saldo opname fisik awal operasional unit">
                                </div>
                            </div>

                            <div class="stock-import-summary">
                                <h3>Ringkasan Import</h3>
                                <ul>
                                    <li><strong><span x-text="validSummary.ok"></span> barang</strong> akan dimasukkan
                                        sebagai saldo awal.</li>
                                    <li>Barang yang belum terdaftar di katalog unit akan dibuatkan otomatis.</li>
                                    <li>Data yang sudah pernah diimpor akan dilewati.</li>
                                </ul>
                            </div>

                            <div class="stock-import-actions">
                                <button type="button" class="btn btn-outline" @click="closeConfirm()">Kembali ke Edit
                                    Data</button>
                                <button type="button" class="btn btn-primary" :disabled="importing || !tanggalAcuan"
                                    @click="doImport()">
                                    Konfirmasi &amp; Proses Import
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- PHASE 2: IMPORT RESULT --}}
                <div x-show="phase === 2" x-cloak x-transition.opacity class="stock-card-backdrop" role="presentation">
                    <section class="stock-card-modal stock-import-modal" role="dialog" aria-modal="true"
                        aria-labelledby="unit-stock-import-result-title">
                        <header class="stock-card-modal-header">
                            <div>
                                <h2 id="unit-stock-import-result-title"
                                    x-text="importing ? 'Menyimpan Stok Awal' : (importResult.success ? 'Import Berhasil' : 'Hasil Import')">
                                </h2>
                                <div class="stock-card-modal-subtitle" x-show="importing">Mohon tunggu, data sedang
                                    diproses.</div>
                            </div>
                        </header>
                        <div class="stock-card-modal-body" aria-live="polite">
                            <div x-show="importing" class="stock-import-processing" role="status">
                                <div class="stock-import-result-icon"
                                    style="background:var(--surface-base);color:var(--brand-primary);">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M20 12a8 8 0 1 1-2.34-5.66" />
                                        <path d="M20 4v6h-6" />
                                    </svg>
                                </div>
                                <p>Import sedang berlangsung. Jangan tutup halaman ini.</p>
                            </div>

                            <template x-if="!importing && importResult.success">
                                <div class="stock-import-result">
                                    <div class="stock-import-result-icon is-success">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                    </div>
                                    <h3>Import Data Awal Berhasil</h3>
                                    <p>Saldo stok awal Gudang Unit telah diperbarui.</p>
                                    <div class="stock-import-counts">
                                        <div class="stock-import-count"><small>Berhasil Dimasukkan</small><strong
                                                x-text="importResult.success_count || 0"></strong></div>
                                        <div class="stock-import-count"><small>Barang Baru Dibuat</small><strong
                                                x-text="importResult.created_count || 0"></strong></div>
                                        <div class="stock-import-count"><small>Dilewati</small><strong
                                                x-text="importResult.skipped_count || 0"></strong></div>
                                    </div>
                                    <button x-ref="importResultAction" type="button" class="btn btn-primary"
                                        @click="currentTab = 'stok_saat_ini'; resetImport(); window.Turbo.visit(window.location.href);">
                                        Lihat Stok Real-Time
                                    </button>
                                </div>
                            </template>

                            <template x-if="!importing && !importResult.success">
                                <div class="stock-import-result">
                                    <div class="stock-import-result-icon is-error">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.5">
                                            <circle cx="12" cy="12" r="10" />
                                            <line x1="15" y1="9" x2="9" y2="15" />
                                            <line x1="9" y1="9" x2="15" y2="15" />
                                        </svg>
                                    </div>
                                    <h3
                                        x-text="importResult.partial ? 'Import Sebagian Berhasil' : 'Import Gagal Diproses'">
                                    </h3>
                                    <p class="stock-import-error" role="alert"
                                        x-text="importResult.errorMsg || 'Terjadi kesalahan sistem.'"></p>
                                    <div x-show="(importResult.error_rows || []).length > 0" class="stock-import-counts">
                                        <div class="stock-import-count"><small>Berhasil Dimasukkan</small><strong
                                                x-text="importResult.success_count || 0"></strong></div>
                                        <div class="stock-import-count"><small>Barang Baru Dibuat</small><strong
                                                x-text="importResult.created_count || 0"></strong></div>
                                        <div class="stock-import-count"><small>Baris Bermasalah</small><strong
                                                x-text="(importResult.error_rows || []).length"></strong></div>
                                    </div>
                                    <ul x-show="(importResult.error_rows || []).length > 0"
                                        class="stock-import-error-list" aria-label="Daftar baris yang gagal diimpor">
                                        <template x-for="rowError in (importResult.error_rows || []).slice(0, 5)"
                                            :key="rowError.row">
                                            <li>
                                                Baris <strong x-text="rowError.row"></strong>
                                                (<span x-text="rowError.kode || 'Tanpa kode'"></span>):
                                                <span x-text="rowError.error"></span>
                                            </li>
                                        </template>
                                    </ul>
                                    <p x-show="(importResult.error_rows || []).length > 5"
                                        class="stock-card-modal-subtitle">
                                        Menampilkan 5 dari <span x-text="importResult.error_rows.length"></span> baris
                                        bermasalah.
                                    </p>
                                    <button x-ref="importResultAction" type="button" class="btn btn-outline"
                                        @click="phase = 0">Kembali ke Edit Data</button>
                                </div>
                            </template>
                        </div>
                    </section>
                </div>

            </div>
        @endif

    </div>

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/stok/index.css')
@endpush

    <script>
        function importGridFlow(initialCatalogItems = []) {
            return {
                catalogItems: Array.isArray(initialCatalogItems) ? initialCatalogItems : [],
                catalogByCode: new Map(),
                catalogByName: new Map(),
                phase: 0,
                pasteRows: [],
                pastePage: 1,
                pastePageSize: 50,
                pasteRowLimit: 50000,
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
                        label: 'Qty Fisik',
                        required: true
                    },
                    {
                        key: 'harga_satuan',
                        label: 'Harga / Item',
                        required: false
                    },
                    {
                        key: 'minimum_stok',
                        label: 'Stok Minimum',
                        required: false
                    },
                    {
                        key: 'catatan',
                        label: 'Catatan',
                        required: false
                    },
                ],
                validationTimer: null,
                validationRun: 0,
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
                tanggalAcuan: new Date().toISOString().split('T')[0],
                catatanUmum: '',
                importing: false,
                importResult: {},
                searchSuggestions: [],
                searchQuery: '',
                searchLoading: false,
                searchActiveRow: null,
                searchActiveField: null,
                searchHighlightedIndex: -1,
                searchDropdownStyle: '',
                searchTimer: null,
                searchController: null,
                searchBlurTimer: null,
                reviewPopoverRow: null,
                reviewPopoverStyle: '',

                init() {
                    this.buildCatalogIndexes();
                    this.resetPasteGrid();
                },

                buildCatalogIndexes() {
                    this.catalogByCode = new Map();
                    this.catalogByName = new Map();
                    for (const item of this.catalogItems) {
                        const codeKey = String(item.kode_barang ?? '').trim().toUpperCase();
                        const nameKey = String(item.nama_barang ?? '').trim().toLowerCase();
                        if (codeKey) this.catalogByCode.set(codeKey, item);
                        if (nameKey) this.catalogByName.set(nameKey, item);
                    }
                },

                openConfirm() {
                    this.phase = 1;
                    this.$nextTick(() => this.$refs.importReferenceDate?.focus());
                },

                closeConfirm() {
                    this.phase = 0;
                    this.$nextTick(() => this.$refs.importConfirmTrigger?.focus());
                },

                resetPasteGrid() {
                    this.pasteRows = Array.from({
                        length: 15
                    }, () => this.emptyPasteRow());
                    this.pastePage = 1;
                    this.validSummary = {
                        total: 0,
                        ok: 0,
                        duplicate: 0,
                        skip: 0,
                        error: 0,
                        new_barang: 0
                    };
                },

                emptyPasteRow() {
                    return {
                        kode_barang: '',
                        nama_barang: '',
                        kategori: '',
                        satuan: '',
                        qty: '',
                        harga_satuan: '',
                        minimum_stok: '',
                        catatan: ''
                    };
                },

                addPasteRows(count = 20) {
                    for (let i = 0; i < count; i++) this.pasteRows.push(this.emptyPasteRow());
                },

                clearPasteGrid() {
                    if (window.confirm('Kosongkan semua baris data di tabel?')) {
                        this.resetPasteGrid();
                    }
                },

                removePasteRow(index) {
                    this.pasteRows.splice(index, 1);
                    if (this.pasteRows.length === 0) this.addPasteRows(10);
                    this.scheduleGridValidation();
                },

                get totalPastePages() {
                    return Math.max(1, Math.ceil(this.pasteRows.length / this.pastePageSize));
                },

                get visiblePasteRows() {
                    const start = (this.pastePage - 1) * this.pastePageSize;
                    return this.pasteRows.slice(start, start + this.pastePageSize);
                },

                pasteRowIndex(visibleIndex) {
                    return (this.pastePage - 1) * this.pastePageSize + visibleIndex;
                },

                get pastePaginationLabel() {
                    const total = this.pasteRows.length;
                    if (total === 0) return '0 baris';
                    const start = (this.pastePage - 1) * this.pastePageSize + 1;
                    const end = Math.min(total, this.pastePage * this.pastePageSize);
                    return `Menampilkan ${start}–${end} dari ${total} baris`;
                },

                get filledRowsCount() {
                    return this.pasteRows.filter(r => this.isRowFilled(r)).length;
                },

                isRowFilled(row) {
                    return this.systemFields.some(f => String(row[f.key] ?? '').trim() !== '');
                },

                validationBadgeClass(val) {
                    if (val.status === 'ok') return 'badge-success';
                    if (val.status === 'has_stok') return 'badge-warning';
                    if (val.status === 'duplicate') return 'badge-warning';
                    return 'badge-critical';
                },

                validationStatusLabel(val) {
                    if (val.status === 'ok') return val.is_new_barang ? 'Barang Baru' : 'Siap Diimpor';
                    if (val.status === 'has_stok') return 'Sudah Ada Stok Awal';
                    if (val.status === 'duplicate') return 'Data Ganda';
                    return 'Perlu Diperbaiki';
                },

                formatHumanIssue(issue) {
                    const text = String(issue ?? '').trim();
                    const map = {
                        'kode_barang wajib diisi': 'Kode barang belum diisi',
                        'nama_barang wajib diisi': 'Nama barang belum diisi',
                        'kategori wajib diisi': 'Kategori belum diisi',
                        'satuan wajib diisi': 'Satuan belum diisi',
                        'qty harus berupa angka': 'Jumlah stok harus berupa angka',
                        'qty tidak boleh negatif': 'Jumlah stok tidak boleh kurang dari 0',
                        'qty harus berupa bilangan bulat': 'Jumlah stok harus angka bulat (tanpa desimal)',
                        'harga_satuan harus berupa angka minimal 0': 'Harga per item harus berupa angka minimal 0',
                        'minimum_stok harus berupa bilangan bulat minimal 0': 'Stok minimum harus berupa angka bulat minimal 0',
                        'Stok awal untuk barang ini sudah pernah tercatat di gudang Store.': 'Stok awal barang ini sudah pernah diinput sebelumnya.',
                    };
                    if (map[text]) return map[text];
                    return text
                        .replace(/^Duplikat di file:\s*/i, 'Data ganda di tabel: ')
                        .replace(/^Satuan tidak cocok:/i, 'Satuan tidak sesuai:');
                },

                validationDetailText(val) {
                    if (!val) return '';
                    if (val.status === 'error') {
                        return Array.isArray(val.issues) && val.issues.length > 0
                            ? val.issues.map(issue => this.formatHumanIssue(issue)).join(' • ')
                            : 'Periksa kembali data pada baris ini.';
                    }
                    if (val.status === 'has_stok') {
                        return 'Stok awal untuk barang ini sudah pernah diinput. Baris ini akan dilewati saat proses import.';
                    }
                    if (val.status === 'duplicate') {
                        return val.dup_reason || 'Data barang sama dengan baris lain di tabel.';
                    }
                    if (val.is_new_barang) {
                        return 'Barang belum ada di gudang, akan dibuatkan otomatis.';
                    }
                    return 'Data lengkap dan siap dimasukkan ke stok awal.';
                },

                get reviewPopoverData() {
                    if (this.reviewPopoverRow === null) return null;
                    return this.pasteRows[this.reviewPopoverRow]?._validation || null;
                },

                toggleReviewPopover(buttonEl, rowIndex) {
                    if (this.reviewPopoverRow === rowIndex) {
                        this.closeReviewPopover();
                        return;
                    }
                    const rect = buttonEl.getBoundingClientRect();
                    const popoverWidth = Math.min(300, window.innerWidth - 24);
                    const left = Math.max(12, Math.min(rect.right - popoverWidth, window.innerWidth - popoverWidth - 12));
                    const top = Math.min(rect.bottom + 6, window.innerHeight - 160);
                    this.reviewPopoverStyle = `top:${Math.round(top)}px;left:${Math.round(left)}px;`;
                    this.reviewPopoverRow = rowIndex;
                },

                closeReviewPopover() {
                    this.reviewPopoverRow = null;
                },

                handleGridPaste(event, visibleRowIndex, colIndex) {
                    const clipboardData = event.clipboardData || window.clipboardData;
                    if (!clipboardData) return;
                    const text = clipboardData.getData('text');
                    if (!text || (!text.includes('\t') && !text.includes('\n'))) return;
                    event.preventDefault();

                    const lines = text.split(/\r\n|\n|\r/).filter(l => l.trim() !== '');
                    const startRow = this.pasteRowIndex(visibleRowIndex);
                    const keys = ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'qty', 'harga_satuan', 'minimum_stok', 'catatan'];

                    while (this.pasteRows.length < startRow + lines.length) {
                        this.pasteRows.push(this.emptyPasteRow());
                    }

                    lines.forEach((line, rIdx) => {
                        const cells = line.split('\t');
                        const targetRow = this.pasteRows[startRow + rIdx];
                        cells.forEach((val, cIdx) => {
                            const fieldKey = keys[colIndex + cIdx];
                            if (fieldKey) targetRow[fieldKey] = val.trim();
                        });
                    });

                    this.scheduleGridValidation();
                },

                scheduleGridValidation() {
                    this.validateGridLocally();
                    clearTimeout(this.validationTimer);
                    this.validationTimer = setTimeout(() => {
                        this.analyzeGrid();
                    }, 600);
                },

                validateGridLocally() {
                    const seenKodes = new Map();
                    const seenNamas = new Map();
                    const summary = {
                        total: 0,
                        ok: 0,
                        has_stok: 0,
                        duplicate: 0,
                        skip: 0,
                        error: 0,
                        new_barang: 0,
                    };
                    const validated = [];

                    for (let i = 0; i < this.pasteRows.length; i++) {
                        const row = this.pasteRows[i];
                        if (!this.isRowFilled(row)) {
                            row._validation = null;
                            continue;
                        }

                        const rowNumber = i + 1;
                        const issues = [];
                        const warnings = [];
                        const kode = String(row.kode_barang ?? '').trim().toUpperCase();
                        const nama = String(row.nama_barang ?? '').trim();
                        const lowerNama = nama.toLowerCase();
                        const kategori = String(row.kategori ?? '').trim();
                        const satuan = String(row.satuan ?? '').trim().toUpperCase();
                        const qtyRaw = String(row.qty ?? '').trim();
                        const numQty = qtyRaw === '' ? NaN : Number(qtyRaw);
                        const qtyValid = qtyRaw !== '' && Number.isFinite(numQty) && Number.isInteger(numQty) && numQty >= 0;
                        const hargaRaw = String(row.harga_satuan ?? '').trim();
                        const numHarga = hargaRaw === '' ? 0 : Number(hargaRaw);
                        const hargaValid = hargaRaw === '' || (Number.isFinite(numHarga) && numHarga >= 0);
                        const minStokRaw = String(row.minimum_stok ?? '').trim();
                        const numMinStok = minStokRaw === '' ? null : Number(minStokRaw);
                        const minStokValid = minStokRaw === '' || (Number.isFinite(numMinStok) && Number.isInteger(numMinStok) && numMinStok >= 0);

                        if (!kode) issues.push('Kode barang belum diisi');
                        if (!nama) issues.push('Nama barang belum diisi');
                        if (!kategori) issues.push('Kategori belum diisi');
                        if (!satuan) issues.push('Satuan belum diisi');

                        if (qtyRaw === '' || !Number.isFinite(numQty)) {
                            issues.push('Jumlah stok harus berupa angka');
                        } else if (numQty < 0) {
                            issues.push('Jumlah stok tidak boleh kurang dari 0');
                        } else if (!Number.isInteger(numQty)) {
                            issues.push('Jumlah stok harus angka bulat (tanpa desimal)');
                        }

                        if (!hargaValid) {
                            issues.push('Harga per item harus berupa angka minimal 0');
                        }

                        if (!minStokValid) {
                            issues.push('Stok minimum harus berupa angka bulat minimal 0');
                        }

                        if (kode) {
                            if (seenKodes.has(kode)) {
                                issues.push(`Kode '${kode}' sudah ditulis di baris ${seenKodes.get(kode)}`);
                            } else {
                                seenKodes.set(kode, rowNumber);
                            }
                        }

                        if (lowerNama) {
                            if (seenNamas.has(lowerNama)) {
                                issues.push(`Nama '${nama}' sudah ditulis di baris ${seenNamas.get(lowerNama)}`);
                            } else {
                                seenNamas.set(lowerNama, rowNumber);
                            }
                        }

                        let isNewBarang = false;
                        let hasStokAwal = false;
                        if (issues.length === 0) {
                            const byKode = this.catalogByCode.get(kode);
                            const byNama = this.catalogByName.get(lowerNama);
                            const existing = byKode || byNama;

                            if (byKode && String(byKode.nama_barang ?? '').trim().toLowerCase() !== lowerNama) {
                                issues.push(`Kode '${kode}' sudah digunakan oleh barang '${byKode.nama_barang}'.`);
                            } else if (byNama && String(byNama.kode_barang ?? '').trim().toUpperCase() !== kode) {
                                issues.push(`Nama '${nama}' sudah digunakan dengan kode '${byNama.kode_barang}'.`);
                            } else if (existing && existing.source === 'barang') {
                                const existingSatuan = String(existing.satuan ?? '').trim().toUpperCase();
                                if (existingSatuan && existingSatuan !== satuan) {
                                    issues.push(`Satuan tidak cocok: barang existing menggunakan '${existingSatuan}'.`);
                                }
                                if (issues.length === 0 && existing.stok_awal_exists) {
                                    hasStokAwal = true;
                                }
                            } else {
                                isNewBarang = true;
                                warnings.push(`Barang baru akan dibuat otomatis: ${kode} — ${nama}`);
                            }
                        }

                        const status = issues.length > 0 ? 'error' : (hasStokAwal ? 'has_stok' : 'ok');
                        const result = {
                            row: rowNumber,
                            kode_barang: kode,
                            nama_barang: nama,
                            kategori: kategori,
                            satuan: satuan,
                            qty: qtyValid ? numQty : null,
                            harga_satuan: hargaValid ? numHarga : 0,
                            minimum_stok: minStokRaw === '' ? null : (minStokValid ? numMinStok : null),
                            catatan: String(row.catatan ?? '').trim(),
                            status,
                            is_duplicate: false,
                            dup_reason: null,
                            is_new_barang: isNewBarang,
                            issues,
                            warnings,
                        };

                        row._validation = result;
                        validated.push(result);
                        summary.total++;
                        summary[status]++;
                        if (isNewBarang && status === 'ok') {
                            summary.new_barang++;
                        }
                    }

                    this.validatedRows = validated;
                    this.validSummary = summary;
                },

                positionCatalogDropdown(inputEl) {
                    if (!inputEl) return;
                    const rect = inputEl.getBoundingClientRect();
                    const width = Math.max(rect.width, 320);
                    const left = Math.min(rect.left, window.innerWidth - width - 16);
                    this.searchDropdownStyle =
                        `top:${Math.round(rect.bottom + 4)}px;left:${Math.max(8, Math.round(left))}px;width:${Math.round(width)}px;`;
                },

                handleCatalogFocus(inputEl, rowIndex, field, query) {
                    clearTimeout(this.searchBlurTimer);
                    const trimmed = String(query ?? '').trim();
                    if (trimmed.length >= 3) {
                        this.scheduleCatalogSearch(inputEl, rowIndex, field, trimmed);
                    }
                },

                scheduleCatalogSearch(inputEl, rowIndex, field, query) {
                    clearTimeout(this.searchTimer);
                    clearTimeout(this.searchBlurTimer);
                    const trimmed = String(query ?? '').trim();

                    if (trimmed.length < 3) {
                        this.closeCatalogSearch();
                        return;
                    }

                    this.searchActiveRow = rowIndex;
                    this.searchActiveField = field;
                    this.searchQuery = trimmed;
                    this.positionCatalogDropdown(inputEl);

                    const localMatches = this.searchLocalCatalog(trimmed, field);
                    if (localMatches.length > 0) {
                        this.searchController?.abort();
                        this.searchLoading = false;
                        this.searchSuggestions = localMatches;
                        this.searchHighlightedIndex = 0;
                        return;
                    }

                    this.searchSuggestions = [];
                    this.searchHighlightedIndex = -1;
                    this.searchTimer = setTimeout(() => {
                        this.fetchCatalogSuggestions(trimmed, field, rowIndex);
                    }, 180);
                },

                searchLocalCatalog(query, field) {
                    const q = query.toLowerCase();
                    const matches = [];

                    for (const item of this.catalogItems) {
                        const kode = String(item.kode_barang ?? '');
                        const nama = String(item.nama_barang ?? '');
                        const kodeLower = kode.toLowerCase();
                        const namaLower = nama.toLowerCase();

                        const kodeIdx = kodeLower.indexOf(q);
                        const namaIdx = namaLower.indexOf(q);
                        if (kodeIdx === -1 && namaIdx === -1) continue;

                        let score = 99;
                        if (field === 'kode_barang') {
                            if (kodeLower === q) score = 0;
                            else if (kodeIdx === 0) score = 1;
                            else if (kodeIdx > 0) score = 2;
                            else if (namaIdx === 0) score = 3;
                            else score = 4;
                        } else {
                            if (namaLower === q) score = 0;
                            else if (namaIdx === 0) score = 1;
                            else if (namaIdx > 0) score = 2;
                            else if (kodeIdx === 0) score = 3;
                            else score = 4;
                        }

                        matches.push({ item, score });
                    }

                    matches.sort((a, b) => a.score - b.score);
                    return matches.slice(0, 10).map(entry => entry.item);
                },

                highlightParts(text, query) {
                    const str = String(text ?? '');
                    const q = String(query ?? '').trim();
                    if (!str || q.length < 3) {
                        return [{ text: str, match: false }];
                    }

                    const lowerStr = str.toLowerCase();
                    const lowerQ = q.toLowerCase();
                    const parts = [];
                    let cursor = 0;
                    let idx = lowerStr.indexOf(lowerQ, cursor);

                    while (idx !== -1) {
                        if (idx > cursor) {
                            parts.push({ text: str.slice(cursor, idx), match: false });
                        }
                        parts.push({ text: str.slice(idx, idx + q.length), match: true });
                        cursor = idx + q.length;
                        idx = lowerStr.indexOf(lowerQ, cursor);
                    }

                    if (cursor < str.length) {
                        parts.push({ text: str.slice(cursor), match: false });
                    }

                    return parts.length > 0 ? parts : [{ text: str, match: false }];
                },

                async fetchCatalogSuggestions(query, field, rowIndex) {
                    this.searchController?.abort();
                    const controller = new AbortController();
                    this.searchController = controller;
                    this.searchLoading = true;

                    try {
                        const url = new URL('{{ route('unit-bisnis.stok.import-search') }}', window.location.origin);
                        url.searchParams.set('q', query);
                        url.searchParams.set('field', field);
                        @if ($activeGudang?->id)
                            url.searchParams.set('gudang_id', '{{ $activeGudang->id }}');
                        @endif

                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            signal: controller.signal,
                        });
                        if (!response.ok) throw new Error('Gagal mencari barang.');

                        const data = await response.json();
                        if (this.searchActiveRow !== rowIndex) return;
                        this.searchSuggestions = Array.isArray(data.items) ? data.items : [];
                        this.searchHighlightedIndex = this.searchSuggestions.length > 0 ? 0 : -1;
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            this.searchSuggestions = [];
                            this.searchHighlightedIndex = -1;
                        }
                    } finally {
                        if (this.searchController === controller) {
                            this.searchLoading = false;
                            this.searchController = null;
                        }
                    }
                },

                handleCatalogKeydown(event, rowIndex) {
                    if (this.searchActiveRow !== rowIndex || this.searchSuggestions.length === 0) return;

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        this.searchHighlightedIndex = (this.searchHighlightedIndex + 1) % this.searchSuggestions.length;
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        this.searchHighlightedIndex = (this.searchHighlightedIndex - 1 + this.searchSuggestions.length) %
                            this.searchSuggestions.length;
                    } else if (event.key === 'Enter' && this.searchHighlightedIndex >= 0) {
                        event.preventDefault();
                        const selected = this.searchSuggestions[this.searchHighlightedIndex];
                        if (selected) this.selectCatalogSuggestion(selected, rowIndex);
                    } else if (event.key === 'Escape') {
                        this.closeCatalogSearch();
                    }
                },

                handleCatalogBlur(rowIndex, field) {
                    clearTimeout(this.searchBlurTimer);
                    this.searchBlurTimer = setTimeout(() => {
                        if (field === 'kode_barang') {
                            const row = this.pasteRows[rowIndex];
                            const code = String(row?.kode_barang ?? '').trim().toUpperCase();
                            const hasName = String(row?.nama_barang ?? '').trim() !== '';
                            if (code !== '' && !hasName && this.searchSuggestions.length > 0) {
                                const exact = this.searchSuggestions.find(
                                    item => String(item.kode_barang ?? '').trim().toUpperCase() === code
                                );
                                if (exact) {
                                    this.selectCatalogSuggestion(exact, rowIndex);
                                    return;
                                }
                            }
                        }
                        if (this.searchActiveRow === rowIndex) {
                            this.closeCatalogSearch();
                        }
                    }, 150);
                },

                selectCatalogSuggestion(item, rowIndex) {
                    const row = this.pasteRows[rowIndex];
                    if (!row || !item) return;

                    row.kode_barang = item.kode_barang || '';
                    row.nama_barang = item.nama_barang || '';
                    row.kategori = item.kategori || '';
                    row.satuan = item.satuan || '';
                    this.closeCatalogSearch();
                    this.scheduleGridValidation();
                },

                closeCatalogSearch() {
                    clearTimeout(this.searchTimer);
                    this.searchController?.abort();
                    this.searchController = null;
                    this.searchLoading = false;
                    this.searchActiveRow = null;
                    this.searchActiveField = null;
                    this.searchHighlightedIndex = -1;
                    this.searchQuery = '';
                    this.searchSuggestions = [];
                },

                async analyzeGrid() {
                    clearTimeout(this.validationTimer);
                    const rows = this.pasteRows
                        .map((row, index) => ({
                            ...row,
                            row: index + 1
                        }))
                        .filter(row => this.isRowFilled(row));

                    if (rows.length === 0) {
                        this.validSummary = {
                            total: 0,
                            ok: 0,
                            duplicate: 0,
                            skip: 0,
                            error: 0,
                            new_barang: 0
                        };
                        return;
                    }

                    const validationRun = ++this.validationRun;
                    this.validating = true;
                    this.validationError = '';

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const response = await fetch('{{ route('unit-bisnis.stok.import-analyze') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                rows
                            }),
                        });
                        const data = await response.json();
                        if (validationRun !== this.validationRun) return;
                        if (!response.ok) {
                            this.validationError = data.message || 'Validasi data gagal.';
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
                    } catch (err) {
                        if (validationRun === this.validationRun) this.validationError = 'Koneksi gagal: ' + err
                            .message;
                    } finally {
                        if (validationRun === this.validationRun) this.validating = false;
                    }
                },

                async doImport() {
                    if (!this.tanggalAcuan || this.validSummary.ok === 0) return;
                    this.phase = 2;
                    this.importing = true;
                    this.importResult = {};

                    const targetRows = this.validatedRows.filter(r => r.status === 'ok');
                    if (targetRows.length === 0) {
                        this.importing = false;
                        this.importResult = {
                            success: false,
                            errorMsg: 'Tidak ada data valid yang bisa diimpor.'
                        };
                        this.$nextTick(() => this.$refs.importResultAction?.focus());
                        return;
                    }

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const res = await fetch('{{ route('unit-bisnis.stok.import-process') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                tanggal_acuan: this.tanggalAcuan,
                                catatan_umum: this.catatanUmum,
                                rows: targetRows,
                            }),
                        });
                        const data = await res.json();
                        this.importing = false;
                        const errorRows = Array.isArray(data.error_rows) ? data.error_rows : [];
                        if (!res.ok || data.success !== true) {
                            this.importResult = {
                                success: false,
                                errorMsg: data.error || data.message || 'Import gagal.',
                                error_rows: errorRows,
                            };
                        } else {
                            const successCount = Number(data.success_count) || 0;
                            this.importResult = {
                                success: errorRows.length === 0,
                                partial: errorRows.length > 0 && successCount > 0,
                                errorMsg: errorRows.length > 0 ? `${errorRows.length} baris gagal disimpan.` : '',
                                success_count: successCount,
                                created_count: Number(data.created_count) || 0,
                                skipped_count: (Number(data.skipped_count) || 0) + (this.validSummary.duplicate ||
                                    0),
                                error_rows: errorRows,
                            };
                        }
                    } catch (err) {
                        this.importing = false;
                        this.importResult = {
                            success: false,
                            errorMsg: 'Koneksi gagal: ' + err.message
                        };
                    }
                    this.$nextTick(() => this.$refs.importResultAction?.focus());
                },

                resetImport() {
                    this.phase = 0;
                    this.resetPasteGrid();
                },
            };
        }
    </script>
@endsection
