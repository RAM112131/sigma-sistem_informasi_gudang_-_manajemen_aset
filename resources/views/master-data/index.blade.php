@extends('layouts.app')

@section('title', 'Master Barang — ' . config('app.name', 'SIGMA'))

@push('styles')
    @vite('resources/css/pages/master-data/index.css')
@endpush

@section('content')
    @php
        $highlightCatalogText = static function (?string $value) use ($filters): \Illuminate\Support\HtmlString {
            $escapedText = e($value ?? '');
            $searchTerm = trim($filters['search'] ?? '');

            if ($searchTerm === '') {
                return new \Illuminate\Support\HtmlString($escapedText);
            }

            $escapedSearchTerm = preg_quote(e($searchTerm), '/');
            $highlightedText = preg_replace(
                '/(' . $escapedSearchTerm . ')/iu',
                '<mark class="catalog-search-match">$1</mark>',
                $escapedText,
            );

            return new \Illuminate\Support\HtmlString($highlightedText ?? $escapedText);
        };
    @endphp
    <div class="master-barang-page" x-data="{
        modal: @js($errors->any() ? old('_modal') : ($activeBarangView === 'add' ? 'create' : null)),
        catalogSearch: @js($filters['search'] ?? ''),
        unitAliases: @js($unitAliases),
        updateSatuanCreateFields(event) {
            const value = String(event.target.value ?? '').trim();
            const codeInput = document.getElementById('create-satuan-kode');
            const nameInput = document.getElementById('create-satuan-nama');
            const preview = document.getElementById('create-satuan-preview');
    
            if (!value) {
                codeInput.value = '';
                nameInput.value = '';
                nameInput.readOnly = false;
                preview.textContent = 'Kode singkat dan nama panjang akan terisi otomatis.';
                return;
            }
    
            const normalized = value.toLocaleLowerCase('id-ID');
            const alias = this.unitAliases[normalized];
            const slug = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase('id-ID').replace(/[^a-z0-9]+/g, '');
            const unit = alias ?? {
                kode: slug,
                nama: value.toLocaleLowerCase('id-ID').replace(/(^|\s)\S/g, character => character.toLocaleUpperCase('id-ID')),
            };
    
            codeInput.value = unit.kode;
            nameInput.value = unit.nama;
            nameInput.readOnly = Boolean(alias);
            preview.textContent = `Akan disimpan sebagai ${unit.kode} — ${unit.nama}.`;
        },
        updateSatuanEditFields(event, satuanId) {
            const value = String(event.target.value ?? '').trim();
            const alias = this.unitAliases[value.toLocaleLowerCase('id-ID')];
            const nameInput = document.getElementById(`edit-satuan-nama-${satuanId}`);
            const generatedName = alias?.nama ??
                value.toLocaleLowerCase('id-ID').replace(/(^|\s)\S/g, character => character.toLocaleUpperCase('id-ID'));
    
            nameInput.value = generatedName;
            nameInput.readOnly = Boolean(alias);
        },
        highlightCatalogMatch(value) {
            const text = String(value ?? '');
            const query = String(this.catalogSearch ?? '').trim();
            const escapeHtml = value => {
                const el = document.createElement('div');
                el.textContent = String(value ?? '');
                return el.innerHTML;
            };
    
            if (!query) return escapeHtml(text);
    
            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const matcher = new RegExp(escapedQuery, 'gi');
            let html = '';
            let lastIndex = 0;
            let match;
    
            while ((match = matcher.exec(text)) !== null) {
                html += escapeHtml(text.slice(lastIndex, match.index));
                html += `<mark class='catalog-search-match'>${escapeHtml(match[0])}</mark>`;
                lastIndex = match.index + match[0].length;
            }
    
            return html + escapeHtml(text.slice(lastIndex));
        }
    }" @keydown.escape.window="modal = null">
        @if ($errors->any())
            <div class="alert-toast alert-toast-danger" role="alert" aria-labelledby="master-barang-error-title">
                <svg aria-hidden="true" class="icon-inline" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <div>
                    <strong id="master-barang-error-title">Periksa kembali data berikut:</strong>
                    <ul style="margin: 8px 0 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <nav class="master-data-tabs" aria-label="Navigasi Master Data Barang">
            @if ($canManageMasterBarang || $canImportMasterBarang || $canCreateMasterBarang)
                <a id="master-tab-add"
                    href="{{ route('master.barang.index', ['tab' => 'barang', 'barang_view' => $canImportMasterBarang ? 'import' : 'add']) }}"
                    class="btn {{ $activeTab === 'barang' && in_array($activeBarangView, ['add', 'import'], true) ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'barang' && in_array($activeBarangView, ['add', 'import'], true)) aria-current="page" @endif>Tambah Barang</a>
            @endif
            @if ($canViewBarang)
                <a id="master-tab-barang" href="{{ route('master.barang.index', ['tab' => 'barang']) }}"
                    class="btn {{ $activeTab === 'barang' && $activeBarangView === 'catalog' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'barang' && $activeBarangView === 'catalog') aria-current="page" @endif>Master Barang</a>
            @endif
            @if ($canViewKategori)
                <a id="master-tab-kategori" href="{{ route('master.barang.index', ['tab' => 'kategori']) }}"
                    class="btn {{ $activeTab === 'kategori' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'kategori') aria-current="page" @endif>Kategori</a>
            @endif
            @if ($canViewSatuan)
                <a id="master-tab-satuan" href="{{ route('master.barang.index', ['tab' => 'satuan']) }}"
                    class="btn {{ $activeTab === 'satuan' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'satuan') aria-current="page" @endif>Satuan</a>
            @endif
            @if ($canViewArea)
                <a id="master-tab-area"
                    href="{{ route('master.barang.index', ['tab' => 'area', 'area_gudang_id' => request()->query('area_gudang_id')]) }}"
                    class="btn {{ $activeTab === 'area' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'area') aria-current="page" @endif>User Peminta / Area</a>
            @endif
        </nav>

        @if ($activeTab === 'kategori')
            @can('kategori.create')
                <div class="catalog-create-toolbar">
                    <button type="button" class="btn btn-primary catalog-create-button" @click="modal = 'kategori-create'">
                        <svg aria-hidden="true" class="icon-inline" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Kategori
                    </button>
                </div>
            @endcan
        @elseif($activeTab === 'satuan')
            @can('satuan.create')
                <div class="catalog-create-toolbar">
                    <button type="button" class="btn btn-primary catalog-create-button" @click="modal = 'satuan-create'">
                        <svg aria-hidden="true" class="icon-inline" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Satuan
                    </button>
                </div>
            @endcan
        @endif

        @if ($activeTab === 'barang')
            @if ($activeBarangView === 'import' && $canImportMasterBarang)
                @include('master-data.import._form', [
                    'warehouses' => $importWarehouses,
                    'canSelectWarehouses' => $canSelectImportWarehouses,
                    'categorySuggestions' => $categorySuggestions,
                    'unitSuggestions' => $unitSuggestions,
                ])
            @else
                <section id="master-barang-panel" aria-labelledby="master-tab-barang">
                    <form method="GET" action="{{ route('master.barang.index') }}" class="card catalog-filter-card"
                        aria-label="Filter katalog master barang">
                        <input type="hidden" name="tab" value="barang">
                        <input type="hidden" name="registrasi" value="{{ $filters['registrasi'] ?? 'semua' }}">
                        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                        <div class="catalog-filter-primary">
                            <div class="form-group">
                                <label for="filter-search" class="form-label">Cari Barang</label>
                                <div class="catalog-search-field">
                                    <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                        height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="m20 20-4-4" />
                                    </svg>
                                    <input id="filter-search" type="search" name="search" class="form-control"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="Cari kode, nama, kategori, atau satuan"
                                        @input.debounce.400ms="$el.form.requestSubmit()">
                                </div>
                                @error('search')
                                    <span class="form-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="filter-kategori" class="form-label">Kategori Master</label>
                                <select id="filter-kategori" name="kategori_id" class="form-control"
                                    @change="$el.form.requestSubmit()">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($kategoriList as $kategori)
                                        <option value="{{ $kategori->id }}" @selected(($filters['kategori_id'] ?? '') == $kategori->id)>
                                            {{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filter-satuan" class="form-label">Satuan Ukur</label>
                                <select id="filter-satuan" name="satuan_id" class="form-control"
                                    @change="$el.form.requestSubmit()">
                                    <option value="">Semua Satuan</option>
                                    @foreach ($satuanList as $satuan)
                                        <option value="{{ $satuan->id }}" @selected(($filters['satuan_id'] ?? '') == $satuan->id)>
                                            {{ $satuan->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="catalog-filter-secondary">
                            <div class="form-group catalog-warehouse-filter" x-data="{
                                options: @js($gudangList->map(fn($gudang) => ['id' => (string) $gudang->id, 'nama' => $gudang->nama])->values()),
                                selected: @js(array_map('strval', $filters['gudang_id'])),
                                search: '',
                                isOpen: false,
                                warehouseName(id) {
                                    return this.options.find(option => option.id === String(id))?.nama || '';
                                },
                                visibleWarehouses() {
                                    const query = this.search.trim().toLocaleLowerCase();
                                    return this.options.filter(option => option.nama.toLocaleLowerCase().includes(query));
                                },
                                toggleWarehouse(id) {
                                    const warehouseId = String(id);
                                    this.selected.includes(warehouseId) ?
                                        this.selected = this.selected.filter(selectedId => selectedId !== warehouseId) :
                                        this.selected.push(warehouseId);
                                },
                                removeWarehouse(id) {
                                    this.selected = this.selected.filter(selectedId => selectedId !== String(id));
                                }
                            }"
                                @keydown.escape.stop="isOpen = false">
                                <span id="filter-gudang-label" class="form-label">Gudang</span>
                                <div class="catalog-warehouse-control" aria-labelledby="filter-gudang-label">
                                    <template x-for="warehouseId in selected" :key="warehouseId">
                                        <span class="catalog-warehouse-chip">
                                            <span class="catalog-warehouse-chip-label"
                                                x-text="warehouseName(warehouseId)"></span>
                                            <button type="button" class="catalog-warehouse-chip-remove"
                                                :aria-label="'Hapus filter ' + warehouseName(warehouseId)"
                                                @click="removeWarehouse(warehouseId); $nextTick(() => $el.closest('form').requestSubmit())">
                                                <svg aria-hidden="true" width="13" height="13"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <path d="m18 6-12 12M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <button type="button" class="catalog-warehouse-picker-trigger"
                                        aria-haspopup="listbox" :aria-expanded="isOpen.toString()"
                                        @click="isOpen = !isOpen">
                                        <span x-show="selected.length === 0">Pilih satu/beberapa gudang</span>
                                        <span x-show="selected.length > 0">Tambah gudang</span>
                                    </button>
                                    <template x-for="warehouseId in selected" :key="'warehouse-input-' + warehouseId">
                                        <input type="hidden" name="gudang_id[]" :value="warehouseId">
                                    </template>
                                </div>
                                <div class="catalog-warehouse-picker" x-show="isOpen" x-cloak x-transition
                                    @click.outside="isOpen = false" role="listbox" aria-labelledby="filter-gudang-label"
                                    aria-multiselectable="true">
                                    <input type="search" class="form-control catalog-warehouse-picker-search"
                                        x-model="search" placeholder="Cari gudang…" aria-label="Cari pilihan gudang">
                                    <div class="catalog-warehouse-picker-list">
                                        <template x-for="gudang in visibleWarehouses()" :key="gudang.id">
                                            <label class="catalog-warehouse-picker-option">
                                                <input type="checkbox" :value="gudang.id"
                                                    :checked="selected.includes(gudang.id)"
                                                    @change="toggleWarehouse(gudang.id); $nextTick(() => $el.closest('form').requestSubmit())">
                                                <span x-text="gudang.nama"></span>
                                            </label>
                                        </template>
                                        <span class="catalog-warehouse-picker-empty"
                                            x-show="visibleWarehouses().length === 0">Gudang tidak ditemukan.</span>
                                    </div>
                                    <button type="button" class="catalog-warehouse-picker-clear"
                                        @click="selected = []; $nextTick(() => $el.closest('form').requestSubmit())">Hapus
                                        semua pilihan</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="filter-status" class="form-label">Status Master</label>
                                <select id="filter-status" name="status" class="form-control"
                                    @change="$el.form.requestSubmit()">
                                    <option value="">Semua Status</option>
                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div class="catalog-filter-actions">
                                <a href="{{ route('master.barang.index', ['tab' => 'barang']) }}"
                                    class="btn btn-outline catalog-filter-reset" aria-label="Reset filter"
                                    title="Reset filter">
                                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 12a9 9 0 1 0 2.64-6.36L3 8" />
                                        <path d="M3 3v5h5" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="card catalog-results-card">
                        <div class="table-responsive">
                            <table class="data-table catalog-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="catalog-row-number">No</th>
                                        <th scope="col">Kode Barang</th>
                                        <th scope="col">Nama Barang</th>
                                        <th scope="col">Kategori Barang</th>
                                        <th scope="col">Satuan</th>
                                        <th scope="col">Gudang</th>
                                        <th scope="col" style="text-align: right;">Aksi Master</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($masterBarangs as $masterBarang)
                                        @php
                                            $editModalKey = 'edit-' . $masterBarang->id;
                                            $registerModalKey = 'register-' . $masterBarang->id;
                                            $isEditFormError = $errors->any() && old('_modal') === $editModalKey;
                                            $isRegisterFormError =
                                                $errors->any() && old('_modal') === $registerModalKey;
                                            $availableGudangs = $gudangList->reject(
                                                fn($gudang): bool => $masterBarang->barangs->contains(
                                                    'gudang_id',
                                                    $gudang->id,
                                                ),
                                            );
                                        @endphp
                                        <tr>
                                            <td class="catalog-row-number">
                                                {{ $masterBarangs->firstItem() + $loop->index }}</td>
                                            <td>
                                                <span class="catalog-code"
                                                    data-search-value="{{ $masterBarang->kode ?: '—' }}"
                                                    x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->kode ?: '—') !!}</span>
                                            </td>
                                            <td>
                                                <div class="catalog-item-heading">
                                                    <span class="catalog-item-name"
                                                        data-search-value="{{ $masterBarang->nama }}"
                                                        x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->nama) !!}</span>
                                                    @unless ($masterBarang->is_aktif)
                                                        <span class="badge badge-warning catalog-item-inactive">Nonaktif</span>
                                                    @endunless
                                                </div>
                                            </td>
                                            <td>
                                                @if ($masterBarang->kategori?->nama)
                                                    <span class="catalog-category"
                                                        data-search-value="{{ $masterBarang->kategori->nama }}"
                                                        x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->kategori->nama) !!}</span>
                                                @else
                                                    <span class="catalog-summary-note">—</span>
                                                @endif
                                            </td>
                                            <td data-search-value="{{ $masterBarang->satuan?->nama ?? '—' }}"
                                                x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->satuan?->nama ?? '—') !!}</td>
                                            <td>
                                                <div class="catalog-warehouse-list" aria-label="Gudang terdaftar">
                                                    @forelse($masterBarang->barangs as $barangLokal)
                                                        <span class="badge badge-brand"
                                                            data-search-value="{{ $barangLokal->gudang?->nama ?? 'Gudang tidak diketahui' }}"
                                                            x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($barangLokal->gudang?->nama ?? 'Gudang tidak diketahui') !!}</span>
                                                    @empty
                                                        <span class="catalog-warehouse-empty">Belum terdaftar di gudang
                                                            mana pun</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                            <td style="text-align: right;">
                                                @canany(['barang.manage', 'barang.update'])
                                                    <div class="catalog-row-actions">
                                                        <button type="button" class="btn btn-outline btn-sm"
                                                            @click="modal = '{{ $editModalKey }}'">Edit</button>
                                                    </div>
                                                @else
                                                    <span class="catalog-summary-note">—</span>
                                                @endcanany
                                            </td>
                                        </tr>

                                        @canany(['barang.manage', 'barang.update'])
                                            <template x-teleport="body">
                                                <div class="modal-backdrop master-barang-modal-scope"
                                                    x-show="modal === '{{ $editModalKey }}'" x-cloak x-transition
                                                    @click.self="modal = null">
                                                    <section class="modal-card wide-modal" role="dialog" aria-modal="true"
                                                        aria-labelledby="edit-master-title-{{ $masterBarang->id }}">
                                                        <header class="modal-header">
                                                            <h2 id="edit-master-title-{{ $masterBarang->id }}"
                                                                class="modal-title">Edit Master Barang</h2>
                                                            <button type="button" class="modal-close"
                                                                aria-label="Tutup dialog edit"
                                                                @click="modal = null">&times;</button>
                                                        </header>

                                                        <form id="edit-master-form-{{ $masterBarang->id }}"
                                                            action="{{ route('master.barang.update', $masterBarang) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="_modal"
                                                                value="{{ $editModalKey }}">
                                                            <div class="modal-body">
                                                                <div class="master-barang-form-grid">
                                                                    <div class="form-group">
                                                                        <label for="edit-kode-{{ $masterBarang->id }}"
                                                                            class="form-label">Kode Perusahaan <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <input id="edit-kode-{{ $masterBarang->id }}"
                                                                            name="kode" class="form-control"
                                                                            value="{{ $isEditFormError ? old('kode', $masterBarang->kode) : $masterBarang->kode }}"
                                                                            required maxlength="50">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-nama-{{ $masterBarang->id }}"
                                                                            class="form-label">Nama Barang <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <input id="edit-nama-{{ $masterBarang->id }}"
                                                                            name="nama" class="form-control"
                                                                            value="{{ $isEditFormError ? old('nama', $masterBarang->nama) : $masterBarang->nama }}"
                                                                            required maxlength="255">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-kategori-{{ $masterBarang->id }}"
                                                                            class="form-label">Kategori <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <select id="edit-kategori-{{ $masterBarang->id }}"
                                                                            name="kategori_id" class="form-control" required>
                                                                            @foreach ($kategoriList as $kategori)
                                                                                <option value="{{ $kategori->id }}"
                                                                                    @selected(($isEditFormError ? old('kategori_id', $masterBarang->kategori_id) : $masterBarang->kategori_id) == $kategori->id)>
                                                                                    {{ $kategori->nama }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-satuan-{{ $masterBarang->id }}"
                                                                            class="form-label">Satuan <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <select id="edit-satuan-{{ $masterBarang->id }}"
                                                                            name="satuan_id" class="form-control" required>
                                                                            @foreach ($satuanList as $satuan)
                                                                                <option value="{{ $satuan->id }}"
                                                                                    @selected(($isEditFormError ? old('satuan_id', $masterBarang->satuan_id) : $masterBarang->satuan_id) == $satuan->id)>
                                                                                    {{ $satuan->nama }} ({{ $satuan->kode }})
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-harga-{{ $masterBarang->id }}"
                                                                            class="form-label">Harga Satuan</label>
                                                                        <input id="edit-harga-{{ $masterBarang->id }}"
                                                                            type="number" name="harga_satuan"
                                                                            class="form-control" min="0"
                                                                            step="0.01" inputmode="decimal"
                                                                            value="{{ $isEditFormError ? old('harga_satuan', $masterBarang->harga_satuan) : $masterBarang->harga_satuan }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-status-{{ $masterBarang->id }}"
                                                                            class="form-label">Status</label>
                                                                        <select id="edit-status-{{ $masterBarang->id }}"
                                                                            name="is_aktif" class="form-control">
                                                                            <option value="1"
                                                                                @selected((string) ($isEditFormError ? old('is_aktif', (int) $masterBarang->is_aktif) : (int) $masterBarang->is_aktif) === '1')>Aktif</option>
                                                                            <option value="0"
                                                                                @selected((string) ($isEditFormError ? old('is_aktif', (int) $masterBarang->is_aktif) : (int) $masterBarang->is_aktif) === '0')>Nonaktif</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group form-group-full">
                                                                        <label for="edit-deskripsi-{{ $masterBarang->id }}"
                                                                            class="form-label">Deskripsi</label>
                                                                        <textarea id="edit-deskripsi-{{ $masterBarang->id }}" name="deskripsi" class="form-control" rows="3">{{ $isEditFormError ? old('deskripsi', $masterBarang->deskripsi) : $masterBarang->deskripsi }}</textarea>
                                                                    </div>

                                                                    {{-- Section Penugasan Gudang --}}
                                                                    <div class="form-group form-group-full"
                                                                        x-data="{
                                                                            registered: @js(
    $masterBarang->barangs
        ->map(
            fn($b) => [
                'id' => $b->gudang_id,
                'nama' => $b->gudang?->nama ?? 'Gudang #' . $b->gudang_id,
            ],
        )
        ->values(),
),
                                                                            available: @js(
    $availableGudangs
        ->map(
            fn($g) => [
                'id' => $g->id,
                'nama' => $g->nama,
            ],
        )
        ->values(),
),
                                                                            selectedGudangId: '',
                                                                            addWarehouse() {
                                                                                if (!this.selectedGudangId) return;
                                                                                const found = this.available.find(g => String(g.id) === String(this.selectedGudangId));
                                                                                if (found) {
                                                                                    this.registered.push(found);
                                                                                    this.available = this.available.filter(g => String(g.id) !== String(this.selectedGudangId));
                                                                                    this.selectedGudangId = '';
                                                                                }
                                                                            },
                                                                            removeWarehouse(item) {
                                                                                this.registered = this.registered.filter(g => String(g.id) !== String(item.id));
                                                                                this.available.push(item);
                                                                                this.available.sort((a, b) => a.nama.localeCompare(b.nama));
                                                                            }
                                                                        }">
                                                                        <input type="hidden" name="has_gudang_sync"
                                                                            value="1">
                                                                        <template x-for="item in registered"
                                                                            :key="item.id">
                                                                            <input type="hidden" name="gudang_ids[]"
                                                                                :value="item.id">
                                                                        </template>

                                                                        <div
                                                                            style="border-top: 1px solid var(--surface-border, #e2e8f0); padding-top: 16px; margin-top: 4px;">
                                                                            <label class="form-label"
                                                                                style="font-weight: 700; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                                                                <span>Fasilitas Gudang Terdaftar</span>
                                                                                <span
                                                                                    style="font-size: 11px; font-weight: normal; color: var(--text-muted);"
                                                                                    x-text="registered.length + ' gudang terhubung'"></span>
                                                                            </label>
                                                                            <p
                                                                                style="margin: 0 0 10px; font-size: 12px; color: var(--text-muted);">
                                                                                Daftar gudang yang memiliki alokasi barang ini.
                                                                                Anda dapat menambahkan beberapa gudang atau
                                                                                menghapus penugasan gudang yang ada.
                                                                            </p>

                                                                            {{-- Daftar Gudang Terdaftar --}}
                                                                            <div
                                                                                style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 42px; align-items: center; background: var(--surface-base, #f8fafc); padding: 8px 12px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--surface-border, #e2e8f0);">
                                                                                <template x-if="registered.length === 0">
                                                                                    <span
                                                                                        style="font-size: 12px; color: var(--text-muted); font-style: italic;">Belum
                                                                                        terdaftar di fasilitas gudang mana pun.
                                                                                        Pilih gudang di bawah untuk
                                                                                        mendaftarkan.</span>
                                                                                </template>
                                                                                <template x-for="item in registered"
                                                                                    :key="item.id">
                                                                                    <div
                                                                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: var(--surface-card, #ffffff); border: 1px solid var(--surface-border, #cbd5e1); border-radius: var(--radius-sm, 6px); font-size: 12.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                                                                        <svg style="width: 14px; height: 14px; color: var(--brand-primary, #145c54); flex-shrink: 0;"
                                                                                            viewBox="0 0 24 24" fill="none"
                                                                                            stroke="currentColor"
                                                                                            stroke-width="2">
                                                                                            <path
                                                                                                d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                                                                            <polyline
                                                                                                points="9 22 9 12 15 12 15 22" />
                                                                                        </svg>
                                                                                        <span
                                                                                            style="font-weight: 600; color: var(--text-primary);"
                                                                                            x-text="item.nama"></span>
                                                                                        <button type="button"
                                                                                            @click="removeWarehouse(item)"
                                                                                            title="Hapus dari gudang ini"
                                                                                            style="background: none; border: none; cursor: pointer; color: var(--status-critical, #c1443c); padding: 0 2px; display: inline-flex; align-items: center; font-size: 16px; line-height: 1; font-weight: bold; margin-left: 2px;">&times;</button>
                                                                                    </div>
                                                                                </template>
                                                                            </div>

                                                                            {{-- Pilihan Tambah Gudang --}}
                                                                            <div
                                                                                style="display: flex; gap: 8px; align-items: center;">
                                                                                <select x-model="selectedGudangId"
                                                                                    class="form-control"
                                                                                    style="flex: 1; min-width: 0;"
                                                                                    :disabled="available.length === 0">
                                                                                    <option value="">-- Pilih Fasilitas
                                                                                        Gudang untuk Ditambahkan --</option>
                                                                                    <template x-for="g in available"
                                                                                        :key="g.id">
                                                                                        <option :value="g.id"
                                                                                            x-text="g.nama"></option>
                                                                                    </template>
                                                                                </select>
                                                                                <button type="button" class="btn btn-outline"
                                                                                    style="white-space: nowrap; flex-shrink: 0;"
                                                                                    @click="addWarehouse()"
                                                                                    :disabled="!selectedGudangId">
                                                                                    + Daftarkan ke Gudang
                                                                                </button>
                                                                            </div>
                                                                            <template
                                                                                x-if="available.length === 0 && registered.length > 0">
                                                                                <span
                                                                                    style="font-size: 11px; color: var(--text-muted); margin-top: 5px; display: block;">Seluruh
                                                                                    fasilitas gudang aktif telah terdaftar untuk
                                                                                    barang ini.</span>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>

                                                        <footer class="modal-footer master-barang-modal-footer">
                                                            <div class="master-barang-modal-actions">
                                                                <form class="master-barang-inline-form"
                                                                    action="{{ route('master.barang.toggle', $masterBarang) }}"
                                                                    method="POST">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button class="btn btn-outline btn-sm"
                                                                        type="submit">{{ $masterBarang->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                                </form>
                                                                @if ($masterBarang->barangs_count === 0)
                                                                    <form class="master-barang-inline-form"
                                                                        action="{{ route('master.barang.destroy', $masterBarang) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Hapus master barang ini?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="btn btn-outline btn-sm"
                                                                            style="color: var(--status-critical);"
                                                                            type="submit">Hapus</button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <button type="submit"
                                                                form="edit-master-form-{{ $masterBarang->id }}"
                                                                class="btn btn-primary">Simpan Perubahan</button>
                                                        </footer>
                                                    </section>
                                                </div>
                                            </template>
                                        @endcan
                                    @empty
                                        <tr>
                                            <td colspan="7"
                                                style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                                                Tidak ada barang yang cocok dengan pencarian atau filter ini. Coba ubah kata
                                                kunci atau reset filter.
                                                <div style="margin-top: 10px;">
                                                    <a href="{{ route('master.barang.index', ['tab' => 'barang']) }}"
                                                        class="btn btn-outline btn-sm">Reset Filter</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="catalog-results-footer">
                            <span class="catalog-results-summary">
                                @if ($masterBarangs->total() > 0)
                                    Menampilkan
                                    {{ number_format($masterBarangs->firstItem()) }}–{{ number_format($masterBarangs->lastItem()) }}
                                    dari {{ number_format($masterBarangs->total()) }} barang master
                                @else
                                    Menampilkan 0 barang master
                                @endif
                            </span>
                            <form method="GET" action="{{ route('master.barang.index') }}"
                                class="catalog-pagination-size">
                                <input type="hidden" name="tab" value="barang">
                                <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                                <input type="hidden" name="kategori_id" value="{{ $filters['kategori_id'] ?? '' }}">
                                <input type="hidden" name="satuan_id" value="{{ $filters['satuan_id'] ?? '' }}">
                                @foreach ($filters['gudang_id'] as $selectedGudangId)
                                    <input type="hidden" name="gudang_id[]" value="{{ $selectedGudangId }}">
                                @endforeach
                                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                                <input type="hidden" name="registrasi" value="{{ $filters['registrasi'] ?? 'semua' }}">
                                <label for="catalog-per-page">Baris:</label>
                                <select id="catalog-per-page" name="per_page" class="form-control">
                                    @foreach ([8, 15, 25, 50] as $pageSize)
                                        <option value="{{ $pageSize }}" @selected($filters['per_page'] === $pageSize)>
                                            {{ $pageSize }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline btn-sm">Terapkan</button>
                            </form>
                            {{ $masterBarangs->links() }}
                        </div>
                    </div>

                </section>
            @endif

            @canany(['barang.manage', 'barang.update'])
                <div class="modal-backdrop master-barang-modal-scope" x-show="modal === 'create'" x-cloak x-transition
                    @click.self="modal = null">
                    <section class="modal-card wide-modal" role="dialog" aria-modal="true"
                        aria-labelledby="create-master-title">
                        <header class="modal-header">
                            <h2 id="create-master-title" class="modal-title">Input Manual Barang</h2>
                            <button type="button" class="modal-close" aria-label="Tutup dialog tambah barang"
                                @click="modal = null">&times;</button>
                        </header>
                        <form action="{{ route('master.barang.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="_modal" value="create">
                            @if ($activeBarangView === 'add')
                                <input type="hidden" name="return_to_add" value="1">
                            @endif
                            <div class="modal-body">
                                <div class="master-barang-form-grid">
                                    <div class="form-group">
                                        <label for="new-kode" class="form-label">Kode Perusahaan <span
                                                aria-hidden="true">*</span></label>
                                        <input id="new-kode" name="kode" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'create' ? old('kode') : '' }}"
                                            maxlength="50" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-nama" class="form-label">Nama Barang <span
                                                aria-hidden="true">*</span></label>
                                        <input id="new-nama" name="nama" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'create' ? old('nama') : '' }}"
                                            maxlength="255" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-kategori" class="form-label">Kategori <span
                                                aria-hidden="true">*</span></label>
                                        <select id="new-kategori" name="kategori_id" class="form-control" required>
                                            <option value="">Pilih kategori</option>
                                            @foreach ($kategoriList as $kategori)
                                                <option value="{{ $kategori->id }}" @selected($errors->any() && old('_modal') === 'create' && old('kategori_id') == $kategori->id)>
                                                    {{ $kategori->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-satuan" class="form-label">Satuan <span
                                                aria-hidden="true">*</span></label>
                                        <select id="new-satuan" name="satuan_id" class="form-control" required>
                                            <option value="">Pilih satuan</option>
                                            @foreach ($satuanList as $satuan)
                                                <option value="{{ $satuan->id }}" @selected($errors->any() && old('_modal') === 'create' && old('satuan_id') == $satuan->id)>
                                                    {{ $satuan->nama }} ({{ $satuan->kode }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-harga" class="form-label">Harga Satuan</label>
                                        <input id="new-harga" type="number" name="harga_satuan" class="form-control"
                                            min="0" step="0.01" inputmode="decimal"
                                            value="{{ $errors->any() && old('_modal') === 'create' ? old('harga_satuan', 0) : 0 }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="new-foto" class="form-label">Foto Barang</label>
                                        <input id="new-foto" type="file" name="foto_barang" class="form-control"
                                            accept="image/*">
                                    </div>
                                    <div class="form-group form-group-full">
                                        <label for="new-deskripsi" class="form-label">Deskripsi</label>
                                        <textarea id="new-deskripsi" name="deskripsi" class="form-control" rows="3">{{ $errors->any() && old('_modal') === 'create' ? old('deskripsi') : '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <footer class="modal-footer">
                                <button type="button" class="btn btn-outline" @click="modal = null">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan Master Barang</button>
                            </footer>
                        </form>
                    </section>
                </div>
            @endcanany
        @endif

        @if ($activeTab === 'kategori')
            <section id="master-kategori-panel" aria-labelledby="master-tab-kategori">
                <div class="kategori-info-banner" role="note">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    <span>Kategori adalah pengelompokan katalog global, bukan data stok per gudang.</span>
                </div>

                <form method="GET" action="{{ route('master.barang.index') }}" class="card kategori-toolbar"
                    aria-label="Cari dan filter kategori">
                    <input type="hidden" name="tab" value="kategori">
                    <div class="form-group">
                        <label for="kategori-search" class="form-label">Cari Kategori</label>
                        <div class="catalog-search-field">
                            <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-4-4" />
                            </svg>
                            <input id="kategori-search" type="search" name="search" class="form-control"
                                value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama kategori…"
                                @input.debounce.400ms="$el.form.requestSubmit()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="kategori-status-filter" class="form-label">Status</label>
                        <select id="kategori-status-filter" name="status" class="form-control"
                            @change="$el.form.requestSubmit()">
                            <option value="">Semua Status</option>
                            <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                            <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                        </select>
                    </div>
                </form>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div class="table-responsive">
                        <table class="data-table kategori-table">
                            <thead>
                                <tr>
                                    <th scope="col">Nama Kategori</th>
                                    <th scope="col">Deskripsi</th>
                                    <th scope="col" style="text-align: right;">Jumlah Barang</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Diperbarui</th>
                                    <th scope="col" style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kategoris as $kategori)
                                    <tr>
                                        <td><span class="kategori-name">{{ $kategori->nama }}</span></td>
                                        <td>
                                            @if ($kategori->deskripsi)
                                                <span class="kategori-description"
                                                    title="{{ $kategori->deskripsi }}">{{ $kategori->deskripsi }}</span>
                                            @else
                                                <span class="kategori-description">—</span>
                                            @endif
                                        </td>
                                        <td class="kategori-usage-count">
                                            {{ number_format($kategori->master_barangs_count) }} barang</td>
                                        <td>
                                            <span
                                                class="badge {{ $kategori->is_aktif ? 'badge-success' : 'kategori-status-inactive' }}">
                                                {{ $kategori->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>{{ $kategori->updated_at?->format('d M Y') ?? '—' }}</td>
                                        <td>
                                            <details class="satuan-actions">
                                                <summary aria-label="Aksi untuk kategori {{ $kategori->nama }}">
                                                    Aksi
                                                    <svg aria-hidden="true" width="14" height="14"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path d="m6 9 6 6 6-6" />
                                                    </svg>
                                                </summary>
                                                <div class="satuan-action-menu kategori-action-menu">
                                                    @can('kategori.update')
                                                        <button type="button"
                                                            @click="modal = 'kategori-edit-{{ $kategori->id }}'">Edit</button>
                                                    @endcan
                                                    <a href="{{ route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null, 'kategori_drawer' => $kategori->id]) }}"
                                                        style="display: flex; min-height: 44px; align-items: center; padding: 8px 10px; border-radius: 5px; color: var(--text-primary); font-size: .82rem; text-decoration: none;">Lihat
                                                        Barang</a>
                                                    @can('kategori.update')
                                                        @if ($kategori->is_aktif)
                                                            <button type="button" class="kategori-deactivate-action"
                                                                @click="modal = 'kategori-toggle-{{ $kategori->id }}'">Nonaktifkan</button>
                                                        @else
                                                            <form
                                                                action="{{ route('master.kategori.toggle', ['kategori' => $kategori, 'tab' => 'kategori']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit">Aktifkan</button>
                                                            </form>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                    @can('kategori.update')
                                        <template x-teleport="body">
                                            <div class="modal-backdrop master-barang-modal-scope"
                                                x-show="modal === 'kategori-edit-{{ $kategori->id }}'" x-cloak x-transition
                                                @click.self="modal = null">
                                                <section class="modal-card kategori-modal-card" role="dialog"
                                                    aria-modal="true"
                                                    aria-labelledby="edit-kategori-title-{{ $kategori->id }}">
                                                    <header class="modal-header">
                                                        <h2 id="edit-kategori-title-{{ $kategori->id }}"
                                                            class="modal-title">Edit Kategori</h2>
                                                        <button type="button" class="modal-close"
                                                            aria-label="Tutup dialog edit kategori"
                                                            @click="modal = null">&times;</button>
                                                    </header>
                                                    <form
                                                        action="{{ route('master.kategori.update', ['kategori' => $kategori, 'tab' => 'kategori']) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="_modal"
                                                            value="kategori-edit-{{ $kategori->id }}">
                                                        <input type="hidden" name="is_aktif" value="0">
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="edit-kategori-nama-{{ $kategori->id }}"
                                                                    class="form-label">Nama Kategori <span
                                                                        aria-hidden="true">*</span></label>
                                                                <input id="edit-kategori-nama-{{ $kategori->id }}"
                                                                    name="nama" class="form-control"
                                                                    value="{{ $errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id ? old('nama', $kategori->nama) : $kategori->nama }}"
                                                                    required maxlength="100">
                                                                @if ($errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id && $errors->has('nama'))
                                                                    <span class="kategori-field-error"
                                                                        role="alert">{{ $errors->first('nama') }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="form-group" x-data="{ description: @js($errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id ? old('deskripsi', $kategori->deskripsi ?? '') : $kategori->deskripsi ?? '') }">
                                                                <label for="edit-kategori-deskripsi-{{ $kategori->id }}"
                                                                    class="form-label">Deskripsi</label>
                                                                <textarea id="edit-kategori-deskripsi-{{ $kategori->id }}" name="deskripsi" class="form-control" rows="3"
                                                                    maxlength="200" x-model="description"></textarea>
                                                                <span class="kategori-character-count"><span
                                                                        x-text="description.length">0</span>/200
                                                                    karakter</span>
                                                                @if ($errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id && $errors->has('deskripsi'))
                                                                    <span class="kategori-field-error"
                                                                        role="alert">{{ $errors->first('deskripsi') }}</span>
                                                                @endif
                                                            </div>
                                                            <label class="kategori-status-toggle">
                                                                <input type="checkbox" name="is_aktif" value="1"
                                                                    @checked(
                                                                        $errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id
                                                                            ? old('is_aktif', $kategori->is_aktif)
                                                                            : $kategori->is_aktif)>
                                                                <span>Status Aktif</span>
                                                            </label>
                                                            <p class="kategori-change-note">Perubahan nama kategori berlaku
                                                                untuk semua barang dan semua gudang.</p>
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Kategori</button>
                                                        </footer>
                                                    </form>
                                                </section>
                                            </div>
                                        </template>
                                        @if ($kategori->is_aktif)
                                            <template x-teleport="body">
                                                <div class="modal-backdrop master-barang-modal-scope"
                                                    x-show="modal === 'kategori-toggle-{{ $kategori->id }}'" x-cloak
                                                    x-transition @click.self="modal = null">
                                                    <section class="modal-card kategori-modal-card" role="dialog"
                                                        aria-modal="true"
                                                        aria-labelledby="toggle-kategori-title-{{ $kategori->id }}">
                                                        <header class="modal-header">
                                                            <h2 id="toggle-kategori-title-{{ $kategori->id }}"
                                                                class="modal-title">Nonaktifkan Kategori</h2>
                                                            <button type="button" class="modal-close"
                                                                aria-label="Tutup konfirmasi kategori"
                                                                @click="modal = null">&times;</button>
                                                        </header>
                                                        <div class="modal-body">
                                                            <p style="margin: 0 0 12px;">Kategori “{{ $kategori->nama }}”
                                                                tidak akan muncul di pilihan barang baru.</p>
                                                            @if ($kategori->active_master_barangs_count > 0)
                                                                <div class="kategori-deactivate-note">
                                                                    Kategori ini masih dipakai
                                                                    {{ number_format($kategori->active_master_barangs_count) }}
                                                                    barang aktif. Barang yang ada tidak berubah, tetapi kategori
                                                                    tidak bisa dipilih untuk barang baru.
                                                                </div>
                                                            @else
                                                                <p class="kategori-field-helper" style="margin-bottom: 0;">
                                                                    Kategori tidak akan muncul di pilihan barang baru.</p>
                                                            @endif
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <form
                                                                action="{{ route('master.kategori.toggle', ['kategori' => $kategori, 'tab' => 'kategori']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn kategori-critical-button">Nonaktifkan</button>
                                                            </form>
                                                        </footer>
                                                    </section>
                                                </div>
                                            </template>
                                        @endif
                                    @endcan
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="kategori-empty-state">
                                                <svg aria-hidden="true" width="38" height="38"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="1.6">
                                                    <circle cx="11" cy="11" r="7" />
                                                    <path d="m20 20-4-4" />
                                                    <path d="M8 11h6" />
                                                </svg>
                                                <strong>Tidak ada kategori yang cocok</strong>
                                                <span>Coba kata kunci lain atau tambah kategori baru.</span>
                                                @can('kategori.create')
                                                    <button type="button" class="btn btn-primary"
                                                        @click="modal = 'kategori-create'">+ Tambah Kategori</button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <footer class="kategori-results-footer">
                        <span>
                            @if ($kategoris->total() > 0)
                                Menampilkan
                                {{ number_format($kategoris->firstItem()) }}–{{ number_format($kategoris->lastItem()) }}
                                dari {{ number_format($kategoris->total()) }} kategori
                            @else
                                Menampilkan 0 kategori
                            @endif
                        </span>
                        {{ $kategoris->links() }}
                    </footer>
                </div>

                @can('kategori.create')
                    <div class="modal-backdrop master-barang-modal-scope" x-show="modal === 'kategori-create'" x-cloak
                        x-transition @click.self="modal = null">
                        <section class="modal-card kategori-modal-card" role="dialog" aria-modal="true"
                            aria-labelledby="create-kategori-title">
                            <header class="modal-header">
                                <h2 id="create-kategori-title" class="modal-title">Tambah Kategori</h2>
                                <button type="button" class="modal-close" aria-label="Tutup dialog tambah kategori"
                                    @click="modal = null">&times;</button>
                            </header>
                            <form action="{{ route('master.kategori.store', ['tab' => 'kategori']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="_modal" value="kategori-create">
                                <input type="hidden" name="is_aktif" value="0">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="create-kategori-nama" class="form-label">Nama Kategori <span
                                                aria-hidden="true">*</span></label>
                                        <input id="create-kategori-nama" name="nama" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'kategori-create' ? old('nama') : '' }}"
                                            required maxlength="100">
                                        @if ($errors->any() && old('_modal') === 'kategori-create' && $errors->has('nama'))
                                            <span class="kategori-field-error"
                                                role="alert">{{ $errors->first('nama') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group" x-data="{ description: @js($errors->any() && old('_modal') === 'kategori-create' ? old('deskripsi', '') : '') }">
                                        <label for="create-kategori-deskripsi" class="form-label">Deskripsi</label>
                                        <textarea id="create-kategori-deskripsi" name="deskripsi" class="form-control" rows="3" maxlength="200"
                                            x-model="description"></textarea>
                                        <span class="kategori-character-count"><span x-text="description.length">0</span>/200
                                            karakter</span>
                                        @if ($errors->any() && old('_modal') === 'kategori-create' && $errors->has('deskripsi'))
                                            <span class="kategori-field-error"
                                                role="alert">{{ $errors->first('deskripsi') }}</span>
                                        @endif
                                    </div>
                                    <label class="kategori-status-toggle">
                                        <input type="checkbox" name="is_aktif" value="1" @checked(!$errors->any() || old('_modal') !== 'kategori-create' || old('is_aktif'))>
                                        <span>Status Aktif</span>
                                    </label>
                                    <p class="kategori-change-note">Perubahan nama kategori berlaku untuk semua barang dan
                                        semua gudang.</p>
                                </div>
                                <footer class="modal-footer">
                                    <button type="button" class="btn btn-outline" @click="modal = null">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                                </footer>
                            </form>
                        </section>
                    </div>
                @endcan

                @if ($categoryDrawer && $categoryBarang)
                    <div class="kategori-drawer-backdrop" @click.self="window.Turbo.visit(@js(route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null])))">
                        <aside class="kategori-drawer" role="dialog" aria-modal="true"
                            aria-labelledby="kategori-drawer-title">
                            <header class="kategori-drawer-header">
                                <div>
                                    <h2 id="kategori-drawer-title">Barang dalam kategori {{ $categoryDrawer->nama }}</h2>
                                    <span class="kategori-drawer-count">{{ number_format($categoryBarang->total()) }}
                                        barang</span>
                                </div>
                                <a class="kategori-drawer-close"
                                    href="{{ route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null]) }}"
                                    aria-label="Tutup panel barang kategori">&times;</a>
                            </header>
                            <form method="GET" action="{{ route('master.barang.index') }}"
                                class="kategori-drawer-search">
                                <input type="hidden" name="tab" value="kategori">
                                <input type="hidden" name="kategori_drawer" value="{{ $categoryDrawer->id }}">
                                @if (!empty($filters['search']))
                                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                @endif
                                @if (isset($filters['status']))
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif
                                <div class="form-group">
                                    <label for="kategori-drawer-search" class="form-label">Cari Barang</label>
                                    <div class="catalog-search-field">
                                        <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                            height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="m20 20-4-4" />
                                        </svg>
                                        <input id="kategori-drawer-search" type="search" name="drawer_search"
                                            class="form-control" value="{{ $filters['drawer_search'] ?? '' }}"
                                            placeholder="Cari kode atau nama barang…"
                                            @input.debounce.400ms="$el.form.requestSubmit()">
                                    </div>
                                </div>
                            </form>
                            <div class="kategori-drawer-content table-responsive">
                                <table class="data-table kategori-drawer-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Kode</th>
                                            <th scope="col">Nama Barang</th>
                                            <th scope="col">Satuan</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($categoryBarang as $masterBarang)
                                            <tr>
                                                <td><code class="satuan-code">{{ $masterBarang->kode ?: '—' }}</code>
                                                </td>
                                                <td>{{ $masterBarang->nama }}</td>
                                                <td>{{ $masterBarang->satuan?->nama ?? '—' }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $masterBarang->is_aktif ? 'badge-success' : 'kategori-status-inactive' }}">
                                                        {{ $masterBarang->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4"
                                                    style="padding: 26px 12px; color: var(--text-muted); text-align: center;">
                                                    Tidak ada barang yang cocok dengan pencarian.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <footer class="kategori-drawer-footer">
                                <a class="kategori-drawer-link"
                                    href="{{ route('master.barang.index', ['tab' => 'barang', 'kategori_id' => $categoryDrawer->id]) }}">Buka
                                    di Master Barang</a>
                                {{ $categoryBarang->links() }}
                                <a href="{{ route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null]) }}"
                                    class="btn btn-outline">Tutup</a>
                            </footer>
                        </aside>
                    </div>
                @endif
            </section>
        @endif

        @if ($activeTab === 'satuan')
            <section id="master-satuan-panel" class="master-satuan-panel" aria-labelledby="master-tab-satuan">
                <div class="satuan-info-banner" role="note">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    <span>Satuan bersifat global, dipakai semua gudang, dan dapat ditambah kapan pun.</span>
                </div>

                <form method="GET" action="{{ route('master.barang.index') }}" class="card satuan-toolbar"
                    aria-label="Cari dan filter satuan">
                    <input type="hidden" name="tab" value="satuan">
                    <input type="hidden" name="per_page" value="20">
                    <div class="form-group">
                        <label for="satuan-search" class="form-label">Cari Satuan</label>
                        <div class="catalog-search-field">
                            <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-4-4" />
                            </svg>
                            <input id="satuan-search" type="search" name="search" class="form-control"
                                value="{{ $filters['search'] ?? '' }}" placeholder="Cari satuan atau nama panjang…">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="satuan-status-filter" class="form-label">Status</label>
                        <select id="satuan-status-filter" name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                            <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </form>

                <div class="card" style="padding: 0; overflow: visible;">
                    <div class="table-responsive">
                        <table class="data-table satuan-table">
                            <thead>
                                <tr>
                                    <th scope="col">Satuan</th>
                                    <th scope="col">Nama Panjang</th>
                                    <th scope="col" style="text-align: right;">Dipakai oleh</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Dibuat</th>
                                    @can('satuan.update')
                                        <th scope="col" style="text-align: right;">Aksi</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($satuans as $satuan)
                                    @php
                                        $editSatuanModalKey = 'satuan-edit-' . $satuan->id;
                                        $isEditSatuanError = $errors->any() && old('_modal') === $editSatuanModalKey;
                                        $toggleSatuanModalKey = 'satuan-toggle-' . $satuan->id;
                                        $hasMasterBarangUsage = $satuan->master_barangs_count > 0;
                                    @endphp
                                    <tr>
                                        <td><code class="satuan-code">{{ $satuan->kode }}</code></td>
                                        <td style="font-weight: 700;">{{ $satuan->nama }}</td>
                                        <td class="satuan-usage-count">{{ number_format($satuan->master_barangs_count) }}
                                            barang</td>
                                        <td><span
                                                class="badge {{ $satuan->is_aktif ? 'badge-success' : 'satuan-status-inactive' }}">{{ $satuan->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td>{{ $satuan->created_at?->format('d M Y') ?? '—' }}</td>
                                        @can('satuan.update')
                                            <td>
                                                <details class="satuan-actions">
                                                    <summary aria-label="Aksi untuk satuan {{ $satuan->kode }}">
                                                        Aksi
                                                        <svg aria-hidden="true" width="14" height="14"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path d="m6 9 6 6 6-6" />
                                                        </svg>
                                                    </summary>
                                                    <div class="satuan-action-menu">
                                                        <button type="button"
                                                            @click="modal = '{{ $editSatuanModalKey }}'">Edit</button>
                                                        @if ($satuan->is_aktif)
                                                            <button type="button" class="satuan-deactivate-action"
                                                                @click="modal = '{{ $toggleSatuanModalKey }}'">Nonaktifkan</button>
                                                        @else
                                                            <form
                                                                action="{{ route('master.satuan.toggle', ['satuan' => $satuan, 'tab' => 'satuan']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit">Aktifkan</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </details>
                                            </td>
                                        @endcan
                                    </tr>
                                    @can('satuan.update')
                                        <template x-teleport="body">
                                            <div class="modal-backdrop master-barang-modal-scope"
                                                x-show="modal === '{{ $editSatuanModalKey }}'" x-cloak x-transition
                                                @click.self="modal = null">
                                                <section class="modal-card satuan-modal-card" role="dialog"
                                                    aria-modal="true"
                                                    aria-labelledby="edit-satuan-title-{{ $satuan->id }}">
                                                    <header class="modal-header">
                                                        <h2 id="edit-satuan-title-{{ $satuan->id }}" class="modal-title">
                                                            Edit Satuan</h2>
                                                        <button type="button" class="modal-close"
                                                            aria-label="Tutup dialog edit satuan"
                                                            @click="modal = null">&times;</button>
                                                    </header>
                                                    <form
                                                        action="{{ route('master.satuan.update', ['satuan' => $satuan, 'tab' => 'satuan']) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="_modal"
                                                            value="{{ $editSatuanModalKey }}">
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="edit-satuan-kode-{{ $satuan->id }}"
                                                                    class="form-label">Satuan <span
                                                                        aria-hidden="true">*</span></label>
                                                                @if ($hasMasterBarangUsage)
                                                                    <div class="satuan-code-lock">
                                                                        <input id="edit-satuan-kode-{{ $satuan->id }}"
                                                                            name="kode" class="form-control"
                                                                            value="{{ $satuan->kode }}" readonly required
                                                                            maxlength="20">
                                                                        <svg aria-hidden="true" width="17" height="17"
                                                                            viewBox="0 0 24 24" fill="none"
                                                                            stroke="currentColor" stroke-width="2">
                                                                            <rect x="3" y="11" width="18" height="11"
                                                                                rx="2" />
                                                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                                                        </svg>
                                                                    </div>
                                                                    <small class="satuan-field-helper">Satuan tidak dapat
                                                                        diubah karena sudah dipakai
                                                                        {{ number_format($satuan->master_barangs_count) }}
                                                                        barang</small>
                                                                @else
                                                                    <input id="edit-satuan-kode-{{ $satuan->id }}"
                                                                        name="kode" class="form-control"
                                                                        value="{{ $isEditSatuanError ? old('kode', $satuan->kode) : $satuan->kode }}"
                                                                        required maxlength="20"
                                                                        @input="updateSatuanEditFields($event, '{{ $satuan->id }}')">
                                                                    <small class="satuan-field-helper">Masukkan singkatan atau
                                                                        nama yang dikenali; nama panjang akan disesuaikan
                                                                        otomatis.</small>
                                                                @endif
                                                                @if ($isEditSatuanError)
                                                                    @error('kode')
                                                                        <small
                                                                            class="satuan-field-error">{{ $message }}</small>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit-satuan-nama-{{ $satuan->id }}"
                                                                    class="form-label">Nama Panjang <span
                                                                        aria-hidden="true">*</span></label>
                                                                <input id="edit-satuan-nama-{{ $satuan->id }}"
                                                                    name="nama" class="form-control"
                                                                    value="{{ $isEditSatuanError ? old('nama', $satuan->nama) : $satuan->nama }}"
                                                                    required maxlength="100"
                                                                    @if ($isEditSatuanError && $errors->has('nama')) aria-invalid="true" @endif>
                                                                <small class="satuan-field-helper">Contoh nama panjang:
                                                                    Kilogram</small>
                                                                @if ($isEditSatuanError)
                                                                    @error('nama')
                                                                        <small
                                                                            class="satuan-field-error">{{ $message }}</small>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="hidden" name="is_aktif" value="0">
                                                                <label class="satuan-status-toggle"
                                                                    for="edit-satuan-aktif-{{ $satuan->id }}">
                                                                    <input id="edit-satuan-aktif-{{ $satuan->id }}"
                                                                        type="checkbox" name="is_aktif" value="1"
                                                                        @checked($isEditSatuanError ? old('is_aktif', $satuan->is_aktif) == '1' : $satuan->is_aktif)>
                                                                    Status Aktif
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Satuan</button>
                                                        </footer>
                                                    </form>
                                                </section>
                                            </div>
                                        </template>
                                    @endcan
                                    @can('satuan.update')
                                        @if ($satuan->is_aktif)
                                            <template x-teleport="body">
                                                <div class="modal-backdrop master-barang-modal-scope"
                                                    x-show="modal === '{{ $toggleSatuanModalKey }}'" x-cloak x-transition
                                                    @click.self="modal = null">
                                                    <section class="modal-card satuan-modal-card" role="dialog"
                                                        aria-modal="true"
                                                        aria-labelledby="deactivate-satuan-title-{{ $satuan->id }}">
                                                        <header class="modal-header">
                                                            <h2 id="deactivate-satuan-title-{{ $satuan->id }}"
                                                                class="modal-title">Nonaktifkan Satuan</h2>
                                                            <button type="button" class="modal-close"
                                                                aria-label="Tutup dialog nonaktifkan satuan"
                                                                @click="modal = null">&times;</button>
                                                        </header>
                                                        <div class="modal-body">
                                                            <p>Satuan '{{ $satuan->kode }}' tidak akan muncul di pilihan
                                                                barang baru. Barang yang sudah memakainya tetap aman.</p>
                                                            @if ($hasMasterBarangUsage)
                                                                <div class="satuan-deactivate-note" role="note">
                                                                    Dipakai oleh
                                                                    {{ number_format($satuan->master_barangs_count) }} barang.
                                                                    Satuan hanya dinonaktifkan, tidak dihapus.
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <form
                                                                action="{{ route('master.satuan.toggle', ['satuan' => $satuan, 'tab' => 'satuan']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn btn-danger">Nonaktifkan</button>
                                                            </form>
                                                        </footer>
                                                    </section>
                                                </div>
                                            </template>
                                        @endif
                                    @endcan
                                @empty
                                    <tr>
                                        <td colspan="@can('satuan.update') 6 @else 5 @endcan"
                                            style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                                            @if (($filters['search'] ?? '') !== '' || ($filters['status'] ?? '') !== '')
                                                Tidak ada satuan yang sesuai dengan pencarian atau filter.
                                            @else
                                                Belum ada data satuan barang.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="satuan-results-footer">
                        <span>
                            @if ($satuans->total() > 0)
                                @if ($satuans->total() <= 20)
                                    Menampilkan {{ number_format($satuans->total()) }} satuan
                                @else
                                    Menampilkan
                                    {{ number_format($satuans->firstItem()) }}–{{ number_format($satuans->lastItem()) }}
                                    dari {{ number_format($satuans->total()) }} satuan
                                @endif
                            @else
                                Menampilkan 0 satuan
                            @endif
                        </span>
                        {{ $satuans->links() }}
                    </div>
                </div>

                @can('satuan.create')
                    <div class="modal-backdrop master-barang-modal-scope" x-show="modal === 'satuan-create'" x-cloak
                        x-transition @click.self="modal = null">
                        <section class="modal-card satuan-modal-card" role="dialog" aria-modal="true"
                            aria-labelledby="create-satuan-title">
                            <header class="modal-header">
                                <h2 id="create-satuan-title" class="modal-title">Tambah Satuan</h2>
                                <button type="button" class="modal-close" aria-label="Tutup dialog tambah satuan"
                                    @click="modal = null">&times;</button>
                            </header>
                            <form action="{{ route('master.satuan.store', ['tab' => 'satuan']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="_modal" value="satuan-create">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="create-satuan-input" class="form-label">Satuan <span
                                                aria-hidden="true">*</span></label>
                                        <input id="create-satuan-input" name="satuan_input" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'satuan-create' ? old('satuan_input', old('kode')) : '' }}"
                                            required maxlength="100" @input="updateSatuanCreateFields($event)"
                                            @if ($errors->any() && old('_modal') === 'satuan-create' && $errors->has('kode')) aria-invalid="true" @endif>
                                        <input id="create-satuan-kode" type="hidden" name="kode"
                                            value="{{ $errors->any() && old('_modal') === 'satuan-create' ? old('kode') : '' }}">
                                        <small id="create-satuan-preview" class="satuan-field-helper"
                                            aria-live="polite">Ketik nama panjang seperti “Liter” atau singkatannya seperti
                                            “ltr”; sistem akan menyiapkan pasangan satuannya.</small>
                                        @if ($errors->any() && old('_modal') === 'satuan-create')
                                            @error('kode')
                                                <small class="satuan-field-error">{{ $message }}</small>
                                            @enderror
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="create-satuan-nama" class="form-label">Nama Panjang <span
                                                aria-hidden="true">*</span></label>
                                        <input id="create-satuan-nama" name="nama" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'satuan-create' ? old('nama') : '' }}"
                                            required maxlength="100"
                                            @if ($errors->any() && old('_modal') === 'satuan-create' && $errors->has('nama')) aria-invalid="true" @endif>
                                        <small class="satuan-field-helper">Satuan yang dikenali memakai nama panjang baku;
                                            satuan baru yang belum dikenal bisa disesuaikan.</small>
                                        @if ($errors->any() && old('_modal') === 'satuan-create')
                                            @error('nama')
                                                <small class="satuan-field-error">{{ $message }}</small>
                                            @enderror
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="is_aktif" value="0">
                                        <label class="satuan-status-toggle" for="create-satuan-aktif">
                                            <input id="create-satuan-aktif" type="checkbox" name="is_aktif"
                                                value="1" @checked($errors->any() && old('_modal') === 'satuan-create' ? old('is_aktif', '1') == '1' : true)>
                                            Status Aktif
                                        </label>
                                    </div>
                                </div>
                                <footer class="modal-footer">
                                    <button type="button" class="btn btn-outline" @click="modal = null">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Satuan</button>
                                </footer>
                            </form>
                        </section>
                    </div>
                @endcan
            </section>
        @endif
        @if ($activeTab === 'area')
            @include('master-data.area._panel')
        @endif
    </div>
@endsection
