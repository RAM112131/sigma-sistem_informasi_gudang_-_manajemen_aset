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

        @push('styles')
    @vite('resources/css/pages/unit-bisnis/recording-gudang-umum/dashboard.css')
@endpush
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

        
    @endif
@endsection
