export function createQuickAddModal(config = {}) {
    return {
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
        unitAliases: config.unitAliases || {},

        // Real-time Semantic Similarity Duplicate Check
        similarItems: [],
        hasSimilar: false,
        ignoreWarning: false,
        isSearchingSimilar: false,
        similarityCheckTimer: null,
        similarityCheckController: null,
        submitting: false,
        categoryCreating: false,
        satuanCreating: false,
        errorMessage: '',

        // Options & endpoints
        kategoris: Array.isArray(config.kategoris) ? config.kategoris : [],
        satuans: Array.isArray(config.satuans) ? config.satuans : [],
        quickAddUrls: {
            checkSimilarity: '/quick-add/check-similarity',
            barang: '/quick-add/barang',
            kategori: '/quick-add/kategori',
            satuan: '/quick-add/satuan',
            ...(config.urls || {}),
        },
        fallbackCsrfToken: config.csrfToken || '',

        init() {
            this.$watch('namaBarang', (val) => this.checkSimilarity(val));
            this.$watch('newSatuanKode', () => this.updateQuickAddSatuanName());
        },

        destroy() {
            this.cancelSimilarityCheck();
        },

        csrfToken() {
            return document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || this.fallbackCsrfToken;
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

        async postJson(url, payload) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                body: JSON.stringify(payload),
            });
            return this.parseJsonResponse(res);
        },

        extractErrorMessage(data, fallback) {
            if (data?.errors) {
                return Object.values(data.errors).flat().join(' ');
            }
            return data?.message || fallback;
        },

        updateQuickAddSatuanName() {
            const value = String(this.newSatuanKode ?? '').trim();
            if (!value) {
                this.newSatuanNama = '';
                return;
            }

            const normalized = value.toLocaleLowerCase('id-ID');
            const alias = this.unitAliases[normalized];
            this.newSatuanNama = alias?.nama ??
                value.toLocaleLowerCase('id-ID').replace(/(^|\s)\S/g, (character) => character.toLocaleUpperCase('id-ID'));
        },

        cancelSimilarityCheck() {
            clearTimeout(this.similarityCheckTimer);
            this.similarityCheckTimer = null;
            this.similarityCheckController?.abort();
            this.similarityCheckController = null;
            this.isSearchingSimilar = false;
        },

        checkSimilarity(name) {
            this.cancelSimilarityCheck();
            const normalizedName = String(name || '').trim();
            if (normalizedName.length < 3) {
                this.similarItems = [];
                this.hasSimilar = false;
                return;
            }

            this.similarityCheckTimer = setTimeout(async () => {
                const controller = new AbortController();
                this.similarityCheckController = controller;
                this.isSearchingSimilar = true;

                try {
                    const res = await fetch(
                        `${this.quickAddUrls.checkSimilarity}?nama=${encodeURIComponent(normalizedName)}`,
                        {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            signal: controller.signal,
                        },
                    );
                    const { ok, data } = await this.parseJsonResponse(res);
                    if (ok && this.similarityCheckController === controller) {
                        this.hasSimilar = Boolean(data.has_similar);
                        this.similarItems = data.similar_items || [];
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                } finally {
                    if (this.similarityCheckController === controller) {
                        this.isSearchingSimilar = false;
                        this.similarityCheckController = null;
                    }
                }
            }, 350);
        },

        selectExisting(item) {
            this.$dispatch('item-selected', item);
            this.openQuickAdd = false;
            this.resetForm();
        },

        filteredKategoris() {
            const query = this.kategoriSearch.trim().toLowerCase();
            if (!query) {
                return this.kategoris.slice(0, 8);
            }
            return this.kategoris.filter((kat) => kat.nama.toLowerCase().includes(query)).slice(0, 8);
        },

        selectKategori(kategori) {
            this.kategoriId = kategori.id;
            this.kategoriSearch = kategori.nama;
        },

        findMatchingKategori(query) {
            const normalized = String(query || '').trim().toLowerCase();
            if (!normalized) {
                return null;
            }
            return this.kategoris.find((kat) => kat.nama.trim().toLowerCase() === normalized) || null;
        },

        openNewKategori() {
            this.newKategoriNama = this.kategoriSearch.trim();
            this.errorMessage = '';
            this.openAddKategori = true;
        },

        async addKategoriFromSearch() {
            const nama = this.kategoriSearch.trim();
            if (!nama || this.categoryCreating) {
                return null;
            }

            const existing = this.findMatchingKategori(nama);
            if (existing) {
                this.selectKategori(existing);
                this.kategoriDropdown = false;
                return existing;
            }

            this.categoryCreating = true;
            this.errorMessage = '';

            try {
                const { ok, data } = await this.postJson(this.quickAddUrls.kategori, { nama });
                if (!ok || !data.success) {
                    this.errorMessage = this.extractErrorMessage(data, 'Kategori gagal ditambahkan.');
                    return null;
                }

                if (!this.kategoris.some((k) => k.id === data.kategori.id)) {
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
            if (!query) {
                return this.satuans.slice(0, 8);
            }
            return this.satuans.filter((satuan) =>
                satuan.nama.toLowerCase().includes(query) || satuan.kode.toLowerCase().includes(query),
            ).slice(0, 8);
        },

        selectSatuan(satuan) {
            this.satuanId = satuan.id;
            this.satuanSearch = `${satuan.nama} (${satuan.kode})`;
        },

        findMatchingSatuan(query) {
            const normalized = String(query || '').trim().toLowerCase();
            if (!normalized) {
                return null;
            }
            return this.satuans.find((sat) =>
                sat.nama.trim().toLowerCase() === normalized ||
                sat.kode.trim().toLowerCase() === normalized ||
                `${sat.nama} (${sat.kode})`.trim().toLowerCase() === normalized,
            ) || null;
        },

        openNewSatuan() {
            this.newSatuanKode = this.satuanSearch.trim();
            this.updateQuickAddSatuanName();
            this.errorMessage = '';
            this.openAddSatuan = true;
        },

        async addSatuanFromSearch() {
            const nama = this.satuanSearch.trim();
            if (!nama || this.satuanCreating) {
                return null;
            }

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
                const { ok, data } = await this.postJson(this.quickAddUrls.satuan, { kode, nama });
                if (!ok || !data.success) {
                    this.errorMessage = this.extractErrorMessage(data, 'Satuan gagal ditambahkan.');
                    return null;
                }

                if (!this.satuans.some((s) => s.id === data.satuan.id)) {
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

            this.cancelSimilarityCheck();
            this.submitting = true;
            this.errorMessage = '';

            try {
                const categoryPromise = !this.kategoriId && this.kategoriSearch.trim()
                    ? this.addKategoriFromSearch()
                    : Promise.resolve(null);
                const unitPromise = !this.satuanId && this.satuanSearch.trim()
                    ? this.addSatuanFromSearch()
                    : Promise.resolve(null);
                const [createdKat, createdSat] = await Promise.all([categoryPromise, unitPromise]);

                if ((!this.kategoriId && this.kategoriSearch.trim() && !createdKat) ||
                    (!this.satuanId && this.satuanSearch.trim() && !createdSat)) {
                    this.submitting = false;
                    return;
                }

                if (!this.kategoriId || !this.satuanId) {
                    this.errorMessage = 'Mohon lengkapi Nama Barang, Kategori, dan Satuan.';
                    this.submitting = false;
                    return;
                }

                const { ok, data } = await this.postJson(this.quickAddUrls.barang, {
                    nama: this.namaBarang.trim(),
                    kode: this.kodeBarang,
                    kategori_id: this.kategoriId,
                    satuan_id: this.satuanId,
                    minimum_stok: this.minimumStok,
                    deskripsi: this.deskripsiBarang,
                });

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
            if (!this.newKategoriNama.trim() || this.categoryCreating) {
                return;
            }
            this.categoryCreating = true;
            this.errorMessage = '';
            try {
                const { ok, data } = await this.postJson(this.quickAddUrls.kategori, {
                    nama: this.newKategoriNama.trim(),
                });
                if (ok && data.success) {
                    if (!this.kategoris.some((k) => k.id === data.kategori.id)) {
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
            if (!this.newSatuanKode.trim() || !this.newSatuanNama.trim() || this.satuanCreating) {
                return;
            }
            this.satuanCreating = true;
            this.errorMessage = '';
            try {
                const { ok, data } = await this.postJson(this.quickAddUrls.satuan, {
                    kode: this.newSatuanKode.trim(),
                    nama: this.newSatuanNama.trim(),
                });
                if (ok && data.success) {
                    if (!this.satuans.some((s) => s.id === data.satuan.id)) {
                        this.satuans.push(data.satuan);
                    }
                    this.satuanId = data.satuan.id;
                    this.satuanSearch = `${data.satuan.nama} (${data.satuan.kode})`;
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

        handleOpen(detail = {}) {
            this.resetForm();
            this.kodeBarang = detail?.kode || '';
            this.namaBarang = detail?.nama || '';
            this.openQuickAdd = true;
        },

        resetForm() {
            this.cancelSimilarityCheck();
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
        },
    };
}

