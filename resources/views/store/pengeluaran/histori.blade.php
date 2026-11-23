@extends('layouts.app')

@section('title', 'Histori Pengeluaran — SIGMA Store')

@push('styles')
    <style>
        .expense-history-page {
            --history-helper: 12px;
            --history-body: 14px;
            min-width: 0;
        }

        .expense-history-page .page-header {
            margin-bottom: 16px;
        }

        .expense-history-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            border-bottom: 2px solid var(--surface-border);
            margin-bottom: 12px;
        }

        .expense-history-tabs .btn {
            min-height: 40px;
            padding: 8px 14px;
            gap: 6px;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .expense-history-filter {
            margin-bottom: 12px;
            padding: 12px 14px;
        }

        .expense-history-filter form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .expense-history-search {
            flex: 1 1 260px;
            min-width: 0;
        }

        .expense-history-date {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--text-muted);
            font-size: var(--history-helper);
            font-weight: 600;
        }

        .expense-history-date input {
            width: 150px;
            min-height: 40px;
        }

        .expense-history-filter .form-control,
        .expense-history-filter .btn {
            min-height: 40px;
        }

        .expense-history-summary {
            margin: 0 2px 10px;
            color: var(--text-muted);
            font-size: var(--history-helper);
        }

        .expense-history-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .expense-history-card {
            margin: 0 !important;
            padding: 12px 14px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            box-shadow: 0 1px 2px rgba(31, 37, 35, .04);
        }

        .expense-history-card-header,
        .expense-history-card-actions,
        .expense-history-card-meta,
        .expense-history-card-footer {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .expense-history-card-header {
            justify-content: space-between;
            gap: 12px;
        }

        .expense-history-document {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 0;
            color: var(--text-primary);
            font-size: 15px;
            font-weight: 800;
            line-height: 1.35;
        }

        .expense-history-header-meta {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            color: var(--text-muted);
            font-size: var(--history-helper);
            font-weight: 500;
        }

        .expense-history-header-meta strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        .expense-history-document-number {
            color: var(--brand-primary);
            font-variant-numeric: tabular-nums;
        }

        .expense-history-card-meta {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: var(--history-helper);
        }

        .expense-history-card-meta strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        .expense-history-badge {
            display: inline-flex;
            min-height: 26px;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: var(--history-helper);
            font-weight: 700;
            line-height: 1.2;
        }

        .expense-history-badge-dispatched {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .expense-history-badge-partial {
            background: rgba(201, 122, 43, .13);
            color: var(--brand-accent-d);
        }

        .expense-history-badge-received {
            background: rgba(63, 122, 82, .12);
            color: var(--status-success);
        }

        .expense-history-direct {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 999px;
            background: rgba(201, 122, 43, .13);
            color: var(--brand-accent-d);
            font-size: 11px;
            font-weight: 700;
        }

        .expense-history-item-count {
            display: inline-flex;
            min-height: 26px;
            align-items: center;
            padding: 4px 9px;
            border-radius: 999px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--history-helper);
            font-weight: 700;
        }

        .expense-history-card-actions .btn {
            min-height: 36px;
            padding: 7px 11px;
            font-size: var(--history-helper);
        }

        .expense-history-print {
            color: var(--brand-primary);
        }

        .expense-history-details {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--surface-border);
        }

        .expense-history-detail-table {
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
        }

        .expense-history-detail-table .data-table th {
            font-size: var(--history-helper);
        }

        .expense-history-detail-table .data-table td {
            font-size: var(--history-body);
        }

        .expense-history-card-footer {
            justify-content: space-between;
            margin-top: 10px;
        }

        .expense-history-empty {
            padding: 32px 16px;
            color: var(--text-muted);
            text-align: center;
        }

        .expense-history-empty strong {
            display: block;
            margin-bottom: 4px;
            color: var(--text-primary);
            font-size: 15px;
        }

        .expense-history-empty p {
            margin: 0;
            font-size: var(--history-helper);
        }

        .expense-history-page .btn:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        @media (max-width: 720px) {
            .expense-history-filter {
                padding: 12px;
            }

            .expense-history-filter form {
                align-items: stretch;
                flex-direction: column;
            }

            .expense-history-search {
                flex-basis: auto;
            }

            .expense-history-date {
                justify-content: space-between;
            }

            .expense-history-date input {
                flex: 1;
                width: auto;
            }

            .expense-history-filter form>.btn {
                width: 100%;
            }

            .expense-history-card-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .expense-history-card-actions {
                width: 100%;
            }

            .expense-history-card-actions .btn {
                flex: 1 1 auto;
                justify-content: center;
            }

            .expense-history-detail-table .data-table {
                min-width: 640px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .expense-history-page *,
            .expense-history-page *::before,
            .expense-history-page *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
    </style>
@endpush

@php
    $docsJsonMap = [];
    foreach ($paginatedDocs as $docRow) {
        $gKey = $docRow->permintaan_barang_id ? (string) $docRow->permintaan_barang_id : $docRow->nomor_keluar;
        $dItems = $allTransfers[$gKey] ?? collect();
        $dFirst = $dItems->first();
        $dNomorKeluar = $docRow->nomor_keluar;
        $dCatatan = optional(optional($dFirst)->permintaanBarang)->catatan ?? '';
        $dNomorPrRaw = optional(optional($dFirst)->permintaanBarang)->nomor_pr;
        $dIsLangsung = empty($dNomorPrRaw) || str_starts_with((string) $dNomorPrRaw, 'REQLANG-');
        $dNomorPr = $dIsLangsung ? null : $dNomorPrRaw;
        $dPemohon = optional(optional($dFirst)->permintaanBarang?->peminta)->name;
        if (!$dPemohon && $dFirst?->penerima) {
            $dPemohon = $dFirst->penerima->name;
        }
        if (!$dPemohon && preg_match('/Pemohon:\s*([^,]+)/i', $dCatatan, $m)) {
            $dPemohon = trim($m[1]);
        }
        $dPemohon = $dPemohon ?: '—';

        $dPetugas = optional(optional($dFirst)->pengirim)->name ?? 'Admin Store';
        if (preg_match('/Petugas:\s*([^,]+)/i', $dCatatan, $m2)) {
            $dPetugas = trim($m2[1]);
        }

        $dTujuan = null;
        if ($dFirst?->gudang_tujuan_id && (int) $dFirst->gudang_tujuan_id !== (int) $dFirst->gudang_asal_id) {
            $dTujuan = optional($dFirst->gudangTujuan)->nama;
        }
        if (
            !$dTujuan &&
            $dFirst?->permintaanBarang?->gudang_peminta_id &&
            (int) $dFirst->permintaanBarang->gudang_peminta_id !== (int) $dFirst->gudang_asal_id
        ) {
            $dTujuan = optional($dFirst->permintaanBarang->gudangPeminta)->nama;
        }
        if ((!$dTujuan || $dTujuan === 'Gudang Store') && preg_match('/Keterangan:\s*([^,]+)/i', $dCatatan, $mk)) {
            $dTujuan = trim($mk[1]);
        }
        $dHasTujuan = !empty($dTujuan) && $dTujuan !== '—' && $dTujuan !== 'Gudang Store';
        $dShowKepada = $dHasTujuan;
        $dTujuan = $dHasTujuan ? $dTujuan : '—';
        $dWaktu = $dFirst?->dikirim_at ?? $dFirst?->created_at;

        $docsJsonMap[$dNomorKeluar] = [
            'nomorKeluar' => $dNomorKeluar,
            'waktu' => $dWaktu ? \Carbon\Carbon::parse($dWaktu)->translatedFormat('d F Y') : '—',
            'nomorPr' => $dNomorPr,
            'isLangsung' => $dIsLangsung,
            'showKepada' => $dShowKepada,
            'petugas' => $dPetugas,
            'tujuan' => $dTujuan,
            'pemohon' => $dPemohon,
            'catatan' => $dCatatan,
            'totalJenis' => $dItems->count(),
            'totalQty' => (int) $dItems->sum(fn($it) => $it->qty_dikirim > 0 ? $it->qty_dikirim : $it->qty_diminta),
            'items' => $dItems
                ->values()
                ->map(function ($it, $idx) {
                    return [
                        'no' => $idx + 1,
                        'kode' => optional($it->barang)->kode ?? '—',
                        'nama' => optional($it->barang)->nama ?? '—',
                        'kategori' => optional(optional($it->barang)->kategori)->nama ?? '—',
                        'satuan' => optional(optional($it->barang)->satuan)->nama ?? '—',
                        'qty' => (int) ($it->qty_dikirim > 0 ? $it->qty_dikirim : $it->qty_diminta),
                    ];
                })
                ->all(),
        ];
    }

    if (isset($printDoc) && $printDoc) {
        $docsJsonMap[$printDoc['nomorKeluar']] = $printDoc;
    }
@endphp

@section('content')
    @include('store.pengeluaran.partials.print_script')

    <script>
        (function() {
            function historiPengeluaranApp() {
                return {
                    expandedDoc: null,
                    docsMap: @js($docsJsonMap),

                    init() {
                        @if (session('print_sj'))
                            const targetNomor = @js(session('print_sj'));
                            this.$nextTick(() => {
                                setTimeout(() => {
                                    this.printSuratJalan(targetNomor);
                                }, 300);
                            });
                        @endif
                    },

                    printSuratJalan(nomorKeluar) {
                        const doc = this.docsMap[nomorKeluar];
                        if (!doc) {
                            alert('Data dokumen ' + nomorKeluar + ' tidak ditemukan.');
                            return;
                        }
                        window.printSuratJalanDirect(doc);
                    }
                };
            }

            window.historiPengeluaranApp = historiPengeluaranApp;
            if (window.Alpine) {
                window.Alpine.data('historiPengeluaranApp', historiPengeluaranApp);
            }
            document.addEventListener('alpine:init', function() {
                Alpine.data('historiPengeluaranApp', historiPengeluaranApp);
            });
        })();
    </script>

    <div class="expense-history-page" x-data="historiPengeluaranApp()" x-init="init()">

        <div class="page-header mb-3">
            <div>
                <h1 class="page-title">Histori Pengeluaran</h1>
                <p class="page-subtitle">Riwayat seluruh dokumen pengeluaran barang yang telah diproses oleh Gudang Store.
                </p>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="expense-tabs expense-history-tabs" role="tablist" aria-label="Menu pengeluaran barang">
            @if (
                $canCreatePengeluaran ??
                    auth()->user()?->hasRole('Super Admin') ||
                        auth()->user()?->can('store_pengeluaran.create') ||
                        auth()->user()?->can('store.distribusi'))
                <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan']) }}" class="btn btn-outline"
                    role="tab">
                    <span aria-hidden="true">▤</span> Berdasarkan Permintaan
                </a>
                <a href="{{ route('store.pengeluaran.index', ['tab' => 'langsung']) }}" class="btn btn-outline"
                    role="tab">
                    <span aria-hidden="true">➜</span> Pemindahan tanpa Permintaan
                </a>
            @endif
            <a href="{{ route('store.pengeluaran.index', ['tab' => 'histori']) }}" class="btn btn-primary" role="tab"
                aria-selected="true">
                <span aria-hidden="true">🕒</span> Histori Pengeluaran
            </a>
        </div>

        {{-- Search & Filter --}}
        <div class="card expense-history-filter">
            <form action="{{ route('store.pengeluaran.index') }}" method="GET">
                <input type="hidden" name="tab" value="histori">

                <div class="expense-history-search">
                    <input type="search" name="search" class="form-control" aria-label="Cari histori pengeluaran"
                        placeholder="Cari no. dokumen, barang, kode, req, pemohon…" value="{{ $search }}"
                        autocomplete="off" oninput="handleLiveSearch(this)"
                        @if (!empty($search)) autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)" @endif>
                </div>

                <div style="min-width: 170px;">
                    <select name="status" class="form-control" aria-label="Filter status pengeluaran"
                        onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="diminta" @selected(($status ?? '') === 'diminta')>Menunggu Pengiriman</option>
                        <option value="dikirim" @selected(($status ?? '') === 'dikirim')>Dalam Pengiriman</option>
                        <option value="diterima" @selected(($status ?? '') === 'diterima')>Selesai / Diterima</option>
                    </select>
                </div>

                <label class="expense-history-date">
                    <span>Dari</span>
                    <input type="date" name="tgl_awal" class="form-control" aria-label="Tanggal awal"
                        value="{{ $tglAwal }}" onchange="this.form.submit()">
                </label>

                <label class="expense-history-date">
                    <span>Sampai</span>
                    <input type="date" name="tgl_akhir" class="form-control" aria-label="Tanggal akhir"
                        value="{{ $tglAkhir }}" onchange="this.form.submit()">
                </label>

                <button type="submit" class="btn btn-primary">Filter</button>

                @if ($search || $tglAwal || $tglAkhir || ($status ?? ''))
                    <a href="{{ route('store.pengeluaran.index', ['tab' => 'histori']) }}" class="btn btn-outline">Reset</a>
                @endif
            </form>
        </div>

        <script>
            window._sigmaHistorySearchTimer = window._sigmaHistorySearchTimer || null;
            window.handleLiveSearch = function(input) {
                clearTimeout(window._sigmaHistorySearchTimer);
                window._sigmaHistorySearchTimer = setTimeout(function() {
                    const val = input.value.trim();
                    if (val.length >= 3 || val.length === 0) {
                        input.form.submit();
                    }
                }, 400);
            };
        </script>

        {{-- Summary --}}
        <div class="expense-history-summary" role="status">
            Menampilkan <strong>{{ $paginatedDocs->firstItem() ?? 0 }}–{{ $paginatedDocs->lastItem() ?? 0 }}</strong>
            dari <strong>{{ $paginatedDocs->total() }}</strong> dokumen pengeluaran
        </div>

        <div class="expense-history-list">
            @forelse($paginatedDocs as $docRow)
                @php
                    $groupKey = $docRow->permintaan_barang_id
                        ? (string) $docRow->permintaan_barang_id
                        : $docRow->nomor_keluar;

                    $items = $allTransfers[$groupKey] ?? collect();
                    $first = $items->first();
                    $nomorKeluar = $docRow->nomor_keluar;
                    $catatan = optional(optional($first)->permintaanBarang)->catatan ?? '';
                    $nomorPrRaw = optional(optional($first)->permintaanBarang)->nomor_pr;
                    $isLangsung = empty($nomorPrRaw) || str_starts_with((string) $nomorPrRaw, 'REQLANG-');
                    $nomorPr = $isLangsung ? null : $nomorPrRaw;

                    $tujuan = null;
                    if ($first?->gudang_tujuan_id && (int) $first->gudang_tujuan_id !== (int) $first->gudang_asal_id) {
                        $tujuan = optional($first->gudangTujuan)->nama;
                    }
                    if (
                        !$tujuan &&
                        $first?->permintaanBarang?->gudang_peminta_id &&
                        (int) $first->permintaanBarang->gudang_peminta_id !== (int) $first->gudang_asal_id
                    ) {
                        $tujuan = optional($first->permintaanBarang->gudangPeminta)->nama;
                    }
                    if (
                        (!$tujuan || $tujuan === 'Gudang Store') &&
                        preg_match('/Keterangan:\s*([^,]+)/i', $catatan, $tujuanMatch)
                    ) {
                        $tujuan = trim($tujuanMatch[1]);
                    }
                    $tujuan = !empty($tujuan) && $tujuan !== '—' && $tujuan !== 'Gudang Store' ? $tujuan : '—';
                    $petugas = optional(optional($first)->pengirim)->name ?? 'Admin Store';
                    if (preg_match('/Petugas:\s*([^,]+)/i', $catatan, $petugasMatch)) {
                        $petugas = trim($petugasMatch[1]);
                    }
                    $waktu = $first?->dikirim_at ?? $first?->created_at;
                    $semuaDiminta = $items->every(fn($transfer) => $transfer->status === 'diminta');
                    $adaDiminta = $items->contains(fn($transfer) => $transfer->status === 'diminta');
                    $semuaDiterima = $items->every(fn($transfer) => $transfer->status === 'diterima');
                    $adaDiterima = $items->contains(fn($transfer) => $transfer->status === 'diterima');
                    $sebagianDiterima = $adaDiterima && !$semuaDiterima;

                    $pemohon = optional(optional($first)->permintaanBarang?->peminta)->name;
                    if (!$pemohon && $first?->penerima) {
                        $pemohon = $first->penerima->name;
                    }
                    if (!$pemohon && preg_match('/Pemohon:\s*([^,]+)/i', $catatan, $pemohonMatch)) {
                        $pemohon = trim($pemohonMatch[1]);
                    }
                    $pemohon = $pemohon ?: '—';
                @endphp

                <article class="card expense-history-card">
                    <div class="expense-history-card-header">
                        <div>
                            <h2 class="expense-history-document">
                                <span class="expense-history-document-number">{{ $nomorKeluar }}</span>
                                @if ($isLangsung)
                                    <span class="expense-history-direct">Langsung</span>
                                @endif
                                <span class="expense-history-header-meta">
                                    <span>Pemohon: <strong>{{ $pemohon }}</strong></span>
                                    <span>Petugas: <strong>{{ $petugas }}</strong></span>
                                </span>
                            </h2>
                            <div class="expense-history-card-meta">
                                <span>{{ $waktu ? \Carbon\Carbon::parse($waktu)->translatedFormat('d M Y, H:i') : '—' }}</span>
                                <span aria-hidden="true">·</span>
                                <span>No. permintaan: <strong>{{ $nomorPr ?: '—' }}</strong></span>
                                <span aria-hidden="true">·</span>
                                <span>{{ $items->count() }} item</span>
                            </div>
                        </div>

                        <div class="expense-history-card-actions">
                            @if ($semuaDiminta)
                                <span class="badge"
                                    style="background:#FEF3D6; color:#B45309; padding:4px 10px; font-weight:700; border-radius:999px; font-size:var(--history-helper);">Menunggu
                                    Pengiriman</span>
                            @elseif($adaDiminta)
                                <span class="badge"
                                    style="background:#FEF3D6; color:#B45309; padding:4px 10px; font-weight:700; border-radius:999px; font-size:var(--history-helper);">Sebagian
                                    Menunggu Kirim</span>
                            @elseif($semuaDiterima)
                                <span class="expense-history-badge expense-history-badge-received">Diterima</span>
                            @elseif($sebagianDiterima)
                                <span class="expense-history-badge expense-history-badge-partial">Sebagian diterima</span>
                            @else
                                <span class="expense-history-badge expense-history-badge-dispatched">Dikirim</span>
                            @endif
                            <span class="expense-history-item-count">Tujuan: {{ $tujuan }}</span>
                            <button type="button" class="btn btn-outline expense-history-print"
                                @click="printSuratJalan(@js($nomorKeluar))">
                                <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 6 2 18 2 18 9" />
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                    <rect x="6" y="14" width="12" height="8" />
                                </svg>
                                Cetak Surat Jalan
                            </button>
                            <button type="button" class="btn btn-outline"
                                aria-controls="expense-history-details-{{ $loop->index }}"
                                :aria-expanded="expandedDoc === @js($groupKey)"
                                @click="expandedDoc = (expandedDoc === @js($groupKey) ? null : @js($groupKey))">
                                <span
                                    x-text="expandedDoc === @js($groupKey) ? 'Tutup Detail' : 'Detail Barang'"></span>
                                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2"
                                    :style="expandedDoc === @js($groupKey) ? 'transform:rotate(180deg)' : ''">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="expense-history-details-{{ $loop->index }}" class="expense-history-details"
                        x-show="expandedDoc === @js($groupKey)" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 transform -translate-y-1"
                        x-transition:enter-end="opacity-100 transform translate-y-0">
                        <div class="table-responsive expense-history-detail-table">
                            <table class="data-table" style="width:100%;margin:0;">
                                <thead>
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Kode</th>
                                        <th>Satuan</th>
                                        <th style="text-align:center;">Qty Alokasi</th>
                                        <th style="text-align:center;">Qty Dikirim</th>
                                        <th>Status</th>
                                        <th>Aksi / Info Pengiriman</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td><strong>{{ optional($item->barang)->nama ?? '—' }}</strong></td>
                                            <td class="tabular">{{ optional($item->barang)->kode ?? '—' }}</td>
                                            <td>{{ optional(optional($item->barang)->satuan)->nama ?? '—' }}</td>
                                            <td class="tabular" style="text-align:center;font-weight:700;">
                                                {{ number_format($item->qty_diminta, 0, ',', '.') }}</td>
                                            <td class="tabular"
                                                style="text-align:center;font-weight:700; color:{{ $item->qty_dikirim > 0 ? '#15803D' : '#94A3B8' }};">
                                                {{ $item->qty_dikirim > 0 ? number_format($item->qty_dikirim, 0, ',', '.') : '—' }}
                                            </td>
                                            <td>
                                                @if ($item->status === 'diterima')
                                                    <span class="badge badge-success">Diterima</span>
                                                @elseif($item->status === 'dikirim')
                                                    <span class="badge badge-brand">Dikirim</span>
                                                @elseif($item->status === 'diminta')
                                                    <span class="badge"
                                                        style="background:#FEF3D6; color:#B45309; font-weight:600; padding:2px 8px; border-radius:6px;">Menunggu
                                                        Kirim</span>
                                                @else
                                                    <span class="badge badge-neutral">{{ ucfirst($item->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->status === 'diminta')
                                                    <form
                                                        action="{{ route('store.pengeluaran.pemindahan.kirim', $item) }}"
                                                        method="POST"
                                                        style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                                                        @csrf
                                                        <input type="number" name="qty_dikirim" class="form-control"
                                                            style="width:75px; min-height:30px; padding:2px 6px; font-size:0.8rem; text-align:center; font-weight:700;"
                                                            min="1" max="{{ $item->qty_diminta }}"
                                                            value="{{ $item->qty_diminta }}"
                                                            title="Jumlah aktual dikirim" required>
                                                        <button type="submit" class="btn btn-primary"
                                                            style="min-height:30px; padding:3px 10px; font-size:0.78rem; font-weight:700; border-radius:6px; white-space:nowrap;">
                                                            Kirim Sekarang
                                                        </button>
                                                    </form>
                                                @else
                                                    <div style="font-size:0.78rem; color:#64748B;">
                                                        @if ($item->dikirim_at)
                                                            <div>{{ $item->dikirim_at->translatedFormat('d M Y, H:i') }}
                                                            </div>
                                                        @else
                                                            <div>—</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card expense-history-empty">
                    <strong>Belum ada histori pengeluaran</strong>
                    <p>
                        @if ($search || $tglAwal || $tglAkhir)
                            Tidak ada dokumen yang sesuai dengan pencarian atau rentang tanggal. Coba ubah filter yang
                            dipilih.
                        @else
                            Transaksi yang telah diproses oleh Store akan tampil di sini.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        @if ($paginatedDocs->hasPages())
            <div class="mt-4">
                {{ $paginatedDocs->links() }}
            </div>
        @endif

    </div>
@endsection
