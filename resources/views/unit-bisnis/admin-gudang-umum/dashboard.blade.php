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

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/admin-gudang-umum/dashboard.css')
@endpush
@endsection
