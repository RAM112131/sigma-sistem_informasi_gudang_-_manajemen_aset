{{-- Reusable Quick-Add Inline Modal Component for Barang, Kategori, & Satuan --}}
@php
    $canCreateKategori = auth()->user()?->can('kategori.create') ?? false;
    $canCreateSatuan = auth()->user()?->can('satuan.create') ?? false;
    $quickAddConfig = [
        'unitAliases' => \App\Services\MasterData\SatuanService::aliases(),
        'kategoris' => $kategoriList ?? \App\Models\KategoriBarang::where('is_aktif', true)->orderBy('nama')->get(['id', 'nama']),
        'satuans' => $satuanList ?? \App\Models\Satuan::where('is_aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
        'csrfToken' => csrf_token(),
        'urls' => [
            'checkSimilarity' => '/quick-add/check-similarity',
            'barang' => '/quick-add/barang',
            'kategori' => '/quick-add/kategori',
            'satuan' => '/quick-add/satuan',
        ],
    ];
@endphp
<div x-data='quickAddModal(@json($quickAddConfig))'
    @open-quick-add.window="handleOpen($event.detail)">

    {{-- Modal Utama Quick-Add Barang --}}
    <div class="modal-backdrop" x-show="openQuickAdd" x-cloak x-transition.opacity>
        <div class="modal-card wide-modal">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">+ Quick-Add Barang Master Baru</h3>
                    <p class="quick-add-subtitle">Tambah katalog produk baru langsung dari dalam transaksi tanpa pindah halaman.</p>
                </div>
                <button type="button" class="modal-close" @click="openQuickAdd = false">&times;</button>
            </div>

            <div class="modal-body">
                <template x-if="errorMessage">
                    <div class="alert-toast alert-toast-danger quick-add-alert">
                        <span x-text="errorMessage"></span>
                    </div>
                </template>

                <div class="form-group">
                    <label class="form-label">Nama Barang Master <span class="quick-add-required">*</span></label>
                    <input type="text" class="form-control" x-model="namaBarang"
                        placeholder="Misal: Plastik Vacuum 20x30cm 100s" required>
                </div>

                {{-- Warning Box: Real-Time Semantic Similarity Check --}}
                <template x-if="hasSimilar">
                    <div class="quick-add-similar-box">
                        <div class="quick-add-similar-title">
                            <svg class="quick-add-similar-icon" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                <line x1="12" y1="9" x2="12" y2="13" />
                                <line x1="12" y1="17" x2="12.01" y2="17" />
                            </svg>
                            <span>⚠️ Peringatan Duplikasi: Ditemukan Barang dengan Nama Mirip</span>
                        </div>
                        <p class="quick-add-similar-desc">
                            Sistem menemukan produk master existing yang mirip di katalog. Apakah Anda ingin memakai
                            produk yang sudah ada?
                        </p>
                        <div class="quick-add-similar-list">
                            <template x-for="item in similarItems" :key="item.id">
                                <div class="quick-add-similar-row">
                                    <div>
                                        <strong class="quick-add-similar-code" x-text="item.kode"></strong>
                                        &mdash;
                                        <span class="quick-add-similar-name" x-text="item.nama"></span>
                                        <span class="quick-add-similar-meta"
                                            x-text="'(' + item.kategori + ' / ' + item.satuan + ')'"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-accent" @click="selectExisting(item)">
                                        ✓ Gunakan Barang Ini
                                    </button>
                                </div>
                            </template>
                        </div>
                        <label class="quick-add-ignore-label">
                            <input type="checkbox" x-model="ignoreWarning" class="quick-add-ignore-checkbox">
                            <span>Tetap buat barang baru ini (Produk Berbeda)</span>
                        </label>
                    </div>
                </template>

                <div class="quick-add-grid-2">
                    <div class="form-group">
                        <label class="form-label">Kode Barang (opsional)</label>
                        <input type="text" class="form-control quick-add-uppercase" x-model="kodeBarang"
                            placeholder="STR-PVC-01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stok Min Default</label>
                        <input type="number" class="form-control" x-model="minimumStok" min="0">
                    </div>
                </div>

                <div class="quick-add-grid-2">
                    <div class="form-group">
                        <div class="quick-add-field-header">
                            <label class="form-label">Kategori <span class="quick-add-required">*</span></label>
                            @if ($canCreateKategori)
                                <button type="button" @click="openAddKategori = true" class="quick-add-inline-trigger">
                                    + Tambah Kategori
                                </button>
                            @endif
                        </div>
                        <div class="quick-add-combobox">
                            <input type="text" class="form-control" x-model="kategoriSearch"
                                @input="kategoriId = ''; kategoriDropdown = true" @focus="kategoriDropdown = true"
                                @click.outside="kategoriDropdown = false" placeholder="Ketik atau pilih kategori..."
                                autocomplete="off" required>
                            <div x-show="kategoriDropdown" x-cloak class="quick-add-dropdown">
                                <template x-for="kat in filteredKategoris()" :key="kat.id">
                                    <button type="button" @click="selectKategori(kat); kategoriDropdown = false"
                                        class="quick-add-dropdown-item">
                                        <span x-text="kat.nama"></span>
                                    </button>
                                </template>
                                @if ($canCreateKategori)
                                    <button type="button"
                                        x-show="kategoriSearch.trim() && !findMatchingKategori(kategoriSearch)"
                                        @click="openNewKategori(); kategoriDropdown = false"
                                        :disabled="categoryCreating" class="quick-add-dropdown-create">
                                        <span x-show="!categoryCreating">+ Tambah Kategori “<span
                                                x-text="kategoriSearch"></span>”</span>
                                        <span x-show="categoryCreating">Menyimpan kategori...</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="quick-add-field-header">
                            <label class="form-label">Satuan Dasar <span class="quick-add-required">*</span></label>
                            <button type="button" @click="openAddSatuan = true" class="quick-add-inline-trigger">
                                + Tambah Satuan
                            </button>
                        </div>
                        <div class="quick-add-combobox">
                            <input type="text" class="form-control" x-model="satuanSearch"
                                @input="satuanId = ''; satuanDropdown = true" @focus="satuanDropdown = true"
                                @click.outside="satuanDropdown = false" placeholder="Ketik atau pilih satuan..."
                                autocomplete="off" required>
                            <div x-show="satuanDropdown" x-cloak class="quick-add-dropdown">
                                <template x-for="sat in filteredSatuans()" :key="sat.id">
                                    <button type="button" @click="selectSatuan(sat); satuanDropdown = false"
                                        class="quick-add-dropdown-item">
                                        <span x-text="sat.nama + ' (' + sat.kode + ')'"></span>
                                    </button>
                                </template>
                                @if ($canCreateSatuan)
                                    <button type="button"
                                        x-show="satuanSearch.trim() && !findMatchingSatuan(satuanSearch)"
                                        @click="openNewSatuan(); satuanDropdown = false" :disabled="satuanCreating"
                                        class="quick-add-dropdown-create">
                                        <span x-show="!satuanCreating">+ Tambah Satuan “<span
                                                x-text="satuanSearch"></span>”</span>
                                        <span x-show="satuanCreating">Menyimpan satuan...</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi / Spesifikasi Produk</label>
                    <textarea class="form-control" x-model="deskripsiBarang" placeholder="Detail catatan barang..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="openQuickAdd = false">Batal</button>
                <button type="button" class="btn btn-primary" @click="submitBarang()"
                    :disabled="submitting || (hasSimilar && !ignoreWarning)">
                    <span x-show="!submitting">Simpan &amp; Pakai Langsung</span>
                    <span x-show="submitting">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Sub-Modal Inline: Tambah Kategori --}}
    @if ($canCreateKategori)
        <div class="modal-backdrop quick-add-submodal-backdrop" x-show="openAddKategori" x-cloak>
            <div class="modal-card quick-add-submodal-card">
                <div class="modal-header">
                    <h3 class="modal-title">+ Quick-Add Kategori</h3>
                    <button type="button" class="modal-close" @click="openAddKategori = false">&times;</button>
                </div>
                <div class="modal-body">
                    <template x-if="errorMessage">
                        <div class="alert-toast alert-toast-danger quick-add-alert-sm">
                            <span x-text="errorMessage"></span>
                        </div>
                    </template>
                    <div class="form-group">
                        <label class="form-label">Nama Kategori Baru</label>
                        <input type="text" class="form-control" x-model="newKategoriNama"
                            placeholder="Contoh: Kemasan Kedap Udara">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" @click="openAddKategori = false">Batal</button>
                    <button type="button" class="btn btn-primary" @click="submitKategori()"
                        :disabled="categoryCreating">
                        <span x-show="!categoryCreating">Simpan Kategori</span>
                        <span x-show="categoryCreating">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Sub-Modal Inline: Tambah Satuan --}}
    <div class="modal-backdrop quick-add-submodal-backdrop" x-show="openAddSatuan" x-cloak>
        <div class="modal-card quick-add-submodal-card">
            <div class="modal-header">
                <h3 class="modal-title">+ Quick-Add Satuan</h3>
                <button type="button" class="modal-close" @click="openAddSatuan = false">&times;</button>
            </div>
            <div class="modal-body">
                <template x-if="errorMessage">
                    <div class="alert-toast alert-toast-danger quick-add-alert-sm">
                        <span x-text="errorMessage"></span>
                    </div>
                </template>
                <div class="form-group">
                    <label class="form-label">Satuan</label>
                    <input type="text" class="form-control" x-model="newSatuanKode"
                        placeholder="Contoh: ltr atau Liter" maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Panjang</label>
                    <input type="text" class="form-control" x-model="newSatuanNama"
                        placeholder="Terisi otomatis; bisa disesuaikan">
                    <small class="satuan-field-helper">Contoh: ltr menghasilkan Liter, sedangkan Liter menghasilkan
                        kode ltr.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" @click="openAddSatuan = false">Batal</button>
                <button type="button" class="btn btn-primary" @click="submitSatuan()" :disabled="satuanCreating">
                    <span x-show="!satuanCreating">Simpan Satuan</span>
                    <span x-show="satuanCreating">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>

</div>
