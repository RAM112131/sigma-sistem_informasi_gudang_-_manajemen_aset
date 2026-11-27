@extends('layouts.app')

@section('title', 'Laporan Gudang Store — SIGMA Armada')

@push('styles')
    @vite('resources/css/pages/store/laporan/index.css')
@endpush

@section('content')
    <div class="page-header laporan-page-header">
        <div>
            <h1 class="page-title">Laporan Gudang Store</h1>
            <p class="page-subtitle">Pilih periode dan laporan untuk meninjau data aktual per tanggal sebelum menyimpan file
                Excel.</p>
        </div>
    </div>

    @if (auth()->user()?->can('store_laporan.export') || auth()->user()?->can('laporan.export'))
        @php
            $menuLaporan = [
                [
                    'jenis' => 'stok',
                    'nama' => 'Laporan Stok',
                    'deskripsi' => 'Saldo akhir dan barang yang perlu direstock.',
                ],
                [
                    'jenis' => 'barang_masuk',
                    'nama' => 'Barang Masuk',
                    'deskripsi' => 'Penerimaan barang dari supplier berdasarkan tanggal.',
                ],
                [
                    'jenis' => 'barang_keluar',
                    'nama' => 'Barang Keluar',
                    'deskripsi' => 'Pengiriman dari Store beserta tujuan dan status.',
                ],
                [
                    'jenis' => 'pembelian_tunai_transfer',
                    'nama' => 'Pembelian Tunai / Transfer',
                    'deskripsi' => 'Rincian pembelian supplier dan metode pembayaran.',
                ],
            ];
        @endphp
        <section class="laporan-page" x-data="{
            periode: @js($periode),
            hariIni: @js(now()->day),
            hanyaPerluRestock: @js($hanyaPerluRestock),
            laporanAktif: @js($jenisAktif),
            daftarLaporan: @js($menuLaporan),
            sedangMemuat: false,
            nomorPermintaanPreview: 0,
            tanggalAktif: @js($tanggalAktif),
            htmlReview: '',
            pesanError: '',
            urls: {
                stok: @js(route('store.laporan.export.preview', ['jenis' => 'stok'])),
                barang_masuk: @js(route('store.laporan.export.preview', ['jenis' => 'barang_masuk'])),
                barang_keluar: @js(route('store.laporan.export.preview', ['jenis' => 'barang_keluar'])),
                pembelian_tunai_transfer: @js(route('store.laporan.export.preview', ['jenis' => 'pembelian_tunai_transfer']))
            },
            async toggleLaporan(jenis) {
                if (this.laporanAktif === jenis) {
                    return;
                }
        
                this.laporanAktif = jenis;
                this.tanggalAktif = null;
                await this.muatReview(jenis);
            },
            namaLaporanAktif() {
                return this.daftarLaporan.find((laporan) => laporan.jenis === this.laporanAktif)?.nama ?? '';
            },
            async muatReview(jenis, tanggal = null) {
                const nomorPermintaan = ++this.nomorPermintaanPreview;
                this.sedangMemuat = true;
                this.htmlReview = '';
                this.pesanError = '';
                const url = new URL(this.urls[jenis], window.location.origin);
                url.searchParams.set('periode', this.periode);
                const tanggalReview = Number(tanggal ?? this.tanggalAktif ?? (jenis === 'stok' && this.periode === @js(now()->format('Y-m')) ? this.hariIni : 1));
                url.searchParams.set('tanggal', tanggalReview);
                if (jenis === 'stok' && this.hanyaPerluRestock) {
                    url.searchParams.set('hanya_perlu_restock', '1');
                }
        
                try {
                    const response = await fetch(url, {
                        headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) {
                        throw new Error(`Review laporan gagal dimuat (HTTP ${response.status}).`);
                    }
        
                    const html = await response.text();
                    if (nomorPermintaan !== this.nomorPermintaanPreview || this.laporanAktif !== jenis) {
                        return;
                    }
                    if (!html.includes('data-laporan-preview')) {
                        throw new Error('Respons review tidak sesuai. Muat ulang halaman dan coba lagi.');
                    }
                    this.htmlReview = html;
                    this.tanggalAktif = tanggalReview;
                } catch (error) {
                    if (nomorPermintaan === this.nomorPermintaanPreview && this.laporanAktif === jenis) {
                        this.pesanError = error.message || 'Review laporan gagal dimuat. Silakan coba lagi.';
                    }
                } finally {
                    if (nomorPermintaan === this.nomorPermintaanPreview) {
                        this.sedangMemuat = false;
                    }
                }
            },
            ubahPeriode() {
                if (this.laporanAktif) {
                    this.tanggalAktif = null;
                    this.muatReview(this.laporanAktif);
                }
            },
            ubahFilterRestock() {
                if (this.laporanAktif === 'stok') {
                    this.muatReview(this.laporanAktif, this.tanggalAktif);
                }
            },
            navigasiReview(event) {
                const tab = event.target.closest('[data-review-tanggal]');
                if (tab) {
                    event.preventDefault();
                    this.muatReview(this.laporanAktif, tab.dataset.reviewTanggal);
                    return;
                }
        
                const tombolExport = event.target.closest('[data-export-url]');
                if (tombolExport) {
                    event.preventDefault();
                    this.simpanLaporan(tombolExport);
                }
            },
            gantiPeriodeReview(event) {
                event.preventDefault();
                const form = event.target;
                const data = new FormData(form);
                this.periode = data.get('periode');
                this.tanggalAktif = null;
                this.muatReview(this.laporanAktif, 1);
            },
            async simpanLaporan(button) {
                const url = button.dataset.exportUrl;
                const filename = button.dataset.filename;
                const status = button.parentElement.querySelector('[data-export-status]');
                button.disabled = true;
                button.textContent = 'Menyiapkan file...';
                status.textContent = '';
        
                try {
                    if (!window.showSaveFilePicker) {
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.append(link);
                        link.click();
                        link.remove();
                        status.textContent = 'Unduhan laporan dimulai.';
                        return;
                    }
        
                    const handle = await window.showSaveFilePicker({
                        suggestedName: filename,
                        types: [{ description: 'Excel Workbook', accept: { 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['.xlsx'] } }]
                    });
                    const response = await fetch(url, { credentials: 'same-origin' });
                    if (!response.ok || !response.headers.get('content-type')?.includes('spreadsheetml')) {
                        throw new Error(`File Excel gagal disiapkan (HTTP ${response.status}). Silakan coba lagi.`);
                    }
        
                    const writable = await handle.createWritable();
                    await writable.write(await response.blob());
                    await writable.close();
                    status.textContent = 'File laporan berhasil disimpan.';
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        status.textContent = error.message || 'File laporan gagal disimpan. Silakan coba lagi.';
                    }
                } finally {
                    button.disabled = false;
                    button.textContent = 'Export Excel';
                }
            }
        }" x-init="if (laporanAktif) muatReview(laporanAktif, tanggalAktif)" @click="navigasiReview($event)"
            @submit="gantiPeriodeReview($event)">
            <section class="card laporan-filter-panel" aria-labelledby="laporan-filter-title">
                <div>
                    <span class="laporan-eyebrow">LAPORAN OPERASIONAL</span>
                    <h2 id="laporan-filter-title" class="laporan-section-title">Tentukan periode laporan</h2>
                    <p class="laporan-section-description">Pilih bulan yang ingin ditinjau. Periode ini akan digunakan untuk
                        laporan yang Anda pilih.</p>
                </div>
                <div class="laporan-menu-filters">
                    <div class="form-group laporan-periode">
                        <label class="form-label" for="laporan-periode">Periode laporan</label>
                        <input id="laporan-periode" type="month" class="form-control" x-model="periode"
                            @change="$nextTick(() => ubahPeriode())" max="{{ now()->format('Y-m') }}">
                    </div>
                    <label class="laporan-restock-filter" :class="{ 'is-disabled': laporanAktif !== 'stok' }">
                        <input type="checkbox" x-model="hanyaPerluRestock" :disabled="laporanAktif !== 'stok'"
                            @change="$nextTick(() => ubahFilterRestock())">
                        <span>Hanya stok yang perlu restock <small>(khusus laporan stok)</small></span>
                    </label>
                </div>
            </section>

            <section class="laporan-selector" aria-labelledby="laporan-selector-title">
                <div class="laporan-section-heading">
                    <div>
                        <span class="laporan-eyebrow">LANGKAH 1</span>
                        <h2 id="laporan-selector-title" class="laporan-section-title">Pilih jenis laporan</h2>
                        <p class="laporan-section-description">Pilih salah satu laporan untuk melihat pratinjau dan
                            mengekspor data.</p>
                    </div>
                    <span class="laporan-period-summary"
                        x-text="new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(`${periode}-01T00:00:00`))"></span>
                </div>

                <div class="laporan-report-grid" role="group" aria-label="Jenis laporan">
                    @foreach ($menuLaporan as $index => $laporan)
                        <button type="button" class="laporan-report-card"
                            :class="{ 'is-selected': laporanAktif === @js($laporan['jenis']) }"
                            :aria-pressed="laporanAktif === @js($laporan['jenis'])"
                            @click="toggleLaporan(@js($laporan['jenis']))">
                            <span class="laporan-report-icon" aria-hidden="true">
                                @if ($laporan['jenis'] === 'stok')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                                        <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3" />
                                    </svg>
                                @elseif($laporan['jenis'] === 'barang_masuk')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 16V4m0 0L7 9m5-5 5 5" />
                                        <path d="M4 16v4h16v-4" />
                                    </svg>
                                @elseif($laporan['jenis'] === 'barang_keluar')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M12 4v12m0 0 5-5m-5 5-5-5" />
                                        <path d="M4 16v4h16v-4" />
                                    </svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="5" width="18" height="14" rx="2" />
                                        <path d="M3 10h18M7 15h3m4 0h3" />
                                    </svg>
                                @endif
                            </span>
                            <span class="laporan-report-copy">
                                <strong>{{ $laporan['nama'] }}</strong>
                                <span>{{ $laporan['deskripsi'] }}</span>
                            </span>
                            <span class="laporan-report-action"
                                x-text="laporanAktif === @js($laporan['jenis']) ? 'Dipilih' : 'Pilih laporan'"></span>
                        </button>
                    @endforeach
                </div>
            </section>

            <section class="card laporan-preview-panel" aria-labelledby="laporan-preview-title">
                <header class="laporan-preview-header">
                    <div>
                        <span class="laporan-eyebrow">LANGKAH 2</span>
                        <h2 id="laporan-preview-title" class="laporan-section-title">Pratinjau laporan</h2>
                        <p class="laporan-section-description" x-show="laporanAktif"
                            x-text="`${namaLaporanAktif()} · Pilih tanggal sheet untuk meninjau data sebelum export.`"></p>
                        <p class="laporan-section-description" x-show="!laporanAktif">Pilih jenis laporan di atas untuk
                            menampilkan data pada periode terpilih.</p>
                    </div>
                    <span class="laporan-preview-period" x-show="laporanAktif" x-text="periode"></span>
                </header>

                <div class="laporan-preview-state" x-show="!laporanAktif">
                    <span class="laporan-preview-state-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 4h16v16H4z" />
                            <path d="M8 8h8M8 12h8M8 16h4" />
                        </svg>
                    </span>
                    <strong>Belum ada laporan dipilih</strong>
                    <span>Pratinjau dan tombol export akan muncul setelah Anda memilih salah satu laporan.</span>
                </div>

                <div class="laporan-preview-loading" x-show="laporanAktif && sedangMemuat" role="status"
                    aria-live="polite">
                    <span class="laporan-loading-indicator" aria-hidden="true"></span>
                    <span>Menyiapkan pratinjau laporan...</span>
                </div>

                <div class="laporan-preview-error" x-show="laporanAktif && pesanError" role="alert">
                    <span x-text="pesanError"></span>
                    <button type="button" class="btn btn-outline btn-sm"
                        @click="muatReview(laporanAktif, tanggalAktif)">Coba lagi</button>
                </div>

                <div class="laporan-preview-content" x-show="htmlReview" x-html="htmlReview"></div>
            </section>
        </section>
    @else
        <div class="card" style="margin-top:16px;">
            <div class="card-body">
                <p style="margin:0;">Anda tidak memiliki izin untuk membuat export laporan.</p>
            </div>
        </div>
    @endif
@endsection
