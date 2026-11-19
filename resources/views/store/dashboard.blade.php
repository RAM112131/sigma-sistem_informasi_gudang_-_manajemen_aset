@extends('layouts.app')

@section('title', 'Dashboard Gudang Store — SIGMA Armada')

@section('content')
    <div class="store-dashboard">
        <header class="store-dashboard-header">
            <div>
                <p class="store-dashboard-eyebrow">OPERASIONAL GUDANG</p>
                <h1>Dashboard Gudang Store</h1>
                <p class="store-dashboard-description">
                    Ringkasan persediaan, permintaan unit bisnis, dan aktivitas gudang.
                </p>
            </div>
            <div class="store-dashboard-actions">
                @canany(['store_penerimaan.create', 'store.penerimaan', 'store.create'])
                    <a href="{{ route('store.penerimaan.index') }}" class="store-action store-action-primary">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Terima Barang
                    </a>
                @endcanany
                @canany(['store_pengeluaran.create', 'store.distribusi'])
                    <a href="{{ route('store.pengeluaran.index', ['tab' => 'permintaan']) }}"
                        class="store-action store-action-secondary">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6M8 13h8M8 17h8" />
                        </svg>
                        Proses Permintaan
                    </a>
                @endcanany
            </div>
        </header>

        <section class="store-dashboard-kpis" aria-label="Ringkasan Gudang Store">
            <article class="store-kpi store-kpi-critical">
                <span class="store-kpi-heading">STOK KRITIS</span>
                <span class="store-kpi-value">{{ number_format($jumlahStokKritis) }}<small> Barang</small></span>
                <span class="store-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m10.3 3.9-7.8 13.5A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                        <path d="M12 9v4m0 3h.01" />
                    </svg>
                </span>
                <span class="store-kpi-tag tag-critical">PERLU DITINJAU</span>
            </article>

            <article class="store-kpi store-kpi-requests">
                <span class="store-kpi-heading">PERMINTAAN MENUNGGU</span>
                <span class="store-kpi-value">{{ number_format($jumlahPermintaanBelumDiproses) }}<small>
                        Permintaan</small></span>
                <span class="store-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M8 2h8l5 5v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h3Z" />
                        <path d="M13 2v6h6M8 13h8M8 17h5" />
                    </svg>
                </span>
                <span class="store-kpi-tag tag-amber">PERMINTAAN UNIT</span>
            </article>

            <article class="store-kpi store-kpi-transit">
                <span class="store-kpi-heading">PEMINDAHAN DALAM TRANSIT</span>
                <span class="store-kpi-value">{{ number_format($jumlahPemindahanBerjalan) }}<small>
                        Pengiriman</small></span>
                <span class="store-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 7h11v11H3zM14 11h4l3 3v4h-7z" />
                        <circle cx="7.5" cy="19" r="2" />
                        <circle cx="17.5" cy="19" r="2" />
                    </svg>
                </span>
                <span class="store-kpi-tag tag-teal">SEDANG DIKIRIM</span>
            </article>

            <article class="store-kpi store-kpi-activity">
                <span class="store-kpi-heading">AKTIVITAS HARI INI</span>
                <span class="store-kpi-value">{{ number_format($totalPenerimaanHariIni + $totalPemindahanHariIni) }}<small>
                        Transaksi</small></span>
                <span class="store-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                </span>
                <span class="store-kpi-tag tag-neutral">{{ number_format($totalPenerimaanHariIni) }} MASUK ·
                    {{ number_format($totalPemindahanHariIni) }} KELUAR</span>
            </article>
        </section>

        <section class="store-dashboard-panels" aria-label="Rincian operasional">
            <article class="store-panel">
                <header class="store-panel-header">
                    <div>
                        <h2>Permintaan dari Unit Bisnis</h2>
                        <p>Permintaan barang yang menunggu diproses oleh Gudang Store.</p>
                    </div>
                    <a href="{{ route('store.pengeluaran.index', ['tab' => 'permintaan']) }}" class="store-panel-action">
                        Lihat Semua <span aria-hidden="true">→</span>
                    </a>
                </header>

                @if ($permintaanBelumDiproses->isEmpty())
                    <div class="store-empty-state">
                        <span class="store-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M8 2h8l5 5v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h3Z" />
                                <path d="M13 2v6h6M8 13h8M8 17h5" />
                            </svg>
                        </span>
                        <strong>Belum ada permintaan baru</strong>
                        <span>Permintaan dari unit bisnis akan muncul di sini.</span>
                    </div>
                @else
                    <div class="store-table-wrap">
                        <table class="store-table">
                            <thead>
                                <tr>
                                    <th>Nomor PR</th>
                                    <th>Unit Peminta</th>
                                    <th>Jumlah Item</th>
                                    @canany(['store_pengeluaran.create', 'store.distribusi'])
                                        <th>Aksi</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permintaanBelumDiproses as $permintaan)
                                    <tr>
                                        <td>
                                            <strong class="store-document-number">{{ $permintaan->nomor_pr }}</strong>
                                            <small>{{ $permintaan->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>{{ $permintaan->gudangPeminta?->nama ?? 'Unit Bisnis' }}</td>
                                        <td>{{ $permintaan->details->count() }} jenis barang</td>
                                        @canany(['store_pengeluaran.create', 'store.distribusi'])
                                            <td>
                                                <a href="{{ route('store.pengeluaran.index', ['tab' => 'permintaan']) }}"
                                                    class="store-row-action">
                                                    Proses <span aria-hidden="true">→</span>
                                                </a>
                                            </td>
                                        @endcanany
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($jumlahPermintaanBelumDiproses > $permintaanBelumDiproses->count())
                    <footer class="store-panel-footer">
                        Menampilkan {{ $permintaanBelumDiproses->count() }} dari
                        {{ number_format($jumlahPermintaanBelumDiproses) }} permintaan terbaru.
                    </footer>
                @endif
            </article>

            <article class="store-panel">
                <header class="store-panel-header">
                    <div>
                        <h2>Stok Mendekati Minimum</h2>
                        <p>Barang dengan saldo saat ini pada atau di bawah ambang minimum.</p>
                    </div>
                    <a href="{{ route('store.stok.index') }}" class="store-panel-action">
                        Lihat Stok <span aria-hidden="true">→</span>
                    </a>
                </header>

                @if ($stokKritis->isEmpty())
                    <div class="store-empty-state">
                        <span class="store-empty-icon store-empty-success" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                        </span>
                        <strong>Stok dalam kondisi aman</strong>
                        <span>Tidak ada barang yang berada di bawah ambang minimum.</span>
                    </div>
                @else
                    <div class="store-table-wrap">
                        <table class="store-table">
                            <thead>
                                <tr>
                                    <th>Barang</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Minimum</th>
                                    @canany(['store_penerimaan.create', 'store.penerimaan', 'store.create'])
                                        <th>Aksi</th>
                                    @endcanany
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stokKritis as $barang)
                                    <tr>
                                        <td>
                                            <strong class="store-document-number">{{ $barang->nama }}</strong>
                                            <small>{{ $barang->kode }} · {{ $barang->kategori?->nama ?? 'Umum' }}</small>
                                        </td>
                                        <td><span
                                                class="store-stock-value">{{ number_format($barang->stok_aktual) }}</span>
                                            {{ $barang->satuan?->nama }}</td>
                                        <td>{{ number_format($barang->minimum_stok) }} {{ $barang->satuan?->nama }}</td>
                                        @canany(['store_penerimaan.create', 'store.penerimaan', 'store.create'])
                                            <td>
                                                <a href="{{ route('store.penerimaan.index') }}" class="store-row-action">
                                                    Tambah stok <span aria-hidden="true">→</span>
                                                </a>
                                            </td>
                                        @endcanany
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($jumlahStokKritis > $stokKritis->count())
                    <footer class="store-panel-footer">
                        Menampilkan {{ $stokKritis->count() }} dari {{ number_format($jumlahStokKritis) }} barang kritis.
                    </footer>
                @endif
            </article>
        </section>
    </div>

    <style>
        .store-dashboard {
            --store-text-helper: 12px;
            --store-text-body: 14px;
            --store-text-section: 18px;
            --store-text-metric: 24px;
            min-width: 0;
        }

        .store-dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 16px;
        }

        .store-dashboard-eyebrow {
            margin: 0 0 4px;
            color: var(--brand-primary);
            font-size: var(--store-text-helper);
            font-weight: 800;
            letter-spacing: .07em;
        }

        .store-dashboard-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
        }

        .store-dashboard-description {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: var(--store-text-body);
        }

        .store-dashboard-actions {
            display: flex;
            flex: 0 0 auto;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .store-action {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            font-size: var(--store-text-helper);
            font-weight: 700;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .store-action svg,
        .store-kpi-icon svg,
        .store-empty-icon svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .store-action-primary {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .store-action-primary:hover {
            border-color: var(--brand-primary-d);
            background: var(--brand-primary-d);
        }

        .store-action-secondary {
            background: var(--surface-card);
            color: var(--brand-primary);
        }

        .store-action-secondary:hover {
            border-color: var(--brand-primary);
            background: var(--surface-base);
        }

        .store-action:focus-visible,
        .store-panel-action:focus-visible,
        .store-row-action:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .store-dashboard-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin: 0 0 16px;
        }

        .store-kpi {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 108px;
            flex-direction: column;
            justify-content: space-between;
            gap: 4px;
            overflow: hidden;
            padding: 12px 14px 10px;
            border: 1px solid var(--surface-border);
            border-top: 3px solid var(--store-kpi-accent, var(--brand-primary));
            border-radius: var(--radius);
            background: var(--surface-card);
        }

        .store-kpi-critical {
            --store-kpi-accent: var(--status-critical);
        }

        .store-kpi-requests {
            --store-kpi-accent: var(--brand-accent);
        }

        .store-kpi-transit {
            --store-kpi-accent: var(--brand-primary);
        }

        .store-kpi-activity {
            --store-kpi-accent: var(--status-neutral);
        }

        .store-kpi-heading {
            padding-right: 28px;
            color: var(--text-muted);
            font-size: var(--store-text-helper);
            font-weight: 800;
            letter-spacing: .045em;
            line-height: 1.25;
        }

        .store-kpi-value {
            color: var(--text-primary);
            font-size: var(--store-text-metric);
            font-weight: 850;
            line-height: 1.15;
            font-variant-numeric: tabular-nums;
        }

        .store-kpi-value small {
            color: var(--text-muted);
            font-size: var(--store-text-helper);
            font-weight: 600;
        }

        .store-kpi-icon {
            position: absolute;
            top: 8px;
            right: 8px;
            display: grid;
            width: 26px;
            height: 26px;
            place-items: center;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--store-kpi-accent);
        }

        .store-kpi-tag {
            width: fit-content;
            max-width: 100%;
            overflow: hidden;
            padding: 3px 5px;
            border-radius: 3px;
            font-size: var(--store-text-helper);
            font-weight: 800;
            letter-spacing: .02em;
            line-height: 1.15;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tag-critical {
            background: rgba(193, 68, 60, .12);
            color: var(--status-critical);
        }

        .tag-amber {
            background: rgba(201, 122, 43, .12);
            color: var(--brand-accent-d);
        }

        .tag-teal {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .tag-neutral {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .store-dashboard-panels {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
            align-items: start;
            gap: 16px;
        }

        .store-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .store-panel-header {
            display: flex;
            min-height: 68px;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .store-panel-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: var(--store-text-section);
            font-weight: 800;
            line-height: 1.25;
        }

        .store-panel-header p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: var(--store-text-helper);
            line-height: 1.4;
        }

        .store-panel-action {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 5px;
            color: var(--brand-primary);
            font-size: var(--store-text-helper);
            font-weight: 700;
            white-space: nowrap;
        }

        .store-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .store-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .store-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--store-text-helper);
            font-weight: 800;
            letter-spacing: .035em;
            white-space: nowrap;
        }

        .store-table td {
            min-height: 48px;
            padding: 9px 12px;
            border-bottom: 1px solid #edf2f0;
            color: var(--text-primary);
            font-size: var(--store-text-body);
            vertical-align: middle;
        }

        .store-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .store-table td:first-child {
            min-width: 140px;
        }

        .store-table td strong,
        .store-table td small {
            display: block;
        }

        .store-table td small {
            margin-top: 3px;
            color: var(--text-muted);
            font-size: var(--store-text-helper);
        }

        .store-document-number {
            color: var(--text-primary);
            font-weight: 700;
        }

        .store-stock-value {
            color: var(--status-critical);
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .store-row-action {
            display: inline-flex;
            min-height: 32px;
            align-items: center;
            gap: 4px;
            color: var(--brand-primary);
            font-size: var(--store-text-helper);
            font-weight: 800;
            white-space: nowrap;
        }

        .store-panel-footer {
            padding: 9px 12px;
            border-top: 1px solid var(--surface-border);
            color: var(--text-muted);
            font-size: var(--store-text-helper);
        }

        .store-empty-state {
            display: flex;
            min-height: 180px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 24px;
            color: var(--text-muted);
            text-align: center;
        }

        .store-empty-icon {
            display: grid;
            width: 36px;
            height: 36px;
            margin-bottom: 4px;
            place-items: center;
            border-radius: var(--radius);
            background: rgba(91, 107, 121, .1);
            color: var(--status-neutral);
        }

        .store-empty-icon svg {
            width: 18px;
            height: 18px;
        }

        .store-empty-success {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .store-empty-state strong {
            color: var(--text-primary);
            font-size: var(--store-text-body);
            font-weight: 800;
        }

        .store-empty-state span:last-child {
            font-size: var(--store-text-helper);
        }

        @media (max-width: 1100px) {
            .store-dashboard-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .store-dashboard-panels {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 600px) {
            .store-dashboard-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .store-dashboard-header h1 {
                font-size: 22px;
            }

            .store-dashboard-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .store-action {
                flex: 1 1 auto;
            }

            .store-dashboard-kpis {
                gap: 8px;
            }

            .store-kpi {
                min-height: 100px;
                padding: 10px;
            }

            .store-kpi-value {
                font-size: 22px;
            }

            .store-panel-header {
                align-items: flex-start;
                flex-wrap: wrap;
                padding: 12px;
            }

            .store-panel-header h2 {
                font-size: 17px;
            }

            .store-table {
                min-width: 480px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .store-action {
                transition: none;
            }
        }
    </style>
@endsection
