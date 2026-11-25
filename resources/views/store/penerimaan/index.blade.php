@extends('layouts.app')

@section('title', 'Penerimaan Barang Hari Ini — SIGMA Store')

@section('content')
    @php
        $barangsPayload = $barangs->map(function ($b) {
            return [
                'id' => $b->id,
                'kode' => $b->kode,
                'nama' => $b->nama,
                'kategori_nama' => $b->kategori?->nama ?? 'Umum',
                'satuan_id' => $b->satuan_id,
                'satuan_nama' => $b->satuan?->nama ?? 'unit',
            ];
        });

        $oldItems = old('items');
        $blankItem = [
            'barang_id' => '',
            'barang_search' => '',
            'kategori_nama' => '-',
            'satuan_id' => '',
            'satuan_nama' => '-',
            'qty_diterima' => 0,
            'kondisi' => 'Baik',
            'qty_rusak' => 0,
            'qty_kurang' => 0,
            'qty_lebih' => 0,
            'pemakai' => '',
            'harga_satuan' => 0,
            'total_harga' => 0,
            'catatan' => '',
            'showDropdown' => false,
        ];
        $initialItems = is_array($oldItems) ? array_values($oldItems) : [];
        while (count($initialItems) < 5) {
            $initialItems[] = $blankItem;
        }
        $oldSourceGroups = old('source_groups');
        if (is_array($oldSourceGroups) && count($oldSourceGroups) > 0) {
            $initialSourceGroups = array_values($oldSourceGroups);
            foreach ($initialSourceGroups as $groupIndex => &$sourceGroup) {
                if (!isset($sourceGroup['items']) || !is_array($sourceGroup['items'])) {
                    $sourceGroup['items'] = $groupIndex === 0 && is_array($oldItems) ? $oldItems : $initialItems;
                }
            }
            unset($sourceGroup);
        } else {
            $initialSourceGroups = [
                [
                    'sumber_supplier' => old('sumber_supplier'),
                    'pemesan' => old('pemesan'),
                    'pengambil_barang' => old('pengambil_barang'),
                    'metode_pembayaran' => old('metode_pembayaran', ''),
                    'catatan_rekap' => old('catatan_rekap'),
                    'tanpa_surat_jalan' => old('tanpa_surat_jalan', 'ada'),
                    'alasan_tanpa_sj' => old('alasan_tanpa_sj'),
                    'items' => $initialItems,
                ],
            ];
        }
        $canCreateStorePenerimaan =
            auth()->user()?->hasRole('Super Admin') ||
            auth()->user()?->can('store_penerimaan.create') ||
            auth()->user()?->can('store.create') ||
            auth()->user()?->can('store.penerimaan');

        $penerimaanConfig = [
            'currentTab' => !$canCreateStorePenerimaan
                ? 'riwayat_penerimaan'
                : ($errors->has('search') || $errors->has('q')
                    ? 'riwayat_penerimaan'
                    : ($errors->any() || old('items')
                        ? 'penerimaan_baru'
                        : $activeTab ?? 'penerimaan_baru')),
            'validationErrors' => $errors->getMessages(),
            'barangsList' => $barangsPayload,
            'satuansList' => $satuans
                ->map(fn($s) => ['id' => $s->id, 'nama' => $s->nama, 'kode' => $s->kode])
                ->values(),
            'historyDateFrom' => $dateFrom,
            'historyDateTo' => $dateTo,
            'sourceGroups' => $initialSourceGroups,
            'nomorRcvDatePrefix' => $nomorRcvDatePrefix,
            'receiptSupplierPrefixes' => $receiptSupplierPrefixes,
            'receiptNextSequence' => (int) $receiptNextSequence,
            'receiptUsedTokens' => array_values($receiptUsedTokens),
            'quickAddSatuanUrl' => '/quick-add/satuan',
            'csrfToken' => csrf_token(),
        ];
    @endphp

    <script>
        (function() {
            const config = @json($penerimaanConfig);

            function storePenerimaanApp() {
                return {
                    currentTab: config.currentTab,
                    validationErrors: config.validationErrors,
                    clientErrors: {},
                    barangsList: Array.isArray(config.barangsList) ? [...config.barangsList] : [],
                    satuansList: Array.isArray(config.satuansList) ? [...config.satuansList] : [],
                    activeRowIndex: null,
                    activeGroupIndex: null,
                    dropdownOpen: false,
                    dropdownRow: null,
                    dropdownAnchor: null,
                    dropdownStyle: {},
                    unitDropdownOpen: false,
                    unitDropdownRow: null,
                    unitDropdownAnchor: null,
                    unitDropdownStyle: {},
                    unitDropdownError: '',
                    unitCreating: false,
                    selectedDetailPenerimaan: null,
                    modalDetailOpen: false,
                    historyDateFrom: config.historyDateFrom,
                    historyDateTo: config.historyDateTo,
                    sourceGroups: Array.isArray(config.sourceGroups) ? JSON.parse(JSON.stringify(config.sourceGroups)) :
                        [],
                    nomorRcvDatePrefix: config.nomorRcvDatePrefix,
                    receiptSupplierPrefixes: config.receiptSupplierPrefixes || {},
                    receiptNextSequence: config.receiptNextSequence || 1,
                    receiptUsedTokens: config.receiptUsedTokens || [],
                    receiptTokenBySupplier: {},
                    receiptTokenOwners: {},
                    receiptTokensInUse: new Set(),
                    historySearchLoading: false,
                    historySearchMessage: '',
                    historySearchController: null,

                    async searchReceiptHistory(searchTerm) {
                        const normalizedSearch = searchTerm.trim();
                        this.historySearchController?.abort();
                        this.historySearchController = null;
                        this.historySearchLoading = false;

                        const target = document.querySelector('#receipt-history-results');
                        if (normalizedSearch.length > 0 && normalizedSearch.length < 3) {
                            this.historySearchMessage = 'Masukkan minimal 3 karakter untuk mencari.';
                            if (target) target.hidden = true;
                            return;
                        }

                        if (target) target.hidden = true;
                        const controller = new AbortController();
                        this.historySearchController = controller;

                        const form = this.$refs.receiptHistoryFilter || document.querySelector(
                            'form.receipt-filter-bar');
                        const url = new URL(form.action);
                        for (const [key, value] of new FormData(form).entries()) {
                            if (typeof value === 'string' && value.trim() !== '') {
                                url.searchParams.set(key, value);
                            } else {
                                url.searchParams.delete(key);
                            }
                        }
                        if (normalizedSearch) {
                            url.searchParams.set('search', normalizedSearch);
                        } else {
                            url.searchParams.delete('search');
                        }
                        url.searchParams.delete('page');

                        this.historySearchLoading = true;
                        this.historySearchMessage = '';

                        try {
                            const response = await fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                signal: controller.signal
                            });
                            if (!response.ok) throw new Error('Pencarian riwayat penerimaan gagal dimuat.');

                            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                            const source = doc.querySelector('#receipt-history-results');
                            if (!source || !target) throw new Error('Hasil pencarian riwayat tidak ditemukan.');

                            target.innerHTML = source.innerHTML;
                            target.hidden = false;
                            window.history.replaceState({}, '', url);
                            if (window.Alpine) window.Alpine.initTree(target);
                        } catch (error) {
                            if (error.name !== 'AbortError') {
                                this.historySearchMessage = error.message ||
                                    'Terjadi kesalahan saat mencari riwayat.';
                            }
                        } finally {
                            if (this.historySearchController === controller) {
                                this.historySearchLoading = false;
                                this.historySearchController = null;
                            }
                        }
                    },

                    receiptNumberPreview(groupIndex) {
                        const group = this.sourceGroups[groupIndex];
                        return group?.nomor_rcv_prefix ?
                            this.receiptNumberForGroup(groupIndex, group.nomor_rcv_prefix) :
                            'Token RCV habis';
                    },

                    supplierReceiptKey(supplier) {
                        return '$' + String(supplier || '');
                    },

                    initializeReceiptNumbers() {
                        this.receiptTokenBySupplier = {};
                        this.receiptTokenOwners = {};
                        this.receiptTokensInUse = new Set((this.receiptUsedTokens || []).map(String));

                        Object.entries(this.receiptSupplierPrefixes || {}).forEach(([supplier, prefix]) => {
                            const token = prefix.slice(this.nomorRcvDatePrefix.length, -1);
                            this.receiptTokenBySupplier[this.supplierReceiptKey(supplier)] = token;
                            this.receiptTokenOwners[token] = supplier;
                            this.receiptTokensInUse.add(token);
                        });

                        this.sourceGroups.forEach(group => {
                            group.nomor_rcv_prefix = '';
                            group.nomor_penerimaan_preview = '';
                        });
                        this.sourceGroups.forEach((group, index) => {
                            if (String(group.sumber_supplier || '').trim()) {
                                this.assignReceiptToken(index);
                            } else {
                                this.ensureReceiptToken(index);
                            }
                        });
                    },

                    ensureReceiptToken(groupIndex) {
                        const group = this.sourceGroups[groupIndex];
                        if (group?.nomor_rcv_prefix) return;

                        const token = this.allocateReceiptToken();
                        if (!token) {
                            group.nomor_rcv_prefix = '';
                            group.nomor_penerimaan_preview = '';
                            return;
                        }

                        group.nomor_rcv_prefix = this.nomorRcvDatePrefix + token + '-';
                        group.nomor_penerimaan_preview = this.receiptNumberForGroup(groupIndex, group.nomor_rcv_prefix);
                    },

                    assignReceiptToken(groupIndex) {
                        const group = this.sourceGroups[groupIndex];
                        const supplier = String(group?.sumber_supplier || '').trim();
                        this.ensureReceiptToken(groupIndex);
                        if (!supplier || !group.nomor_rcv_prefix) return;

                        const key = this.supplierReceiptKey(supplier);
                        const existingPrefix = this.receiptSupplierPrefixes[supplier];
                        const sameSupplierGroup = this.sourceGroups.find((candidate, index) =>
                            index !== groupIndex &&
                            String(candidate.sumber_supplier || '').trim() === supplier &&
                            candidate.nomor_rcv_prefix
                        );
                        const desiredPrefix = existingPrefix || sameSupplierGroup?.nomor_rcv_prefix || group
                            .nomor_rcv_prefix;
                        const oldToken = group.nomor_rcv_prefix.slice(this.nomorRcvDatePrefix.length, -1);
                        let token = desiredPrefix.slice(this.nomorRcvDatePrefix.length, -1);
                        const tokenOwner = this.receiptTokenOwners[token];

                        if (tokenOwner && tokenOwner !== supplier) {
                            token = this.allocateReceiptToken();
                            if (!token) {
                                group.nomor_rcv_prefix = '';
                                group.nomor_penerimaan_preview = '';
                                return;
                            }
                        }

                        if (oldToken !== token &&
                            !this.receiptTokenOwners[oldToken] &&
                            !this.receiptUsedTokens.includes(oldToken) &&
                            !this.sourceGroups.some((candidate, index) =>
                                index !== groupIndex && candidate.nomor_rcv_prefix?.endsWith(oldToken + '-')
                            )) {
                            this.receiptTokensInUse.delete(oldToken);
                        }

                        this.receiptTokensInUse.add(token);
                        this.receiptTokenOwners[token] = supplier;
                        this.receiptTokenBySupplier[key] = token;
                        group.nomor_rcv_prefix = this.nomorRcvDatePrefix + token + '-';
                        group.nomor_penerimaan_preview = this.receiptNumberForGroup(groupIndex, group.nomor_rcv_prefix);
                    },

                    allocateReceiptToken() {
                        let token = '';
                        const randomValues = new Uint32Array(1);
                        for (let attempt = 0; attempt < 1000 && !token; attempt += 1) {
                            window.crypto.getRandomValues(randomValues);
                            const candidate = String(randomValues[0] % 1000).padStart(3, '0');
                            if (!this.receiptTokensInUse.has(candidate)) token = candidate;
                        }
                        if (!token) {
                            for (let candidate = 0; candidate < 1000; candidate += 1) {
                                const available = String(candidate).padStart(3, '0');
                                if (!this.receiptTokensInUse.has(available)) {
                                    token = available;
                                    break;
                                }
                            }
                        }
                        if (token) this.receiptTokensInUse.add(token);

                        return token;
                    },

                    receiptNumberForGroup(groupIndex, prefix) {
                        const nextSequence = this.receiptNextSequence + groupIndex;
                        return prefix + String(nextSequence).padStart(4, '0');
                    },

                    receiptDetails(receipt) {
                        if (Array.isArray(receipt?.details) && receipt.details.length > 0) return receipt.details;
                        if (!receipt?.barang_id) return [];

                        return [{
                            id: `legacy-${receipt.id}`,
                            barang: receipt.barang,
                            satuan: receipt.satuan,
                            qty_diterima: receipt.qty_diterima,
                            qty_rusak: 0,
                            qty_kurang: 0,
                            qty_lebih: 0,
                            harga_satuan: 0,
                            total_harga: 0,
                            pemakai: receipt.pemakai,
                            catatan: receipt.catatan
                        }];
                    },

                    receiptQty(receipt, field) {
                        return this.receiptDetails(receipt).reduce((total, item) => total + Number(item[field] || 0),
                            0);
                    },

                    receiptLineValue(item) {
                        return Number(item.total_harga || (Number(item.qty_diterima || 0) * Number(item.harga_satuan ||
                            0)));
                    },

                    receiptValue(receipt) {
                        return this.receiptDetails(receipt).reduce((total, item) => total + this.receiptLineValue(item),
                            0);
                    },

                    receiptIssueValue(receipt, field) {
                        return this.receiptDetails(receipt).reduce((total, item) => total + Number(item[field] || 0) *
                            Number(item.harga_satuan || 0), 0);
                    },

                    receiptUsableValue(receipt) {
                        return Math.max(0, this.receiptValue(receipt) - this.receiptIssueValue(receipt, 'qty_rusak'));
                    },

                    receiptMoney(value) {
                        return 'Rp' + new Intl.NumberFormat('id-ID').format(Number(value || 0));
                    },

                    receiptDate(value) {
                        if (!value) return '—';
                        const date = new Date(value);
                        return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric'
                        }).format(date);
                    },

                    defaultItem() {
                        return {
                            barang_id: '',
                            barang_search: '',
                            kategori_nama: '-',
                            satuan_id: '',
                            satuan_nama: '-',
                            satuan_search: '',
                            qty_diterima: 0,
                            kondisi: 'Baik',
                            qty_rusak: 0,
                            qty_kurang: 0,
                            qty_lebih: 0,
                            pemakai: '',
                            harga_satuan: 0,
                            total_harga: 0,
                            catatan: '',
                            showDropdown: false
                        };
                    },

                    defaultSourceGroup() {
                        return {
                            sumber_supplier: '',
                            pemesan: '',
                            pengambil_barang: '',
                            metode_pembayaran: '',
                            catatan_rekap: '',
                            tanpa_surat_jalan: 'ada',
                            alasan_tanpa_sj: '',
                            items: Array.from({
                                length: 5
                            }, () => this.defaultItem()),
                            expanded: true
                        };
                    },

                    addSourceGroup() {
                        this.sourceGroups.push(this.defaultSourceGroup());
                        this.ensureReceiptToken(this.sourceGroups.length - 1);
                    },

                    groupQty(group) {
                        return (group.items || []).reduce((total, row) => total + Number(row.qty_diterima || 0), 0);
                    },

                    groupTotal(group) {
                        return (group.items || []).reduce((total, row) => total + (Number(row.qty_diterima || 0) *
                            Number(row.harga_satuan || 0)), 0);
                    },

                    isBlankItem(row) {
                        return !row.barang_id &&
                            !String(row.barang_search || '').trim() &&
                            Number(row.qty_diterima || 0) === 0 &&
                            !row.satuan_id &&
                            (row.kondisi || 'Baik') === 'Baik' &&
                            Number(row.qty_rusak || 0) === 0 &&
                            Number(row.qty_kurang || 0) === 0 &&
                            Number(row.qty_lebih || 0) === 0 &&
                            !String(row.pemakai || '').trim() &&
                            Number(row.harga_satuan || 0) === 0 &&
                            !String(row.catatan || '').trim();
                    },

                    removeSourceGroup(index) {
                        if (this.sourceGroups.length > 1) {
                            this.sourceGroups.splice(index, 1);
                        }
                    },

                    fieldError(field) {
                        return this.clientErrors[field] || this.validationErrors[field]?.[0] || '';
                    },

                    validateReceipt(event) {
                        const form = event.target;
                        const errors = {};
                        const tanggal = form.querySelector('[name=tanggal_diterima]');

                        this.sourceGroups.forEach((group, index) => this.assignReceiptToken(index));

                        if (!tanggal?.value) {
                            errors.tanggal_diterima = 'Tanggal penerimaan wajib diisi.';
                        }

                        this.sourceGroups.forEach((group, index) => {
                            const prefix = `source_groups.${index}`;
                            const groupFields = [
                                ['sumber_supplier',
                                    'Nama supplier wajib diisi pada setiap kelompok sumber.'
                                ],
                                ['pemesan', 'Nama pemesan / order wajib diisi pada kelompok sumber.'],
                                ['pengambil_barang',
                                    'Nama pengambil barang / ekspedisi wajib diisi pada kelompok sumber.'
                                ],
                                ['metode_pembayaran',
                                    'Metode pembayaran wajib dipilih pada kelompok sumber.'
                                ],
                            ];

                            groupFields.forEach(([name, message]) => {
                                const value = group?.[name];
                                if (!String(value ?? '').trim()) {
                                    errors[`${prefix}.${name}`] = message;
                                }
                            });
                            if (group.tanpa_surat_jalan === 'belum_ada' && String(group.alasan_tanpa_sj || '')
                                .trim().length < 3) {
                                errors[`${prefix}.alasan_tanpa_sj`] =
                                    'Catatan surat jalan menyusul wajib diisi minimal 3 karakter.';
                            }
                        });

                        this.sourceGroups.forEach((group, groupIndex) => {
                            const filledRows = (group.items || []).filter(row => !this.isBlankItem(row));
                            if (filledRows.length === 0) {
                                errors[`source_groups.${groupIndex}.items`] =
                                    'Tambahkan minimal satu barang untuk supplier ini.';
                            }

                            const selectedBarangIds = new Set();
                            (group.items || []).forEach((row, itemIndex) => {
                                if (this.isBlankItem(row)) return;

                                const prefix = `source_groups.${groupIndex}.items.${itemIndex}`;
                                if (!row.barang_id) {
                                    errors[`${prefix}.barang_id`] =
                                        'Pilih barang dari daftar hasil pencarian.';
                                } else if (selectedBarangIds.has(String(row.barang_id))) {
                                    errors[`${prefix}.barang_id`] = this.duplicateBarangMessage;
                                } else {
                                    selectedBarangIds.add(String(row.barang_id));
                                }
                                if (!Number.isInteger(Number(row.qty_diterima)) || Number(row
                                        .qty_diterima) < 1) {
                                    errors[`${prefix}.qty_diterima`] =
                                        'Qty diterima wajib diisi minimal 1.';
                                }
                                if (!row.satuan_id) {
                                    errors[`${prefix}.satuan_id`] = 'Satuan barang wajib dipilih.';
                                }
                                if (['Rusak', 'Rusak dan Kurang'].includes(row.kondisi) && (!Number
                                        .isInteger(Number(row.qty_rusak)) || Number(row.qty_rusak) <
                                        1 || Number(row.qty_rusak) > Number(row.qty_diterima))) {
                                    errors[`${prefix}.qty_rusak`] =
                                        'Qty rusak harus minimal 1 dan tidak melebihi qty diterima.';
                                }
                                if (['Kurang', 'Rusak dan Kurang'].includes(row.kondisi) && (!Number
                                        .isInteger(Number(row.qty_kurang)) || Number(row.qty_kurang) < 1
                                    )) {
                                    errors[`${prefix}.qty_kurang`] =
                                        'Isi jumlah barang yang kurang minimal 1.';
                                }
                                if (['Lebih', 'Rusak dan Lebih'].includes(row.kondisi) && (!Number
                                        .isInteger(Number(row.qty_lebih)) || Number(row.qty_lebih) <
                                        1 || Number(row.qty_lebih) > Number(row.qty_diterima))) {
                                    errors[`${prefix}.qty_lebih`] =
                                        'Qty lebih harus minimal 1 dan tidak melebihi qty diterima.';
                                }
                            });
                        });

                        this.clientErrors = errors;
                        if (Object.keys(errors).length > 0) {
                            this.currentTab = 'penerimaan_baru';
                            const firstError = Object.keys(errors)[0];
                            this.$nextTick(() => {
                                const field = form.querySelector(`[data-field='${firstError}']`);
                                field?.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'center',
                                    inline: 'center'
                                });
                                field?.focus({
                                    preventScroll: true
                                });
                            });
                            return;
                        }

                        this.$nextTick(() => form.submit());
                    },

                    init() {
                        this.dropdownScrollHandler = () => {
                            this.refreshDropdownPosition();
                            this.refreshUnitDropdownPosition();
                        };
                        document.addEventListener('scroll', this.dropdownScrollHandler, true);

                        this.sourceGroups.forEach(group => {
                            if (typeof group.expanded === 'undefined') group.expanded = true;
                            group.tanpa_surat_jalan = ['belum_ada', true, 1, '1', 'true', 'on', 'yes'].includes(
                                    group.tanpa_surat_jalan) ?
                                'belum_ada' :
                                'ada';
                            if (typeof group.alasan_tanpa_sj === 'undefined') group.alasan_tanpa_sj = '';
                            if (!Array.isArray(group.items)) group.items = [];
                            group.items.forEach(row => {
                                if (row.barang_id) {
                                    const found = this.barangsList.find(b => String(b.id) === String(row
                                        .barang_id));
                                    if (found) {
                                        row.barang_search = found.kode + ' — ' + found.nama;
                                        row.kategori_nama = found.kategori_nama;
                                        row.satuan_id = found.satuan_id;
                                        row.satuan_nama = found.satuan_nama;
                                    }
                                }
                                const satuan = this.satuansList.find(item => String(item.id) === String(
                                    row.satuan_id));
                                row.satuan_search = row.satuan_id ? (satuan?.nama || row.satuan_nama ||
                                    '') : '';
                                row.showDropdown = false;
                            });
                        });
                        this.initializeReceiptNumbers();
                    },

                    destroy() {
                        document.removeEventListener('scroll', this.dropdownScrollHandler, true);
                    },

                    addRow(groupIndex) {
                        this.sourceGroups[groupIndex].items.push(this.defaultItem());
                        delete this.clientErrors[`source_groups.${groupIndex}.items`];
                    },

                    removeRow(groupIndex, itemIndex) {
                        const rows = this.sourceGroups[groupIndex].items;
                        rows.splice(itemIndex, 1);
                        this.clearDuplicateBarangErrors(groupIndex);
                    },

                    selectBarang(row, brg) {
                        const groupIndex = this.sourceGroups.findIndex(group => group.items.includes(row));
                        const itemIndex = this.sourceGroups[groupIndex].items.indexOf(row);
                        if (this.isBarangSelectedInGroup(groupIndex, row, brg.id)) {
                            this.clientErrors[`source_groups.${groupIndex}.items.${itemIndex}.barang_id`] = this
                                .duplicateBarangMessage;
                            return;
                        }

                        row.barang_id = brg.id;
                        delete this.clientErrors[`source_groups.${groupIndex}.items.${itemIndex}.barang_id`];
                        this.clearDuplicateBarangErrors(groupIndex);
                        row.barang_search = brg.kode + ' — ' + brg.nama;
                        row.kategori_nama = brg.kategori_nama;
                        row.satuan_id = brg.satuan_id;
                        row.satuan_nama = brg.satuan_nama;
                        row.satuan_search = brg.satuan_nama;
                        row.showDropdown = false;
                        this.closeBarangDropdown();
                    },

                    openBarangDropdown(row, groupIndex, itemIndex, event) {
                        this.dropdownRow = row;
                        this.activeGroupIndex = groupIndex;
                        this.activeRowIndex = itemIndex;
                        this.dropdownAnchor = event.currentTarget;
                        this.dropdownOpen = true;
                        this.$nextTick(() => this.refreshDropdownPosition());
                    },

                    refreshDropdownPosition() {
                        if (!this.dropdownOpen || !this.dropdownAnchor?.isConnected) return;

                        const rect = this.dropdownAnchor.getBoundingClientRect();
                        const viewportPadding = 8;
                        const width = Math.min(rect.width, window.innerWidth - (viewportPadding * 2));
                        const left = Math.max(viewportPadding, Math.min(rect.left, window.innerWidth - width -
                            viewportPadding));
                        const below = window.innerHeight - rect.bottom - viewportPadding;
                        const above = rect.top - viewportPadding;
                        const openAbove = below < 180 && above > below;
                        const maxHeight = Math.max(100, Math.min(260, openAbove ? above : below));

                        this.dropdownStyle = {
                            position: 'fixed',
                            left: `${left}px`,
                            width: `${width}px`,
                            top: openAbove ? 'auto' : `${rect.bottom}px`,
                            bottom: openAbove ? `${window.innerHeight - rect.top}px` : 'auto',
                            maxHeight: `${maxHeight}px`,
                            zIndex: 100000
                        };
                    },

                    closeBarangDropdown() {
                        this.dropdownOpen = false;
                        this.dropdownRow = null;
                        this.dropdownAnchor = null;
                    },

                    openUnitDropdown(row, event) {
                        this.closeBarangDropdown();
                        this.unitDropdownRow = row;
                        this.unitDropdownAnchor = event.currentTarget;
                        this.unitDropdownError = '';
                        this.unitDropdownOpen = true;
                        this.$nextTick(() => this.refreshUnitDropdownPosition());
                    },

                    refreshUnitDropdownPosition() {
                        if (!this.unitDropdownOpen || !this.unitDropdownAnchor?.isConnected) return;

                        const rect = this.unitDropdownAnchor.getBoundingClientRect();
                        const padding = 8;
                        const width = Math.min(Math.max(rect.width, 140), window.innerWidth - padding * 2);
                        const left = Math.max(padding, Math.min(rect.left, window.innerWidth - width - padding));
                        const availableBelow = Math.max(80, window.innerHeight - rect.bottom - padding);

                        this.unitDropdownStyle = {
                            position: 'fixed',
                            left: `${left}px`,
                            top: `${rect.bottom + 2}px`,
                            width: `${width}px`,
                            maxHeight: `${Math.min(280, availableBelow)}px`,
                            zIndex: 100001
                        };
                    },

                    chooseUnit(row, satuan) {
                        row.satuan_id = satuan.id;
                        row.satuan_nama = satuan.nama;
                        row.satuan_search = satuan.nama;
                        const groupIndex = this.sourceGroups.findIndex(group => group.items.includes(row));
                        const itemIndex = this.sourceGroups[groupIndex].items.indexOf(row);
                        delete this.clientErrors[`source_groups.${groupIndex}.items.${itemIndex}.satuan_id`];
                        this.unitDropdownError = '';
                        this.unitDropdownOpen = false;
                        this.unitDropdownRow = null;
                        this.unitDropdownAnchor = null;
                    },

                    closeUnitDropdown() {
                        this.unitDropdownOpen = false;
                        this.unitDropdownRow = null;
                        this.unitDropdownAnchor = null;
                        this.unitDropdownError = '';
                    },

                    filteredReceiptSatuans(query) {
                        const normalizedQuery = this.normalizeSearch(query);
                        if (!normalizedQuery) return this.satuansList.slice(0, 10);

                        return this.satuansList.filter(satuan =>
                            this.normalizeSearch(satuan.nama).includes(normalizedQuery) ||
                            this.normalizeSearch(satuan.kode || '').includes(normalizedQuery)
                        ).slice(0, 10);
                    },

                    async addReceiptSatuan(row) {
                        const nama = String(row.satuan_search || '').trim();
                        if (!nama || this.unitCreating) return;

                        const existing = this.satuansList.find(satuan =>
                            this.normalizeSearch(satuan.nama) === this.normalizeSearch(nama) ||
                            this.normalizeSearch(satuan.kode || '') === this.normalizeSearch(nama)
                        );
                        if (existing) {
                            this.chooseUnit(row, existing);
                            return;
                        }

                        this.unitCreating = true;
                        this.unitDropdownError = '';
                        const kode = nama.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0,
                            20);

                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || config.csrfToken;
                            const response = await fetch(config.quickAddSatuanUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    kode,
                                    nama
                                }),
                            });
                            const data = await response.json();
                            if (!response.ok || !data.success || !data.satuan) {
                                this.unitDropdownError = data.message || 'Satuan gagal ditambahkan.';
                                return;
                            }

                            const satuan = {
                                id: data.satuan.id,
                                nama: data.satuan.nama,
                                kode: data.satuan.kode,
                            };
                            if (!this.satuansList.some(item => String(item.id) === String(satuan.id))) {
                                this.satuansList.push(satuan);
                            }
                            this.chooseUnit(row, satuan);
                        } catch {
                            this.unitDropdownError =
                                'Terjadi kesalahan saat menambahkan satuan. Silakan coba lagi.';
                        } finally {
                            this.unitCreating = false;
                        }
                    },

                    normalizeSearch(value) {
                        return String(value ?? '').trim().toLocaleLowerCase('id-ID');
                    },

                    normalizeCode(value) {
                        return String(value ?? '').toLocaleLowerCase('id-ID').replace(/[^a-z0-9]/g, '');
                    },

                    duplicateBarangMessage: 'Barang yang sama hanya boleh dimasukkan satu kali dalam supplier ini.',

                    isBarangSelectedInGroup(groupIndex, currentRow, barangId) {
                        return (this.sourceGroups[groupIndex]?.items || []).some(row =>
                            row !== currentRow && String(row.barang_id || '') === String(barangId)
                        );
                    },

                    clearDuplicateBarangErrors(groupIndex) {
                        const prefix = `source_groups.${groupIndex}.items.`;
                        Object.keys(this.clientErrors).forEach(field => {
                            if (field.startsWith(prefix) && field.endsWith('.barang_id') &&
                                this.clientErrors[field] === this.duplicateBarangMessage) {
                                delete this.clientErrors[field];
                            }
                        });
                    },

                    matchesBarangSearch(barang, query) {
                        const q = this.normalizeSearch(query);
                        if (!q) return true;

                        const normalizedQuery = this.normalizeCode(q);
                        const nama = this.normalizeSearch(barang.nama);
                        const kode = this.normalizeSearch(barang.kode);
                        const normalizedKode = this.normalizeCode(barang.kode);
                        const normalizedLabel = this.normalizeCode(`${barang.kode} ${barang.nama}`);
                        const queryParts = q.split(/\s+[—–-]\s+/).map(part => part.trim()).filter(Boolean);
                        const matchesParts = queryParts.length > 1 && queryParts.every(part =>
                            kode.includes(part) || nama.includes(part)
                        );

                        return nama.includes(q) ||
                            kode.includes(q) ||
                            (normalizedQuery !== '' && (normalizedKode.includes(normalizedQuery) || normalizedLabel
                                .includes(normalizedQuery))) ||
                            matchesParts;
                    },

                    hasMatchingBarangAlreadySelected(query, groupIndex, currentRow) {
                        return this.barangsList.some(barang =>
                            this.matchesBarangSearch(barang, query) &&
                            this.isBarangSelectedInGroup(groupIndex, currentRow, barang.id)
                        );
                    },

                    filteredBarangs(query, groupIndex = this.activeGroupIndex, currentRow = this.dropdownRow) {
                        const q = this.normalizeSearch(query);
                        return this.barangsList
                            .filter(barang => this.matchesBarangSearch(barang, q))
                            .filter(barang => !this.isBarangSelectedInGroup(groupIndex, currentRow, barang.id))
                            .slice(0, 10);
                    },

                    highlightBarangMatch(value, query) {
                        const text = String(value ?? '');
                        const terms = String(query ?? '').trim().split(/\s+/).filter(Boolean);
                        const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        })[character]);

                        if (terms.length === 0) return escapeHtml(text);

                        const escapeRegExp = value => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        const matcher = new RegExp(terms.sort((left, right) => right.length - left.length).map(
                            escapeRegExp).join('|'), 'gi');
                        let html = '';
                        let lastIndex = 0;
                        let match;

                        while ((match = matcher.exec(text)) !== null) {
                            html += escapeHtml(text.slice(lastIndex, match.index));
                            html +=
                                `<mark style="padding:0 1px;border-radius:2px;background:rgba(245,190,66,.38);color:inherit;">${escapeHtml(match[0])}</mark>`;
                            lastIndex = match.index + match[0].length;
                        }

                        return html + escapeHtml(text.slice(lastIndex));
                    },

                    formatCurrency(value) {
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0,
                        }).format(Number(value || 0));
                    },

                    openQuickAdd(row, groupIndex, index) {
                        this.activeRowIndex = index;
                        this.activeGroupIndex = groupIndex;
                        row.showDropdown = false;
                        this.closeBarangDropdown();
                        const query = String(row.barang_search || '').trim();
                        const looksLikeCode = /^(?=.*\d)[a-z0-9]+(?:[-_/][a-z0-9]+)*$/i.test(query);
                        const payload = {
                            kode: looksLikeCode ? query.toUpperCase() : '',
                            nama: looksLikeCode ? '' : query,
                        };
                        if (typeof this.$dispatch === 'function') {
                            this.$dispatch('open-quick-add', payload);
                        } else {
                            window.dispatchEvent(new CustomEvent('open-quick-add', {
                                detail: payload
                            }));
                        }
                    },

                    handleItemSelected(detail) {
                        const newBrg = {
                            id: detail.id,
                            kode: detail.kode,
                            nama: detail.nama,
                            kategori_nama: detail.kategori_nama || 'Umum',
                            satuan_id: detail.satuan_id,
                            satuan_nama: detail.satuan_nama || 'unit'
                        };
                        this.barangsList.push(newBrg);
                        if (this.activeGroupIndex !== null && this.activeRowIndex !== null && this.sourceGroups[this
                                .activeGroupIndex]?.items?.[this.activeRowIndex]) {
                            this.selectBarang(this.sourceGroups[this.activeGroupIndex].items[this.activeRowIndex],
                                newBrg);
                        }
                    },

                    handleWindowClick(event) {
                        if (!event.target.closest('[data-receipt-autocomplete]')) this.closeBarangDropdown();
                        if (!event.target.closest('[data-receipt-unit]')) this.closeUnitDropdown();
                    }
                };
            }

            window.storePenerimaanApp = storePenerimaanApp;
            if (window.Alpine) {
                window.Alpine.data('storePenerimaanApp', storePenerimaanApp);
            }
            document.addEventListener('alpine:init', function() {
                Alpine.data('storePenerimaanApp', storePenerimaanApp);
            });
        })();
    </script>

    <div x-data="storePenerimaanApp()" @item-selected.window="handleItemSelected($event.detail)"
        @click.window="handleWindowClick($event)" @keydown.escape.window="closeBarangDropdown(); closeUnitDropdown()"
        @resize.window="refreshDropdownPosition(); refreshUnitDropdownPosition()">
        {{-- Page Header --}}
        <div class="page-header">
            <div>
                <h1 class="page-title">Penerimaan Barang Hari Ini</h1>
                <p class="page-subtitle">Pencatatan penerimaan harian dengan rincian barang per supplier.</p>
            </div>
        </div>

        <div class="receipt-tabs" role="tablist" aria-label="Menu penerimaan barang"
            style="display:flex;gap:8px;border-bottom:2px solid var(--surface-border);margin-bottom:20px;">
            @if ($canCreateStorePenerimaan)
                <button type="button" role="tab" :aria-selected="currentTab === 'penerimaan_baru'" class="btn"
                    :class="currentTab === 'penerimaan_baru' ? 'btn-primary' : 'btn-outline'"
                    @click="currentTab = 'penerimaan_baru'"
                    style="border-bottom-left-radius:0;border-bottom-right-radius:0;">
                    <span aria-hidden="true">＋</span> Form Penerimaan Baru
                </button>
            @endif
            <button type="button" role="tab" :aria-selected="currentTab === 'riwayat_penerimaan'" class="btn"
                :class="currentTab === 'riwayat_penerimaan' ? 'btn-primary' : 'btn-outline'"
                @click="currentTab = 'riwayat_penerimaan'"
                style="border-bottom-left-radius:0;border-bottom-right-radius:0;">
                <span aria-hidden="true">◷</span> Riwayat Penerimaan
            </button>
        </div>

        <div x-show="currentTab === 'penerimaan_baru'" x-cloak class="receipt-form-panel">
            <form action="{{ route('store.penerimaan.store') }}" method="POST" enctype="multipart/form-data" novalidate
                @submit.prevent="validateReceipt($event)">
                @csrf
                @if ($errors->any())
                    <div role="alert"
                        style="background: rgba(193, 68, 60, .1); border: 1px solid var(--status-critical); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: var(--status-critical);">
                        <strong>Periksa kembali data penerimaan:</strong>
                        <ul style="list-style: disc; margin: 6px 0 0 20px; padding: 0;">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div x-show="Object.keys(clientErrors).length > 0" x-cloak role="alert"
                    style="background: rgba(193, 68, 60, .1); border: 1px solid var(--status-critical); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: var(--status-critical);">
                    <strong>Masih ada data wajib yang belum benar.</strong>
                    <span>Periksa pesan merah pada field yang ditandai.</span>
                </div>

                <style>
                    .receipt-tabs {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 8px;
                        border-bottom: 2px solid var(--surface-border);
                        margin-bottom: 18px;
                    }

                    .receipt-tabs .btn {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        gap: 7px;
                        min-height: 40px;
                        flex: 0 0 auto;
                    }

                    .receipt-form-panel,
                    .receipt-form-panel form {
                        width: 100%;
                        max-width: 100%;
                        min-width: 0;
                    }

                    .receipt-date-strip {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        width: 100%;
                        padding: 10px 14px;
                        margin-bottom: 14px;
                        border: 1px solid var(--surface-border);
                        border-radius: 8px;
                        background: var(--surface-card);
                    }

                    .receipt-date-label {
                        color: var(--text-muted);
                        font-size: .8rem;
                    }

                    .receipt-date-value {
                        color: var(--text-primary);
                        font-weight: 800;
                    }

                    .receipt-source-card {
                        width: 100%;
                        max-width: 100%;
                        min-width: 0;
                        margin-bottom: 12px;
                        border: 1px solid var(--surface-border);
                        border-radius: 9px;
                        background: var(--surface-card);
                        overflow: visible;
                    }

                    .receipt-source-head {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 9px 10px;
                        border-bottom: 1px solid var(--surface-border);
                    }

                    .receipt-source-number {
                        width: 22px;
                        height: 22px;
                        display: grid;
                        place-items: center;
                        flex: 0 0 22px;
                        color: white;
                        background: var(--brand-primary);
                        border-radius: 4px;
                        font-size: .72rem;
                        font-weight: 800;
                    }

                    .receipt-source-title {
                        width: min(260px, 45vw);
                        border: 0;
                        background: transparent;
                        font-weight: 700;
                    }

                    .receipt-source-content {
                        min-width: 0;
                        padding: 10px;
                    }

                    .receipt-history-card {
                        overflow: hidden;
                    }

                    .receipt-history-header {
                        display: flex;
                        align-items: center;
                        gap: 11px;
                        padding: 11px 14px;
                    }

                    .receipt-history-mark {
                        display: grid;
                        place-items: center;
                        flex: 0 0 34px;
                        width: 34px;
                        height: 34px;
                        border-radius: 8px;
                        color: var(--brand-primary);
                        background: rgba(15, 82, 125, .08);
                        font-size: .64rem;
                        font-weight: 900;
                    }

                    .receipt-history-heading {
                        min-width: 0;
                        display: flex;
                        flex: 1;
                        flex-direction: column;
                        gap: 2px;
                    }

                    .receipt-history-heading strong {
                        color: var(--brand-primary);
                        font-size: .86rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-history-heading span {
                        color: var(--text-muted);
                        font-size: .72rem;
                    }

                    .receipt-history-header-actions {
                        display: flex;
                        align-items: center;
                        justify-content: flex-end;
                        gap: 8px;
                        flex-wrap: wrap;
                    }

                    .receipt-history-body {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 14px;
                        padding: 0 14px 11px 59px;
                    }

                    .receipt-history-context {
                        min-width: 0;
                        color: var(--text-muted);
                        font-size: .72rem;
                        line-height: 1.5;
                        overflow-wrap: anywhere;
                    }

                    .receipt-history-context strong {
                        color: var(--text-primary);
                        font-weight: 700;
                    }

                    .receipt-history-summary {
                        display: flex;
                        flex: 0 0 auto;
                        align-items: center;
                        gap: 14px;
                    }

                    .receipt-history-metric {
                        display: flex;
                        flex-direction: column;
                        gap: 1px;
                        text-align: right;
                    }

                    .receipt-history-metric span {
                        color: var(--text-muted);
                        font-size: .64rem;
                    }

                    .receipt-history-metric strong {
                        color: var(--text-primary);
                        font-size: .78rem;
                        white-space: nowrap;
                    }

                    .receipt-history-metric.is-value strong {
                        color: var(--brand-primary);
                    }

                    .receipt-history-empty {
                        padding: 22px 14px;
                        color: var(--text-muted);
                        text-align: center;
                    }

                    .receipt-dashboard {
                        display: grid;
                        gap: 14px;
                    }

                    .receipt-dashboard-heading {
                        display: flex;
                        align-items: flex-end;
                        justify-content: space-between;
                        gap: 12px;
                    }

                    .receipt-dashboard-heading h2 {
                        margin: 0;
                        font-size: 1rem;
                        font-weight: 850;
                    }

                    .receipt-dashboard-heading p {
                        margin: 3px 0 0;
                        color: var(--text-muted);
                        font-size: .75rem;
                    }

                    .receipt-filter-bar {
                        display: grid;
                        grid-template-columns: minmax(190px, 1.5fr) minmax(150px, 1fr) repeat(2, minmax(135px, .8fr)) auto;
                        align-items: center;
                        gap: 7px;
                        padding: 10px;
                        border: 1px solid var(--surface-border);
                        border-radius: 8px;
                        background: var(--surface-card);
                    }

                    .receipt-filter-bar .form-control {
                        min-width: 0;
                        min-height: 36px;
                        padding: 7px 9px;
                        font-size: .76rem;
                    }

                    .receipt-filter-bar .receipt-filter-search {
                        min-width: 0;
                        width: 100%;
                    }

                    .receipt-filter-bar .receipt-filter-search .search-input {
                        min-width: 0;
                        height: 36px;
                        font-size: .76rem;
                    }

                    .receipt-filter-custom-dates {
                        grid-column: 1 / -1;
                        display: flex;
                        justify-content: flex-end;
                        gap: 7px;
                    }

                    .receipt-filter-actions {
                        display: flex;
                        gap: 6px;
                    }

                    .receipt-kpi-grid {
                        display: grid;
                        grid-template-columns: repeat(4, minmax(0, 1fr));
                        gap: 10px;
                    }

                    .receipt-kpi {
                        display: flex;
                        min-width: 0;
                        flex-direction: column;
                        gap: 5px;
                        padding: 12px 13px;
                        border: 1px solid var(--surface-border);
                        border-radius: 9px;
                        background: var(--surface-card);
                        color: inherit;
                        text-decoration: none;
                        transition: transform .15s ease, border-color .15s ease;
                    }

                    .receipt-kpi:hover {
                        transform: translateY(-1px);
                        border-color: var(--brand-primary);
                    }

                    .receipt-kpi-label {
                        color: var(--text-muted);
                        font-size: .72rem;
                        font-weight: 700;
                    }

                    .receipt-kpi-value {
                        font-size: 1.23rem;
                        font-weight: 850;
                        line-height: 1.1;
                    }

                    .receipt-kpi-foot {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 5px;
                        color: var(--text-muted);
                        font-size: .68rem;
                    }

                    .receipt-comparison {
                        font-weight: 750;
                    }

                    .receipt-comparison.up {
                        color: var(--status-success);
                    }

                    .receipt-comparison.down {
                        color: var(--status-warning);
                    }

                    .receipt-comparison.neutral {
                        color: var(--text-muted);
                    }

                    .receipt-dashboard-panel {
                        min-width: 0;
                        overflow: hidden;
                        border: 1px solid var(--surface-border);
                        border-radius: 9px;
                        background: var(--surface-card);
                    }

                    .receipt-panel-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 10px;
                        padding: 11px 13px;
                        border-bottom: 1px solid var(--surface-border);
                    }

                    .receipt-panel-header h3 {
                        margin: 0;
                        font-size: .84rem;
                        font-weight: 850;
                    }

                    .receipt-panel-header p {
                        margin: 3px 0 0;
                        color: var(--text-muted);
                        font-size: .69rem;
                    }

                    .receipt-panel-header-period {
                        color: var(--text-muted);
                        font-size: .68rem;
                        white-space: nowrap;
                    }

                    .receipt-followup-list {
                        max-height: 300px;
                        overflow: auto;
                    }

                    .receipt-followup-row {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto auto;
                        align-items: center;
                        gap: 10px;
                        padding: 9px 13px;
                        border-bottom: 1px solid var(--surface-border);
                    }

                    .receipt-followup-row:last-child {
                        border-bottom: 0;
                    }

                    .receipt-followup-main {
                        min-width: 0;
                    }

                    .receipt-followup-main strong {
                        display: block;
                        color: var(--brand-primary);
                        font-size: .75rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-followup-main span {
                        display: block;
                        margin-top: 2px;
                        color: var(--text-muted);
                        font-size: .7rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-followup-value {
                        color: var(--text-primary);
                        font-size: .72rem;
                        font-weight: 750;
                        white-space: nowrap;
                    }

                    .receipt-followup-actions {
                        display: flex;
                        gap: 5px;
                    }

                    .receipt-followup-empty {
                        display: flex;
                        align-items: center;
                        gap: 9px;
                        padding: 15px 13px;
                        color: var(--status-success);
                        font-size: .78rem;
                        font-weight: 750;
                    }

                    .receipt-dashboard-columns {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                        gap: 12px;
                    }

                    .receipt-value-breakdown {
                        padding: 12px 13px;
                    }

                    .receipt-value-row {
                        display: grid;
                        grid-template-columns: minmax(115px, 1fr) minmax(80px, auto);
                        gap: 8px;
                        align-items: center;
                        padding: 5px 0;
                        font-size: .74rem;
                        color: inherit;
                        text-decoration: none;
                    }

                    a.receipt-value-row:hover span {
                        text-decoration: underline;
                    }

                    .receipt-value-row span {
                        color: var(--text-muted);
                    }

                    .receipt-value-row strong {
                        text-align: right;
                    }

                    .receipt-value-bars {
                        display: flex;
                        height: 9px;
                        overflow: hidden;
                        margin: 7px 0 9px;
                        border-radius: 99px;
                        background: var(--surface-base);
                    }

                    .receipt-value-bar-usable {
                        background: var(--status-success);
                    }

                    .receipt-value-bar-damaged {
                        background: var(--status-critical);
                    }

                    .receipt-value-note {
                        padding: 8px 9px;
                        border-radius: 6px;
                        background: var(--surface-base);
                        color: var(--text-muted);
                        font-size: .68rem;
                        line-height: 1.45;
                    }

                    .receipt-trend-wrap {
                        display: flex;
                        min-height: 168px;
                        align-items: flex-end;
                        gap: 5px;
                        overflow-x: auto;
                        padding: 13px;
                    }

                    .receipt-trend-item {
                        display: flex;
                        flex: 1 0 24px;
                        min-width: 24px;
                        height: 145px;
                        flex-direction: column;
                        justify-content: flex-end;
                        align-items: center;
                        gap: 5px;
                        color: var(--text-muted);
                        text-decoration: none;
                    }

                    .receipt-trend-bar {
                        display: flex;
                        width: min(100%, 22px);
                        min-height: 2px;
                        flex-direction: column;
                        justify-content: flex-end;
                        overflow: hidden;
                        border-radius: 4px 4px 1px 1px;
                        background: var(--brand-primary);
                    }

                    .receipt-trend-label {
                        font-size: .59rem;
                        white-space: nowrap;
                    }

                    .receipt-rank-table-wrap {
                        max-width: 100%;
                        overflow-x: auto;
                    }

                    .receipt-rank-table {
                        width: 100%;
                        min-width: 510px;
                        border-collapse: collapse;
                    }

                    .receipt-rank-table th,
                    .receipt-rank-table td {
                        padding: 8px 11px;
                        border-bottom: 1px solid var(--surface-border);
                        font-size: .72rem;
                        text-align: left;
                    }

                    .receipt-rank-table th {
                        color: var(--text-muted);
                        font-size: .64rem;
                        text-transform: uppercase;
                    }

                    .receipt-rank-table td:last-child,
                    .receipt-rank-table th:last-child {
                        text-align: right;
                    }

                    .receipt-rank-link {
                        color: var(--brand-primary);
                        font-weight: 750;
                        text-decoration: none;
                    }

                    .receipt-rank-link:hover {
                        text-decoration: underline;
                    }

                    .receipt-category-list {
                        display: grid;
                        gap: 8px;
                        padding: 12px 13px;
                    }

                    .receipt-category-row {
                        display: grid;
                        grid-template-columns: minmax(90px, 1fr) minmax(80px, 2fr) auto;
                        align-items: center;
                        gap: 8px;
                        color: inherit;
                        text-decoration: none;
                    }

                    .receipt-category-name {
                        font-size: .72rem;
                        font-weight: 700;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .receipt-category-track {
                        height: 8px;
                        overflow: hidden;
                        border-radius: 99px;
                        background: var(--surface-base);
                    }

                    .receipt-category-fill {
                        height: 100%;
                        border-radius: inherit;
                        background: var(--brand-primary);
                    }

                    .receipt-category-percent {
                        min-width: 42px;
                        color: var(--text-muted);
                        font-size: .68rem;
                        text-align: right;
                    }

                    .receipt-latest-list {
                        display: grid;
                    }

                    .receipt-latest-row {
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto auto;
                        align-items: center;
                        gap: 10px;
                        padding: 8px 13px;
                        border-bottom: 1px solid var(--surface-border);
                    }

                    .receipt-latest-row:last-child {
                        border-bottom: 0;
                    }

                    .receipt-latest-main {
                        min-width: 0;
                    }

                    .receipt-latest-main strong {
                        display: block;
                        color: var(--brand-primary);
                        font-size: .73rem;
                    }

                    .receipt-latest-main span {
                        display: block;
                        margin-top: 2px;
                        color: var(--text-muted);
                        font-size: .68rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-history-table-wrap {
                        max-width: 100%;
                        overflow-x: auto;
                        border: 1px solid var(--surface-border);
                        border-radius: 9px;
                        background: var(--surface-card);
                    }

                    .receipt-history-table {
                        width: 100%;
                        min-width: 970px;
                        border-collapse: collapse;
                    }

                    .receipt-history-table th,
                    .receipt-history-table td {
                        padding: 9px 10px;
                        border-bottom: 1px solid var(--surface-border);
                        font-size: .72rem;
                        vertical-align: top;
                        text-align: left;
                    }

                    .receipt-history-table th {
                        position: sticky;
                        top: 0;
                        z-index: 1;
                        color: var(--text-muted);
                        background: var(--surface-base);
                        font-size: .63rem;
                        text-transform: uppercase;
                        letter-spacing: .025em;
                    }

                    .receipt-history-table tbody tr:last-child td {
                        border-bottom: 0;
                    }

                    .receipt-history-table .receipt-rcv {
                        color: var(--brand-primary);
                        font-weight: 800;
                        overflow-wrap: anywhere;
                    }

                    .receipt-history-issue-tags {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 4px;
                    }

                    .receipt-history-section-title {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 10px;
                        margin: 4px 0 8px;
                    }

                    .receipt-history-section-title h3 {
                        margin: 0;
                        font-size: .9rem;
                        font-weight: 850;
                    }

                    .receipt-followup-modal {
                        width: min(480px, calc(100vw - 28px));
                    }

                    .receipt-detail-modal {
                        width: min(1180px, calc(100vw - 32px));
                        max-width: min(1180px, calc(100vw - 32px));
                        max-height: calc(100vh - 32px);
                        overflow: auto;
                    }

                    .receipt-detail-number {
                        margin-top: 3px;
                        color: var(--brand-primary);
                        font-size: .78rem;
                        font-weight: 800;
                    }

                    .receipt-detail-modal .modal-body {
                        display: grid;
                        gap: 10px;
                    }

                    .receipt-detail-alert {
                        padding: 9px 11px;
                        border: 1px solid rgba(185, 112, 18, .18);
                        border-radius: 7px;
                        color: #8a4b08;
                        background: rgba(217, 143, 34, .15);
                        font-size: .74rem;
                    }

                    .receipt-detail-supplier {
                        min-width: 0;
                        padding: 11px 12px 8px;
                        border: 1px solid var(--surface-border);
                        border-radius: 8px;
                        background: var(--surface-card);
                    }

                    .receipt-detail-supplier-head {
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        margin-bottom: 9px;
                    }

                    .receipt-detail-supplier-index {
                        display: inline-flex;
                        width: 20px;
                        height: 20px;
                        align-items: center;
                        justify-content: center;
                        flex: 0 0 20px;
                        border-radius: 5px;
                        color: #fff;
                        background: var(--brand-primary);
                        font-size: .68rem;
                        font-weight: 800;
                    }

                    .receipt-detail-supplier-head strong {
                        min-width: 0;
                        font-size: .78rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-detail-facts {
                        display: grid;
                        grid-template-columns: repeat(4, minmax(0, 1fr));
                        gap: 7px;
                        margin-bottom: 0;
                    }

                    .receipt-detail-facts>div {
                        min-width: 0;
                        display: flex;
                        flex-direction: column;
                        gap: 4px;
                        padding: 0;
                        border: 0;
                        background: transparent;
                    }

                    .receipt-detail-facts span,
                    .receipt-detail-group-meta span {
                        color: var(--text-muted);
                        font-size: .66rem;
                    }

                    .receipt-detail-facts strong {
                        overflow-wrap: anywhere;
                        font-size: .75rem;
                    }

                    .receipt-detail-group-meta {
                        display: grid;
                        grid-template-columns: repeat(4, minmax(0, 1fr));
                        gap: 7px;
                        margin-bottom: 8px;
                    }

                    .receipt-detail-group-meta>div {
                        min-width: 0;
                        display: flex;
                        flex-direction: column;
                        gap: 3px;
                    }

                    .receipt-detail-group-meta strong {
                        font-size: .73rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-detail-group-note {
                        margin: 0 0 7px;
                        color: var(--text-muted);
                        font-size: .69rem;
                        overflow-wrap: anywhere;
                    }

                    .receipt-detail-table-wrap {
                        max-width: 100%;
                        overflow-x: auto;
                        border-top: 1px solid var(--surface-border);
                    }

                    .receipt-detail-table-wrap table {
                        width: 100%;
                        min-width: 760px;
                    }

                    .receipt-detail-table-wrap th,
                    .receipt-detail-table-wrap td {
                        padding: 7px 6px;
                        font-size: .69rem;
                        white-space: nowrap;
                    }

                    .receipt-detail-table-wrap th {
                        color: var(--text-muted);
                    }

                    .receipt-detail-table-wrap td:first-child {
                        min-width: 150px;
                        white-space: normal;
                    }

                    .receipt-detail-subtotal {
                        display: flex;
                        justify-content: flex-end;
                        gap: 20px;
                        padding: 8px 5px 2px;
                        font-size: .72rem;
                    }

                    .receipt-detail-reading-note {
                        margin: 0;
                        padding: 8px 10px;
                        border-radius: 7px;
                        color: var(--text-muted);
                        background: var(--surface-base);
                        font-size: .68rem;
                        line-height: 1.45;
                    }

                    .receipt-detail-value-breakdown {
                        display: grid;
                        gap: 6px;
                        padding: 9px 11px;
                        border: 1px solid var(--surface-border);
                        border-radius: 8px;
                        background: var(--surface-card);
                        font-size: .73rem;
                    }

                    .receipt-detail-value-row {
                        display: flex;
                        align-items: flex-start;
                        justify-content: space-between;
                        gap: 12px;
                    }

                    .receipt-detail-value-row strong {
                        flex: 0 0 auto;
                        text-align: right;
                    }

                    .receipt-detail-value-row.is-damaged {
                        color: var(--status-critical);
                    }

                    .receipt-detail-value-row.is-short {
                        color: var(--status-warning);
                    }

                    .receipt-detail-summary {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        padding: 10px 12px;
                        border-radius: 8px;
                        color: var(--brand-primary);
                        background: rgba(15, 82, 125, .10);
                        font-size: .72rem;
                    }

                    .receipt-detail-summary strong {
                        display: block;
                        margin-top: 2px;
                        font-size: .92rem;
                        text-align: right;
                    }

                    .receipt-detail-summary small {
                        color: var(--text-muted);
                        font-size: .65rem;
                    }

                    .receipt-meta-grid {
                        display: grid;
                        grid-template-columns: repeat(5, minmax(0, 1fr));
                        gap: 8px;
                    }

                    .receipt-meta-grid .form-group {
                        margin: 0;
                    }

                    .receipt-meta-grid .form-label {
                        margin-bottom: 4px;
                        font-size: .72rem;
                    }

                    .receipt-meta-grid .form-control {
                        min-height: 34px;
                        padding: 6px 8px;
                        font-size: .8rem;
                    }

                    .receipt-items-wrap {
                        width: 100%;
                        max-width: 100%;
                        min-width: 0;
                        overflow-x: auto;
                        margin-top: 10px;
                        overscroll-behavior-inline: contain;
                    }

                    .receipt-items-table {
                        width: 1020px;
                        min-width: 1020px;
                        table-layout: fixed;
                    }

                    .receipt-items-table th {
                        padding: 6px 5px;
                        font-size: .68rem;
                        white-space: nowrap;
                    }

                    .receipt-items-table td {
                        padding: 4px;
                        vertical-align: top;
                    }

                    .receipt-items-table .form-control {
                        min-height: 32px;
                        padding: 5px 7px;
                        font-size: .76rem;
                    }

                    .receipt-items-table .receipt-condition-qty {
                        width: 78%;
                        min-width: 72px;
                        max-width: 96px;
                        min-height: 28px;
                        height: 28px;
                        margin-top: 4px;
                        padding: 4px 6px;
                        font-size: .72rem;
                    }

                    .receipt-add-button {
                        width: 100%;
                        margin-top: 6px;
                        border: 1px dashed var(--surface-border);
                        background: transparent;
                    }

                    @media (max-width: 900px) {
                        .receipt-meta-grid {
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                        }
                    }

                    @media (max-width: 900px) {
                        .receipt-history-body {
                            align-items: flex-start;
                            flex-direction: column;
                            gap: 8px;
                            padding-left: 59px;
                        }

                        .receipt-history-summary {
                            width: 100%;
                            justify-content: flex-start;
                        }

                        .receipt-history-metric {
                            align-items: flex-start;
                            text-align: left;
                        }

                        .receipt-detail-facts,
                        .receipt-detail-group-meta {
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                        }
                    }

                    @media (max-width: 640px) {
                        .receipt-history-header {
                            align-items: flex-start;
                            flex-wrap: wrap;
                        }

                        .receipt-history-header-actions {
                            margin-left: 45px;
                        }

                        .receipt-history-body {
                            padding-left: 14px;
                        }

                        .receipt-history-summary {
                            justify-content: space-between;
                            gap: 8px;
                        }
                    }

                    @media (max-width: 520px) {
                        .receipt-history-header-actions {
                            width: 100%;
                            margin-left: 45px;
                            justify-content: flex-start;
                        }

                        .receipt-history-summary {
                            flex-wrap: wrap;
                        }

                        .receipt-history-metric {
                            min-width: calc(33.333% - 8px);
                        }

                        .receipt-detail-modal {
                            width: calc(100vw - 20px);
                            max-height: calc(100vh - 20px);
                        }

                        .receipt-detail-facts,
                        .receipt-detail-group-meta {
                            grid-template-columns: 1fr 1fr;
                            gap: 7px;
                        }

                        .receipt-detail-value-row {
                            gap: 6px;
                            font-size: .68rem;
                        }

                        .receipt-detail-summary {
                            align-items: flex-start;
                        }
                    }

                    @media (max-width: 900px) {
                        .receipt-filter-bar {
                            grid-template-columns: repeat(3, minmax(0, 1fr));
                        }
                    }

                    @media (max-width: 640px) {
                        .receipt-filter-bar {
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                        }

                        .receipt-filter-bar .receipt-filter-search {
                            grid-column: 1 / -1;
                        }

                        .receipt-filter-actions {
                            grid-column: 1 / -1;
                        }

                        .receipt-filter-actions .btn {
                            flex: 1;
                        }
                    }

                    @media (max-width: 520px) {
                        .receipt-tabs .btn {
                            flex: 1 1 100%;
                        }

                        .receipt-date-strip {
                            align-items: flex-start;
                            flex-direction: column;
                            gap: 2px;
                        }

                        .receipt-meta-grid {
                            grid-template-columns: 1fr;
                        }

                        .receipt-source-title {
                            width: min(190px, 42vw);
                        }

                        .receipt-history-header-actions {
                            padding-left: 0;
                        }

                        .receipt-history-item-meta {
                            grid-column: 1 / -1;
                            grid-row: auto;
                            justify-content: flex-start;
                        }
                    }
                </style>

                <div class="receipt-date-strip">
                    <span class="receipt-date-label">Tanggal Penerimaan</span>
                    <strong class="receipt-date-value">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</strong>
                    <input type="hidden" name="tanggal_diterima" value="{{ now()->toDateString() }}"
                        data-field="tanggal_diterima">
                </div>
                <template x-for="(group, groupIndex) in sourceGroups" :key="groupIndex">
                    <section class="receipt-source-card">
                        <header class="receipt-source-head">
                            <span class="receipt-source-number" x-text="groupIndex + 1"></span>
                            <input type="text" class="receipt-source-title"
                                :name="'source_groups[' + groupIndex + '][sumber_supplier]'" x-model="group.sumber_supplier"
                                placeholder="Isi nama supplier terlebih dahulu" aria-label="Nama supplier" required
                                :data-field="'source_groups.' + groupIndex + '.sumber_supplier'"
                                @input="delete clientErrors['source_groups.' + groupIndex + '.sumber_supplier']"
                                @blur="assignReceiptToken(groupIndex)">
                            <span style="margin-left:auto;color:var(--text-muted);font-size:.74rem;white-space:nowrap;"
                                x-text="(group.items || []).length + ' barang, ' + groupQty(group) + ' qty'"></span>
                            <button type="button" class="btn btn-outline" @click="group.expanded = !group.expanded"
                                :aria-expanded="group.expanded" style="padding:4px 8px;line-height:1;"
                                :aria-label="group.expanded ? 'Ciutkan supplier' : 'Buka supplier'">
                                <span x-text="group.expanded ? '⌄' : '›'"></span>
                            </button>
                            <button type="button" class="btn btn-outline" x-show="sourceGroups.length > 1"
                                @click="removeSourceGroup(groupIndex)" style="padding:4px 8px;color:var(--status-critical);"
                                title="Hapus supplier">×</button>
                        </header>

                        <div class="receipt-source-content" x-show="group.expanded" x-transition>
                            <input type="hidden" :name="'source_groups[' + groupIndex + '][nomor_rcv_prefix]'"
                                x-model="group.nomor_rcv_prefix">
                            <input type="hidden" :name="'source_groups[' + groupIndex + '][nomor_penerimaan_preview]'"
                                :value="receiptNumberPreview(groupIndex)">
                            <span x-show="fieldError('source_groups.' + groupIndex + '.sumber_supplier')" x-cloak
                                style="display:block;margin-bottom:8px;color:var(--status-critical);font-size:.75rem;"
                                x-text="fieldError('source_groups.' + groupIndex + '.sumber_supplier')"></span>
                            <div class="receipt-meta-grid">
                                <div class="form-group">
                                    <label class="form-label">Nomor RCV (otomatis)</label>
                                    <input type="text" class="form-control" :value="receiptNumberPreview(groupIndex)"
                                        readonly aria-label="Nomor RCV otomatis">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pembayaran <span
                                            style="color:var(--status-critical);">*</span></label>
                                    <select :name="'source_groups[' + groupIndex + '][metode_pembayaran]'"
                                        x-model="group.metode_pembayaran" class="form-control"
                                        :data-field="'source_groups.' + groupIndex + '.metode_pembayaran'" required
                                        @change="delete clientErrors['source_groups.' + groupIndex + '.metode_pembayaran']">
                                        <option value="">Pilih</option>
                                        <option value="Tunai">Tunai</option>
                                        <option value="Transfer">Transfer</option>
                                        <option value="Kredit">Kredit</option>
                                        <option value="Debit">Debit</option>
                                    </select>
                                    <span x-show="fieldError('source_groups.' + groupIndex + '.metode_pembayaran')" x-cloak
                                        class="field-error"
                                        x-text="fieldError('source_groups.' + groupIndex + '.metode_pembayaran')"></span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pemesan <span
                                            style="color:var(--status-critical);">*</span></label>
                                    <input type="text" :name="'source_groups[' + groupIndex + '][pemesan]'"
                                        x-model="group.pemesan" class="form-control"
                                        :data-field="'source_groups.' + groupIndex + '.pemesan'" required
                                        @input="delete clientErrors['source_groups.' + groupIndex + '.pemesan']">
                                    <span x-show="fieldError('source_groups.' + groupIndex + '.pemesan')" x-cloak
                                        class="field-error"
                                        x-text="fieldError('source_groups.' + groupIndex + '.pemesan')"></span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pengambil / Kurir <span
                                            style="color:var(--status-critical);">*</span></label>
                                    <input type="text" :name="'source_groups[' + groupIndex + '][pengambil_barang]'"
                                        x-model="group.pengambil_barang" class="form-control"
                                        :data-field="'source_groups.' + groupIndex + '.pengambil_barang'" required
                                        @input="delete clientErrors['source_groups.' + groupIndex + '.pengambil_barang']">
                                    <span x-show="fieldError('source_groups.' + groupIndex + '.pengambil_barang')" x-cloak
                                        class="field-error"
                                        x-text="fieldError('source_groups.' + groupIndex + '.pengambil_barang')"></span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Surat Jalan <span
                                            style="color:var(--status-critical);">*</span></label>
                                    <select :name="'source_groups[' + groupIndex + '][tanpa_surat_jalan]'"
                                        x-model="group.tanpa_surat_jalan" class="form-control">
                                        <option value="ada">Ada</option>
                                        <option value="belum_ada">Belum ada</option>
                                    </select>
                                </div>
                            </div>
                            <div x-show="group.tanpa_surat_jalan === 'belum_ada'" x-cloak style="margin-top:8px;">
                                <label class="form-label" style="font-size:.72rem;">Catatan tindak lanjut surat jalan
                                    <span style="color:var(--status-critical);">*</span></label>
                                <input type="text" :name="'source_groups[' + groupIndex + '][alasan_tanpa_sj]'"
                                    x-model="group.alasan_tanpa_sj" class="form-control"
                                    :data-field="'source_groups.' + groupIndex + '.alasan_tanpa_sj'"
                                    placeholder="Contoh: surat jalan menyusul dari supplier">
                                <span x-show="fieldError('source_groups.' + groupIndex + '.alasan_tanpa_sj')" x-cloak
                                    class="field-error"
                                    x-text="fieldError('source_groups.' + groupIndex + '.alasan_tanpa_sj')"></span>
                            </div>
                            <div style="margin-top:8px;">
                                <label class="form-label" style="font-size:.72rem;">Catatan Supplier</label>
                                <input type="text" :name="'source_groups[' + groupIndex + '][catatan_rekap]'"
                                    x-model="group.catatan_rekap" class="form-control" placeholder="Opsional">
                            </div>

                            <div class="receipt-items-wrap">
                                <table class="data-table receipt-items-table">
                                    <colgroup>
                                        <col style="width:18%;">
                                        <col style="width:10%;">
                                        <col style="width:8%;">
                                        <col style="width:7%;">
                                        <col style="width:13%;">
                                        <col style="width:12%;">
                                        <col style="width:12%;">
                                        <col style="width:12%;">
                                        <col style="width:4%;">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Kategori</th>
                                            <th>Satuan</th>
                                            <th>Qty</th>
                                            <th>Kondisi</th>
                                            <th>User / Pemakai</th>
                                            <th>Harga/unit</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, itemIndex) in group.items" :key="itemIndex">
                                            <tr>
                                                <td style="position:relative;">
                                                    <input type="hidden"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][barang_id]'"
                                                        x-model="row.barang_id">
                                                    <input type="text" x-model="row.barang_search"
                                                        data-receipt-autocomplete
                                                        @focus="openBarangDropdown(row, groupIndex, itemIndex, $event)"
                                                        @input="row.barang_id = ''; delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.barang_id']; openBarangDropdown(row, groupIndex, itemIndex, $event)"
                                                        placeholder="Cari nama/kode barang" class="form-control"
                                                        :data-field="'source_groups.' + groupIndex + '.items.' + itemIndex +
                                                            '.barang_id'"
                                                        autocomplete="off">
                                                    <span
                                                        x-show="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.barang_id')"
                                                        x-cloak
                                                        style="display:block;color:var(--status-critical);font-size:.7rem;"
                                                        x-text="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.barang_id')"></span>
                                                </td>
                                                <td><span class="badge badge-neutral"
                                                        style="display:block;overflow:hidden;text-overflow:ellipsis;"
                                                        x-text="row.kategori_nama || '—'"></span></td>
                                                <td>
                                                    <input type="hidden"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][satuan_id]'"
                                                        x-model="row.satuan_id">
                                                    <input type="text" class="form-control" data-receipt-unit
                                                        :data-field="'source_groups.' + groupIndex + '.items.' + itemIndex +
                                                            '.satuan_id'"
                                                        x-model="row.satuan_search" @focus="openUnitDropdown(row, $event)"
                                                        @input="row.satuan_id = ''; row.satuan_nama = ''; delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.satuan_id']; openUnitDropdown(row, $event)"
                                                        placeholder="Ketik atau pilih satuan..." autocomplete="off"
                                                        :aria-expanded="unitDropdownOpen && unitDropdownRow === row"
                                                        aria-haspopup="listbox">
                                                    <span
                                                        x-show="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.satuan_id')"
                                                        x-cloak
                                                        style="display:block;color:var(--status-critical);font-size:.7rem;"
                                                        x-text="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.satuan_id')"></span>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][qty_diterima]'"
                                                        x-model.number="row.qty_diterima" class="form-control tabular"
                                                        min="1" placeholder="0" required
                                                        :data-field="'source_groups.' + groupIndex + '.items.' + itemIndex +
                                                            '.qty_diterima'"
                                                        @input="delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_diterima']">
                                                    <span
                                                        x-show="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_diterima')"
                                                        x-cloak
                                                        style="display:block;color:var(--status-critical);font-size:.7rem;"
                                                        x-text="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_diterima')"></span>
                                                </td>
                                                <td>
                                                    <select
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][kondisi]'"
                                                        x-model="row.kondisi" class="form-control"
                                                        @change="if (!['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(row.kondisi)) row.qty_rusak = 0; if (!['Kurang', 'Rusak dan Kurang'].includes(row.kondisi)) row.qty_kurang = 0; if (!['Lebih', 'Rusak dan Lebih'].includes(row.kondisi)) row.qty_lebih = 0; delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_rusak']; delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_kurang']; delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_lebih'];">
                                                        <option value="Baik">Baik</option>
                                                        <option value="Rusak">Rusak</option>
                                                        <option value="Kurang">Kurang</option>
                                                        <option value="Rusak dan Kurang">Rusak &amp; Kurang</option>
                                                        <option value="Lebih">Lebih</option>
                                                        <option value="Rusak dan Lebih">Rusak &amp; Lebih</option>
                                                    </select>
                                                    <input
                                                        x-show="['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(row.kondisi)"
                                                        x-cloak type="number"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][qty_rusak]'"
                                                        x-model.number="row.qty_rusak"
                                                        @input="delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_rusak']"
                                                        min="1" :max="row.qty_diterima" placeholder="Qty rusak"
                                                        class="form-control receipt-condition-qty"
                                                        :data-field="'source_groups.' + groupIndex + '.items.' + itemIndex +
                                                            '.qty_rusak'">
                                                    <span
                                                        x-show="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_rusak')"
                                                        x-cloak
                                                        style="display:block;color:var(--status-critical);font-size:.7rem;"
                                                        x-text="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_rusak')"></span>
                                                    <input x-show="['Kurang', 'Rusak dan Kurang'].includes(row.kondisi)"
                                                        x-cloak type="number"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][qty_kurang]'"
                                                        x-model.number="row.qty_kurang"
                                                        @input="delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_kurang']"
                                                        min="1" placeholder="Qty kurang"
                                                        class="form-control receipt-condition-qty"
                                                        :data-field="'source_groups.' + groupIndex + '.items.' + itemIndex +
                                                            '.qty_kurang'">
                                                    <span
                                                        x-show="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_kurang')"
                                                        x-cloak
                                                        style="display:block;color:var(--status-critical);font-size:.7rem;"
                                                        x-text="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_kurang')"></span>
                                                    <input x-show="['Lebih', 'Rusak dan Lebih'].includes(row.kondisi)"
                                                        x-cloak type="number"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][qty_lebih]'"
                                                        x-model.number="row.qty_lebih"
                                                        @input="delete clientErrors['source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_lebih']"
                                                        min="1" :max="row.qty_diterima"
                                                        :required="['Lebih', 'Rusak dan Lebih'].includes(row.kondisi)"
                                                        placeholder="Qty lebih" class="form-control receipt-condition-qty"
                                                        :data-field="'source_groups.' + groupIndex + '.items.' + itemIndex +
                                                            '.qty_lebih'">
                                                    <span
                                                        x-show="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_lebih')"
                                                        x-cloak
                                                        style="display:block;color:var(--status-critical);font-size:.7rem;"
                                                        x-text="fieldError('source_groups.' + groupIndex + '.items.' + itemIndex + '.qty_lebih')"></span>
                                                </td>
                                                <td><input type="text"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][pemakai]'"
                                                        x-model="row.pemakai" placeholder="Stok / TMS / SHT"
                                                        class="form-control" maxlength="255"
                                                        aria-label="User atau pemakai barang"></td>
                                                <td><input type="number"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][harga_satuan]'"
                                                        x-model.number="row.harga_satuan" min="0" step="1000"
                                                        placeholder="Opsional" class="form-control tabular"></td>
                                                <td><input type="hidden"
                                                        :name="'source_groups[' + groupIndex + '][items][' + itemIndex +
                                                            '][total_harga]'"
                                                        :value="Number(row.qty_diterima || 0) * Number(row.harga_satuan || 0)"><span
                                                        class="tabular" style="font-size:.74rem;"
                                                        x-text="formatCurrency(Number(row.qty_diterima || 0) * Number(row.harga_satuan || 0))"></span>
                                                </td>
                                                <td style="text-align:center;"><button type="button"
                                                        class="btn btn-outline" @click="removeRow(groupIndex, itemIndex)"
                                                        style="padding:3px 7px;color:var(--text-muted);"
                                                        aria-label="Hapus barang">×</button></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn receipt-add-button"
                                :data-field="'source_groups.' + groupIndex + '.items'" @click="addRow(groupIndex)">+
                                Tambah Barang</button>
                            <span x-show="fieldError('source_groups.' + groupIndex + '.items')" x-cloak
                                style="display:block;margin-top:4px;color:var(--status-critical);font-size:.75rem;"
                                x-text="fieldError('source_groups.' + groupIndex + '.items')"></span>
                        </div>
                    </section>
                </template>

                <button type="button" class="btn receipt-add-button"
                    style="margin:0 0 12px;padding:9px;font-weight:700;" @click="addSourceGroup()">+ Tambah
                    Supplier</button>

                <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
                    <button type="button" class="btn btn-outline"
                        @click="currentTab='riwayat_penerimaan'">Batal</button>
                    <button type="submit" class="btn btn-primary" style="padding:10px 24px;font-weight:800;">✓ Simpan
                        Penerimaan</button>
                </div>
            </form>

            <div x-show="dropdownOpen && dropdownRow" x-cloak data-receipt-autocomplete :style="dropdownStyle"
                style="overflow-y:auto;background:var(--surface-card);border:1px solid var(--surface-border);border-radius:7px;box-shadow:0 12px 28px rgba(0,0,0,.22);">
                <template x-for="brg in filteredBarangs(dropdownRow?.barang_search, activeGroupIndex, dropdownRow)"
                    :key="brg.id">
                    <button type="button"
                        style="display:block;width:100%;padding:9px 10px;text-align:left;border:0;border-bottom:1px solid var(--surface-border);background:var(--surface-card);cursor:pointer;"
                        @mousedown.prevent @click="selectBarang(dropdownRow, brg)">
                        <strong style="font-size:.82rem;"><span
                                x-html="highlightBarangMatch(brg.kode, dropdownRow?.barang_search)"></span> — <span
                                x-html="highlightBarangMatch(brg.nama, dropdownRow?.barang_search)"></span></strong>
                        <small style="display:block;color:var(--text-muted);font-size:.72rem;"><span
                                x-html="highlightBarangMatch(brg.kategori_nama, dropdownRow?.barang_search)"></span> ·
                            <span x-text="brg.satuan_nama"></span></small>
                    </button>
                </template>
                <template
                    x-if="dropdownRow && normalizeSearch(dropdownRow.barang_search) && filteredBarangs(dropdownRow.barang_search, activeGroupIndex, dropdownRow).length === 0 && hasMatchingBarangAlreadySelected(dropdownRow.barang_search, activeGroupIndex, dropdownRow)">
                    <div role="status"
                        style="padding:9px 10px;background:var(--surface-base);color:var(--status-critical);font-size:.78rem;">
                        Barang ini sudah ditambahkan pada supplier yang sama.
                    </div>
                </template>
                <template
                    x-if="dropdownRow && normalizeSearch(dropdownRow.barang_search) && filteredBarangs(dropdownRow.barang_search, activeGroupIndex, dropdownRow).length === 0 && !hasMatchingBarangAlreadySelected(dropdownRow.barang_search, activeGroupIndex, dropdownRow)">
                    <button type="button"
                        style="display:block;width:100%;padding:9px 10px;text-align:left;border:0;background:var(--surface-base);color:var(--brand-primary);cursor:pointer;"
                        @mousedown.prevent @click="openQuickAdd(dropdownRow, activeGroupIndex, activeRowIndex)">+ Tambah
                        barang baru: <span x-text="dropdownRow?.barang_search"></span></button>
                </template>
            </div>

            <div x-show="unitDropdownOpen && unitDropdownRow" x-cloak data-receipt-unit :style="unitDropdownStyle"
                style="overflow-y:auto;background:var(--surface-card);border:1px solid var(--surface-border);border-radius:7px;box-shadow:0 12px 28px rgba(0,0,0,.22);">
                <template x-for="satuan in filteredReceiptSatuans(unitDropdownRow?.satuan_search)" :key="satuan.id">
                    <button type="button" role="option"
                        :aria-selected="String(unitDropdownRow?.satuan_id) === String(satuan.id)" @mousedown.prevent
                        @click="chooseUnit(unitDropdownRow, satuan)"
                        style="display:block;width:100%;padding:8px 10px;text-align:left;border:0;border-bottom:1px solid var(--surface-border);background:var(--surface-card);cursor:pointer;">
                        <span x-text="satuan.nama"></span><small x-show="satuan.kode"
                            style="margin-left:4px;color:var(--text-muted);" x-text="'(' + satuan.kode + ')'"></small>
                    </button>
                </template>
                @if (auth()->user()?->can('satuan.create') ?? false)
                    <button type="button"
                        x-show="unitDropdownRow?.satuan_search?.trim() && !filteredReceiptSatuans(unitDropdownRow?.satuan_search).some(satuan => satuan.nama.toLowerCase() === unitDropdownRow.satuan_search.trim().toLowerCase() || satuan.kode.toLowerCase() === unitDropdownRow.satuan_search.trim().toLowerCase())"
                        @mousedown.prevent @click="addReceiptSatuan(unitDropdownRow)" :disabled="unitCreating"
                        style="display:block;width:100%;padding:9px 10px;text-align:left;border:0;background:var(--surface-base);color:var(--brand-primary);cursor:pointer;font-weight:700;">
                        <span x-show="!unitCreating">+ Tambah Satuan “<span
                                x-text="unitDropdownRow?.satuan_search"></span>”</span>
                        <span x-show="unitCreating">Menyimpan satuan...</span>
                    </button>
                @endif
                <p x-show="unitDropdownError" x-cloak role="alert"
                    style="margin:0;padding:8px 10px;color:var(--status-critical);font-size:.72rem;"
                    x-text="unitDropdownError"></p>
            </div>
        </div>


        {{-- ═══════════════════════════════════════════ TAB 2: RIWAYAT PENERIMAAN BARANG ═══ --}}
        <div x-show="currentTab === 'riwayat_penerimaan'" x-cloak>
            <div class="receipt-dashboard">
                <div class="receipt-dashboard-heading">
                    <div>
                        <h2>Riwayat Penerimaan</h2>
                        <p>Cari dan buka detail dokumen penerimaan barang.</p>
                    </div>
                </div>

                <form x-ref="receiptHistoryFilter" action="{{ route('store.penerimaan.index') }}" method="GET"
                    class="receipt-filter-bar">
                    <input type="hidden" name="tab" value="riwayat_penerimaan">
                    <label class="search-box receipt-filter-search" aria-label="Pencarian semantik penerimaan">
                        <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" class="search-icon icon-inline" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input type="search" name="search" value="{{ $search }}" class="search-input"
                            placeholder="Ketik minimal 3 karakter; hasil muncul otomatis..." autocomplete="off"
                            aria-label="Cari nomor RCV, supplier, atau detail barang"
                            @input.debounce.300ms="searchReceiptHistory($event.target.value)">
                    </label>
                    <select name="supplier" class="form-control">
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $supplierOption)
                            <option value="{{ $supplierOption }}" @selected($supplier === $supplierOption)>{{ $supplierOption }}
                            </option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" x-model="historyDateFrom"
                        @change="if (historyDateTo < historyDateFrom) historyDateTo = historyDateFrom"
                        class="form-control" aria-label="Tanggal awal">
                    <input type="date" name="date_to" value="{{ $dateTo }}" x-model="historyDateTo"
                        :min="historyDateFrom" min="{{ $dateFrom }}" class="form-control"
                        aria-label="Tanggal akhir">
                    <div class="receipt-filter-actions">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('store.penerimaan.index', ['tab' => 'riwayat_penerimaan']) }}"
                            class="btn btn-outline">Reset</a>
                    </div>
                </form>
                <div style="min-height:20px;margin:4px 2px 8px;font-size:.75rem;color:var(--text-muted);"
                    aria-live="polite">
                    <span x-show="historySearchLoading" role="status">Mencari riwayat...</span>
                    <span x-show="historySearchMessage" x-text="historySearchMessage" role="alert"
                        style="color:var(--status-critical);"></span>
                </div>
                @error('search')
                    <p class="form-error" role="alert">{{ $message }}</p>
                @enderror
                @error('q')
                    <p class="form-error" role="alert">{{ $message }}</p>
                @enderror

                <div id="receipt-history-results">
                    <section>
                        <div class="receipt-history-section-title">
                            <h3>Dokumen Penerimaan</h3>
                            <span>{{ number_format($penerimaans->total(), 0, ',', '.') }} dokumen</span>
                        </div>
                        @if ($penerimaans->count())
                            <div class="receipt-history-table-wrap">
                                <table class="receipt-history-table">
                                    <thead>
                                        <tr>
                                            <th>No. RCV</th>
                                            <th>Tanggal</th>
                                            <th>Supplier</th>
                                            <th>Barang</th>
                                            <th>Qty diterima</th>
                                            <th>Nilai</th>
                                            <th>Kondisi</th>
                                            <th>Surat jalan</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($penerimaans as $p)
                                            @php
                                                $details = $p->details;
                                                $receivedQuantity = $details->isNotEmpty()
                                                    ? $details->sum('qty_diterima')
                                                    : (int) $p->qty_diterima;
                                                $receiptValue = $details->sum(
                                                    fn($detail) => (float) ($detail->total_harga ??
                                                        (float) $detail->qty_diterima *
                                                            (float) ($detail->harga_satuan ?? 0)),
                                                );
                                                $damagedQuantity = $details->sum('qty_rusak');
                                                $shortQuantity = $details->sum('qty_kurang');
                                                $excessQuantity = $details->sum('qty_lebih');
                                                $highlight = function ($value) use ($search) {
                                                    $value = (string) $value;
                                                    $term = trim((string) $search);
                                                    if (mb_strlen($term) < 3) {
                                                        return e($value);
                                                    }

                                                    $parts = preg_split(
                                                        '/(' . preg_quote($term, '/') . ')/iu',
                                                        $value,
                                                        -1,
                                                        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
                                                    );
                                                    if ($parts === false) {
                                                        return e($value);
                                                    }

                                                    return implode(
                                                        '',
                                                        array_map(
                                                            fn($part) => mb_strtolower($part) === mb_strtolower($term)
                                                                ? '<mark style="background:rgba(20,92,84,.2);color:var(--brand-primary);padding:0 2px;border-radius:2px;">' .
                                                                    e($part) .
                                                                    '</mark>'
                                                                : e($part),
                                                            $parts,
                                                        ),
                                                    );
                                                };
                                                $visibleDetails = $details;
                                                if (mb_strlen(trim((string) $search)) >= 3) {
                                                    $literalMatches = $details->filter(
                                                        fn($detail) => str_contains(
                                                            mb_strtolower((string) ($detail->barang?->nama ?? '')),
                                                            mb_strtolower(trim($search)),
                                                        ),
                                                    );
                                                    if ($literalMatches->isNotEmpty()) {
                                                        $visibleDetails = $literalMatches->concat(
                                                            $details->diff($literalMatches),
                                                        );
                                                    }
                                                }
                                                $visibleDetails = $visibleDetails->take(2);
                                            @endphp
                                            <tr id="receipt-{{ $p->id }}">
                                                <td>
                                                    <span class="receipt-rcv">{!! $highlight($p->nomor_penerimaan) !!}</span><br>
                                                    <span
                                                        style="color:var(--text-muted);font-size:.65rem;">{!! $highlight($p->penerima?->name ?? 'Admin Store') !!}</span>
                                                </td>
                                                <td>
                                                    {{ $p->tanggal_diterima?->format('d M Y') }}<br>
                                                    <span
                                                        style="color:var(--text-muted);font-size:.66rem;">{{ $p->tanggal_diterima?->format('H:i') }}</span>
                                                </td>
                                                <td>{!! $highlight($p->sumber_supplier ?: 'Tanpa supplier') !!}</td>
                                                <td>
                                                    @foreach ($visibleDetails as $visibleDetail)
                                                        {!! $highlight($visibleDetail->barang?->nama ?? 'Barang Master') !!}@if (!$loop->last)
                                                            ,
                                                        @endif
                                                    @endforeach
                                                    @if ($details->count() > 2)
                                                        <br><span
                                                            style="color:var(--text-muted);font-size:.66rem;">+{{ $details->count() - 2 }}
                                                            lainnya</span>
                                                    @endif
                                                </td>
                                                <td class="tabular">{{ number_format($receivedQuantity, 0, ',', '.') }}
                                                </td>
                                                <td class="tabular">Rp {{ number_format($receiptValue, 0, ',', '.') }}
                                                </td>
                                                <td>
                                                    @if ($damagedQuantity > 0)
                                                        <span class="badge badge-critical">Rusak
                                                            {{ $damagedQuantity }}</span>
                                                    @endif
                                                    @if ($shortQuantity > 0)
                                                        <span class="badge badge-warning">Kurang
                                                            {{ $shortQuantity }}</span>
                                                    @endif
                                                    @if ($excessQuantity > 0)
                                                        <span class="badge badge-success">Lebih
                                                            {{ $excessQuantity }}</span>
                                                    @endif
                                                    @if ($damagedQuantity === 0 && $shortQuantity === 0 && $excessQuantity === 0)
                                                        <span class="badge badge-success">Baik semua</span>
                                                    @endif
                                                </td>
                                                <td><span
                                                        class="badge {{ $p->tanpa_surat_jalan ? 'badge-warning' : 'badge-success' }}">{{ $p->tanpa_surat_jalan ? 'Tanpa SJ' : 'Ada SJ' }}</span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-outline"
                                                        style="padding:5px 8px;font-size:.68rem;"
                                                        @click="selectedDetailPenerimaan = {{ json_encode($p->load('details.barang.kategori', 'details.satuan', 'penerima', 'gudang')) }}; modalDetailOpen = true">Detail</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top:12px;">{{ $penerimaans->links() }}</div>
                        @else
                            <div class="card receipt-history-empty">
                                Belum ada dokumen penerimaan yang sesuai.
                                <button type="button" class="btn btn-primary"
                                    style="margin-left:8px;padding:5px 9px;font-size:.7rem;"
                                    @click="currentTab='penerimaan_baru'">Catat penerimaan</button>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>

        {{-- Modal Detail Summary Penerimaan --}}
        <div class="modal-backdrop" x-show="modalDetailOpen" x-cloak x-transition.opacity>
            <div class="modal-card receipt-detail-modal">
                <div class="modal-header">
                    <div>
                        <h3 class="modal-title">Detail Penerimaan</h3>
                        <div class="receipt-detail-number" x-text="selectedDetailPenerimaan?.nomor_penerimaan || ''">
                        </div>
                    </div>
                    <button type="button" class="modal-close" @click="modalDetailOpen = false">&times;</button>
                </div>
                <div class="modal-body" x-show="selectedDetailPenerimaan">
                    <div class="receipt-detail-facts">
                        <div><span>Tanggal</span><strong
                                x-text="receiptDate(selectedDetailPenerimaan?.tanggal_diterima)"></strong></div>
                        <div><span>Gudang</span><strong
                                x-text="selectedDetailPenerimaan?.gudang?.nama || 'Gudang Store'"></strong></div>
                        <div><span>Admin</span><strong
                                x-text="selectedDetailPenerimaan?.penerima?.name || 'Admin Store'"></strong></div>
                        <div><span>Surat jalan</span><strong
                                x-text="selectedDetailPenerimaan?.tanpa_surat_jalan ? 'Tanpa surat jalan' : 'Terlampir'"></strong>
                        </div>
                    </div>

                    <div class="receipt-detail-alert" x-show="selectedDetailPenerimaan?.tanpa_surat_jalan">
                        <strong>Tanpa surat jalan:</strong>
                        <span
                            x-text="selectedDetailPenerimaan?.alasan_tanpa_sj || 'Catatan tindak lanjut belum tersedia.'"></span>
                    </div>

                    <section class="receipt-detail-supplier">
                        <header class="receipt-detail-supplier-head">
                            <span class="receipt-detail-supplier-index">1</span>
                            <strong x-text="selectedDetailPenerimaan?.sumber_supplier || 'Tanpa supplier'"></strong>
                        </header>
                        <div class="receipt-detail-group-meta">
                            <div><span>Pemesan</span><strong x-text="selectedDetailPenerimaan?.pemesan || '—'"></strong>
                            </div>
                            <div><span>Pengambil</span><strong
                                    x-text="selectedDetailPenerimaan?.pengambil_barang || '—'"></strong></div>
                            <div><span>Pemakai</span><strong
                                    x-text="receiptDetails(selectedDetailPenerimaan).map(item => item.pemakai).filter(Boolean).join(', ') || '—'"></strong>
                            </div>
                            <div><span>Pembayaran</span><strong
                                    x-text="selectedDetailPenerimaan?.metode_pembayaran || '—'"></strong></div>
                        </div>
                        <p class="receipt-detail-group-note"
                            x-show="selectedDetailPenerimaan?.catatan_rekap || selectedDetailPenerimaan?.catatan">
                            Catatan: <span
                                x-text="selectedDetailPenerimaan?.catatan_rekap || selectedDetailPenerimaan?.catatan"></span>
                        </p>
                        <div class="table-responsive receipt-detail-table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Kategori</th>
                                        <th>Diterima</th>
                                        <th>Baik</th>
                                        <th>Rusak</th>
                                        <th>Kurang</th>
                                        <th>Lebih</th>
                                        <th>Harga/unit</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="dt in receiptDetails(selectedDetailPenerimaan)"
                                        :key="dt.id">
                                        <tr>
                                            <td data-label="Barang">
                                                <div style="font-weight:700;" x-text="dt.barang?.nama || 'Barang'"></div>
                                                <div style="font-size:.68rem;color:var(--text-muted);"
                                                    x-text="dt.catatan || dt.barang?.kode || ''"></div>
                                            </td>
                                            <td data-label="Kategori"
                                                x-text="dt.barang?.kategori?.nama || 'Tanpa kategori'"></td>
                                            <td data-label="Diterima" class="tabular"
                                                x-text="Number(dt.qty_diterima || 0) + ' ' + (dt.satuan?.nama || 'unit')">
                                            </td>
                                            <td data-label="Baik" class="tabular"
                                                x-text="Math.max(0, Number(dt.qty_diterima || 0) - Number(dt.qty_rusak || 0))">
                                            </td>
                                            <td data-label="Rusak"><span class="badge"
                                                    :class="Number(dt.qty_rusak || 0) > 0 ? 'badge-critical' : 'badge-neutral'"
                                                    x-text="Number(dt.qty_rusak || 0) || '—'"></span></td>
                                            <td data-label="Kurang"><span class="badge"
                                                    :class="Number(dt.qty_kurang || 0) > 0 ? 'badge-warning' : 'badge-neutral'"
                                                    x-text="Number(dt.qty_kurang || 0) || '—'"></span></td>
                                            <td data-label="Lebih"><span class="badge"
                                                    :class="Number(dt.qty_lebih || 0) > 0 ? 'badge-success' : 'badge-neutral'"
                                                    x-text="Number(dt.qty_lebih || 0) || '—'"></span></td>
                                            <td data-label="Harga/unit" x-text="receiptMoney(dt.harga_satuan)"></td>
                                            <td data-label="Total" x-text="receiptMoney(receiptLineValue(dt))"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div class="receipt-detail-subtotal">
                                <span>Subtotal</span>
                                <strong x-text="receiptMoney(receiptValue(selectedDetailPenerimaan))"></strong>
                            </div>
                        </div>
                    </section>

                    <p class="receipt-detail-reading-note">Cara membaca: Diterima adalah jumlah fisik yang datang. Rusak
                        sudah termasuk di dalam Diterima (Baik = Diterima − Rusak). Kurang dan Lebih adalah selisih terhadap
                        dokumen/pesanan; Lebih juga sudah termasuk di Diterima.</p>

                    <div class="receipt-detail-value-breakdown">
                        <div class="receipt-detail-value-row">
                            <span>Nilai diterima (fisik × harga)</span>
                            <strong x-text="receiptMoney(receiptValue(selectedDetailPenerimaan))"></strong>
                        </div>
                        <div class="receipt-detail-value-row is-damaged"
                            x-show="receiptQty(selectedDetailPenerimaan, 'qty_rusak') > 0">
                            <span
                                x-text="'− Barang rusak (' + receiptQty(selectedDetailPenerimaan, 'qty_rusak') + ' unit, untuk retur/klaim)'"></span>
                            <strong
                                x-text="receiptMoney(receiptIssueValue(selectedDetailPenerimaan, 'qty_rusak'))"></strong>
                        </div>
                        <div class="receipt-detail-value-row is-short"
                            x-show="receiptQty(selectedDetailPenerimaan, 'qty_kurang') > 0">
                            <span
                                x-text="'Selisih kurang (' + receiptQty(selectedDetailPenerimaan, 'qty_kurang') + ' unit, belum diterima, untuk klaim/koreksi tagihan)'"></span>
                            <strong
                                x-text="receiptMoney(receiptIssueValue(selectedDetailPenerimaan, 'qty_kurang'))"></strong>
                        </div>
                        <div class="receipt-detail-value-row"
                            x-show="receiptQty(selectedDetailPenerimaan, 'qty_lebih') > 0">
                            <span
                                x-text="'Selisih lebih (' + receiptQty(selectedDetailPenerimaan, 'qty_lebih') + ' unit, sudah termasuk jumlah diterima)'"></span>
                            <strong
                                x-text="receiptMoney(receiptIssueValue(selectedDetailPenerimaan, 'qty_lebih'))"></strong>
                        </div>
                    </div>

                    <div class="receipt-detail-summary">
                        <span
                            x-text="'1 supplier · ' + receiptDetails(selectedDetailPenerimaan).length + ' barang · ' + receiptQty(selectedDetailPenerimaan, 'qty_diterima') + ' diterima (' + Math.max(0, receiptQty(selectedDetailPenerimaan, 'qty_diterima') - receiptQty(selectedDetailPenerimaan, 'qty_rusak')) + ' baik, ' + receiptQty(selectedDetailPenerimaan, 'qty_rusak') + ' rusak, kurang ' + receiptQty(selectedDetailPenerimaan, 'qty_kurang') + ', lebih ' + receiptQty(selectedDetailPenerimaan, 'qty_lebih') + ')'"></span>
                        <strong><small>Nilai layak pakai</small><span
                                x-text="receiptMoney(receiptUsableValue(selectedDetailPenerimaan))"></span></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" @click="modalDetailOpen = false">Tutup</button>
                </div>
            </div>
        </div>

        {{-- Include Reusable Quick-Add Modal Component --}}
        @include('components.quick-add-modal', [
            'kategoriList' => $kategoriList ?? null,
            'satuanList' => $satuans ?? null,
        ])

    </div>
@endsection
