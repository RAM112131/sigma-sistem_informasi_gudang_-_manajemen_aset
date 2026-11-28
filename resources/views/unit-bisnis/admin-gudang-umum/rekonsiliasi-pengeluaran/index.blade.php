@extends('layouts.app')

@section('title', 'Rekonsiliasi Pengeluaran — ' . config('app.name', 'SIGMA'))

@section('content')
    @php
        $workspaceItems = collect($workspaceItems)
            ->map(static function (array $item, int $index): array {
                $item['qty_keluar'] = (float) old("items.{$index}.qty_keluar", $item['qty_keluar']);
                $item['qty_kembali'] = (float) old("items.{$index}.qty_kembali", $item['qty_kembali']);

                return $item;
            })
            ->all();
    @endphp

    <div class="gu-dashboard pb-24">
        <header class="gu-page-heading">
            <h1>Rekonsiliasi Pengeluaran &amp; Pengembalian (BPB)</h1>
            <p>Verifikasi bon keluar dan fisik sisa retur Recording GU, alokasi beban pokok (COGS), serta pemotongan
                inventaris.</p>
        </header>

        @if (!$activeGudang)
            <div class="gu-alert" role="alert">
                <strong>Gudang aktif belum tersedia</strong>
                <span>Akun ini belum memiliki penugasan gudang yang dapat direkonsiliasi.</span>
            </div>
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

        <section class="gu-kpi-grid" aria-label="Ringkasan rekonsiliasi">
            <article class="gu-kpi-card gu-kpi-warning">
                <span class="gu-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="1.8">
                        <path d="M8 4h8l2 2v14H6V4h2Z" />
                        <path d="M9 4V2h6v2M9 12h6M9 16h4" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Menunggu Rekonsiliasi</span>
                    <strong class="gu-kpi-value">{{ number_format($metrics['pending_count']) }}</strong>
                    <span class="gu-kpi-foot">Dokumen BPB · Nilai estimasi terbuka: Rp
                        {{ number_format($metrics['pending_value'], 0, ',', '.') }}</span>
                </div>
            </article>

            <article class="gu-kpi-card gu-kpi-success">
                <span class="gu-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m8 12 2.5 2.5L16 9" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Transaksi Terverifikasi Hari Ini</span>
                    <strong class="gu-kpi-value">{{ number_format($metrics['verified_today_count']) }}</strong>
                    <span class="gu-kpi-foot">Belum terverifikasi: {{ number_format($metrics['pending_count']) }}
                        dokumen</span>
                </div>
            </article>

            <article class="gu-kpi-card gu-kpi-primary">
                <span class="gu-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9" />
                        <path
                            d="M15.5 8.5c-.7-.7-1.6-1-3-1-1.7 0-2.8.8-2.8 2s1 1.8 2.8 2.2 2.8 1 2.8 2.3-1.1 2.2-3 2.2c-1.3 0-2.5-.4-3.3-1.2M12.5 5.5v13" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Pengeluaran Bulan Ini</span>
                    <strong class="gu-kpi-value gu-kpi-value-money">Rp
                        {{ number_format($metrics['month_value'], 0, ',', '.') }}</strong>
                    <span class="gu-kpi-foot">Akumulasi pemakaian seluruh pemakai</span>
                </div>
            </article>

            <article class="gu-kpi-card gu-kpi-primary">
                <span class="gu-kpi-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="1.8">
                        <path d="M4 19V5M4 19h17" />
                        <path d="M7 15l4-4 3 2 5-6" />
                    </svg>
                </span>
                <div>
                    <span class="gu-kpi-label">Area Pemakaian Tertinggi Hari Ini</span>
                    <strong class="gu-kpi-value">{{ $metrics['top_department'] ?: 'Belum ada transaksi' }}</strong>
                    <span class="gu-kpi-foot">Pemakaian terverifikasi hari ini</span>
                </div>
            </article>
        </section>

        <section class="gu-card">
            <div class="gu-card-heading">
                <h2>Daftar Antrean BPB dari Recording GU</h2>

                <form method="GET" action="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index') }}"
                    x-data="{}" class="gu-bpb-filter-form">
                    <input type="hidden" name="gudang_id" value="{{ $filters['gudang_id'] }}">
                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                    <label class="gu-bpb-search">
                        <span class="sr-only">Cari nomor BPB, bagian, atau SPK</span>
                        <svg aria-hidden="true" class="gu-bpb-search-icon" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-4-4" />
                        </svg>
                        <input type="search" name="q" value="{{ $filters['q'] }}"
                            placeholder="Cari nomor pengeluaran, divisi, SPK..."
                            class="gu-bpb-filter-control gu-bpb-search-input"
                            x-on:input.debounce.450ms="$el.form.requestSubmit()">
                    </label>
                    <label class="block">
                        <span class="sr-only">Peminta</span>
                        <select name="bagian_peminta" class="gu-bpb-filter-control" x-on:change="$el.form.requestSubmit()">
                            <option value="">Semua Peminta</option>
                            @foreach ($pemintas as $peminta)
                                <option value="{{ $peminta }}" @selected($filters['bagian_peminta'] === $peminta)>{{ $peminta }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="sr-only">Tanggal BPB</span>
                        <input type="date" name="tanggal" value="{{ $filters['tanggal'] }}"
                            class="gu-bpb-filter-control" x-on:change="$el.form.requestSubmit()">
                    </label>
                </form>
            </div>

            <div class="gu-queue-table-wrap hidden lg:block" role="region" aria-label="Daftar antrean bon pengeluaran"
                tabindex="0">
                <table class="gu-queue-table gu-bpb-list-table">
                    <thead>
                        <tr>
                            <th scope="col" class="px-4 py-2.5">Nomor Pengeluaran</th>
                            <th scope="col" class="px-4 py-2.5">Divisi Peminta</th>
                            <th scope="col" class="px-4 py-2.5">Tanggal</th>
                            <th scope="col" class="px-4 py-2.5 text-center">Total Item</th>
                            <th scope="col" class="px-4 py-2.5">Status Verifikasi</th>
                            <th scope="col" class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issues as $issue)
                            @php
                                $isPending = $issue->status_verifikasi === 'menunggu_verifikasi';
                                $isSelected = $selectedIssue && $selectedIssue->is($issue);
                                $statusLabel = match ($issue->status_verifikasi) {
                                    'terverifikasi', 'sesuai' => 'Terverifikasi',
                                    'ditolak' => 'Ditolak',
                                    default => $isSelected ? 'Sedang Diverifikasi' : 'Menunggu Verifikasi',
                                };
                                $statusClass = match ($issue->status_verifikasi) {
                                    'terverifikasi', 'sesuai' => 'bg-[#3F7A52] text-white',
                                    'ditolak' => 'bg-[#C1443C] text-white',
                                    default => $isSelected ? 'bg-[#B8791A] text-[#1F2523]' : 'bg-[#5B6B79] text-white',
                                };
                            @endphp
                            <tr @class([
                                'border-l-4 border-l-[#145C54] bg-[#E8F1EF]' => $isSelected,
                                'transition-colors hover:bg-[#F2F0EB]' => !$isSelected,
                            ])>
                                <td class="px-4 py-3">
                                    <span class="sr-only">Nomor Pengeluaran: </span>
                                    <span
                                        class="block font-mono font-bold {{ $isSelected ? 'text-[#145C54]' : 'text-[#1F2523]' }}">{{ $issue->nomor_bpb }}</span>
                                </td>
                                <td class="px-4 py-3 font-semibold">{{ $issue->bagian_peminta }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="block">{{ \Illuminate\Support\Carbon::parse($issue->tanggal)->translatedFormat('d M Y') }}</span>
                                    <span
                                        class="mt-0.5 block font-mono text-[11px] text-[#5B6B79]">{{ $issue->waktu_keluar }}
                                        WIB</span>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    <span
                                        class="rounded border border-[#E4E2DC] bg-white px-2 py-0.5 font-medium">{{ number_format($issue->details_count) }}
                                        item</span>
                                    @if ($isPending && $issue->draft_details_count > 0)
                                        <span class="mt-1 block text-[10px] font-semibold text-[#145C54]">Draf
                                            tersimpan</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><span
                                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}"><span
                                            class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $statusLabel }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($isSelected)
                                        <span
                                            class="inline-flex items-center gap-1 rounded bg-[#145C54] px-3 py-1.5 font-medium text-white">Aktif
                                            Dibuka
                                            <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20"
                                                fill="none" stroke="currentColor" stroke-width="1.8">
                                                <path d="m5 7 5 5 5-5" />
                                            </svg>
                                        </span>
                                    @else
                                        <a href="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index', array_merge($filters, ['pengeluaran_id' => $issue->getKey()])) }}#verification-workspace"
                                            class="inline-flex min-h-10 items-center justify-center gap-1 rounded border border-[#E4E2DC] bg-white px-3 text-xs font-medium text-[#1F2523] transition hover:bg-[#F7F6F3] focus:outline-none focus:ring-2 focus:ring-[#145C54]/30">
                                            <span>{{ $isPending ? ($issue->draft_details_count > 0 ? 'Buka Draf' : 'Pilih') : 'Lihat hasil' }}</span>
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
                                <td colspan="6" class="gu-table-empty">
                                    <p class="font-semibold text-[#1F2523]">Tidak ada BPB pada filter ini</p>
                                    <p class="mt-1 text-sm text-[#5B6B79]">Coba ubah tanggal, user pencatat, atau kata
                                        pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="grid gap-3 py-3 lg:hidden">
                @forelse($issues as $issue)
                    @php
                        $isPending = $issue->status_verifikasi === 'menunggu_verifikasi';
                        $isSelected = $selectedIssue && $selectedIssue->is($issue);
                        $statusLabel = match ($issue->status_verifikasi) {
                            'terverifikasi', 'sesuai' => 'Terverifikasi',
                            'ditolak' => 'Ditolak',
                            default => $isSelected ? 'Sedang Diverifikasi' : 'Menunggu Verifikasi',
                        };
                        $statusClass = match ($issue->status_verifikasi) {
                            'terverifikasi', 'sesuai' => 'bg-[#3F7A52] text-white',
                            'ditolak' => 'bg-[#C1443C] text-white',
                            default => $isSelected ? 'bg-[#B8791A] text-[#1F2523]' : 'bg-[#5B6B79] text-white',
                        };
                    @endphp
                    <article @class([
                        'rounded-lg border border-[#145C54] bg-[#E8F1EF] p-3' => $isSelected,
                        'rounded-lg border border-[#E4E2DC] p-3' => !$isSelected,
                    ])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#5B6B79]">Nomor
                                    Pengeluaran</p>
                                <p class="truncate font-mono font-bold text-[#1F2523]">{{ $issue->nomor_bpb }}</p>
                                <p class="mt-1 text-xs text-[#5B6B79]">
                                    {{ \Illuminate\Support\Carbon::parse($issue->tanggal)->translatedFormat('d M Y') }} ·
                                    {{ $issue->waktu_keluar }} WIB</p>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-[#1F2523]">{{ $issue->bagian_peminta }}</p>
                                <p class="mt-1 text-xs text-[#5B6B79]">{{ number_format($issue->details_count) }} item BPB
                                </p>
                            </div>
                            @if ($isSelected)
                                <span
                                    class="inline-flex min-h-11 shrink-0 items-center rounded bg-[#145C54] px-3 text-xs font-bold text-white">Aktif
                                    Dibuka</span>
                            @else
                                <a href="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index', array_merge($filters, ['pengeluaran_id' => $issue->getKey()])) }}#verification-workspace"
                                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded bg-[#145C54] px-3 text-xs font-bold text-white focus:outline-none focus:ring-2 focus:ring-[#145C54]/30">
                                    {{ $isPending ? ($issue->draft_details_count > 0 ? 'Buka Draf' : 'Pilih') : 'Lihat' }}
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="py-8 text-center">
                        <p class="font-semibold text-[#1F2523]">Tidak ada BPB pada filter ini</p>
                        <p class="mt-1 text-sm text-[#5B6B79]">Coba ubah filter pencarian.</p>
                    </div>
                @endforelse
            </div>

            @if ($issues->hasPages())
                <div class="mt-4 border-t border-[#E4E2DC] pt-3">
                    {{ $issues->links() }}
                </div>
            @endif
        </section>

        @if ($selectedIssue)
            @php
                $isPending = $selectedIssue->status_verifikasi === 'menunggu_verifikasi';
                $selectedStatusLabel = match ($selectedIssue->status_verifikasi) {
                    'terverifikasi', 'sesuai' => 'Terverifikasi',
                    'ditolak' => 'Ditolak',
                    default => 'Menunggu verifikasi',
                };
            @endphp

            <section id="verification-workspace" class="gu-card scroll-mt-24 flex flex-col gap-4 p-4 sm:p-5">
                <div class="flex flex-col gap-3 border-b border-[#E4E2DC] pb-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-[#1F2523]">Nomor Pengeluaran: <span
                                    class="font-mono">{{ $selectedIssue->nomor_bpb }}</span></h2>
                            <span
                                class="rounded-full bg-[#145C54] px-2.5 py-0.5 text-[11px] font-semibold text-white">{{ $isPending ? 'Sesi Aktif' : $selectedStatusLabel }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[#5B6B79]">
                            <span><strong class="text-[#1F2523]">Divisi:</strong>
                                {{ $selectedIssue->bagian_peminta }}</span>
                            <span aria-hidden="true">•</span>
                            <span><strong class="text-[#1F2523]">Tanggal:</strong>
                                {{ \Illuminate\Support\Carbon::parse($selectedIssue->tanggal)->translatedFormat('d M Y') }}
                                · {{ $selectedIssue->waktu_keluar }} WIB</span>
                            <span aria-hidden="true">•</span>
                            <span><strong class="text-[#1F2523]">Pencatat:</strong>
                                {{ $selectedIssue->pencatat?->name ?? 'Petugas Recording' }}</span>
                        </div>
                        @if ($selectedIssue->catatan)
                            <p class="mt-2 text-xs text-[#5B6B79]"><strong class="text-[#1F2523]">Catatan
                                    Recording:</strong> {{ $selectedIssue->catatan }}</p>
                        @endif
                        @if ($selectedIssue->catatan_verifikasi)
                            <p class="mt-2 text-xs text-[#5B6B79]"><strong class="text-[#1F2523]">Catatan Admin:</strong>
                                {{ $selectedIssue->catatan_verifikasi }}</p>
                        @endif
                    </div>
                    @if ($selectedIssue->status_verifikasi === 'terverifikasi' || $selectedIssue->status_verifikasi === 'sesuai')
                        <div
                            class="shrink-0 rounded border border-[#3F7A52]/20 bg-emerald-50 px-3 py-2 text-xs text-emerald-900">
                            <span class="block font-bold">Disetujui Admin GU</span>
                            <span class="mt-0.5 block">{{ $selectedIssue->verifier?->name ?? 'Admin GU' }} ·
                                {{ $selectedIssue->diverifikasi_at?->translatedFormat('d M Y, H:i') ?? 'Waktu tidak tersedia' }}</span>
                        </div>
                    @endif
                </div>
                <script type="application/json" id="reconciliation-workspace-items">@json($workspaceItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
                <form method="POST"
                    action="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.verify', $selectedIssue->getKey()) }}"
                    x-data="{
                        items: JSON.parse(document.getElementById('reconciliation-workspace-items').textContent),
                        formatQuantity(value) {
                            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Number(value) || 0);
                        },
                        formatMoney(value) {
                            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value) || 0);
                        },
                        get totalCOGS() {
                            return this.items.reduce((total, item) => {
                                const netUsed = Math.max(0, Number(item.qty_keluar) - Number(item.qty_kembali));
                                return total + netUsed * Number(item.hpp);
                            }, 0);
                        },
                        saving: false
                    }" x-on:submit="saving = true">
                    @csrf
                    <input type="hidden" name="gudang_id" value="{{ $activeGudang->getKey() }}">

                    <div class="gu-table-wrap" role="region" aria-label="Rincian rekonsiliasi pengeluaran"
                        tabindex="0">
                        <table class="gu-queue-table gu-bpb-detail-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="w-12 px-3 py-2.5 text-center">No</th>
                                    <th scope="col" class="px-3 py-2.5">Kode Barang</th>
                                    <th scope="col" class="min-w-[220px] px-4 py-2.5">Nama Barang &amp; Kategori</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Qty Keluar</th>
                                    <th scope="col" class="min-w-[160px] bg-slate-100/60 px-4 py-2.5 text-center">Qty
                                        Pengembalian</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Qty Pemakaian</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">Satuan</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">HPP Terkunci</th>
                                    <th scope="col" class="px-4 py-2.5 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E4E2DC] text-[#1F2523]">
                                @foreach ($workspaceItems as $index => $item)
                                    <tr class="transition-colors hover:bg-[#F2F0EB]">
                                        <td class="px-3 py-3 text-center font-medium tabular-nums text-[#5B6B79]">
                                            {{ $loop->iteration }}</td>
                                        <td class="px-3 py-3 font-mono font-semibold text-[#145C54]">
                                            <input type="hidden" name="items[{{ $index }}][detail_id]"
                                                value="{{ $item['id'] }}">
                                            {{ $item['kode_barang'] ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-[#1F2523]">{{ $item['nama_barang'] }}</p>
                                            <p class="mt-0.5 text-[11px] font-medium text-[#145C54]">Kategori:
                                                {{ $item['kategori'] ?: 'Tidak dikategorikan' }}</p>
                                            @if ($item['catatan'])
                                                <p class="mt-1 text-[11px] text-[#5B6B79]">{{ $item['catatan'] }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            @if ($isPending)
                                                <input id="issue-{{ $item['id'] }}" type="number"
                                                    name="items[{{ $index }}][qty_keluar]" min="0"
                                                    step="0.001" required value="{{ $item['qty_keluar'] }}"
                                                    x-model.number="items[{{ $index }}].qty_keluar"
                                                    aria-label="Qty keluar {{ $item['nama_barang'] }}"
                                                    class="min-h-11 w-24 rounded border border-[#145C54] bg-white px-2 text-right font-mono text-xs font-bold tabular-nums text-[#1F2523] outline-none focus:ring-2 focus:ring-[#145C54]/20">
                                            @else
                                                <p class="font-semibold tabular-nums">
                                                    {{ number_format($item['qty_keluar'], 3, ',', '.') }}</p>
                                            @endif
                                            <p class="mt-1 text-[10px] text-[#5B6B79]">
                                                Recording: {{ number_format($item['qty_keluar_dilaporkan'], 3, ',', '.') }}
                                                {{ $item['satuan'] }}
                                            </p>
                                            @error("items.$index.qty_keluar")
                                                <span class="mt-1 block text-[11px] text-red-700">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="bg-slate-50 px-4 py-3 text-center">
                                            @if ($isPending)
                                                <input id="return-{{ $item['id'] }}" type="number"
                                                    name="items[{{ $index }}][qty_kembali]" min="0"
                                                    x-bind:max="items[{{ $index }}].qty_keluar" step="0.001"
                                                    required value="{{ $item['qty_kembali'] }}"
                                                    x-model.number="items[{{ $index }}].qty_kembali"
                                                    aria-label="Qty pengembalian {{ $item['nama_barang'] }}"
                                                    class="min-h-11 w-24 rounded border border-[#145C54] bg-white px-2 text-center font-mono text-xs font-bold tabular-nums text-[#1F2523] outline-none focus:ring-2 focus:ring-[#145C54]/20">
                                            @else
                                                <p class="font-semibold tabular-nums">
                                                    {{ number_format($item['qty_kembali'], 3, ',', '.') }}</p>
                                            @endif
                                            <p class="mt-1 text-[10px] text-[#5B6B79]">
                                                Recording:
                                                {{ number_format($item['qty_kembali_dilaporkan'], 3, ',', '.') }}
                                                {{ $item['satuan'] }}
                                            </p>
                                            @error("items.$index.qty_kembali")
                                                <span class="mt-1 block text-[11px] text-red-700">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td
                                            class="bg-emerald-50/50 px-3 py-3 text-right font-bold tabular-nums text-[#1F2523]">
                                            <span
                                                x-text="formatQuantity(Math.max(0, Number(items[{{ $index }}].qty_keluar) - Number(items[{{ $index }}].qty_kembali)))"></span>
                                        </td>
                                        <td class="px-3 py-3 text-center font-medium text-[#5B6B79]">{{ $item['satuan'] }}
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums text-[#5B6B79]">
                                            <span class="block">Rp {{ number_format($item['hpp'], 0, ',', '.') }}</span>
                                            @unless ($item['hpp_tersedia'])
                                                <span class="mt-1 block text-[10px] font-medium text-amber-800">HPP belum
                                                    tersedia</span>
                                            @endunless
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold tabular-nums text-[#1F2523]"
                                            x-text="'Rp ' + formatMoney(Math.max(0, Number(items[{{ $index }}].qty_keluar) - Number(items[{{ $index }}].qty_kembali)) * Number(items[{{ $index }}].hpp))">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-[#E4E2DC] bg-[#F7F6F3] font-semibold text-[#1F2523]">
                                <tr>
                                    <td colspan="8"
                                        class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-[#5B6B79]">
                                        Total Penjumlahan Harga Pemakaian:</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold tabular-nums text-[#145C54]"
                                        x-text="'Rp ' + formatMoney(totalCOGS)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if ($isPending)
                        <div class="border-t border-[#E4E2DC] bg-[#F7F6F3]/70 p-4">
                            <label class="block">
                                <span class="mb-1 block text-xs font-semibold text-[#5B6B79]">Catatan verifikasi <span
                                        class="font-normal">(opsional)</span></span>
                                <textarea name="catatan_verifikasi" rows="2" maxlength="1000"
                                    class="w-full rounded border border-[#E4E2DC] bg-white px-3 py-2 text-sm text-[#1F2523] outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15"
                                    placeholder="Catatan pemeriksaan fisik atau selisih retur...">{{ old('catatan_verifikasi', $selectedIssue->catatan_verifikasi) }}</textarea>
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
                                        Sesi Rekonsiliasi Aktif <span aria-hidden="true">•</span> Nomor Pengeluaran:
                                        {{ $selectedIssue->nomor_bpb }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button type="submit" formmethod="POST"
                                    formaction="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.draft', $selectedIssue->getKey()) }}"
                                    x-bind:disabled="saving"
                                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded border border-[#E4E2DC] bg-white px-3.5 text-xs font-medium text-[#1F2523] transition hover:bg-[#F7F6F3] focus:outline-none focus:ring-2 focus:ring-[#145C54]/30 disabled:cursor-wait disabled:opacity-60">
                                    <svg aria-hidden="true" class="h-4 w-4 text-[#5B6B79]" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M5 3h12l4 4v14H3V3z" />
                                        <path d="M7 3v6h10V3M7 21v-8h10v8" />
                                    </svg>
                                    <span x-text="saving ? 'Menyimpan...' : 'Simpan Draf'"></span>
                                </button>
                                <button type="submit" x-bind:disabled="saving"
                                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded border border-[#B56A20] bg-[#C97A2B] px-4 text-xs font-bold text-[#1F2523] transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-[#C97A2B]/40 disabled:cursor-wait disabled:opacity-60">
                                    <svg aria-hidden="true" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="m8 12 2.5 2.5L16 9" />
                                    </svg>
                                    <span x-text="saving ? 'Memverifikasi...' : 'Verifikasi'"></span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div
                            class="-mx-4 flex flex-col gap-2 border-t border-[#E4E2DC] bg-emerald-50/60 p-4 text-sm sm:-mx-5 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <p class="font-semibold text-emerald-900">Rekonsiliasi telah dikunci; perubahan retur
                                berikutnya akan membuat BPB kembali menunggu verifikasi.</p>
                            <div class="text-right font-bold tabular-nums text-[#1F2523]">Total COGS: Rp
                                {{ number_format($workspaceItems ? array_sum(array_column($workspaceItems, 'subtotal')) : 0, 0, ',', '.') }}
                            </div>
                        </div>
                    @endif
                </form>
            </section>
        @else
            <section class="gu-card gu-empty-state">
                <span class="gu-empty-state-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                        stroke-linejoin="round" stroke-width="1.8">
                        <path d="M8 4h8l2 2v14H6V4h2Z" />
                        <path d="M9 10h6M9 14h6M9 18h3" />
                    </svg>
                </span>
                <h2 class="font-bold text-[#1F2523]">Pilih BPB untuk mulai rekonsiliasi</h2>
                <p class="mt-1 text-sm text-[#5B6B79]">Rincian barang, retur aktual, HPP, dan pemakaian bersih akan tampil
                    di area ini.</p>
            </section>
        @endif
    </div>

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/admin-gudang-umum/rekonsiliasi-pengeluaran/index.css')
@endpush
@endsection
