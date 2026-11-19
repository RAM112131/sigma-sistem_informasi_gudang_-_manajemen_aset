@extends('layouts.app')

@section('title', 'Laporan Gudang Store — SIGMA Armada')

@push('styles')
    <style>
        .laporan-page {
            --laporan-helper: 13px;
            --laporan-body: 14px;
            --laporan-heading: 18px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .laporan-page-header {
            margin-bottom: 0;
        }

        .laporan-page-header .page-title {
            font-size: 24px;
        }

        .laporan-page .page-subtitle {
            max-width: 720px;
            font-size: var(--laporan-body);
            line-height: 1.5;
        }

        .laporan-filter-panel,
        .laporan-preview-panel {
            width: 100%;
            margin: 0;
            padding: 20px;
        }

        .laporan-filter-panel,
        .laporan-preview-header,
        .laporan-section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .laporan-eyebrow {
            display: block;
            margin-bottom: 4px;
            color: var(--brand-primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .laporan-section-title {
            margin: 0;
            color: var(--text-primary);
            font-size: var(--laporan-heading);
            font-weight: 800;
            line-height: 1.3;
        }

        .laporan-section-description {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: var(--laporan-body);
            line-height: 1.5;
        }

        .laporan-menu-filters {
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }

        .laporan-periode {
            width: 190px;
            margin: 0;
        }

        .laporan-periode .form-control {
            min-height: 42px;
        }

        .laporan-restock-filter {
            display: flex;
            min-height: 42px;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
            font-size: var(--laporan-helper);
            cursor: pointer;
        }

        .laporan-restock-filter small {
            color: var(--text-muted);
            font-size: inherit;
        }

        .laporan-restock-filter.is-disabled {
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .laporan-restock-filter input {
            width: 16px;
            height: 16px;
            accent-color: var(--brand-primary);
        }

        .laporan-selector {
            padding: 4px 0;
        }

        .laporan-selector .laporan-section-heading {
            margin-bottom: 12px;
        }

        .laporan-period-summary,
        .laporan-preview-period {
            display: inline-flex;
            min-height: 32px;
            align-items: center;
            padding: 5px 10px;
            border: 1px solid var(--surface-border);
            border-radius: 999px;
            background: var(--surface-card);
            color: var(--text-muted);
            font-size: var(--laporan-helper);
            font-weight: 700;
            white-space: nowrap;
        }

        .laporan-report-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .laporan-report-card {
            display: grid;
            min-width: 0;
            min-height: 112px;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            color: var(--text-primary);
            text-align: left;
            cursor: pointer;
            transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease;
        }

        .laporan-report-card:hover {
            border-color: var(--brand-primary);
            box-shadow: 0 3px 10px rgba(20, 92, 84, .08);
        }

        .laporan-report-card.is-selected {
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, .035);
            box-shadow: inset 0 0 0 1px var(--brand-primary);
        }

        .laporan-report-card:focus-visible,
        .laporan-preview-panel button:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 3px;
        }

        .laporan-report-icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--brand-primary);
        }

        .laporan-report-card.is-selected .laporan-report-icon {
            background: var(--brand-primary);
            color: #fff;
        }

        .laporan-report-icon svg,
        .laporan-preview-state-icon svg {
            width: 22px;
            height: 22px;
        }

        .laporan-report-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 5px;
        }

        .laporan-report-copy strong {
            color: var(--text-primary);
            font-size: 15px;
            font-weight: 800;
            line-height: 1.35;
        }

        .laporan-report-copy span {
            color: var(--text-muted);
            font-size: var(--laporan-helper);
            line-height: 1.45;
        }

        .laporan-report-action {
            display: inline-flex;
            min-height: 34px;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            color: var(--brand-primary);
            font-size: var(--laporan-helper);
            font-weight: 700;
            white-space: nowrap;
        }

        .laporan-report-card.is-selected .laporan-report-action {
            border-color: transparent;
            background: rgba(20, 92, 84, .1);
        }

        .laporan-preview-panel {
            overflow: hidden;
            padding: 0;
        }

        .laporan-preview-header {
            align-items: flex-start;
            padding: 18px 20px;
            border-bottom: 1px solid var(--surface-border);
        }

        .laporan-preview-state {
            display: flex;
            min-height: 210px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 24px;
            color: var(--text-muted);
            text-align: center;
        }

        .laporan-preview-state-icon {
            display: grid;
            width: 46px;
            height: 46px;
            margin-bottom: 4px;
            place-items: center;
            border-radius: 50%;
            background: rgba(20, 92, 84, .09);
            color: var(--brand-primary);
        }

        .laporan-preview-state strong {
            color: var(--text-primary);
            font-size: 15px;
        }

        .laporan-preview-state>span:last-child {
            max-width: 460px;
            font-size: var(--laporan-helper);
            line-height: 1.5;
        }

        .laporan-preview-loading,
        .laporan-preview-error {
            display: flex;
            min-height: 180px;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 24px;
            color: var(--text-muted);
            font-size: var(--laporan-body);
        }

        .laporan-loading-indicator {
            width: 20px;
            height: 20px;
            border: 2px solid var(--surface-border);
            border-top-color: var(--brand-primary);
            border-radius: 50%;
            animation: laporan-spin .7s linear infinite;
        }

        @keyframes laporan-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .laporan-preview-error {
            flex-wrap: wrap;
            color: var(--status-critical);
        }

        .laporan-review-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 16px 20px;
        }

        .laporan-review-heading {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .laporan-review-heading strong {
            color: var(--brand-primary);
            font-size: 16px;
        }

        .laporan-review-heading span {
            color: var(--text-muted);
            font-size: var(--laporan-helper);
        }

        .laporan-export-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .laporan-export-control .btn {
            min-height: 42px;
        }

        .laporan-export-message {
            max-width: 280px;
            color: var(--status-success);
            font-size: var(--laporan-helper);
        }

        .laporan-export-message:empty {
            display: none;
        }

        .laporan-export-message[data-error="true"] {
            color: var(--status-critical);
        }

        .laporan-review-toolbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            padding: 14px 20px;
            border-top: 1px solid var(--surface-border);
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-base);
        }

        .laporan-caption {
            display: block;
            margin-bottom: 6px;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
        }

        .laporan-period-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .laporan-period-form input[type="month"] {
            min-height: 40px;
            padding: 6px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
        }

        .laporan-review-summary {
            text-align: right;
        }

        .laporan-review-summary strong {
            color: var(--text-primary);
            font-size: 14px;
        }

        .laporan-sheet-tabs {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding: 12px 20px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-card);
        }

        .laporan-sheet-tab {
            display: grid;
            min-width: 40px;
            min-height: 40px;
            place-items: center;
            border: 1px solid transparent;
            border-radius: 6px;
            color: var(--text-muted);
            font-size: var(--laporan-helper);
            text-decoration: none;
        }

        .laporan-sheet-tab:hover,
        .laporan-sheet-tab.is-active {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .laporan-sheet-tab.is-active {
            background: var(--surface-base);
            font-weight: 800;
        }

        .laporan-sheet-frame {
            padding: 16px 20px 20px;
        }

        .laporan-table-scroll {
            max-width: 100%;
            overflow: auto;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
        }

        .laporan-data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            color: var(--text-primary);
            font-size: var(--laporan-helper);
        }

        .laporan-data-table th {
            position: sticky;
            top: 0;
            padding: 11px 12px;
            border-right: 1px solid rgba(255, 255, 255, .16);
            background: var(--brand-primary);
            color: #fff;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .laporan-data-table td {
            padding: 10px 12px;
            border-top: 1px solid var(--surface-border);
            border-right: 1px solid var(--surface-border);
            background: var(--surface-card);
            vertical-align: top;
        }

        .laporan-data-table tr:nth-child(even) td {
            background: var(--surface-base);
        }

        .laporan-data-table th:last-child,
        .laporan-data-table td:last-child {
            border-right: 0;
        }

        .laporan-empty {
            padding: 36px 16px !important;
            color: var(--text-muted);
            text-align: center;
        }

        .laporan-sheet-status {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px 20px;
            padding-top: 12px;
            color: var(--text-muted);
            font-size: var(--laporan-helper);
        }

        .laporan-review-top .btn:focus-visible,
        .laporan-period-form .btn:focus-visible,
        .laporan-sheet-tab:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        @media (max-width: 900px) {
            .laporan-report-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .laporan-filter-panel {
                align-items: flex-start;
                flex-direction: column;
            }

            .laporan-menu-filters {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 600px) {
            .laporan-page {
                gap: 12px;
            }

            .laporan-page-header .page-title {
                font-size: 22px;
            }

            .laporan-filter-panel {
                padding: 16px;
            }

            .laporan-menu-filters {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }

            .laporan-periode {
                width: 100%;
            }

            .laporan-restock-filter {
                align-items: flex-start;
            }

            .laporan-report-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 8px;
            }

            .laporan-report-card {
                min-height: 100px;
                grid-template-columns: 40px minmax(0, 1fr);
                padding: 12px;
            }

            .laporan-report-action {
                grid-column: 2;
                justify-self: start;
                min-height: 32px;
            }

            .laporan-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .laporan-preview-header {
                padding: 16px;
            }

            .laporan-review-top,
            .laporan-review-toolbar {
                align-items: flex-start;
                flex-direction: column;
                padding: 14px 16px;
            }

            .laporan-export-control,
            .laporan-period-form {
                width: 100%;
                flex-wrap: wrap;
            }

            .laporan-export-control .btn,
            .laporan-period-form .btn {
                width: 100%;
                justify-content: center;
            }

            .laporan-export-message {
                max-width: none;
            }

            .laporan-period-form input[type="month"] {
                flex: 1;
                min-width: 0;
            }

            .laporan-review-summary {
                text-align: left;
            }

            .laporan-sheet-tabs {
                padding: 10px 12px;
            }

            .laporan-sheet-frame {
                padding: 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .laporan-page *,
            .laporan-page *::before,
            .laporan-page *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
    </style>
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
