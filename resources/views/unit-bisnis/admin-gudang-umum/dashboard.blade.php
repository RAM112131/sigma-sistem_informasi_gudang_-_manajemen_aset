@extends('layouts.app')

@section('title', 'Dashboard Admin Gudang Umum — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="gu-dashboard" data-admin-gu-dashboard>
        <header class="gu-page-heading">
            <h1>Dashboard</h1>
            <p>Pantau ringkasan stok, transaksi, dan aktivitas gudang umum.</p>
        </header>
        <script type="application/json" data-admin-gu-chart-data>@json($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>

        @if (!$activeGudang)
            <div class="gu-alert" role="alert">
                <strong>Data dashboard belum dapat ditampilkan.</strong>
                <span>Akun ini belum memiliki gudang aktif yang ditugaskan.</span>
            </div>
        @endif

        <section class="gu-kpi-grid" aria-label="Ringkasan gudang">
            <article class="gu-kpi-card gu-kpi-success">
                <span class="gu-kpi-icon gu-kpi-icon-primary" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                        <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Barang Aktif</span>
                    <strong class="gu-kpi-value">
                        @if (!$activeGudang || $stats['active_sku_count'] === 0)
                            Belum ada data
                        @else
                            {{ number_format($stats['active_sku_count']) }}
                        @endif
                    </strong>
                    <span class="gu-kpi-foot">Katalog pada gudang aktif</span>
                </div>
            </article>

            <article class="gu-kpi-card gu-kpi-warning">
                <span class="gu-kpi-icon gu-kpi-icon-warning" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 7h11v11H3zM14 11h4l3 3v4h-7z" />
                        <circle cx="7.5" cy="18.5" r="1.5" />
                        <circle cx="17.5" cy="18.5" r="1.5" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Kiriman menunggu penerimaan</span>
                    <strong class="gu-kpi-value">{{ number_format($stats['pending_receipt_count']) }}</strong>
                    <span class="gu-kpi-foot">Perlu dikonfirmasi Recording GU</span>
                </div>
            </article>

            <article class="gu-kpi-card gu-kpi-critical">
                <span class="gu-kpi-icon gu-kpi-icon-danger" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="m12 3 9 16H3L12 3Z" />
                        <path d="M12 9v4m0 3h.01" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Stok menipis</span>
                    <strong class="gu-kpi-value">{{ number_format($stats['low_stock_count']) }}</strong>
                    <span class="gu-kpi-foot">
                        @if ($stats['minimum_configured_sku_count'] === 0)
                            Batas minimum belum dikonfigurasi
                        @else
                            Dari {{ number_format($stats['minimum_configured_sku_count']) }} barang dengan batas minimum
                        @endif
                    </span>
                </div>
            </article>

            <article class="gu-kpi-card gu-kpi-primary">
                <span class="gu-kpi-icon gu-kpi-icon-value" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path
                            d="M15.5 8.5c-.7-.7-1.6-1-3-1-1.7 0-2.8.8-2.8 2s1 1.8 2.8 2.2 2.8 1 2.8 2.3-1.1 2.2-3 2.2c-1.3 0-2.5-.4-3.3-1.2M12.5 5.5v13" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Estimasi nilai persediaan</span>
                    <strong class="gu-kpi-value gu-kpi-value-money">
                        @if (!$activeGudang || $stats['active_sku_count'] === 0)
                            Belum ada data
                        @elseif($stats['estimated_inventory_value'] === null)
                            Belum tersedia
                        @else
                            Rp {{ number_format($stats['estimated_inventory_value'], 0, ',', '.') }}
                        @endif
                    </strong>
                    <span class="gu-kpi-foot">
                        @if ($stats['unpriced_sku_count'] > 0)
                            Harga belum tersedia untuk {{ number_format($stats['unpriced_sku_count']) }} SKU yang memiliki
                            stok
                        @else
                            Estimasi dari saldo stok × harga master barang
                        @endif
                    </span>
                </div>
            </article>
        </section>

        <section class="gu-analytics-grid" aria-label="Grafik transaksi dan divisi">
            <article class="gu-card gu-chart-card gu-movement-chart-card">
                <div class="gu-card-heading">
                    <h2>Transaksi Mingguan</h2>
                    @if ($activeGudang)
                        <form class="gu-chart-controls" method="GET"
                            action="{{ route('unit-bisnis.admin-gu.dashboard') }}"
                            aria-label="Filter rentang grafik transaksi" data-admin-gu-chart-form>
                            <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                            <input type="hidden" name="period" value="custom" data-chart-period>
                            <div class="gu-chart-period-tabs" role="group" aria-label="Periode grafik">
                                <button
                                    class="gu-chart-period-button {{ $chartRange['period'] === 'week' ? 'is-active' : '' }}"
                                    type="button" data-chart-week
                                    aria-pressed="{{ $chartRange['period'] === 'week' ? 'true' : 'false' }}">7 Hari
                                    Terakhir</button>
                            </div>
                            <div class="gu-date-range">
                                <label class="gu-date-field" for="gu-chart-start-date">
                                    <input id="gu-chart-start-date" type="date" name="start_date"
                                        aria-label="Tanggal mulai" value="{{ $chartRange['start_date'] }}"
                                        max="{{ today()->toDateString() }}" required data-chart-start-date>
                                </label>
                                <span class="gu-date-separator" aria-hidden="true">&mdash;</span>
                                <label class="gu-date-field" for="gu-chart-end-date">
                                    <input id="gu-chart-end-date" type="date" name="end_date" aria-label="Tanggal akhir"
                                        value="{{ $chartRange['end_date'] }}" max="{{ today()->toDateString() }}" required
                                        data-chart-end-date>
                                </label>
                            </div>
                        </form>
                    @endif
                </div>
                <div class="gu-chart-filter-error" role="alert" data-chart-filter-error
                    @if ($chartRange['errors'] === []) hidden @endif>
                    @if ($chartRange['errors'] !== [])
                        Rentang tanggal tidak valid. Filter direset ke 7 hari terakhir.
                        {{ implode(' ', $chartRange['errors']) }}
                    @endif
                </div>
                <p class="gu-chart-filter-status" role="status" aria-live="polite" data-chart-filter-status hidden></p>
                <div class="gu-chart-frame gu-chart-frame-wide" data-movement-chart-panel
                    @if (!$activeGudang || !$chartData['movements']['has_data']) hidden @endif>
                    <canvas data-admin-gu-movement-chart role="img"
                        aria-label="Grafik jumlah transaksi masuk dan keluar untuk periode terpilih"></canvas>
                </div>
                <div class="gu-chart-footer" data-movement-chart-footer @if (!$activeGudang || !$chartData['movements']['has_data']) hidden @endif>
                    <div class="gu-chart-average">
                        <span>Rata-rata transaksi keluar per hari:</span>
                        <strong
                            data-chart-average>{{ number_format($chartData['movements']['average_outgoing_transactions_per_day'], 1, ',', '.') }}
                            transaksi</strong>
                    </div>
                    <div class="gu-chart-legend" aria-label="Legenda grafik">
                        <span><i class="gu-legend-dot gu-legend-incoming" aria-hidden="true"></i>Transaksi Masuk</span>
                        <span><i class="gu-legend-dot gu-legend-outgoing" aria-hidden="true"></i>Transaksi Keluar</span>
                    </div>
                    <div class="gu-chart-ratio">
                        <span>Perubahan transaksi masuk vs keluar</span>
                        @if ($chartData['movements']['incoming_ratio'] === null)
                            <strong aria-label="Tidak tersedia" data-chart-ratio>-</strong>
                        @else
                            <strong
                                class="{{ $chartData['movements']['incoming_ratio'] >= 0 ? 'is-positive' : 'is-negative' }}"
                                data-chart-ratio>{{ $chartData['movements']['incoming_ratio'] > 0 ? '+' : '' }}{{ number_format($chartData['movements']['incoming_ratio'], 1, ',', '.') }}%</strong>
                        @endif
                    </div>
                </div>
                <div class="gu-empty-state" data-movement-chart-empty @if ($activeGudang && $chartData['movements']['has_data']) hidden @endif>
                    <span class="gu-empty-state-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" />
                        </svg>
                    </span>
                    <strong>Belum ada data transaksi</strong>
                    <span>Data mutasi masuk dan keluar untuk rentang yang dipilih belum tersedia.</span>
                </div>
            </article>

            <article class="gu-card gu-chart-card">
                <div class="gu-card-heading">
                    <div>
                        <span class="gu-eyebrow">Pengeluaran barang</span>
                        <h2>Divisi dengan transaksi terbanyak</h2>
                    </div>
                </div>
                <p class="gu-card-description">Jumlah dokumen pengeluaran per divisi pada seluruh riwayat gudang ini.</p>
                @if ($activeGudang && $chartData['divisions']['has_data'])
                    <div class="gu-chart-frame gu-division-chart-frame">
                        <canvas data-admin-gu-division-chart role="img"
                            aria-label="Peringkat lima divisi berdasarkan jumlah transaksi pengeluaran"></canvas>
                    </div>
                @else
                    <div class="gu-empty-state">
                        <span class="gu-empty-state-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" />
                            </svg>
                        </span>
                        <strong>{{ $activeGudang ? 'Belum ada transaksi pengeluaran' : 'Pilih gudang aktif' }}</strong>
                        <span>{{ $activeGudang ? 'Transaksi per divisi akan muncul setelah dokumen pengeluaran tercatat.' : 'Pilih gudang untuk melihat transaksi pengeluaran per divisi.' }}</span>
                    </div>
                @endif
            </article>
        </section>

        <section class="gu-work-grid" aria-label="Antrean kerja harian dan kontrol stok">
            <article class="gu-card">
                <div class="gu-card-heading">
                    <div>
                        <span class="gu-eyebrow">Perlu ditindaklanjuti</span>
                        <h2>Antrean Verifikasi Penerimaan</h2>
                    </div>
                    @if ($verificationQueue !== [])
                        <span class="gu-count-badge">{{ number_format(count($verificationQueue)) }} transaksi</span>
                    @endif
                </div>
                <p class="gu-card-description">Catatan fisik dari Recording GU yang menunggu persetujuan &amp; input harga.
                </p>

                <div class="gu-queue-table-wrap" role="region" aria-label="Daftar transaksi menunggu verifikasi"
                    tabindex="0">
                    <table class="gu-queue-table">
                        <thead>
                            <tr>
                                <th scope="col">No. Dokumen</th>
                                <th scope="col">Jenis &amp; Sumber</th>
                                <th scope="col">Tugas Admin</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($verificationQueue as $transaction)
                                <tr>
                                    <td><strong>{{ $transaction['document'] }}</strong></td>
                                    <td>{{ $transaction['type'] }} - {{ $transaction['source'] }}</td>
                                    <td><span class="gu-task-badge">{{ $transaction['task'] }}</span></td>
                                    <td>
                                        <button class="gu-review-button" type="button" disabled
                                            data-verification-unavailable title="Menu verifikasi belum tersedia">
                                            Periksa &amp; Setujui
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="gu-table-empty" colspan="4">
                                        @if (!$activeGudang)
                                            Pilih gudang aktif untuk melihat antrean verifikasi.
                                        @else
                                            Belum ada transaksi yang menunggu verifikasi.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="gu-queue-note">Aksi persetujuan akan tersedia setelah menu verifikasi dibuat.</p>
            </article>

            <article class="gu-card">
                <div class="gu-card-heading">
                    <div>
                        <span class="gu-eyebrow">Kontrol persediaan</span>
                        <h2>Kontrol Stok di Bawah Minimum</h2>
                    </div>
                    @if ($activeGudang)
                        <a class="gu-stock-action gu-stock-action-header"
                            href="{{ route('unit-bisnis.admin-gu.permintaan.index') }}">Ajukan ke Store</a>
                    @endif
                </div>
                <p class="gu-card-description">Prioritas pengajuan permintaan barang ke Gudang Store Armada.</p>

                <div class="gu-list gu-stock-list" role="region"
                    aria-label="Daftar stok di bawah minimum, gulir untuk melihat seluruh item" tabindex="0"
                    data-low-stock-scroll>
                    @forelse($lowStockItems as $item)
                        @php
                            $stockProgress =
                                $item['minimum_stok'] > 0
                                    ? min(100, (int) round(($item['stok_aktual'] / $item['minimum_stok']) * 100))
                                    : 0;
                            $stockDisplay = rtrim(rtrim(number_format($item['stok_aktual'], 3, ',', '.'), '0'), ',');
                        @endphp
                        <div class="gu-stock-item">
                            <div class="gu-stock-item-heading">
                                <div>
                                    <strong>{{ $item['nama'] }}</strong>
                                    <span>Kode Barang: {{ $item['kode'] }}</span>
                                </div>
                                <span
                                    class="gu-stock-state {{ $item['stok_aktual'] <= 0 ? 'is-critical' : 'is-warning' }}">
                                    {{ $item['stok_aktual'] <= 0 ? 'Stok habis' : 'Perlu restock' }}
                                </span>
                            </div>
                            <div class="gu-stock-level">
                                <span>Sisa {{ $stockDisplay }} {{ $item['satuan'] ?? 'unit' }} / Min
                                    {{ number_format($item['minimum_stok']) }} {{ $item['satuan'] ?? 'unit' }}</span>
                                <div class="gu-progress-track" role="progressbar"
                                    aria-label="Capaian stok minimum untuk {{ $item['nama'] }}" aria-valuemin="0"
                                    aria-valuemax="100" aria-valuenow="{{ $stockProgress }}">
                                    <span class="gu-progress-fill {{ $item['stok_aktual'] <= 0 ? 'is-critical' : '' }}"
                                        style="width: {{ $stockProgress }}%"></span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="gu-empty-inline">
                            @if (!$activeGudang)
                                Pilih gudang aktif untuk melihat peringatan stok.
                            @elseif($stats['active_sku_count'] === 0)
                                Belum ada data master barang untuk gudang ini.
                            @elseif($stats['minimum_configured_sku_count'] === 0)
                                Batas stok minimum belum dikonfigurasi pada katalog barang.
                            @else
                                Tidak ada SKU yang berada di bawah batas minimum.
                            @endif
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="gu-card gu-activity-card" aria-labelledby="gu-verification-history-title">
            <div class="gu-card-heading">
                <div>
                    <span class="gu-eyebrow">Log persetujuan</span>
                    <h2 id="gu-verification-history-title">Riwayat Verifikasi Selesai Hari Ini</h2>
                </div>
                <span class="gu-period-badge">Pengeluaran &amp; pengembalian</span>
            </div>
            <div class="gu-table-wrap" role="region" aria-label="Riwayat verifikasi selesai hari ini" tabindex="0">
                <table class="gu-history-table">
                    <thead>
                        <tr>
                            <th scope="col">Waktu</th>
                            <th scope="col">No. Dokumen</th>
                            <th scope="col">Jenis Transaksi</th>
                            <th scope="col">Dampak Stok/HPP</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($verificationHistory as $history)
                            <tr>
                                <td>{{ $history['occurred_at'] }}</td>
                                <td><strong>{{ $history['reference'] }}</strong></td>
                                <td><span class="gu-activity-type">{{ $history['type'] }}</span></td>
                                <td class="gu-history-impact">{{ $history['impact'] }}</td>
                                <td><span class="gu-verified-badge">{{ $history['status'] }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td class="gu-table-empty" colspan="5">
                                    @if (!$activeGudang)
                                        Riwayat belum dapat ditampilkan karena gudang aktif belum tersedia.
                                    @else
                                        Belum ada verifikasi pengeluaran atau pengembalian yang selesai hari ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="gu-table-note">Pengembalian ditampilkan jika dicatat hari ini dan terhubung dengan BPB yang disetujui
                Admin GU hari ini.</p>
        </section>
    </div>

    <style>
        .gu-dashboard {
            --gu-ink: var(--text-primary);
            --gu-muted: var(--text-muted);
            --gu-border: var(--surface-border);
            --gu-surface: var(--surface-card);
            --gu-background: var(--surface-base);
            --gu-primary: var(--brand-primary);
            --gu-primary-dark: var(--brand-primary-d);
            --gu-warning: var(--status-warning);
            --gu-danger: var(--status-critical);
            --dash-text-helper: 12px;
            --dash-text-body: 14px;
            --dash-text-section: 20px;
            --dash-text-metric: 24px;
            display: grid;
            gap: 16px;
            color: var(--gu-ink);
        }

        .gu-page-heading h1 {
            margin: 0;
            color: var(--gu-ink);
            font-size: 24px;
            font-weight: 850;
            line-height: 1.2;
        }

        .gu-page-heading p {
            margin: 4px 0 0;
            color: var(--gu-muted);
            font-size: var(--dash-text-body);
            line-height: 1.5;
        }

        .gu-card {
            min-width: 0;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius-lg);
            background: var(--gu-surface);
        }

        .gu-eyebrow {
            display: block;
            color: var(--gu-primary);
            font-size: var(--dash-text-helper);
            font-weight: 750;
            letter-spacing: .075em;
            line-height: 1.4;
            text-transform: uppercase;
        }

        .gu-text-link:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .gu-alert {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 14px 16px;
            border: 1px solid rgba(184, 121, 26, .3);
            border-radius: var(--radius);
            background: rgba(184, 121, 26, .08);
            color: var(--status-warning);
            font-size: .88rem;
            line-height: 1.5;
        }

        .gu-alert strong {
            font-size: .92rem;
        }

        .gu-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .gu-kpi-card {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 108px;
            flex-direction: column;
            justify-content: space-between;
            gap: 4px;
            overflow: hidden;
            padding: 12px 14px 10px;
            border: 1px solid var(--gu-border);
            border-top: 3px solid var(--kpi-accent, var(--gu-primary));
            border-radius: var(--radius);
            background: var(--gu-surface);
            color: var(--gu-ink);
        }

        .gu-kpi-icon {
            position: absolute;
            top: 8px;
            right: 8px;
            display: grid;
            width: 24px;
            height: 24px;
            place-items: center;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius);
            background: var(--gu-background);
            color: var(--kpi-accent, var(--gu-primary));
        }

        .gu-kpi-icon svg {
            width: 14px;
            height: 14px;
        }

        .gu-kpi-success {
            --kpi-accent: var(--status-success);
        }

        .gu-kpi-warning {
            --kpi-accent: var(--status-warning);
        }

        .gu-kpi-critical {
            --kpi-accent: var(--status-critical);
        }

        .gu-kpi-primary {
            --kpi-accent: var(--brand-primary);
        }

        .gu-kpi-card>div {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 4px;
        }

        .gu-kpi-label {
            padding-right: 28px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            font-weight: 800;
            letter-spacing: .055em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .gu-kpi-value {
            color: var(--gu-ink);
            font-size: var(--dash-text-metric);
            font-weight: 850;
            font-variant-numeric: tabular-nums;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .gu-kpi-value-money {
            font-size: 22px;
        }

        .gu-kpi-foot {
            width: fit-content;
            max-width: 100%;
            padding: 3px 5px;
            border-radius: var(--radius);
            background: rgba(91, 107, 121, .1);
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .gu-analytics-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
            gap: 16px;
        }

        .gu-card {
            --gu-card-padding-x: 16px;
            --gu-card-padding-y: 14px;
            padding: var(--gu-card-padding-y) var(--gu-card-padding-x);
        }

        .gu-card-heading {
            display: flex;
            min-height: 68px;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin: calc(0px - var(--gu-card-padding-y)) calc(0px - var(--gu-card-padding-x)) 14px;
            padding: var(--gu-card-padding-y) var(--gu-card-padding-x);
            border-bottom: 1px solid var(--gu-border);
        }

        .gu-chart-card .gu-card-heading {
            flex-wrap: wrap;
        }

        .gu-card-heading h2 {
            margin: 0;
            color: var(--gu-ink);
            font-size: var(--dash-text-section);
            font-weight: 800;
            line-height: 1.2;
        }

        .gu-card-description {
            margin: 8px 0 14px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.5;
        }

        .gu-movement-chart-card {
            --gu-card-padding-x: 14px;
            --gu-card-padding-y: 12px;
            width: 100%;
            padding: var(--gu-card-padding-y) var(--gu-card-padding-x) 10px;
        }

        .gu-movement-chart-card .gu-card-heading {
            min-height: 0;
            flex-wrap: nowrap;
            align-items: center;
            gap: 8px;
            padding-block: 6px;
        }

        .gu-movement-chart-card .gu-card-heading h2 {
            flex: 0 0 auto;
            min-width: max-content;
            white-space: nowrap;
        }

        .gu-period-badge {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            flex: 0 0 auto;
            padding: 5px 8px;
            border-radius: var(--radius);
            background: var(--gu-background);
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            font-weight: 700;
        }

        .gu-chart-frame {
            position: relative;
            min-width: 0;
        }

        .gu-chart-frame-wide {
            height: 210px;
        }

        .gu-division-chart-frame {
            width: 100%;
            height: 230px;
        }

        .gu-chart-frame canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .gu-chart-controls {
            display: flex;
            min-width: 0;
            max-width: 100%;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 6px;
            margin: 0;
        }

        .gu-chart-period-tabs {
            display: inline-flex;
            gap: 2px;
            padding: 1px 2px;
            border-radius: var(--radius);
            background: var(--gu-background);
        }

        .gu-chart-period-button {
            min-height: 38px;
            padding: 3px 9.5px;
            border: 0;
            border-radius: 3px;
            background: transparent;
            color: var(--gu-muted);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 600;
            white-space: nowrap;
            cursor: pointer;
        }

        .gu-chart-period-button.is-active {
            background: var(--gu-surface);
            color: var(--gu-primary);
            box-shadow: 0 0 0 1px var(--gu-border);
        }

        .gu-chart-period-button:focus-visible,
        .gu-date-field input:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .gu-date-range {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border-radius: var(--radius);
            background: var(--gu-background);
        }

        .gu-date-icon {
            width: 13px;
            height: 13px;
            flex: 0 0 auto;
            color: var(--gu-muted);
        }

        .gu-date-field {
            display: flex;
            min-width: 0;
            align-items: center;
        }

        .gu-date-field input {
            width: 88px;
            min-width: 0;
            min-height: 38px;
            padding: 1px;
            border: 0;
            background: transparent;
            color: var(--gu-ink);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-variant-numeric: tabular-nums;
            cursor: pointer;
        }

        .gu-date-separator {
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
        }

        .gu-chart-filter-error {
            margin: 0 0 12px;
            padding: 10px 12px;
            border: 1px solid rgba(184, 121, 26, .3);
            border-radius: var(--radius);
            background: rgba(184, 121, 26, .08);
            color: var(--status-warning);
            font-size: var(--dash-text-helper);
            line-height: 1.45;
        }

        .gu-chart-filter-error[hidden],
        .gu-chart-filter-status[hidden],
        [data-movement-chart-panel][hidden],
        [data-movement-chart-footer][hidden],
        [data-movement-chart-empty][hidden] {
            display: none;
        }

        .gu-chart-filter-status {
            margin: 0 0 8px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
        }

        .gu-chart-filter-error ul {
            display: grid;
            gap: 3px;
            margin: 5px 0 0;
            padding-left: 18px;
        }

        .gu-chart-footer {
            display: flex;
            min-width: 0;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
            gap: 6px 12px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--gu-border);
        }

        .gu-chart-average,
        .gu-chart-ratio {
            display: flex;
            min-width: 0;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            white-space: normal;
        }

        .gu-chart-average strong,
        .gu-chart-ratio strong {
            color: var(--gu-ink);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .gu-chart-average-values {
            display: flex;
            min-width: 0;
            flex-wrap: wrap;
            gap: 4px;
        }

        .gu-chart-average-values strong {
            padding: 2px 6px;
            border-radius: var(--radius);
            background: rgba(91, 107, 121, .1);
        }

        .gu-chart-average-values>span {
            color: var(--gu-muted);
        }

        .gu-chart-ratio strong.is-positive {
            color: var(--gu-primary-dark);
        }

        .gu-chart-ratio strong.is-negative {
            color: var(--gu-danger);
        }

        .gu-chart-ratio {
            justify-content: flex-start;
            color: var(--gu-primary);
            font-weight: 650;
        }

        .gu-chart-legend {
            display: flex;
            min-width: 0;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px 14px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            white-space: normal;
        }

        .gu-chart-legend span {
            display: inline-flex;
            min-width: 0;
            align-items: center;
            gap: 6px;
        }

        .gu-legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .gu-legend-incoming {
            background: var(--brand-primary);
        }

        .gu-legend-outgoing {
            background: var(--status-critical);
        }

        .gu-work-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .gu-list {
            display: grid;
            gap: 9px;
        }

        .gu-stock-item {
            display: grid;
            min-width: 0;
            grid-template-columns: minmax(0, 1.3fr) minmax(135px, 1fr);
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius);
            background: var(--gu-background);
        }

        .gu-stock-item-heading {
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .gu-stock-item-heading>div {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 2px;
        }

        .gu-stock-item-heading strong {
            overflow: hidden;
            color: var(--gu-ink);
            font-size: var(--dash-text-body);
            line-height: 1.4;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .gu-stock-item-heading>div>span {
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.4;
        }

        .gu-stock-state {
            flex: 0 0 auto;
            padding: 4px 6px;
            border-radius: var(--radius);
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
            font-size: var(--dash-text-helper);
            font-weight: 750;
        }

        .gu-stock-state.is-critical {
            background: rgba(193, 68, 60, .12);
            color: var(--status-critical);
        }

        .gu-stock-state.is-warning {
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
        }

        .gu-stock-level {
            display: grid;
            min-width: 0;
            gap: 4px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            font-variant-numeric: tabular-nums;
        }

        .gu-stock-level>span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .gu-progress-track {
            height: 4px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--gu-border);
        }

        .gu-progress-fill {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--status-warning);
        }

        .gu-progress-fill.is-critical {
            background: var(--status-critical);
        }

        .gu-queue-table-wrap {
            max-height: 360px;
            overflow: auto;
            margin-top: 14px;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius);
        }

        .gu-queue-table {
            width: 100%;
            min-width: 570px;
            border-collapse: collapse;
            text-align: left;
        }

        .gu-queue-table th,
        .gu-queue-table td {
            padding: 10px 11px;
            border-bottom: 1px solid var(--gu-border);
            font-size: var(--dash-text-helper);
            line-height: 1.45;
            vertical-align: middle;
        }

        .gu-queue-table th {
            background: var(--gu-background);
            color: var(--gu-muted);
            font-weight: 800;
            letter-spacing: .035em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .gu-queue-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-queue-table td {
            color: var(--gu-muted);
        }

        .gu-queue-table td strong {
            color: var(--gu-ink);
            white-space: nowrap;
        }

        .gu-task-badge {
            display: inline-flex;
            min-height: 26px;
            align-items: center;
            padding: 4px 7px;
            border-radius: var(--radius);
            background: rgba(184, 121, 26, .12);
            color: var(--status-warning);
            font-size: var(--dash-text-helper);
            font-weight: 750;
            white-space: nowrap;
        }

        .gu-review-button {
            min-height: 38px;
            padding: 6px 9px;
            border: 0;
            border-radius: var(--radius);
            background: var(--brand-primary);
            color: #fff;
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 700;
            white-space: nowrap;
        }

        .gu-review-button:disabled {
            cursor: not-allowed;
            opacity: .68;
        }

        .gu-queue-note {
            margin: 9px 0 0;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.45;
        }

        .gu-stock-list {
            max-height: 330px;
            gap: 6px;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding-right: 4px;
        }

        .gu-stock-action {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border-radius: var(--radius);
            background: var(--gu-primary);
            color: #fff;
            font-size: var(--dash-text-helper);
            font-weight: 750;
            text-decoration: none;
            white-space: nowrap;
        }

        .gu-stock-action-header {
            min-height: 44px;
            align-self: center;
            padding-inline: 12px;
        }

        .gu-stock-action:hover {
            background: var(--gu-primary-dark);
            color: #fff;
        }

        .gu-stock-action:focus-visible,
        .gu-queue-table-wrap:focus-visible,
        .gu-stock-list:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .gu-text-link {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            margin-top: 9px;
            color: var(--gu-primary);
            font-size: .8rem;
            font-weight: 750;
            text-decoration: none;
        }

        .gu-text-link:hover {
            color: var(--gu-primary-dark);
            text-decoration: underline;
        }

        .gu-empty-inline {
            padding: 17px 4px;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.5;
        }

        .gu-empty-state {
            display: flex;
            min-height: 238px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 22px;
            color: var(--gu-muted);
            text-align: center;
        }

        .gu-empty-state-icon {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border-radius: var(--radius);
            background: var(--gu-background);
            color: var(--gu-primary);
        }

        .gu-empty-state-icon svg {
            width: 21px;
            height: 21px;
        }

        .gu-empty-state strong {
            color: var(--gu-ink);
            font-size: .88rem;
        }

        .gu-empty-state>span:last-child {
            max-width: 310px;
            font-size: var(--dash-text-helper);
            line-height: 1.5;
        }

        .gu-activity-card {
            padding-bottom: 12px;
        }

        .gu-table-wrap {
            width: 100%;
            margin-top: 16px;
            overflow-x: auto;
            border: 1px solid var(--gu-border);
            border-radius: var(--radius);
        }

        .gu-table-wrap:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .gu-activity-type {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 4px 7px;
            border-radius: var(--radius);
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            white-space: nowrap;
        }

        .gu-history-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            text-align: left;
        }

        .gu-history-table th,
        .gu-history-table td {
            padding: 8px 11px;
            border-bottom: 1px solid var(--gu-border);
            font-size: var(--dash-text-helper);
            line-height: 1.4;
            vertical-align: middle;
        }

        .gu-history-table th {
            background: var(--gu-background);
            color: var(--gu-muted);
            font-weight: 800;
            letter-spacing: .035em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .gu-history-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-history-table td {
            color: var(--gu-muted);
        }

        .gu-history-table td strong {
            color: var(--gu-ink);
            white-space: nowrap;
        }

        .gu-history-impact {
            max-width: 520px;
        }

        .gu-verified-badge {
            display: inline-flex;
            min-height: 24px;
            align-items: center;
            padding: 4px 7px;
            border-radius: var(--radius);
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
            font-size: var(--dash-text-helper);
            font-weight: 750;
            white-space: nowrap;
        }

        .gu-table-empty {
            padding: 28px !important;
            color: var(--gu-muted) !important;
            text-align: center;
        }

        .gu-table-note {
            margin: 10px 0 0;
            color: var(--gu-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.45;
        }

        @media (max-width: 1120px) {
            .gu-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gu-analytics-grid {
                grid-template-columns: 1fr;
            }

            .gu-work-grid {
                grid-template-columns: 1fr;
            }

            .gu-movement-chart-card {
                width: 100%;
            }
        }

        @media (max-width: 760px) {
            .gu-work-grid {
                grid-template-columns: 1fr;
            }

            .gu-chart-controls {
                justify-content: flex-start;
            }

            .gu-movement-chart-card .gu-card-heading h2 {
                font-size: 15px;
            }

            .gu-movement-chart-card .gu-chart-controls {
                flex: 0 1 auto;
                width: auto;
                gap: 4px;
            }

            .gu-movement-chart-card .gu-chart-period-tabs {
                flex: 0 0 auto;
            }

            .gu-movement-chart-card .gu-chart-period-button {
                padding-inline: 4px;
            }

            .gu-movement-chart-card .gu-date-range {
                flex: 1 1 auto;
                gap: 1px;
                padding-inline: 3px;
            }

            .gu-movement-chart-card .gu-date-icon {
                width: 11px;
            }

            .gu-movement-chart-card .gu-date-field {
                flex: 0 1 60px;
            }

            .gu-movement-chart-card .gu-date-field input {
                width: 60px;
                font-size: 10px;
            }

            .gu-movement-chart-card .gu-date-separator {
                font-size: 10px;
            }
        }

        @media (max-width: 600px) {
            .gu-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .gu-kpi-card {
                min-height: 100px;
                padding: 10px;
            }

            .gu-kpi-value {
                font-size: 22px;
            }
        }

        @media (max-width: 520px) {
            .gu-dashboard {
                gap: 13px;
            }

            .gu-card {
                --gu-card-padding-x: 14px;
                --gu-card-padding-y: 12px;
            }

            .gu-card-heading {
                align-items: flex-start;
            }

            .gu-chart-controls {
                display: flex;
                width: 100%;
                justify-content: flex-start;
                gap: 4px;
            }

            .gu-movement-chart-card .gu-card-heading {
                flex-wrap: wrap;
            }

            .gu-movement-chart-card .gu-card-heading h2 {
                flex: 0 0 100%;
                min-width: 0;
                font-size: var(--dash-text-section);
                white-space: normal;
            }

            .gu-movement-chart-card .gu-chart-controls {
                flex: 0 0 100%;
                width: 100%;
            }

            .gu-movement-chart-card .gu-chart-period-button,
            .gu-movement-chart-card .gu-date-field input {
                min-height: 44px;
            }

            .gu-chart-period-tabs {
                flex: 0 0 auto;
            }

            .gu-chart-period-button {
                padding-inline: 8px;
            }

            .gu-date-range {
                flex: 1 1 0;
                justify-content: space-between;
                width: auto;
                gap: 2px;
                padding-inline: 4px;
            }

            .gu-date-field {
                flex: 1 1 0;
            }

            .gu-date-field input {
                width: 100%;
                min-width: 0;
                font-size: var(--dash-text-helper);
            }

            .gu-chart-footer {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .gu-chart-average,
            .gu-chart-ratio {
                font-size: var(--dash-text-helper);
            }

            .gu-chart-average strong,
            .gu-chart-ratio strong {
                font-size: var(--dash-text-helper);
            }

            .gu-chart-legend {
                font-size: var(--dash-text-helper);
            }

            .gu-chart-ratio {
                justify-content: flex-start;
            }

            .gu-division-chart-frame {
                height: 210px;
            }

            .gu-stock-item-heading {
                align-items: flex-start;
            }

            .gu-history-table {
                min-width: 760px;
            }

            .gu-chart-frame-wide {
                height: 220px;
            }
        }

        @media (max-width: 360px) {
            .gu-chart-controls {
                gap: 3px;
            }

            .gu-chart-period-button {
                padding-inline: 6px;
            }

            .gu-date-range {
                padding-inline: 3px;
            }

            .gu-date-icon {
                display: none;
            }
        }

        @media (max-width: 520px) {
            .gu-stock-list {
                max-height: 450px;
            }

            .gu-stock-item {
                grid-template-columns: minmax(0, 1fr);
                gap: 6px;
            }

            .gu-stock-item-heading {
                align-items: flex-start;
            }
        }
    </style>
@endsection
