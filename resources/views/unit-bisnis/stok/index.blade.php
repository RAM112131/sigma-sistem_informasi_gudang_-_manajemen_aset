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
            <div x-show="currentTab === 'import_data_awal'" x-cloak x-data="importGridFlow()" x-init="init()">

                {{-- PHASE 0: PASTE DATA --}}
                <div x-show="phase === 0" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="card-title">Input Saldo Awal Gudang Unit</h2>
                                <p style="font-size:.82rem;color:var(--text-muted);margin:2px 0 0;">Tempel data dari Excel
                                    atau isi tabel saldo awal <strong>{{ $activeGudang?->nama }}</strong>.</p>
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
                            <code style="margin-left:6px;">kode_barang · nama_barang · kategori · satuan · qty ·
                                catatan</code>
                        </div>
                    </div>

                    <section class="card stock-paste-card" aria-labelledby="unit-paste-heading">
                        <div class="stock-paste-heading">
                            <div>
                                <h2 id="unit-paste-heading" class="card-title">Tempel dari Excel / Edit Data</h2>
                                <p class="stock-paste-help">Salin baris dari Excel lalu tempel ke tabel, atau isi setiap
                                    kolom secara manual.</p>
                            </div>
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
                                                    @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 0)"></td>
                                            <td><input type="text" x-model="row.nama_barang" placeholder="Nama barang"
                                                    @input="scheduleGridValidation()"
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
                                            <td><input type="text" x-model="row.catatan"
                                                    placeholder="Catatan opsional" @input="scheduleGridValidation()"
                                                    @paste="handleGridPaste($event, visibleRowIndex, 5)"></td>
                                            <td>
                                                <template x-if="row._validation">
                                                    <span class="badge" :class="validationBadgeClass(row._validation)"
                                                        :title="(row._validation.issues || []).concat(row._validation
                                                            .warnings || []).join(' | ')"
                                                        x-text="validationStatusLabel(row._validation)"></span>
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
                                    Duplikat/Lewati: <strong x-text="validSummary.duplicate"></strong></span>
                                <span style="color:var(--status-critical);" x-show="validSummary.error > 0">✗ Error:
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
        function importGridFlow() {
            return {
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

                init() {
                    this.resetPasteGrid();
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
                    if (val.status === 'duplicate') return 'badge-warning';
                    return 'badge-critical';
                },

                validationStatusLabel(val) {
                    if (val.status === 'ok') return val.is_new_barang ? 'Baru' : 'Valid';
                    if (val.status === 'duplicate') return 'Duplikat';
                    return 'Error';
                },

                handleGridPaste(event, visibleRowIndex, colIndex) {
                    const clipboardData = event.clipboardData || window.clipboardData;
                    if (!clipboardData) return;
                    const text = clipboardData.getData('text');
                    if (!text || (!text.includes('\t') && !text.includes('\n'))) return;
                    event.preventDefault();

                    const lines = text.split(/\r\n|\n|\r/).filter(l => l.trim() !== '');
                    const startRow = this.pasteRowIndex(visibleRowIndex);
                    const keys = ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'qty', 'catatan'];

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
                    clearTimeout(this.validationTimer);
                    this.validationTimer = setTimeout(() => {
                        this.analyzeGrid();
                    }, 500);
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
