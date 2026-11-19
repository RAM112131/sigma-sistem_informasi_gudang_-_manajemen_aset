@extends('layouts.app')

@php
    $recordingPanel = request()->query('panel');
    $user = auth()->user();
    $canAccessRecordingPanel = match ($recordingPanel) {
        'piket-malam' => $user?->can('penerimaan.input') || $user?->can('pengeluaran.input'),
        'cek-stok' => $user?->can('barang.view') ?? false,
        default => false,
    };
    $recordingPanelTitle = match ($recordingPanel) {
        'piket-malam' => 'Piket Malam',
        'cek-stok' => 'Cek Stok Gudang Unit',
        default => 'Dashboard Tugas Harian — Recording Gudang Umum',
    };
@endphp

@section('title', $recordingPanelTitle . ' — ' . config('app.name', 'SIGMA'))

@section('content')
    @if ($canAccessRecordingPanel && $recordingPanel === 'piket-malam')
        <section class="gu-night-shift" x-data="{
            direction: 'Pengeluaran bumbu',
            items: [{ name: '', qty: '', unit: 'Kg', note: '' }],
            signatureStarted: false,
            signed: false,
            notice: '',
            addItem() { this.items.push({ name: '', qty: '', unit: 'Kg', note: '' }); },
            removeItem(index) { if (this.items.length > 1) this.items.splice(index, 1); },
            signaturePoint(event) {
                const canvas = event.currentTarget;
                const bounds = canvas.getBoundingClientRect();
                return {
                    x: (event.clientX - bounds.left) * (canvas.width / bounds.width),
                    y: (event.clientY - bounds.top) * (canvas.height / bounds.height)
                };
            },
            startSignature(event) {
                const canvas = this.$refs.signatureCanvas;
                const context = canvas.getContext('2d');
                const point = this.signaturePoint(event);
                event.currentTarget.setPointerCapture(event.pointerId);
                context.beginPath();
                context.moveTo(point.x, point.y);
                context.lineWidth = 2;
                context.lineCap = 'round';
                context.strokeStyle = '#145c54';
                this.signatureStarted = true;
                this.signed = true;
            },
            drawSignature(event) {
                if (!this.signatureStarted) return;
                const point = this.signaturePoint(event);
                const context = this.$refs.signatureCanvas.getContext('2d');
                context.lineTo(point.x, point.y);
                context.stroke();
            },
            endSignature() { this.signatureStarted = false; },
            clearSignature() {
                const canvas = this.$refs.signatureCanvas;
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                this.signed = false;
            }
        }">
            <header class="page-header">
                <div>
                    <h1 class="page-title">Piket Malam</h1>
                    <p class="page-subtitle">Catat penerimaan atau pengeluaran bumbu di luar jam operasional untuk
                        {{ $activeGudang?->nama ?? 'Gudang Unit aktif' }}.</p>
                </div>
                <a href="{{ route('unit-bisnis.recording-gu.dashboard') }}" class="btn btn-outline">Kembali ke Dashboard</a>
            </header>

            <div class="gu-night-notice" role="status">
                <strong>Pratinjau frontend.</strong>
                <span>Form ini belum menyimpan transaksi. Setelah terhubung, transaksi piket memakai validasi, tanda tangan
                    digital, dan BPB yang sama dengan pengeluaran reguler serta masuk ke rekap tanggal kejadian.</span>
            </div>
            <div x-show="notice" x-cloak class="gu-night-feedback" role="status" x-text="notice"></div>

            <form class="card gu-night-form"
                @submit.prevent="notice = signed ? 'Belum disimpan: integrasi penyimpanan transaksi piket belum tersedia.' : 'Isi tanda tangan digital penerima untuk melanjutkan pratinjau.'">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Form Transaksi Piket</h2>
                        <p class="gu-night-subtitle">Pilih jenis transaksi bumbu; tanggal kejadian menjadi tanggal rekap.
                        </p>
                    </div>
                    <span class="badge badge-neutral">Mode pratinjau</span>
                </div>

                <div class="gu-night-body">
                    <div class="gu-night-fields">
                        <label class="form-group">
                            <span class="form-label">Jenis transaksi <b>*</b></span>
                            <select class="form-control" x-model="direction" required>
                                <option>Pengeluaran bumbu</option>
                                <option>Penerimaan bumbu</option>
                            </select>
                        </label>
                        <label class="form-group">
                            <span class="form-label">Tanggal kejadian <b>*</b></span>
                            <input class="form-control" type="date" value="{{ now()->toDateString() }}" required>
                        </label>
                        <label class="form-group">
                            <span class="form-label"
                                x-text="direction === 'Pengeluaran bumbu' ? 'Nama peminta *' : 'Nama penerima *'"></span>
                            <input class="form-control" type="text" placeholder="Pilih nama dari daftar internal"
                                required>
                        </label>
                        <label class="form-group">
                            <span class="form-label">Bagian / tujuan produksi</span>
                            <input class="form-control" type="text" placeholder="Contoh: Persiapan produksi">
                        </label>
                        <label class="form-group gu-night-span">
                            <span class="form-label">Nomor SPK (opsional)</span>
                            <input class="form-control" type="text" placeholder="Referensi SPK marinasi">
                        </label>
                    </div>

                    <div class="gu-night-items-heading">
                        <div>
                            <h3>Rincian Bumbu</h3>
                            <p>Masukkan beberapa item dalam satu transaksi.</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" @click="addItem()">＋ Tambah Bumbu</button>
                    </div>
                    <div class="gu-night-items">
                        <template x-for="(item, index) in items" :key="index">
                            <article class="gu-night-item">
                                <label><span>Barang</span><input class="form-control" type="text" x-model="item.name"
                                        placeholder="Cari nama bumbu" required></label>
                                <label><span>Qty</span><input class="form-control" type="number" min="0.01"
                                        step="any" x-model="item.qty" placeholder="0" required></label>
                                <label><span>Satuan</span><input class="form-control" type="text" x-model="item.unit"
                                        placeholder="Kg" required></label>
                                <label class="gu-night-item-note"><span>Catatan timbang ulang</span><input
                                        class="form-control" type="text" x-model="item.note"
                                        placeholder="Opsional"></label>
                                <button type="button" class="gu-night-remove" @click="removeItem(index)"
                                    :disabled="items.length === 1"
                                    :aria-label="`Hapus bumbu baris ${index + 1}`">Hapus</button>
                            </article>
                        </template>
                    </div>

                    <div class="gu-night-signature">
                        <div class="gu-night-signature-copy">
                            <h3>Tanda Tangan Digital Penerima <b>*</b></h3>
                            <p>Contoh area tanda tangan penerima. Hasil belum dikirim atau disimpan.</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" @click="clearSignature()">Bersihkan</button>
                        <canvas x-ref="signatureCanvas" width="720" height="150" class="gu-night-canvas"
                            aria-label="Area tanda tangan digital" @pointerdown="startSignature($event)"
                            @pointermove="drawSignature($event)" @pointerup="endSignature()" @pointercancel="endSignature()"
                            @pointerleave="endSignature()"></canvas>
                        <span class="gu-night-sign-state" :class="{ 'is-signed': signed }"
                            x-text="signed ? 'Tanda tangan contoh tersedia' : 'Belum ada tanda tangan'"></span>
                    </div>

                    <label class="form-group">
                        <span class="form-label">Keterangan</span>
                        <textarea class="form-control" rows="2" placeholder="Catatan kejadian piket"></textarea>
                    </label>

                    <div class="gu-night-footer">
                        <span>Nomor BPB dibuat setelah integrasi transaksi tersedia.</span>
                        <div>
                            <button type="button" class="btn btn-outline"
                                @click="notice = 'Draft belum tersimpan karena autosave belum tersedia.'">Simpan
                                Draft</button>
                            <button type="submit" class="btn btn-primary">Pratinjau Transaksi</button>
                        </div>
                    </div>
                </div>
            </form>

            <section class="card gu-night-history">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Riwayat Piket Malam</h2>
                        <p class="gu-night-subtitle">Riwayat akan memuat transaksi bumbu dari tanggal kejadian.</p>
                    </div>
                    <span class="badge badge-neutral">Belum terhubung</span>
                </div>
                <div class="gu-night-empty">Data riwayat akan ditampilkan setelah backend transaksi tersedia.</div>
            </section>
        </section>

        <style>
            .gu-night-shift {
                display: grid;
                gap: 16px;
            }

            .gu-night-notice,
            .gu-night-feedback {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding: 12px 14px;
                border: 1px solid #ead8b8;
                border-radius: 8px;
                background: #fff8e9;
                color: #80531b;
                font-size: .82rem;
            }

            .gu-night-feedback {
                border-color: #cbded9;
                background: #eff7f3;
                color: #315a53;
            }

            .gu-night-form,
            .gu-night-history {
                min-width: 0;
                overflow: hidden;
            }

            .gu-night-subtitle {
                margin: 5px 0 0;
                color: var(--text-muted);
                font-size: .78rem;
            }

            .gu-night-body {
                display: grid;
                gap: 20px;
                padding: 18px;
            }

            .gu-night-fields {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .gu-night-fields .form-group,
            .gu-night-item label {
                display: grid;
                gap: 5px;
                margin: 0;
            }

            .gu-night-fields .form-control,
            .gu-night-item .form-control {
                min-height: 42px;
            }

            .gu-night-span {
                grid-column: 1/-1;
            }

            .gu-night-fields .form-label b {
                color: var(--status-critical);
            }

            .gu-night-items-heading,
            .gu-night-footer,
            .gu-night-footer>div,
            .gu-night-signature {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .gu-night-items-heading {
                padding-bottom: 10px;
                border-bottom: 1px solid var(--surface-border);
            }

            .gu-night-items-heading h3,
            .gu-night-signature h3 {
                margin: 0;
                font-size: .92rem;
            }

            .gu-night-items-heading p,
            .gu-night-signature p {
                margin: 4px 0 0;
                color: var(--text-muted);
                font-size: .76rem;
            }

            .gu-night-items {
                display: grid;
                gap: 10px;
            }

            .gu-night-item {
                display: grid;
                grid-template-columns: minmax(180px, 1.5fr) minmax(90px, .55fr) minmax(90px, .6fr) minmax(170px, 1.3fr) auto;
                align-items: end;
                gap: 10px;
                padding: 12px;
                border: 1px solid var(--surface-border);
                border-radius: 8px;
            }

            .gu-night-item label>span {
                color: var(--text-muted);
                font-size: .72rem;
                font-weight: 700;
            }

            .gu-night-remove {
                min-height: 40px;
                padding: 0 9px;
                border: 1px solid #f0d6d2;
                border-radius: 6px;
                background: #fff8f7;
                color: var(--status-critical);
                font: inherit;
                font-size: .75rem;
                font-weight: 700;
                cursor: pointer;
            }

            .gu-night-remove:disabled {
                opacity: .5;
                cursor: not-allowed;
            }

            .gu-night-signature {
                flex-wrap: wrap;
                justify-content: flex-start;
                padding: 14px;
                border: 1px solid var(--surface-border);
                border-radius: 8px;
            }

            .gu-night-signature-copy {
                margin-right: auto;
            }

            .gu-night-canvas {
                width: 100%;
                height: 120px;
                border: 1px dashed #9eb6af;
                border-radius: 7px;
                background: repeating-linear-gradient(0deg, #fff, #fff 29px, #e9efec 30px);
                touch-action: none;
                cursor: crosshair;
            }

            .gu-night-sign-state {
                color: var(--status-warning);
                font-size: .74rem;
                font-weight: 700;
            }

            .gu-night-sign-state.is-signed {
                color: var(--status-success);
            }

            .gu-night-footer {
                flex-wrap: wrap;
                padding-top: 12px;
                border-top: 1px solid var(--surface-border);
            }

            .gu-night-footer>span {
                color: var(--text-muted);
                font-size: .76rem;
            }

            .gu-night-empty {
                padding: 22px;
                color: var(--text-muted);
                text-align: center;
            }

            @media(max-width:850px) {
                .gu-night-item {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .gu-night-item-note {
                    grid-column: 1/-1;
                }

                .gu-night-remove {
                    justify-self: start;
                }
            }

            @media(max-width:600px) {
                .gu-night-body {
                    padding: 12px;
                }

                .gu-night-fields {
                    grid-template-columns: 1fr;
                }

                .gu-night-span {
                    grid-column: auto;
                }

                .gu-night-items-heading,
                .gu-night-footer {
                    align-items: stretch;
                    flex-direction: column;
                }

                .gu-night-items-heading .btn {
                    min-height: 44px;
                }

                .gu-night-item {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 9px;
                }

                .gu-night-item label:first-child,
                .gu-night-item-note {
                    grid-column: 1/-1;
                }

                .gu-night-remove {
                    min-height: 44px;
                }

                .gu-night-signature {
                    align-items: flex-start;
                }

                .gu-night-signature-copy {
                    width: 100%;
                }

                .gu-night-footer>div {
                    display: grid;
                    grid-template-columns: 1fr;
                    width: 100%;
                }

                .gu-night-footer .btn {
                    min-height: 44px;
                }
            }
        </style>
    @elseif($canAccessRecordingPanel && $recordingPanel === 'cek-stok')
        <div class="gu-stock" x-data="{
            search: '',
            category: 'Semua kategori',
            status: 'Semua status',
            notice: '',
            rows: [
                { code: 'BMB-MRN-001', name: 'Bumbu Marinasi Original', category: 'Bumbu', qty: 18, unit: 'Kg', minimum: 20 },
                { code: 'KMS-VAC-021', name: 'Plastik Vacuum Roll 20x30', category: 'Kemasan', qty: 120, unit: 'Roll', minimum: 50 },
                { code: 'BBM-SLR-012', name: 'Solar Industri', category: 'Bahan Bakar', qty: 12, unit: 'Liter', minimum: 25 }
            ],
            stockStatus(row) {
                if (row.qty <= row.minimum * 0.5) return 'Kritis';
                if (row.qty <= row.minimum) return 'Menipis';
                return 'Aman';
            },
            get filteredRows() {
                const term = this.search.trim().toLowerCase();
                return this.rows.filter(row => {
                    const matchesSearch = !term || `${row.code} ${row.name} ${row.category}`.toLowerCase().includes(term);
                    const matchesCategory = this.category === 'Semua kategori' || row.category === this.category;
                    const matchesStatus = this.status === 'Semua status' || this.stockStatus(row) === this.status;
                    return matchesSearch && matchesCategory && matchesStatus;
                });
            }
        }">
            <header class="page-header">
                <div>
                    <h1 class="page-title">Cek Stok Gudang Unit</h1>
                    <p class="page-subtitle">Lihat saldo Gudang Unit aktif secara read-only. Saldo Gudang Store tidak
                        ditampilkan atau digabungkan.</p>
                </div>
                <a href="{{ route('unit-bisnis.recording-gu.dashboard') }}" class="btn btn-outline">Kembali ke
                    Dashboard</a>
            </header>

            <div class="gu-stock-notice" role="status">
                <strong>Pratinjau frontend.</strong>
                <span>Baris stok di bawah adalah contoh untuk memperlihatkan status aman/menipis/kritis; bukan saldo
                    aktual.</span>
            </div>

            <section class="gu-stock-summary" aria-label="Ringkasan contoh stok">
                <article><span>Total contoh barang</span><strong x-text="rows.length"></strong></article>
                <article><span>Menipis / kritis</span><strong
                        x-text="rows.filter(row => stockStatus(row) !== 'Aman').length"></strong></article>
                <article><span>Gudang aktif</span><strong>{{ $activeGudang?->nama ?? 'Belum terpilih' }}</strong></article>
            </section>

            <section class="card gu-stock-card">
                <div class="card-header gu-stock-card-header">
                    <div>
                        <h2 class="card-title">Saldo Barang</h2>
                        <p>Gunakan pencarian untuk mengecek ketersediaan sebelum mencatat pengeluaran.</p>
                    </div>
                    <span class="badge badge-neutral">Read-only</span>
                </div>

                <div class="gu-stock-filters">
                    <label>
                        <span>Cari barang</span>
                        <input class="form-control" type="search" x-model="search"
                            placeholder="Nama, kode, atau kategori">
                    </label>
                    <label>
                        <span>Kategori</span>
                        <select class="form-control" x-model="category">
                            <option>Semua kategori</option>
                            <option>Bumbu</option>
                            <option>Kemasan</option>
                            <option>Bahan Bakar</option>
                        </select>
                    </label>
                    <label>
                        <span>Status stok</span>
                        <select class="form-control" x-model="status">
                            <option>Semua status</option>
                            <option>Aman</option>
                            <option>Menipis</option>
                            <option>Kritis</option>
                        </select>
                    </label>
                </div>

                <div class="table-responsive gu-stock-table-wrap">
                    <table class="data-table gu-stock-table">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th>Kategori</th>
                                <th>Saldo Unit</th>
                                <th>Minimum</th>
                                <th>Status</th>
                                <th>Kartu Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in filteredRows" :key="row.code">
                                <tr>
                                    <td data-label="Barang"><strong x-text="row.name"></strong><small
                                            x-text="row.code"></small></td>
                                    <td data-label="Kategori" x-text="row.category"></td>
                                    <td data-label="Saldo Unit"><strong x-text="`${row.qty} ${row.unit}`"></strong></td>
                                    <td data-label="Minimum" x-text="`${row.minimum} ${row.unit}`"></td>
                                    <td data-label="Status"><span class="gu-stock-status"
                                            :class="`status-${stockStatus(row).toLowerCase()}`"
                                            x-text="stockStatus(row)"></span></td>
                                    <td data-label="Kartu Stok"><button type="button" class="btn btn-outline btn-sm"
                                            @click="notice = 'Kartu stok kronologis tersedia setelah sumber mutasi terhubung.'">Lihat
                                            Mutasi</button></td>
                                </tr>
                            </template>
                            <tr x-show="filteredRows.length === 0">
                                <td colspan="6" class="gu-stock-empty">Tidak ada barang yang cocok dengan filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div x-show="notice" x-cloak class="gu-stock-feedback" role="status" x-text="notice"></div>
                <p class="gu-stock-footnote">Jika stok menipis, konfirmasikan atau laporkan kebutuhan kepada Admin Gudang
                    Umum. Recording tidak membuat permintaan Store maupun PR/PO.</p>
            </section>
        </div>

        <style>
            .gu-stock {
                display: grid;
                gap: 16px;
            }

            .gu-stock-notice,
            .gu-stock-feedback {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding: 12px 14px;
                border: 1px solid #ead8b8;
                border-radius: 8px;
                background: #fff8e9;
                color: #80531b;
                font-size: .82rem;
            }

            .gu-stock-feedback {
                border-color: #cbded9;
                background: #eff7f3;
                color: #315a53;
            }

            .gu-stock-summary {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .gu-stock-summary article {
                display: grid;
                gap: 5px;
                padding: 14px 16px;
                border: 1px solid var(--surface-border);
                border-radius: 9px;
                background: var(--surface-card);
            }

            .gu-stock-summary article span,
            .gu-stock-card-header p {
                color: var(--text-muted);
                font-size: .77rem;
            }

            .gu-stock-summary article strong {
                font-size: 1rem;
            }

            .gu-stock-card {
                min-width: 0;
                overflow: hidden;
            }

            .gu-stock-card-header {
                align-items: flex-start;
            }

            .gu-stock-card-header p {
                margin: 4px 0 0;
            }

            .gu-stock-filters {
                display: grid;
                grid-template-columns: minmax(220px, 2fr) repeat(2, minmax(150px, 1fr));
                gap: 12px;
                padding: 16px;
            }

            .gu-stock-filters label {
                display: grid;
                gap: 5px;
                color: var(--text-muted);
                font-size: .76rem;
                font-weight: 700;
            }

            .gu-stock-filters .form-control {
                min-height: 42px;
            }

            .gu-stock-table-wrap {
                border-top: 1px solid var(--surface-border);
            }

            .gu-stock-table td:first-child strong,
            .gu-stock-table td:first-child small {
                display: block;
            }

            .gu-stock-table td:first-child small {
                margin-top: 3px;
                color: var(--text-muted);
                font-size: .7rem;
            }

            .gu-stock-status {
                display: inline-flex;
                padding: 5px 8px;
                border-radius: 99px;
                font-size: .72rem;
                font-weight: 750;
            }

            .gu-stock-status.status-aman {
                background: #e8f4ec;
                color: #2d7044;
            }

            .gu-stock-status.status-menipis {
                background: #fff2d9;
                color: #885b13;
            }

            .gu-stock-status.status-kritis {
                background: #fde9e7;
                color: #a33832;
            }

            .gu-stock-empty {
                padding: 20px !important;
                color: var(--text-muted) !important;
                text-align: center;
            }

            .gu-stock-footnote {
                margin: 0;
                padding: 12px 16px;
                border-top: 1px solid var(--surface-border);
                color: var(--text-muted);
                font-size: .78rem;
            }

            @media(max-width:700px) {
                .gu-stock-summary {
                    grid-template-columns: 1fr;
                }

                .gu-stock-filters {
                    grid-template-columns: 1fr;
                    padding: 12px;
                }

                .gu-stock-table thead {
                    display: none;
                }

                .gu-stock-table,
                .gu-stock-table tbody,
                .gu-stock-table tr,
                .gu-stock-table td {
                    display: block;
                    width: 100%;
                }

                .gu-stock-table tr {
                    padding: 10px 12px;
                    border-bottom: 1px solid var(--surface-border);
                }

                .gu-stock-table td {
                    display: grid;
                    grid-template-columns: minmax(105px, .7fr) minmax(0, 1.3fr);
                    gap: 8px;
                    padding: 6px 0;
                    border: 0;
                }

                .gu-stock-table td:before {
                    content: attr(data-label);
                    color: var(--text-muted);
                    font-size: .72rem;
                    font-weight: 700;
                }

                .gu-stock-table td:first-child {
                    display: block;
                    padding-bottom: 9px;
                }

                .gu-stock-table td:first-child:before {
                    display: none;
                }

                .gu-stock-table td:first-child strong {
                    font-size: .95rem;
                }

                .gu-stock-table .btn {
                    justify-self: start;
                    min-height: 40px;
                }
            }
        </style>
    @else
        <section class="recording-dashboard" aria-labelledby="recording-dashboard-title">
            <header class="recording-dashboard-heading">
                <div>
                    <h1 id="recording-dashboard-title">Dashboard Tugas Harian</h1>
                    <p>Kelola stok dan catat pergerakan barang pada gudang unit yang ditugaskan.</p>
                </div>
                <span class="recording-dashboard-warehouse">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
                    </svg>
                    {{ $activeGudang?->nama ?? 'Gudang belum ditetapkan' }}
                </span>
            </header>

            @unless ($activeGudang)
                <div class="recording-dashboard-alert" role="alert">
                    <strong>Gudang aktif belum ditetapkan.</strong>
                    <span>Hubungi Admin Gudang Umum agar pencatatan diarahkan ke gudang yang benar.</span>
                </div>
            @endunless

            <section class="recording-menu-grid" aria-label="Menu operasional Recording Gudang Umum">
                <article class="recording-menu-card">
                    <div class="recording-menu-card-heading">
                        <span class="recording-menu-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                                <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
                            </svg>
                        </span>
                        <div>
                            <span class="recording-menu-eyebrow">Persediaan</span>
                            <h2>Stok Barang</h2>
                        </div>
                    </div>
                    <p>Periksa daftar barang dan saldo stok pada gudang unit yang sedang aktif.</p>
                    <a class="recording-menu-action" href="{{ route('unit-bisnis.stok.index') }}">
                        Buka Stok Barang
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M5 12h14" />
                            <path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </article>

                <article class="recording-menu-card">
                    <div class="recording-menu-card-heading">
                        <span class="recording-menu-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 4h16v16H4z" />
                                <path d="M12 7v9" />
                                <path d="m8 12 4 4 4-4" />
                            </svg>
                        </span>
                        <div>
                            <span class="recording-menu-eyebrow">Barang Masuk</span>
                            <h2>Penerimaan Fisik Barang</h2>
                        </div>
                    </div>
                    <p>Catat penerimaan dari supplier atau konfirmasi barang yang dikirim Gudang Store.</p>
                    <a class="recording-menu-action" href="{{ route('unit-bisnis.recording-gu.penerimaan.index') }}">
                        Catat Penerimaan
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M5 12h14" />
                            <path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </article>

                <article class="recording-menu-card">
                    <div class="recording-menu-card-heading">
                        <span class="recording-menu-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 7h16M4 17h16" />
                                <path d="m8 4-3 3 3 3m8 4 3 3-3 3" />
                            </svg>
                        </span>
                        <div>
                            <span class="recording-menu-eyebrow">Barang Keluar</span>
                            <h2>Pengeluaran &amp; Pengembalian (BPB)</h2>
                        </div>
                    </div>
                    <p>Catat pengeluaran barang dan proses pengembaliannya melalui Bukti Pengeluaran Barang.</p>
                    <a class="recording-menu-action" href="{{ route('unit-bisnis.recording-gu.pengeluaran.index') }}">
                        Buka Pengeluaran &amp; Pengembalian
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M5 12h14" />
                            <path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </article>
            </section>

            <p class="recording-dashboard-footnote">
                Recording GU mencatat pergerakan fisik barang. Verifikasi administrasi dan permintaan barang ke Store
                ditangani Admin Gudang Umum.
            </p>
        </section>

        <style>
            .recording-dashboard {
                --recording-ink: #17313d;
                --recording-muted: #617581;
                --recording-border: #dce8e6;
                color: var(--recording-ink);
            }

            .recording-dashboard-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin: 0 0 14px;
            }

            .recording-dashboard-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 7px;
            }

            .recording-session-user {
                color: var(--recording-muted);
                font-size: .68rem;
            }

            .recording-shortcuts {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 12px;
            }

            .recording-shortcuts a {
                display: inline-flex;
                min-height: 40px;
                align-items: center;
                gap: 7px;
                padding: 8px 12px;
                border: 1px solid var(--recording-border);
                border-radius: 6px;
                background: #fff;
                color: #17695e;
                font-size: .72rem;
                font-weight: 700;
                text-decoration: none;
            }

            .recording-shortcuts a:hover {
                border-color: #9fc6bb;
                background: #f3f8f6;
            }

            .recording-dashboard-heading h1 {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 750;
                letter-spacing: -.02em;
            }

            .recording-dashboard-heading p {
                margin: 3px 0 0;
                color: var(--recording-muted);
                font-size: .8rem;
            }

            .recording-demo-tag {
                padding: 5px 8px;
                border: 1px solid #ead8b8;
                border-radius: 6px;
                background: #fff8e9;
                color: #80531b;
                font-size: .68rem;
                font-weight: 650;
                white-space: nowrap;
            }

            .recording-summary-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 12px;
            }

            .recording-summary-card,
            .recording-panel {
                min-width: 0;
                border: 1px solid var(--recording-border);
                border-radius: 8px;
                background: #fff;
                box-shadow: 0 1px 3px rgba(21, 55, 61, .035);
            }

            .recording-summary-card {
                min-height: 78px;
                padding: 10px 12px;
            }

            .recording-summary-heading,
            .recording-panel-heading,
            .recording-progress-copy,
            .recording-exception-topline,
            .recording-exception-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }

            .recording-summary-heading {
                color: #526875;
                font-size: .7rem;
                font-weight: 600;
            }

            .recording-summary-icon {
                display: grid;
                width: 22px;
                height: 22px;
                place-items: center;
                border-radius: 4px;
                background: #edf4f2;
                color: #17695e;
                font-size: .9rem;
                font-weight: 700;
            }

            .recording-summary-number {
                margin-top: 4px;
                color: #122c39;
                font-size: 1.12rem;
                font-weight: 750;
                line-height: 1.2;
            }

            .recording-summary-number span {
                color: #536875;
                font-size: .69rem;
                font-weight: 500;
            }

            .recording-summary-note {
                display: flex;
                align-items: center;
                gap: 4px;
                margin-top: 4px;
                color: #526875;
                font-size: .65rem;
                white-space: nowrap;
            }

            .recording-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
            }

            .recording-dot-success {
                background: var(--status-success);
            }

            .recording-dot-warning {
                background: var(--status-warning);
            }

            .recording-dot-critical {
                background: var(--status-critical);
            }

            .recording-separator {
                color: #a2b0b7;
                padding: 0 2px;
            }

            .recording-dashboard-content {
                display: grid;
                grid-template-columns: minmax(280px, .88fr) minmax(0, 1.42fr);
                align-items: start;
                gap: 12px;
            }

            .recording-dashboard-column {
                display: grid;
                gap: 12px;
            }

            .recording-panel {
                padding: 12px;
            }

            .recording-incoming-badge {
                padding: 3px 6px;
                border-radius: 4px;
                background: #b66a1c;
                color: #fff;
                font-size: .58rem;
                font-weight: 750;
                text-transform: uppercase;
            }

            .recording-incoming-card {
                display: grid;
                grid-template-columns: 26px minmax(0, 1fr) auto;
                align-items: center;
                gap: 10px;
                padding: 9px;
                border: 1px solid #dce9e5;
                border-radius: 5px;
                background: #edf5f2;
            }

            .recording-incoming-icon {
                display: grid;
                width: 24px;
                height: 24px;
                place-items: center;
                border: 1px solid #c9ddd7;
                border-radius: 4px;
                background: #f7fbf9;
                color: #17695e;
                font-size: .8rem;
            }

            .recording-incoming-details {
                display: grid;
                gap: 2px;
                color: #526875;
                font-size: .63rem;
            }

            .recording-incoming-reference {
                display: flex;
                align-items: center;
                gap: 7px;
                color: #29434f;
                font-size: .65rem;
            }

            .recording-incoming-reference span {
                color: #54707b;
                font-size: .57rem;
                text-transform: uppercase;
            }

            .recording-incoming-action {
                display: inline-flex;
                min-height: 30px;
                align-items: center;
                justify-content: center;
                gap: 5px;
                padding: 5px 8px;
                border-radius: 4px;
                background: #087165;
                color: #fff;
                font-size: .62rem;
                font-weight: 650;
                text-decoration: none;
                white-space: nowrap;
            }

            .recording-incoming-action:hover {
                background: #075b53;
            }

            .recording-incoming-footnote {
                margin: 7px 0 0;
                color: var(--recording-muted);
                font-size: .57rem;
            }

            .recording-panel-heading {
                min-height: 22px;
                margin-bottom: 10px;
            }

            .recording-panel-heading h2 {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 0;
                color: #17313d;
                font-size: .81rem;
                font-weight: 700;
            }

            .recording-panel-symbol {
                color: #17695e;
                font-size: .9rem;
            }

            .recording-status-badge,
            .recording-count-badge,
            .recording-transaction-badge {
                display: inline-flex;
                align-items: center;
                border-radius: 4px;
                padding: 3px 6px;
                font-size: .59rem;
                font-weight: 750;
                line-height: 1.15;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .recording-status-badge {
                background: #e9edf0;
                color: #445964;
            }

            .recording-count-badge {
                background: #c57926;
                color: #fff;
            }

            .recording-progress-copy {
                margin-bottom: 6px;
                color: #364f5b;
                font-size: .68rem;
            }

            .recording-progress-copy strong {
                font-weight: 650;
            }

            .recording-progress-track {
                height: 6px;
                overflow: hidden;
                border-radius: 8px;
                background: #e8efed;
            }

            .recording-progress-track span {
                display: block;
                width: 40%;
                height: 100%;
                border-radius: inherit;
                background: #167165;
            }

            .recording-opname-meta {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                margin-top: 7px;
                color: var(--recording-muted);
                font-size: .59rem;
            }

            .recording-primary-action {
                display: flex;
                min-height: 32px;
                align-items: center;
                justify-content: center;
                gap: 6px;
                margin-top: 9px;
                border-radius: 5px;
                background: #087165;
                color: #fff;
                font-size: .7rem;
                font-weight: 650;
                text-decoration: none;
            }

            .recording-primary-action:hover {
                background: #075b53;
            }

            .recording-state-panel p {
                margin: 5px 0;
                color: var(--recording-muted);
                font-size: .68rem;
                line-height: 1.5;
            }

            .recording-text-action {
                display: inline-flex;
                min-height: 40px;
                align-items: center;
                gap: 5px;
                margin-top: 4px;
                color: #087165;
                font-size: .7rem;
                font-weight: 700;
                text-decoration: none;
            }

            .recording-exception-panel .recording-panel-heading h2 .recording-panel-symbol {
                color: var(--status-critical);
            }

            .recording-exception-list {
                display: grid;
                gap: 7px;
            }

            .recording-exception-item {
                display: grid;
                gap: 2px;
                padding: 8px;
                border: 1px solid #e8eeec;
                border-radius: 5px;
                background: #f8faf9;
                color: #526875;
                font-size: .65rem;
            }

            .recording-exception-topline {
                color: #273f4b;
                font-size: .68rem;
            }

            .recording-status-pending {
                background: #bd7127;
                color: #fff;
            }

            .recording-exception-footer {
                margin-top: 3px;
                color: var(--recording-muted);
                font-size: .61rem;
            }

            .recording-exception-footer a {
                color: #086e64;
                font-weight: 650;
                text-decoration: none;
            }

            .recording-user-caption {
                color: var(--recording-muted);
                font-size: .62rem;
                white-space: nowrap;
            }

            .recording-table-wrap {
                overflow-x: auto;
            }

            .recording-transactions-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
                font-size: .66rem;
            }

            .recording-transactions-table th {
                padding: 8px 7px;
                background: #edf4f2;
                color: #536b77;
                font-size: .57rem;
                font-weight: 750;
                letter-spacing: .025em;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .recording-transactions-table td {
                padding: 9px 7px;
                border-bottom: 1px solid #edf1f0;
                color: #344d59;
                vertical-align: middle;
            }

            .recording-transactions-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .recording-transactions-table tbody tr:nth-child(even) {
                background: #f8faf9;
            }

            .recording-transactions-table th:nth-child(1),
            .recording-transactions-table th:nth-child(4),
            .recording-transactions-table th:nth-child(5),
            .recording-transactions-table td:nth-child(1),
            .recording-transactions-table td:nth-child(4),
            .recording-transactions-table td:nth-child(5) {
                white-space: nowrap;
            }

            .recording-transaction-badge {
                color: #fff;
                font-size: .55rem;
            }

            .recording-badge-blue {
                background: #32566c;
            }

            .recording-badge-green {
                background: #087165;
            }

            .recording-badge-orange {
                background: #b8791a;
            }

            .recording-badge-slate {
                background: #5b6b79;
            }

            .recording-quantity {
                font-variant-numeric: tabular-nums;
                text-align: right;
            }

            .recording-document-number {
                color: #5a7180 !important;
                font-size: .61rem;
            }

            .recording-dashboard-footnote {
                margin: 10px 0 0;
                color: var(--recording-muted);
                font-size: .65rem;
            }

            @media (max-width: 1080px) {
                .recording-summary-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .recording-dashboard-content {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            @media (max-width: 600px) {
                .recording-dashboard-heading {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .recording-dashboard-meta {
                    justify-content: flex-start;
                }

                .recording-shortcuts a {
                    flex: 1 1 140px;
                    justify-content: center;
                }

                .recording-summary-grid {
                    gap: 8px;
                }

                .recording-summary-card {
                    padding: 9px;
                }

                .recording-summary-note {
                    flex-wrap: wrap;
                    white-space: normal;
                }

                .recording-opname-meta {
                    flex-direction: column;
                }

                .recording-transactions-panel {
                    padding: 10px;
                }

                .recording-incoming-card {
                    grid-template-columns: 26px minmax(0, 1fr);
                }

                .recording-incoming-action {
                    grid-column: 2;
                    justify-self: start;
                }
            }
        </style>
    @endif
@endsection
