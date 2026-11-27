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
                                        <td>{{ $permintaan->details_count }} jenis barang</td>
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

    @push('styles')
    @vite('resources/css/pages/store/dashboard.css')
@endpush
@endsection
