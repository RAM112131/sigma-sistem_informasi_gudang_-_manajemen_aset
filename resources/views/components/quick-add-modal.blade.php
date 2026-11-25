{{-- Reusable Quick-Add Inline Modal Component for Barang, Kategori, & Satuan --}}
@php($canCreateKategori = auth()->user()?->can('kategori.create') ?? false)
<div x-data="{
    openQuickAdd: false,
    openAddKategori: false,
    openAddSatuan: false,

    // Form fields
    namaBarang: '',
    kodeBarang: '',
    kategoriId: '',
    kategoriSearch: '',
    kategoriDropdown: false,
    satuanId: '',
    satuanSearch: '',
    satuanDropdown: false,
    minimumStok: 10,
    deskripsiBarang: '',

    // Inline Kategori & Satuan fields
    newKategoriNama: '',
    newSatuanKode: '',
    newSatuanNama: '',
    unitAliases: @js(\App\Services\MasterData\SatuanService::aliases()),

    updateQuickAddSatuanName() {
        const value = String(this.newSatuanKode ?? '').trim();
        if (!value) {
            this.newSatuanNama = '';
            return;
        }

        const normalized = value.toLocaleLowerCase('id-ID');
        const alias = this.unitAliases[normalized];
        this.newSatuanNama = alias?.nama ??
            value.toLocaleLowerCase('id-ID').replace(/(^|\s)\S/g, character => character.toLocaleUpperCase('id-ID'));
    },

    // Real-time Semantic Similarity Duplicate Check
    similarItems: [],
    hasSimilar: false,
    ignoreWarning: false,
    isSearchingSimilar: false,
    submitting: false,
    categoryCreating: false,
    satuanCreating: false,
    errorMessage: '',

    // Options
    kategoris: {{ json_encode($kategoriList ?? \App\Models\KategoriBarang::orderBy('nama')->get(['id', 'nama'])) }},
    satuans: {{ json_encode($satuanList ??\App\Models\Satuan::where('is_aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama'])) }},

    init() {
        this.$watch('namaBarang', (val) => {
            if (val.trim().length >= 3) {
                this.checkSimilarity(val);
            } else {
                this.similarItems = [];
                this.hasSimilar = false;
            }
        });
        this.$watch('newSatuanKode', () => this.updateQuickAddSatuanName());
    },

    csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    },

    async parseJsonResponse(res) {
        const text = await res.text();
        let data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            data = { message: `Server mengembalikan respons tidak valid (HTTP ${res.status}).` };
        }
        return { ok: res.ok, status: res.status, data };
    },

    extractErrorMessage(data, fallback) {
        if (data?.errors) {
            return Object.values(data.errors).flat().join(' ');
        }
        return data?.message || fallback;
    },

    checkSimilarity(name) {
        this.isSearchingSimilar = true;
        fetch('/api/quick-add/check-similarity?nama=' + encodeURIComponent(name), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => this.parseJsonResponse(res))
            .then(({ ok, data }) => {
                if (ok) {
                    this.hasSimilar = Boolean(data.has_similar);
                    this.similarItems = data.similar_items || [];
                }
            })
            .catch(err => console.error(err))
            .finally(() => this.isSearchingSimilar = false);
    },

    selectExisting(item) {
        this.$dispatch('item-selected', item);
        this.openQuickAdd = false;
        this.resetForm();
    },

    filteredKategoris() {
        const query = this.kategoriSearch.trim().toLowerCase();
        if (!query) return this.kategoris.slice(0, 8);
        return this.kategoris.filter(kat => kat.nama.toLowerCase().includes(query)).slice(0, 8);
    },

    selectKategori(kategori) {
        this.kategoriId = kategori.id;
        this.kategoriSearch = kategori.nama;
    },

    findMatchingKategori(query) {
        const normalized = String(query || '').trim().toLowerCase();
        if (!normalized) return null;
        return this.kategoris.find(kat => kat.nama.trim().toLowerCase() === normalized) || null;
    },

    openNewKategori() {
        this.addKategoriFromSearch();
    },

    async addKategoriFromSearch() {
        const nama = this.kategoriSearch.trim();
        if (!nama || this.categoryCreating) return null;

        const existing = this.findMatchingKategori(nama);
        if (existing) {
            this.selectKategori(existing);
            this.kategoriDropdown = false;
            return existing;
        }

        this.categoryCreating = true;
        this.errorMessage = '';

        try {
            const res = await fetch('/api/quick-add/kategori', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ nama })
            });
            const { ok, data } = await this.parseJsonResponse(res);
            if (!ok || !data.success) {
                this.errorMessage = this.extractErrorMessage(data, 'Kategori gagal ditambahkan.');
                return null;
            }

            if (!this.kategoris.some(k => k.id === data.kategori.id)) {
                this.kategoris.push(data.kategori);
            }
            this.selectKategori(data.kategori);
            this.kategoriDropdown = false;
            return data.kategori;
        } catch (err) {
            this.errorMessage = 'Gagal terhubung ke server saat menambahkan kategori.';
            console.error(err);
            return null;
        } finally {
            this.categoryCreating = false;
        }
    },

    filteredSatuans() {
        const query = this.satuanSearch.trim().toLowerCase();
        if (!query) return this.satuans.slice(0, 8);
        return this.satuans.filter(satuan =>
            satuan.nama.toLowerCase().includes(query) || satuan.kode.toLowerCase().includes(query)
        ).slice(0, 8);
    },

    selectSatuan(satuan) {
        this.satuanId = satuan.id;
        this.satuanSearch = satuan.nama + ' (' + satuan.kode + ')';
    },

    findMatchingSatuan(query) {
        const normalized = String(query || '').trim().toLowerCase();
        if (!normalized) return null;
        return this.satuans.find(sat =>
            sat.nama.trim().toLowerCase() === normalized ||
            sat.kode.trim().toLowerCase() === normalized ||
            (sat.nama + ' (' + sat.kode + ')').trim().toLowerCase() === normalized
        ) || null;
    },

    async openNewSatuan() {
        const nama = this.satuanSearch.trim();
        if (!nama || this.satuanCreating) return null;

        const existing = this.findMatchingSatuan(nama);
        if (existing) {
            this.selectSatuan(existing);
            this.satuanDropdown = false;
            return existing;
        }

        this.satuanCreating = true;
        this.errorMessage = '';
        const kode = nama.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 20);

        try {
            const res = await fetch('/api/quick-add/satuan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ kode, nama })
            });
            const { ok, data } = await this.parseJsonResponse(res);
            if (!ok || !data.success) {
                this.errorMessage = this.extractErrorMessage(data, 'Satuan gagal ditambahkan.');
                return null;
            }

            if (!this.satuans.some(s => s.id === data.satuan.id)) {
                this.satuans.push(data.satuan);
            }
            this.selectSatuan(data.satuan);
            this.satuanDropdown = false;
            return data.satuan;
        } catch (err) {
            this.errorMessage = 'Gagal terhubung ke server saat menambahkan satuan.';
            console.error(err);
            return null;
        } finally {
            this.satuanCreating = false;
        }
    },

    async submitBarang() {
        if (!this.namaBarang.trim()) {
            this.errorMessage = 'Mohon lengkapi Nama Barang, Kategori, dan Satuan.';
            return;
        }

        this.submitting = true;
        this.errorMessage = '';

        try {
            if (!this.kategoriId && this.kategoriSearch.trim()) {
                const matchedKat = this.findMatchingKategori(this.kategoriSearch);
                if (matchedKat) {
                    this.selectKategori(matchedKat);
                } else {
                    const createdKat = await this.addKategoriFromSearch();
                    if (!createdKat) {
                        this.submitting = false;
                        return;
                    }
                }
            }

            if (!this.satuanId && this.satuanSearch.trim()) {
                const matchedSat = this.findMatchingSatuan(this.satuanSearch);
                if (matchedSat) {
                    this.selectSatuan(matchedSat);
                } else {
                    const createdSat = await this.openNewSatuan();
                    if (!createdSat) {
                        this.submitting = false;
                        return;
                    }
                }
            }

            if (!this.kategoriId || !this.satuanId) {
                this.errorMessage = 'Mohon lengkapi Nama Barang, Kategori, dan Satuan.';
                this.submitting = false;
                return;
            }

            const res = await fetch('/api/quick-add/barang', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({
                    nama: this.namaBarang.trim(),
                    kode: this.kodeBarang,
                    kategori_id: this.kategoriId,
                    satuan_id: this.satuanId,
                    minimum_stok: this.minimumStok,
                    deskripsi: this.deskripsiBarang
                })
            });

            const { ok, data } = await this.parseJsonResponse(res);
            if (ok && data.success) {
                this.$dispatch('item-selected', data.barang);
                this.openQuickAdd = false;
                this.resetForm();
            } else {
                this.errorMessage = this.extractErrorMessage(data, 'Gagal menambahkan barang.');
            }
        } catch (err) {
            this.errorMessage = 'Gagal terhubung ke server. Silakan coba lagi.';
            console.error(err);
        } finally {
            this.submitting = false;
        }
    },

    async submitKategori() {
        if (!this.newKategoriNama.trim() || this.categoryCreating) return;
        this.categoryCreating = true;
        this.errorMessage = '';
        try {
            const res = await fetch('/api/quick-add/kategori', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ nama: this.newKategoriNama.trim() })
            });
            const { ok, data } = await this.parseJsonResponse(res);
            if (ok && data.success) {
                if (!this.kategoris.some(k => k.id === data.kategori.id)) {
                    this.kategoris.push(data.kategori);
                }
                this.kategoriId = data.kategori.id;
                this.kategoriSearch = data.kategori.nama;
                this.openAddKategori = false;
                this.newKategoriNama = '';
            } else {
                this.errorMessage = this.extractErrorMessage(data, 'Gagal menyimpan kategori.');
            }
        } catch (err) {
            this.errorMessage = 'Gagal terhubung ke server saat menyimpan kategori.';
            console.error(err);
        } finally {
            this.categoryCreating = false;
        }
    },

    async submitSatuan() {
        if (!this.newSatuanKode.trim() || !this.newSatuanNama.trim() || this.satuanCreating) return;
        this.satuanCreating = true;
        this.errorMessage = '';
        try {
            const res = await fetch('/api/quick-add/satuan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ kode: this.newSatuanKode.trim(), nama: this.newSatuanNama.trim() })
            });
            const { ok, data } = await this.parseJsonResponse(res);
            if (ok && data.success) {
                if (!this.satuans.some(s => s.id === data.satuan.id)) {
                    this.satuans.push(data.satuan);
                }
                this.satuanId = data.satuan.id;
                this.satuanSearch = data.satuan.nama + ' (' + data.satuan.kode + ')';
                this.openAddSatuan = false;
                this.newSatuanKode = '';
                this.newSatuanNama = '';
            } else {
                this.errorMessage = this.extractErrorMessage(data, 'Gagal menyimpan satuan.');
            }
        } catch (err) {
            this.errorMessage = 'Gagal terhubung ke server saat menyimpan satuan.';
            console.error(err);
        } finally {
            this.satuanCreating = false;
        }
    },

    resetForm() {
        this.namaBarang = '';
        this.kodeBarang = '';
        this.kategoriId = '';
        this.kategoriSearch = '';
        this.kategoriDropdown = false;
        this.satuanId = '';
        this.satuanSearch = '';
        this.satuanDropdown = false;
        this.minimumStok = 10;
        this.deskripsiBarang = '';
        this.similarItems = [];
        this.hasSimilar = false;
        this.ignoreWarning = false;
        this.errorMessage = '';
    }
}"
    @open-quick-add.window="resetForm(); const detail = $event.detail || {}; kodeBarang = detail.kode || ''; namaBarang = detail.nama || ''; openQuickAdd = true;">

    {{-- Modal Utama Quick-Add Barang --}}
    <div class="modal-backdrop" x-show="openQuickAdd" x-cloak x-transition.opacity>
        <div class="modal-card wide-modal">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">+ Quick-Add Barang Master Baru</h3>
                    <p style="font-size: .8rem; color: var(--text-muted); margin: 2px 0 0;">Tambah katalog produk baru
                        langsung dari dalam transaksi tanpa pindah halaman.</p>
                </div>
                <button type="button" class="modal-close" @click="openQuickAdd = false">&times;</button>
            </div>

            <div class="modal-body">
                <template x-if="errorMessage">
                    <div class="alert-toast alert-toast-danger" style="margin-bottom: 16px;">
                        <span x-text="errorMessage"></span>
                    </div>
                </template>

                <div class="form-group">
                    <label class="form-label">Nama Barang Master <span
                            style="color: var(--status-critical);">*</span></label>
                    <input type="text" class="form-control" x-model="namaBarang"
                        placeholder="Misal: Plastik Vacuum 20x30cm 100s" required>
                </div>

                {{-- Warning Box: Real-Time Semantic Similarity Check --}}
                <template x-if="hasSimilar">
                    <div
                        style="background: rgba(184, 121, 26, 0.1); border: 1.5px solid var(--brand-accent); border-radius: var(--radius); padding: 14px; margin-bottom: 18px;">
                        <div
                            style="display: flex; align-items: center; gap: 8px; color: var(--brand-accent); font-weight: 700; font-size: .88rem; margin-bottom: 8px;">
                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                <line x1="12" y1="9" x2="12" y2="13" />
                                <line x1="12" y1="17" x2="12.01" y2="17" />
                            </svg>
                            <span>⚠️ Peringatan Duplikasi: Ditemukan Barang dengan Nama Mirip</span>
                        </div>
                        <p style="font-size: .82rem; color: var(--text-primary); margin: 0 0 10px;">
                            Sistem menemukan produk master existing yang mirip di katalog. Apakah Anda ingin memakai
                            produk yang sudah ada?
                        </p>
                        <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px;">
                            <template x-for="item in similarItems" :key="item.id">
                                <div
                                    style="display: flex; align-items: center; justify-content: space-between; background: #fff; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--surface-border);">
                                    <div>
                                        <strong style="color: var(--brand-primary);" x-text="item.kode"></strong>
                                        &mdash;
                                        <span style="font-weight: 700;" x-text="item.nama"></span>
                                        <span style="font-size: .75rem; color: var(--text-muted);"
                                            x-text="'(' + item.kategori + ' / ' + item.satuan + ')'"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-accent" @click="selectExisting(item)">
                                        ✓ Gunakan Barang Ini
                                    </button>
                                </div>
                            </template>
                        </div>
                        <label
                            style="display: inline-flex; align-items: center; gap: 8px; font-size: .82rem; font-weight: 600; cursor: pointer; color: var(--text-muted);">
                            <input type="checkbox" x-model="ignoreWarning"
                                style="width: 16px; height: 16px; accent-color: var(--brand-accent);">
                            <span>Tetap buat barang baru ini (Produk Berbeda)</span>
                        </label>
                    </div>
                </template>

                <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Kode Barang (opsional)</label>
                        <input type="text" class="form-control" x-model="kodeBarang" placeholder="STR-PVC-01"
                            style="text-transform: uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stok Min Default</label>
                        <input type="number" class="form-control" x-model="minimumStok" min="0">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <label class="form-label" style="margin-bottom:0;">Kategori <span
                                    style="color: var(--status-critical);">*</span></label>
                            @if ($canCreateKategori)
                                <button type="button" @click="openAddKategori = true"
                                    style="background: none; border: none; font-size: .78rem; color: var(--brand-primary); font-weight: 700; cursor: pointer;">
                                    + Tambah Kategori
                                </button>
                            @endif
                        </div>
                        <div style="position: relative;">
                            <input type="text" class="form-control" x-model="kategoriSearch"
                                @input="kategoriId = ''; kategoriDropdown = true" @focus="kategoriDropdown = true"
                                @click.outside="kategoriDropdown = false" placeholder="Ketik atau pilih kategori..."
                                autocomplete="off" required>
                            <div x-show="kategoriDropdown" x-cloak
                                style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; background: var(--surface-card); border: 1px solid var(--surface-border); border-radius: 6px; box-shadow: 0 8px 18px rgba(0,0,0,.12); max-height: 180px; overflow-y: auto; margin-top: 4px;">
                                <template x-for="kat in filteredKategoris()" :key="kat.id">
                                    <button type="button" @click="selectKategori(kat); kategoriDropdown = false"
                                        style="display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid var(--surface-border); background: transparent; padding: 9px 12px; cursor: pointer;">
                                        <span x-text="kat.nama"></span>
                                    </button>
                                </template>
                                @if ($canCreateKategori)
                                    <button type="button"
                                        x-show="kategoriSearch.trim() && !filteredKategoris().some(kat => kat.nama.toLowerCase() === kategoriSearch.trim().toLowerCase())"
                                        @click="openNewKategori(); kategoriDropdown = false"
                                        :disabled="categoryCreating"
                                        style="display: block; width: 100%; text-align: left; border: 0; background: rgba(20,92,84,.06); color: var(--brand-primary); padding: 10px 12px; cursor: pointer; font-weight: 700;">
                                        <span x-show="!categoryCreating">+ Tambah Kategori “<span
                                                x-text="kategoriSearch"></span>”</span>
                                        <span x-show="categoryCreating">Menyimpan kategori...</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                            <label class="form-label" style="margin-bottom:0;">Satuan Dasar <span
                                    style="color: var(--status-critical);">*</span></label>
                            <button type="button" @click="openAddSatuan = true"
                                style="background: none; border: none; font-size: .78rem; color: var(--brand-primary); font-weight: 700; cursor: pointer;">
                                + Tambah Satuan
                            </button>
                        </div>
                        <div style="position: relative;">
                            <input type="text" class="form-control" x-model="satuanSearch"
                                @input="satuanId = ''; satuanDropdown = true" @focus="satuanDropdown = true"
                                @click.outside="satuanDropdown = false" placeholder="Ketik atau pilih satuan..."
                                autocomplete="off" required>
                            <div x-show="satuanDropdown" x-cloak
                                style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; background: var(--surface-card); border: 1px solid var(--surface-border); border-radius: 6px; box-shadow: 0 8px 18px rgba(0,0,0,.12); max-height: 180px; overflow-y: auto; margin-top: 4px;">
                                <template x-for="sat in filteredSatuans()" :key="sat.id">
                                    <button type="button" @click="selectSatuan(sat); satuanDropdown = false"
                                        style="display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid var(--surface-border); background: transparent; padding: 9px 12px; cursor: pointer;">
                                        <span x-text="sat.nama + ' (' + sat.kode + ')' "></span>
                                    </button>
                                </template>
                                @if (auth()->user()?->can('satuan.create') ?? false)
                                    <button type="button"
                                        x-show="satuanSearch.trim() && !filteredSatuans().some(sat => sat.nama.toLowerCase() === satuanSearch.trim().toLowerCase() || sat.kode.toLowerCase() === satuanSearch.trim().toLowerCase())"
                                        @click="openNewSatuan(); satuanDropdown = false" :disabled="satuanCreating"
                                        style="display: block; width: 100%; text-align: left; border: 0; background: rgba(20,92,84,.06); color: var(--brand-primary); padding: 10px 12px; cursor: pointer; font-weight: 700;">
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
        <div class="modal-backdrop" x-show="openAddKategori" x-cloak style="z-index: 1100;">
            <div class="modal-card" style="max-width: 420px;">
                <div class="modal-header">
                    <h3 class="modal-title">+ Quick-Add Kategori</h3>
                    <button type="button" class="modal-close" @click="openAddKategori = false">&times;</button>
                </div>
                <div class="modal-body">
                    <template x-if="errorMessage">
                        <div class="alert-toast alert-toast-danger" style="margin-bottom: 12px;">
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
    <div class="modal-backdrop" x-show="openAddSatuan" x-cloak style="z-index: 1100;">
        <div class="modal-card" style="max-width: 420px;">
            <div class="modal-header">
                <h3 class="modal-title">+ Quick-Add Satuan</h3>
                <button type="button" class="modal-close" @click="openAddSatuan = false">&times;</button>
            </div>
            <div class="modal-body">
                <template x-if="errorMessage">
                    <div class="alert-toast alert-toast-danger" style="margin-bottom: 12px;">
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
