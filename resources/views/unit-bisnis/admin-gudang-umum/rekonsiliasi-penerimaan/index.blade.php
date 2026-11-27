@extends('layouts.app')

@section('title', 'Rekonsiliasi Penerimaan — ' . config('app.name', 'SIGMA'))

@push('styles')
    @vite('resources/css/pages/unit-bisnis/admin-gudang-umum/rekonsiliasi-penerimaan/index.css')
@endpush

@section('content')
    @php
        $workspaceItems = collect($workspaceItems)
            ->map(static function (array $item, int $index): array {
                $item['qty_diperiksa'] = (float) old("items.{$index}.qty_diperiksa", $item['qty_diperiksa']);
                $item['qty_rusak_diperiksa'] = (float) old(
                    "items.{$index}.qty_rusak_diperiksa",
                    $item['qty_rusak_diperiksa'],
                );
                $item['harga_beli'] = (float) old("items.{$index}.harga_beli", $item['harga_beli']);

                return $item;
            })
            ->all();

        $tabs = [
            'menunggu_verifikasi' => ['label' => 'Menunggu Diverifikasi', 'count' => $metrics['pending_count']],
            'sudah_diverifikasi' => ['label' => 'Sudah Diverifikasi', 'count' => $metrics['verified_count']],
            'tindak_lanjut' => ['label' => 'Tindak Lanjut & Klaim', 'count' => $metrics['follow_up_count']],
        ];
    @endphp

    <div class="receipt-dashboard space-y-4 pb-24">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h1 class="mt-1 text-2xl font-extrabold leading-tight tracking-tight text-[#1F2523]">Rekonsiliasi Penerimaan
                </h1>
                <p class="mt-1 max-w-3xl text-xs leading-relaxed text-[#5B6B79] sm:text-sm">
                    Verifikasi ulang penerimaan supplier langsung maupun barang dari permintaan Store berdasarkan catatan
                    Recording GU.
                </p>
            </div>
        </header>

        @if (!$activeGudang)
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <h2 class="font-bold">Gudang aktif belum tersedia</h2>
                <p class="mt-1">Akun ini belum memiliki penugasan gudang yang dapat direkonsiliasi.</p>
            </section>
        @endif

        @if ($errors->any())
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <h2 class="font-bold">Rekonsiliasi belum dapat disimpan</h2>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <nav class="receipt-status-tabs" aria-label="Status rekonsiliasi penerimaan">
            @foreach ($tabs as $tabStatus => $tab)
                <a href="{{ route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', array_merge($filters, ['status' => $tabStatus, 'penerimaan_id' => null, 'sumber_penerimaan' => null])) }}"
                    @if ($filters['status'] === $tabStatus) aria-current="page" @endif
                    class="btn shrink-0 focus-visible:ring-2 focus-visible:ring-[#145C54]/40 {{ $filters['status'] === $tabStatus ? 'btn-primary' : 'btn-outline' }}">
                    @if ($tabStatus === 'menunggu_verifikasi')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 2" />
                        </svg>
                    @elseif($tabStatus === 'sudah_diverifikasi')
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="9" />
                            <path d="m8 12 2.5 2.5L16 9" />
                        </svg>
                    @else
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M12 3 2.8 20h18.4L12 3Z" />
                            <path d="M12 9v5m0 3h.01" />
                        </svg>
                    @endif
                    <span>{{ $tab['label'] }}</span>
                    <span
                        class="receipt-tab-count {{ $filters['status'] === $tabStatus ? 'receipt-tab-count-active' : '' }}">{{ number_format($tab['count']) }}</span>
                </a>
            @endforeach
        </nav>

        <section class="receipt-dashboard-kpis" aria-label="Ringkasan rekonsiliasi penerimaan">
            <article class="receipt-kpi receipt-kpi-pending">
                <span class="receipt-kpi-heading">Menunggu Verifikasi</span>
                <span class="receipt-kpi-value">{{ number_format($metrics['pending_count']) }}<small> Dokumen
                        penerimaan</small></span>
                <span class="receipt-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M8 4h8l2 2v14H6V4h2Z" />
                        <path d="M9 4V2h6v2M9 12h6M9 16h4" />
                    </svg>
                </span>
                <span class="receipt-kpi-tag receipt-kpi-tag-warning">PRIORITAS VERIFIKASI</span>
            </article>

            <article class="receipt-kpi receipt-kpi-verified">
                <span class="receipt-kpi-heading">Terverifikasi Hari Ini</span>
                <span class="receipt-kpi-value">{{ number_format($metrics['verified_today_count']) }}<small>
                        Dokumen</small></span>
                <span class="receipt-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m8 12 2.5 2.5L16 9" />
                    </svg>
                </span>
                <span class="receipt-kpi-tag receipt-kpi-tag-success">HARI INI</span>
            </article>

            <article class="receipt-kpi receipt-kpi-follow-up">
                <span class="receipt-kpi-heading">Tindak Lanjut &amp; Klaim</span>
                <span class="receipt-kpi-value">{{ number_format($metrics['follow_up_count']) }}<small>
                        Dokumen</small></span>
                <span class="receipt-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3 2.8 20h18.4L12 3Z" />
                        <path d="M12 9v5m0 3h.01" />
                    </svg>
                </span>
                <span class="receipt-kpi-tag receipt-kpi-tag-critical">PERLU DITANGANI</span>
            </article>

            <article class="receipt-kpi">
                <span class="receipt-kpi-heading">Nilai Supplier Diverifikasi Bulan Ini</span>
                <span class="receipt-kpi-value">Rp {{ number_format($metrics['month_value'], 0, ',', '.') }}</span>
                <span class="receipt-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 19V5M4 19h17" />
                        <path d="m7 15 4-4 3 2 5-6" />
                    </svg>
                </span>
                <span class="receipt-kpi-tag receipt-kpi-tag-brand">TIDAK TERMASUK TRANSFER</span>
            </article>
        </section>

        <section class="receipt-panel">
            <form id="receipt-filter-form" method="GET"
                action="{{ route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index') }}" class="receipt-user-filters">
                <input type="hidden" name="gudang_id" value="{{ $filters['gudang_id'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <label class="receipt-search">
                    <span class="sr-only">Cari nomor RCV, surat jalan, supplier, atau nomor permintaan</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>
                    <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="100"
                        placeholder="Cari RCV, surat jalan, supplier, PR..." autocomplete="off">
                </label>
                <label class="sr-only" for="receipt-receiver-filter">Petugas penerima</label>
                <select id="receipt-receiver-filter" name="diterima_oleh" class="receipt-filter-select">
                    <option value="">Semua Petugas Penerima</option>
                    @foreach ($receivers as $receiver)
                        <option value="{{ $receiver->getKey() }}" @selected((int) $filters['diterima_oleh'] === (int) $receiver->getKey())>{{ $receiver->name }}
                        </option>
                    @endforeach
                </select>
                <label class="sr-only" for="receipt-date-filter">Tanggal penerimaan</label>
                <input id="receipt-date-filter" type="date" name="tanggal" value="{{ $filters['tanggal'] }}"
                    class="receipt-filter-date">
                <button type="submit" class="receipt-filter-submit" aria-label="Terapkan filter">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M20 7v5h-5M4 17v-5h5" />
                        <path d="M5.5 9a7 7 0 0 1 11.6-2L20 12M4 12l2.9 5a7 7 0 0 0 11.6-2" />
                    </svg>
                </button>
            </form>

            <p class="receipt-search-feedback" data-receipt-search-feedback role="status" aria-live="polite" hidden></p>

            <div id="receipt-results">
                <div class="receipt-table-wrap hidden lg:block">
                    <table class="receipt-table">
                        <thead
                            class="border-b border-[#E4E2DC] bg-[#F7F6F3] text-[11px] font-semibold uppercase tracking-wider text-[#5B6B79]">
                            <tr>
                                <th scope="col" class="px-4 py-2.5">No. Dokumen</th>
                                <th scope="col" class="px-4 py-2.5">Jenis</th>
                                <th scope="col" class="px-4 py-2.5">Sumber</th>
                                <th scope="col" class="px-4 py-2.5">Tanggal Terima</th>
                                <th scope="col" class="px-4 py-2.5 text-center">Rincian</th>
                                <th scope="col" class="px-4 py-2.5">Status</th>
                                <th scope="col" class="px-4 py-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E4E2DC] text-[#1F2523]">
                            @forelse($receipts as $receipt)
                                @php
                                    $isPending = in_array(
                                        $receipt->status_verifikasi,
                                        ['menunggu_verifikasi', 'tercatat'],
                                        true,
                                    );
                                    $isFollowUp = $receipt->status_verifikasi === 'perlu_tindak_lanjut';
                                    $isSelected =
                                        $selectedReceipt &&
                                        $selectedReceipt->sumber_penerimaan === $receipt->sumber_penerimaan &&
                                        (int) $selectedReceipt->id === (int) $receipt->id;
                                    $statusLabel = $isPending
                                        ? 'Menunggu Verifikasi'
                                        : ($isFollowUp
                                            ? 'Tindak Lanjut'
                                            : 'Terverifikasi');
                                    $statusClass = $isPending
                                        ? 'bg-[#B8791A] text-[#1F2523]'
                                        : ($isFollowUp
                                            ? 'bg-[#C1443C] text-white'
                                            : 'bg-[#3F7A52] text-white');
                                @endphp
                                <tr @class([
                                    'border-l-4 border-l-[#145C54] bg-[#E8F1EF]' => $isSelected,
                                    'transition-colors hover:bg-[#F2F0EB]' => !$isSelected,
                                ])>
                                    <td class="px-4 py-3">
                                        <span
                                            class="block font-mono font-bold {{ $isSelected ? 'text-[#145C54]' : 'text-[#1F2523]' }}">{{ $receipt->nomor_rcv }}</span>
                                        @if ($receipt->po_referensi)
                                            <span
                                                class="mt-0.5 block text-[11px] text-[#5B6B79]">{{ $receipt->sumber_penerimaan === 'permintaan' ? 'PR' : 'PO' }}
                                                {{ $receipt->po_referensi }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded px-2 py-1 text-[10px] font-bold {{ $receipt->sumber_penerimaan === 'permintaan' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $receipt->sumber_penerimaan === 'permintaan' ? 'Dari Store' : 'Langsung' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">
                                        {{ $receipt->nama_supplier }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="block">{{ $receipt->received_at->translatedFormat('d M Y') }}</span>
                                        <span
                                            class="mt-0.5 block font-mono text-[11px] text-[#5B6B79]">{{ $receipt->received_at->format('H:i') }}
                                            WIB</span>
                                    </td>
                                    <td class="px-4 py-3 text-center tabular-nums"><span
                                            class="rounded border border-[#E4E2DC] bg-white px-2 py-0.5 font-medium">{{ number_format($receipt->details_count) }}
                                            item</span></td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($isSelected)
                                            <span
                                                class="inline-flex min-h-10 items-center rounded bg-[#145C54] px-3 font-medium text-white">Aktif
                                                Dibuka</span>
                                        @else
                                            <a href="{{ route(
                                                'unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index',
                                                array_merge($filters, [
                                                    'penerimaan_id' => $receipt->id,
                                                    'sumber_penerimaan' => $receipt->sumber_penerimaan,
                                                ]),
                                            ) }}#verification-workspace"
                                                class="
                        inline-flex min-h-10 items-center justify-center gap-1 rounded border
                        border-[#E4E2DC] bg-white px-3 text-xs font-medium text-[#1F2523]
                        transition hover:bg-[#F7F6F3] focus:outline-none focus:ring-2 focus:ring-[#145C54]/30
                      ">
                                                <span>{{ $isPending ? 'Pilih' : 'Lihat hasil' }}</span>
                                                <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20"
                                                    fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path d="m7 4 6 6-6 6" />
                                                </svg>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <p class="font-semibold text-[#1F2523]">Tidak ada penerimaan pada tab ini</p>
                                        <p class="mt-1 text-sm text-[#5B6B79]">Coba ubah filter atau pilih tab status
                                            lainnya.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-3 p-3 lg:hidden">
                    @forelse($receipts as $receipt)
                        @php
                            $isPending = in_array(
                                $receipt->status_verifikasi,
                                ['menunggu_verifikasi', 'tercatat'],
                                true,
                            );
                            $isFollowUp = $receipt->status_verifikasi === 'perlu_tindak_lanjut';
                            $isSelected =
                                $selectedReceipt &&
                                $selectedReceipt->sumber_penerimaan === $receipt->sumber_penerimaan &&
                                (int) $selectedReceipt->id === (int) $receipt->id;
                            $statusLabel = $isPending
                                ? 'Menunggu Verifikasi'
                                : ($isFollowUp
                                    ? 'Tindak Lanjut'
                                    : 'Terverifikasi');
                            $statusClass = $isPending
                                ? 'bg-[#B8791A] text-[#1F2523]'
                                : ($isFollowUp
                                    ? 'bg-[#C1443C] text-white'
                                    : 'bg-[#3F7A52] text-white');
                        @endphp
                        <article @class([
                            'rounded-lg border border-[#145C54] bg-[#E8F1EF] p-3' => $isSelected,
                            'rounded-lg border border-[#E4E2DC] p-3' => !$isSelected,
                        ])>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-[#5B6B79]">
                                        {{ $receipt->sumber_penerimaan === 'permintaan' ? 'No. Surat Jalan' : 'Nomor RCV' }}
                                    </p>
                                    <p class="truncate font-mono font-bold text-[#1F2523]">{{ $receipt->nomor_rcv }}</p>
                                    <p class="mt-1 text-xs text-[#5B6B79]">
                                        {{ $receipt->received_at->translatedFormat('d M Y') }} ·
                                        {{ $receipt->received_at->format('H:i') }} WIB</p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                            <div class="mt-3 flex items-end justify-between gap-3">
                                <div class="min-w-0">
                                    <span
                                        class="inline-flex rounded px-2 py-1 text-[10px] font-bold {{ $receipt->sumber_penerimaan === 'permintaan' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $receipt->sumber_penerimaan === 'permintaan' ? 'Dari Store' : 'Langsung' }}
                                    </span>
                                    <p class="mt-1 truncate text-sm font-medium text-[#1F2523]">
                                        {{ $receipt->nama_supplier }}</p>
                                    <p class="mt-1 text-xs text-[#5B6B79]">{{ number_format($receipt->details_count) }}
                                        item penerimaan</p>
                                </div>
                                @if ($isSelected)
                                    <span
                                        class="inline-flex min-h-11 shrink-0 items-center rounded bg-[#145C54] px-3 text-xs font-bold text-white">Aktif
                                        Dibuka</span>
                                @else
                                    <a href="{{ route(
                                        'unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index',
                                        array_merge($filters, [
                                            'penerimaan_id' => $receipt->id,
                                            'sumber_penerimaan' => $receipt->sumber_penerimaan,
                                        ]),
                                    ) }}#verification-workspace"
                                        class="
                    inline-flex min-h-11 shrink-0 items-center justify-center rounded
                    bg-[#145C54] px-3 text-xs font-bold text-white focus:outline-none focus:ring-2 focus:ring-[#145C54]/30
                  ">
                                        {{ $isPending ? 'Pilih' : 'Lihat' }}
                                    </a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="py-8 text-center">
                            <p class="font-semibold text-[#1F2523]">Tidak ada penerimaan pada tab ini</p>
                            <p class="mt-1 text-sm text-[#5B6B79]">Coba ubah filter pencarian.</p>
                        </div>
                    @endforelse
                </div>

                @if ($receipts->hasPages())
                    <div class="border-t border-[#E4E2DC] px-4 py-3 sm:px-5">
                        {{ $receipts->links() }}
                    </div>
                @endif
            </div>
        </section>

        <div id="receipt-workspace-region">
            @if ($selectedReceipt)
                @php
                    $isPending = in_array(
                        $selectedReceipt->status_verifikasi,
                        ['menunggu_verifikasi', 'tercatat'],
                        true,
                    );
                    $isFollowUp = $selectedReceipt->status_verifikasi === 'perlu_tindak_lanjut';
                    $selectedStatusLabel = $isPending
                        ? 'Menunggu verifikasi'
                        : ($isFollowUp
                            ? 'Perlu tindak lanjut'
                            : 'Terverifikasi');
                    $isRequestReceipt = $selectedReceipt->sumber_penerimaan === 'permintaan';
                @endphp

                <section id="verification-workspace" class="receipt-panel scroll-mt-24 flex flex-col gap-4 p-4 sm:p-5">
                    <div class="flex flex-col gap-3 border-b border-[#E4E2DC] pb-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-[#1F2523]">
                                    {{ $isRequestReceipt ? 'Nomor Surat Jalan:' : 'Nomor RCV:' }} <span
                                        class="font-mono">{{ $selectedReceipt->nomor_rcv }}</span></h2>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $isPending ? 'bg-[#145C54] text-white' : ($isFollowUp ? 'bg-[#C1443C] text-white' : 'bg-[#3F7A52] text-white') }}">
                                    {{ $isPending ? 'Sesi Aktif' : $selectedStatusLabel }}
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#5B6B79]">
                                @if ($isRequestReceipt)
                                    <span><strong class="text-[#1F2523]">Asal gudang:</strong>
                                        {{ $selectedReceipt->nama_supplier }}</span>
                                    @if ($selectedReceipt->po_referensi)
                                        <span aria-hidden="true">•</span>
                                        <span><strong class="text-[#1F2523]">Nomor permintaan:</strong>
                                            {{ $selectedReceipt->po_referensi }}</span>
                                    @endif
                                @else
                                    <span><strong class="text-[#1F2523]">Supplier:</strong>
                                        {{ $selectedReceipt->nama_supplier }}</span>
                                    @if ($selectedReceipt->po_referensi)
                                        <span aria-hidden="true">•</span>
                                        <span><strong class="text-[#1F2523]">Referensi PO:</strong>
                                            {{ $selectedReceipt->po_referensi }}</span>
                                    @endif
                                    @if ($selectedReceipt->nomor_surat_jalan)
                                        <span aria-hidden="true">•</span>
                                        <span><strong class="text-[#1F2523]">Surat Jalan:</strong>
                                            {{ $selectedReceipt->nomor_surat_jalan }}</span>
                                    @endif
                                @endif
                                <span aria-hidden="true">•</span>
                                <span><strong class="text-[#1F2523]">Tanggal:</strong>
                                    {{ $selectedReceipt->tanggal_terima?->translatedFormat('d M Y') }} ·
                                    {{ substr((string) $selectedReceipt->waktu_terima, 0, 5) }} WIB</span>
                                <span aria-hidden="true">•</span>
                                <span><strong class="text-[#1F2523]">Penerima:</strong>
                                    {{ $selectedReceipt->penerima?->name ?? 'Petugas Recording' }}</span>
                            </div>
                            @if (!$isRequestReceipt && !$selectedReceipt->has_surat_jalan && $selectedReceipt->alasan_tanpa_sj)
                                <p class="mt-2 text-xs text-amber-800"><strong>Tanpa surat jalan:</strong>
                                    {{ $selectedReceipt->alasan_tanpa_sj }}</p>
                            @endif
                            @if ($selectedReceipt->catatan)
                                <p class="mt-2 text-xs text-[#5B6B79]"><strong class="text-[#1F2523]">Catatan
                                        Recording:</strong> {{ $selectedReceipt->catatan }}</p>
                            @endif
                            @if ($selectedReceipt->catatan_verifikasi)
                                <div class="mt-2 rounded border border-[#E4E2DC] bg-[#F7F6F3] p-3 text-xs text-[#5B6B79]">
                                    <strong class="text-[#1F2523]">Riwayat verifikasi &amp; tindak lanjut</strong>
                                    <p class="mt-1 whitespace-pre-line">{{ $selectedReceipt->catatan_verifikasi }}</p>
                                </div>
                            @endif
                        </div>
                        @if (!$isPending)
                            <div
                                class="shrink-0 rounded border {{ $isFollowUp ? 'border-[#C1443C]/20 bg-red-50 text-red-900' : 'border-[#3F7A52]/20 bg-emerald-50 text-emerald-900' }} px-3 py-2 text-xs">
                                <span
                                    class="block font-bold">{{ $isFollowUp ? 'Diperlukan tindak lanjut' : 'Disetujui Admin GU' }}</span>
                                <span class="mt-0.5 block">{{ $selectedReceipt->verifier?->name ?? 'Admin GU' }} ·
                                    {{ $selectedReceipt->diverifikasi_at?->translatedFormat('d M Y, H:i') ?? 'Waktu tidak tersedia' }}</span>
                            </div>
                        @endif
                    </div>

                    <script type="application/json" id="receipt-reconciliation-items">@json($workspaceItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
                    @if ($isPending)
                        <form method="POST"
                            action="{{ route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $selectedReceipt->id) }}"
                            x-data="{
                                items: JSON.parse(document.getElementById('receipt-reconciliation-items').textContent),
                                formatNumber(value) {
                                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value) || 0);
                                },
                                get totalNilai() {
                                    return this.items.reduce((total, item) => {
                                        const good = Math.max(0, Number(item.qty_diperiksa) - Number(item.qty_rusak_diperiksa));
                                        return total + good * Number(item.harga_beli);
                                    }, 0);
                                },
                                saving: false
                            }" x-on:submit="saving = true">
                            @csrf
                            <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                            <input type="hidden" name="sumber_penerimaan"
                                value="{{ $selectedReceipt->sumber_penerimaan }}">
                    @endif

                    <div class="overflow-x-auto rounded border border-[#E4E2DC]">
                        <table class="receipt-table receipt-detail-table">
                            <thead
                                class="border-b border-[#E4E2DC] bg-[#F7F6F3] text-[11px] font-semibold uppercase tracking-wider text-[#5B6B79]">
                                <tr>
                                    <th scope="col" class="w-12 px-3 py-2.5 text-center">No</th>
                                    <th scope="col" class="px-3 py-2.5">Kode Barang</th>
                                    <th scope="col" class="min-w-[220px] px-4 py-2.5">Nama Barang</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Qty Dicatat</th>
                                    <th scope="col" class="min-w-[145px] bg-slate-100/60 px-3 py-2.5 text-center">Qty
                                        Fisik</th>
                                    <th scope="col" class="min-w-[145px] bg-slate-100/60 px-3 py-2.5 text-center">Qty
                                        Rusak</th>
                                    @unless ($isRequestReceipt)
                                        <th scope="col" class="min-w-[150px] px-3 py-2.5 text-right">Harga Beli</th>
                                    @endunless
                                    <th scope="col" class="px-3 py-2.5 text-right">Qty Baik</th>
                                    @unless ($isRequestReceipt)
                                        <th scope="col" class="px-4 py-2.5 text-right">Nilai Barang Baik</th>
                                    @endunless
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E4E2DC] text-[#1F2523]">
                                @foreach ($selectedReceipt->details as $index => $detail)
                                    @php($workspaceItem = $workspaceItems[$index])
                                    <tr class="transition-colors hover:bg-[#F2F0EB]">
                                        <td class="px-3 py-3 text-center font-medium tabular-nums text-[#5B6B79]">
                                            {{ $loop->iteration }}</td>
                                        <td class="px-3 py-3 font-mono font-semibold text-[#145C54]">
                                            @if ($isPending)
                                                <input type="hidden" name="items[{{ $index }}][detail_id]"
                                                    value="{{ $detail->id }}">
                                            @endif
                                            {{ $detail->kode_barang ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-[#1F2523]">{{ $detail->nama_barang }}</p>
                                            <p class="mt-0.5 text-[11px] text-[#5B6B79]">
                                                {{ $detail->kategori ?: 'Tidak dikategorikan' }} · {{ $detail->satuan }}
                                            </p>
                                            @if ($detail->catatan)
                                                <p class="mt-1 text-[11px] text-[#5B6B79]">{{ $detail->catatan }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right font-semibold tabular-nums">
                                            {{ number_format((float) $detail->qty_diterima, 3, ',', '.') }}
                                            <span
                                                class="block text-[10px] font-normal text-[#5B6B79]">{{ $detail->satuan }}</span>
                                        </td>
                                        <td class="bg-slate-50 px-3 py-3 text-center">
                                            @if ($isPending)
                                                <input type="number" name="items[{{ $index }}][qty_diperiksa]"
                                                    min="0" step="0.001" required
                                                    value="{{ $workspaceItem['qty_diperiksa'] }}"
                                                    x-model.number="items[{{ $index }}].qty_diperiksa"
                                                    aria-label="Jumlah fisik {{ $detail->nama_barang }}"
                                                    class="min-h-11 w-24 rounded border border-[#145C54] bg-white px-2 text-right font-mono text-xs font-bold tabular-nums text-[#1F2523] outline-none focus:ring-2 focus:ring-[#145C54]/20">
                                            @else
                                                <span
                                                    class="font-semibold tabular-nums">{{ number_format((float) ($detail->qty_diperiksa ?? $detail->qty_diterima), 3, ',', '.') }}</span>
                                            @endif
                                            @error("items.$index.qty_diperiksa")
                                                <span class="mt-1 block text-[11px] text-red-700">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="bg-slate-50 px-3 py-3 text-center">
                                            @if ($isPending)
                                                <input type="number"
                                                    name="items[{{ $index }}][qty_rusak_diperiksa]" min="0"
                                                    step="0.001" required
                                                    value="{{ $workspaceItem['qty_rusak_diperiksa'] }}"
                                                    x-model.number="items[{{ $index }}].qty_rusak_diperiksa"
                                                    aria-label="Jumlah rusak {{ $detail->nama_barang }}"
                                                    class="min-h-11 w-24 rounded border border-[#145C54] bg-white px-2 text-right font-mono text-xs font-bold tabular-nums text-[#1F2523] outline-none focus:ring-2 focus:ring-[#145C54]/20">
                                            @else
                                                <span
                                                    class="font-semibold tabular-nums">{{ number_format((float) ($detail->qty_rusak_diperiksa ?? $detail->qty_rusak), 3, ',', '.') }}</span>
                                            @endif
                                            <span class="ml-1 text-[10px] text-[#5B6B79]">{{ $detail->satuan }}</span>
                                        </td>
                                        @unless ($isRequestReceipt)
                                            <td class="px-3 py-3 text-right">
                                                @if ($isPending)
                                                    <label class="sr-only" for="purchase-price-{{ $detail->id }}">Harga
                                                        beli {{ $detail->nama_barang }}</label>
                                                    <input id="purchase-price-{{ $detail->id }}" type="number"
                                                        name="items[{{ $index }}][harga_beli]" min="0"
                                                        step="0.01" required value="{{ $workspaceItem['harga_beli'] }}"
                                                        x-model.number="items[{{ $index }}].harga_beli"
                                                        class="
                            min-h-11 w-28 rounded border border-[#E4E2DC] bg-white px-2 text-right
                            font-mono text-xs tabular-nums text-[#1F2523] outline-none focus:border-[#145C54]
                            focus:ring-2 focus:ring-[#145C54]/20
                          ">
                                                @else
                                                    <span class="font-mono tabular-nums">Rp
                                                        {{ number_format((float) $detail->harga_beli, 0, ',', '.') }}</span>
                                                @endif
                                            </td>
                                        @endunless
                                        <td class="bg-emerald-50/50 px-3 py-3 text-right font-bold tabular-nums">
                                            @if ($isPending)
                                                <span
                                                    x-text="formatNumber(Math.max(0, Number(items[{{ $index }}].qty_diperiksa) - Number(items[{{ $index }}].qty_rusak_diperiksa)))"></span>
                                            @else
                                                {{ number_format(max(0, (float) ($detail->qty_diperiksa ?? $detail->qty_diterima) - (float) ($detail->qty_rusak_diperiksa ?? $detail->qty_rusak)), 3, ',', '.') }}
                                            @endif
                                        </td>
                                        @unless ($isRequestReceipt)
                                            <td class="px-4 py-3 text-right font-bold tabular-nums">
                                                @if ($isPending)
                                                    <span
                                                        x-text="'Rp ' + formatNumber(Math.max(0, Number(items[{{ $index }}].qty_diperiksa) - Number(items[{{ $index }}].qty_rusak_diperiksa)) * Number(items[{{ $index }}].harga_beli))"></span>
                                                @else
                                                    Rp {{ number_format((float) $detail->subtotal_nilai, 0, ',', '.') }}
                                                @endif
                                            </td>
                                        @endunless
                                    </tr>
                                @endforeach
                            </tbody>
                            @if ($isPending && !$isRequestReceipt)
                                <tfoot class="border-t-2 border-[#E4E2DC] bg-[#F7F6F3] font-semibold text-[#1F2523]">
                                    <tr>
                                        <td colspan="8"
                                            class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-[#5B6B79]">
                                            Total Nilai Barang Baik:</td>
                                        <td class="px-4 py-3 text-right text-sm font-bold tabular-nums text-[#145C54]"
                                            x-text="'Rp ' + formatNumber(totalNilai)"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                    @if ($isPending)
                        <div class="border-t border-[#E4E2DC] bg-[#F7F6F3]/70 p-4">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-[#5B6B79]">Catatan verifikasi <span
                                        class="font-normal">(opsional)</span></span>
                                <textarea name="catatan_verifikasi" rows="2" maxlength="1000"
                                    class="w-full rounded border border-[#E4E2DC] bg-white px-3 py-2 text-sm text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15"
                                    placeholder="Catatan pemeriksaan fisik, selisih kuantitas, atau kondisi barang...">{{ old('catatan_verifikasi', $selectedReceipt->catatan_verifikasi) }}</textarea>
                            </label>
                        </div>

                        <div
                            class="sticky bottom-0 z-30 -mx-4 flex flex-col gap-3 border-t border-[#E4E2DC] bg-white/95 p-3 backdrop-blur sm:-mx-5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-[#145C54] text-xs font-bold text-white"
                                    aria-hidden="true">GU</div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-[#1F2523]">
                                        {{ auth()->user()?->name ?? 'Admin Gudang Umum' }} (Admin GU - Verifikator)</p>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-[#5B6B79]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#3F7A52]" aria-hidden="true"></span>
                                        Sesi Rekonsiliasi Aktif <span aria-hidden="true">•</span>
                                        {{ $selectedReceipt->nomor_rcv }}
                                    </p>
                                </div>
                            </div>
                            <button type="submit" x-bind:disabled="saving"
                                class="
                inline-flex min-h-11 items-center justify-center gap-2 rounded border border-[#0F4B44]
                bg-[#145C54] px-4 text-xs font-bold text-white transition-colors hover:bg-[#0F4B44]
                focus:outline-none focus:ring-2 focus:ring-[#145C54]/30 disabled:cursor-wait disabled:opacity-60
              ">
                                <svg aria-hidden="true" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="m8 12 2.5 2.5L16 9" />
                                </svg>
                                <span x-text="saving ? 'Memverifikasi...' : 'Verifikasi Penerimaan'"></span>
                            </button>
                        </div>
                        </form>
                    @elseif($isFollowUp)
                        <form method="POST"
                            action="{{ route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.follow-up', $selectedReceipt->id) }}"
                            class="border-t border-[#E4E2DC] bg-[#F7F6F3]/70 p-4">
                            @csrf
                            <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">
                            <input type="hidden" name="sumber_penerimaan"
                                value="{{ $selectedReceipt->sumber_penerimaan }}">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-[#1F2523]">Catat tindak lanjut / klaim
                                    penerimaan</span>
                                <span class="mb-2 block text-xs text-[#5B6B79]">Tambahkan hasil konfirmasi atau keputusan
                                    tindak lanjut. Catatan sebelumnya tetap tersimpan.</span>
                                <textarea name="catatan_tindak_lanjut" rows="3" maxlength="1000" required
                                    class="w-full rounded border border-[#E4E2DC] bg-white px-3 py-2 text-sm text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15"
                                    placeholder="Contoh: Klaim supplier diajukan dengan referensi ...">{{ old('catatan_tindak_lanjut') }}</textarea>
                            </label>
                            @error('catatan_tindak_lanjut')
                                <span class="mt-1 block text-xs text-red-700">{{ $message }}</span>
                            @enderror
                            <div class="mt-3 flex justify-end">
                                <button type="submit"
                                    class="inline-flex min-h-11 items-center justify-center rounded bg-[#145C54] px-4 text-xs font-bold text-white transition hover:bg-[#0F4B44] focus:outline-none focus:ring-2 focus:ring-[#145C54]/30">
                                    Simpan Catatan Tindak Lanjut
                                </button>
                            </div>
                        </form>
                    @else
                        <div
                            class="-mx-4 flex flex-col gap-2 border-t border-[#E4E2DC] bg-emerald-50/60 p-4 text-sm sm:-mx-5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <p class="font-semibold text-emerald-900">Rekonsiliasi telah selesai; kuantitas fisik tersimpan
                                pada data penerimaan Recording GU.</p>
                            @unless ($isRequestReceipt)
                                <div class="text-right font-bold tabular-nums text-[#1F2523]">Nilai barang baik: Rp
                                    {{ number_format((float) $selectedReceipt->details->sum('subtotal_nilai'), 0, ',', '.') }}
                                </div>
                            @endunless
                        </div>
                    @endif
                </section>
            @else
                <section class="rounded-lg border border-dashed border-[#C9C6C0] bg-white px-5 py-12 text-center">
                    <h2 class="font-bold text-[#1F2523]">Pilih penerimaan untuk mulai rekonsiliasi</h2>
                    <p class="mt-1 text-sm text-[#5B6B79]">Rincian barang, kuantitas fisik, barang rusak, dan harga beli
                        akan tampil di area ini.</p>
                </section>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('receipt-filter-form');
            const searchInput = form?.querySelector('input[name="q"]');
            const receiverFilter = document.getElementById('receipt-receiver-filter');
            const results = document.getElementById('receipt-results');
            const workspace = document.getElementById('receipt-workspace-region');
            const feedback = document.querySelector('[data-receipt-search-feedback]');

            if (!form || !searchInput || !receiverFilter || !results || !workspace || !feedback) {
                console.error(
                    'Pencarian penerimaan tidak dapat diinisialisasi: elemen filter atau hasil tidak ditemukan.'
                );
                return;
            }

            let activeRequest = null;
            let debounceTimer = null;
            let feedbackTimer = null;

            const refreshReceipts = async () => {
                activeRequest?.abort();
                if (feedbackTimer !== null) {
                    window.clearTimeout(feedbackTimer);
                    feedbackTimer = null;
                }

                const request = new AbortController();
                activeRequest = request;

                const url = new URL(form.action, window.location.href);
                url.search = new URLSearchParams(new FormData(form)).toString();
                url.searchParams.delete('page');

                form.setAttribute('aria-busy', 'true');
                feedback.hidden = false;
                feedback.classList.remove('is-error');
                feedback.textContent = 'Memuat hasil pencarian...';

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        signal: request.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Pencarian penerimaan gagal dimuat. Coba lagi.');
                    }

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const updatedResults = page.querySelector('#receipt-results');
                    const updatedWorkspace = page.querySelector('#receipt-workspace-region');
                    const updatedReceiverFilter = page.querySelector('#receipt-receiver-filter');

                    if (!updatedResults || !updatedWorkspace || !updatedReceiverFilter) {
                        throw new Error(
                            'Hasil pencarian penerimaan tidak ditemukan. Coba muat ulang halaman.');
                    }

                    results.innerHTML = updatedResults.innerHTML;
                    workspace.innerHTML = updatedWorkspace.innerHTML;
                    receiverFilter.innerHTML = updatedReceiverFilter.innerHTML;
                    receiverFilter.value = updatedReceiverFilter.value;
                    window.history.replaceState({}, '', url);

                    if (window.Alpine) {
                        window.Alpine.initTree(workspace);
                    }

                    feedback.hidden = false;
                    feedback.textContent = 'Hasil penerimaan diperbarui.';
                    feedbackTimer = window.setTimeout(() => {
                        feedback.hidden = true;
                        feedback.textContent = '';
                        feedbackTimer = null;
                    }, 1500);
                } catch (error) {
                    if (!request.signal.aborted) {
                        feedback.classList.add('is-error');
                        feedback.textContent = error instanceof Error ?
                            error.message :
                            'Terjadi kesalahan saat mencari penerimaan.';
                    }
                } finally {
                    if (activeRequest === request) {
                        form.removeAttribute('aria-busy');
                        activeRequest = null;
                    }
                }
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                window.clearTimeout(debounceTimer);
                refreshReceipts();
            });

            searchInput.addEventListener('input', () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(refreshReceipts, 350);
            });

            form.addEventListener('change', (event) => {
                if (event.target === receiverFilter || event.target?.name === 'tanggal') {
                    refreshReceipts();
                }
            });
        });
    </script>
@endpush
