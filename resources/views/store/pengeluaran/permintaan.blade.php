@extends('layouts.app')

@section('title', 'Permintaan Barang Unit — SIGMA Store')

@section('content')
    <div x-data="{
        expandedRow: null,
        activePermintaan: { id: null, nomor_pr: '' },
        modalTolakOpen: false,
        openTolak(id, nomor) {
            this.activePermintaan = { id, nomor_pr: nomor };
            this.modalTolakOpen = true;
        }
    }">
        <div class="page-header mb-3">
            <div>
                <h1 class="page-title">Permintaan Barang</h1>
                <p class="page-subtitle">Kelola dan proses pengeluaran stok barang untuk permintaan dari unit bisnis
                    operasional.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-toast alert-toast-danger mb-3" role="alert">
                <strong>Proses belum berhasil.</strong>
                <ul style="margin:4px 0 0 16px;">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Tab Navigation Switcher -->
        <style>
            .expense-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                border-bottom: 2px solid var(--surface-border);
                margin-bottom: 12px;
            }

            .expense-tabs .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                min-height: 36px;
                padding: 4px 12px;
                font-size: 0.85rem;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
        </style>
        <div class="expense-tabs" role="tablist">
            <a href="{{ route('store.pengeluaran.index', ['tab' => 'permintaan']) }}" class="btn btn-primary" role="tab"
                aria-selected="true">
                <span aria-hidden="true">▤</span> Permintaan
                @if ($countMenunggu > 0)
                    <span class="badge badge-warning"
                        style="font-size:.7rem;padding:2px 6px;margin-left:4px;">{{ $countMenunggu }}</span>
                @endif
            </a>
            @if (
                $canCreatePengeluaran ??
                    auth()->user()?->hasRole('Super Admin') ||
                        auth()->user()?->can('store_pengeluaran.create') ||
                        auth()->user()?->can('store.distribusi'))
                <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan']) }}" class="btn btn-outline"
                    role="tab" aria-selected="false">
                    <span aria-hidden="true">➜</span> Form Pengeluaran Barang
                    @if ($countSiapProses > 0)
                        <span class="badge badge-primary"
                            style="font-size:.7rem;padding:2px 6px;margin-left:4px;">{{ $countSiapProses }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('store.pengeluaran.index', ['tab' => 'histori']) }}" class="btn btn-outline" role="tab"
                aria-selected="false">
                <span aria-hidden="true">🕒</span> Histori Pengeluaran
            </a>
        </div>

        <!-- Search & Dropdown Filter Bar -->
        <div class="card" style="margin-bottom:10px !important; padding:10px 14px;">
            <form action="{{ route('store.pengeluaran.index') }}" method="GET"
                style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                <input type="hidden" name="tab" value="permintaan">

                <div style="flex:1; min-width:260px;">
                    <input type="search" name="search" id="searchInput" class="form-control"
                        style="min-height:34px; font-size:0.85rem;"
                        placeholder="Cari no. permintaan, departemen, pemohon, barang" value="{{ $search }}"
                        autocomplete="off" oninput="debounceSearch(this)">
                </div>

                <div style="width:200px;">
                    <select name="status" class="form-control" style="min-height:34px; font-size:0.85rem;"
                        onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        <option value="baru" @selected($status === 'baru')>Menunggu Pengeluaran</option>
                        <option value="diproses" @selected($status === 'diproses')>Diproses / Pengeluaran</option>
                        <option value="selesai" @selected($status === 'selesai')>Selesai</option>
                        <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-outline"
                    style="min-height:34px; padding:4px 12px; font-size:0.82rem;">Cari</button>
                @if ($search || $status)
                    <a href="{{ route('store.pengeluaran.index', ['tab' => 'permintaan']) }}" class="btn btn-outline"
                        style="min-height:34px; padding:4px 12px; font-size:0.82rem; color:var(--text-muted);">Reset</a>
                @endif
            </form>
        </div>

        <!-- Tight & Compact List of Permintaan Cards -->
        <div style="display:flex; flex-direction:column; gap:6px;">
            @forelse($permintaans as $permintaan)
                <div class="card"
                    style="margin-bottom:0 !important; border:1px solid var(--surface-border, #E2E8F0); border-radius:8px; padding:8px 14px; box-shadow:0 1px 2px rgba(0,0,0,0.02); background:#FFFFFF;">
                    <!-- Card Header Row -->
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                        <!-- Left: Meta Info -->
                        <div>
                            <h3 style="font-size:0.93rem; font-weight:700; color:#0F172A; margin:0 0 2px 0;">
                                Rincian Permintaan <span
                                    style="color:var(--brand-primary, #0284C7);">{{ $permintaan->nomor_pr }}</span>
                            </h3>
                            <p style="font-size:0.8rem; color:#64748B; margin:0;">
                                Diajukan oleh <strong
                                    style="color:#334155;">{{ $permintaan->peminta?->name ?? '—' }}</strong>
                                ke unit <strong
                                    style="color:#334155;">{{ $permintaan->gudangPeminta?->nama ?? 'Gudang' }}</strong>
                                pada {{ $permintaan->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>

                        <!-- Right: Status Badge & Accordion Toggle Button -->
                        <div style="display:flex; align-items:center; gap:8px;">
                            <!-- Status Badge -->
                            @if ($permintaan->status === 'baru')
                                <span class="badge"
                                    style="background:#FEF3D6; color:#B45309; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;">
                                    Menunggu Pengeluaran
                                </span>
                            @elseif($permintaan->status === 'diproses')
                                <span class="badge"
                                    style="background:#DCFCE7; color:#15803D; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;">
                                    Diproses / Pengeluaran
                                </span>
                            @elseif($permintaan->status === 'selesai')
                                <span class="badge"
                                    style="background:#E0F2FE; color:#0369A1; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;">
                                    Selesai
                                </span>
                            @else
                                <span class="badge"
                                    style="background:#FEE2E2; color:#B91C1C; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;">
                                    Ditolak
                                </span>
                            @endif

                            <!-- Button Accordion Detail Barang -->
                            <button type="button" class="btn btn-outline"
                                @click="expandedRow = (expandedRow === {{ $permintaan->id }} ? null : {{ $permintaan->id }})"
                                style="min-height:30px; padding:2px 10px; font-size:0.78rem; font-weight:600; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                <span>Detail Barang</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :style="expandedRow === {{ $permintaan->id }} ?
                                        'transform: rotate(180deg); transition: transform 0.2s;' :
                                        'transition: transform 0.2s;'">
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Accordion Item Table & Actions -->
                    <div x-show="expandedRow === {{ $permintaan->id }}" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 transform -translate-y-1"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        style="margin-top:10px; padding-top:10px; border-top:1px solid #F1F5F9;">

                        <div class="table-responsive"
                            style="border:1px solid #E2E8F0; border-radius:6px; overflow:hidden; background:#FFFFFF;">
                            <table class="data-table" style="width:100%; margin:0; border-collapse:collapse;">
                                <thead>
                                    <tr
                                        style="background:#FAF8F5; border-bottom:1px solid #E2E8F0; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.04em; color:#64748B;">
                                        <th style="padding:8px 12px; text-align:left;">NAMA BARANG</th>
                                        <th style="padding:8px 12px; text-align:left;">KATEGORI</th>
                                        <th style="padding:8px 12px; text-align:center;">SATUAN</th>
                                        <th style="padding:8px 12px; text-align:center;">QTY DIMINTA</th>

                                        <th style="padding:8px 12px; text-align:center;">STOK STORE TERSEDIA</th>
                                        <th style="padding:8px 12px; text-align:center;">STATUS ITEM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permintaan->details as $detail)
                                        @php $satuanNama = $detail->satuan?->nama ?? $detail->barang?->satuan?->nama; @endphp
                                        <tr style="border-bottom:1px solid #F1F5F9; font-size:0.83rem;">
                                            <td style="padding:8px 12px;">
                                                <strong
                                                    style="color:#1E293B;">{{ $detail->barang?->nama ?? 'Barang' }}</strong>
                                            </td>
                                            <td style="padding:8px 12px;">
                                                @if ($detail->barang?->kategori?->nama)
                                                    <span
                                                        style="display:inline-block; background:#EFF6FF; color:#1D4ED8; font-size:0.72rem; font-weight:600; padding:2px 9px; border-radius:12px; white-space:nowrap;">
                                                        {{ $detail->barang->kategori->nama }}
                                                    </span>
                                                @else
                                                    <span style="color:#94A3B8; font-size:0.78rem;">—</span>
                                                @endif
                                            </td>
                                            <td style="padding:8px 12px; text-align:center;">
                                                @if ($satuanNama)
                                                    <span
                                                        style="display:inline-block; background:#F1F5F9; color:#475569; font-size:0.72rem; font-weight:600; padding:2px 9px; border-radius:12px; white-space:nowrap;">
                                                        {{ $satuanNama }}
                                                    </span>
                                                @else
                                                    <span style="color:#94A3B8; font-size:0.78rem;">—</span>
                                                @endif
                                            </td>
                                            <td class="tabular"
                                                style="padding:8px 12px; text-align:center; font-weight:700; color:#334155;">
                                                {{ $detail->qty_diminta }}
                                            </td>

                                            <td class="tabular"
                                                style="padding:8px 12px; text-align:center; font-weight:700; color: {{ $detail->stok_store_tersedia < $detail->qty_sisa ? '#DC2626' : '#166534' }};">
                                                {{ $detail->stok_store_tersedia }}
                                            </td>
                                            <td style="padding:8px 12px; text-align:center;">
                                                @if ($detail->qty_diproses >= $detail->qty_diminta)
                                                    <span class="badge"
                                                        style="background:#DCFCE7; color:#15803D; font-size:0.75rem; padding:3px 10px; border-radius:14px; font-weight:600;">Terpenuhi</span>
                                                @elseif($detail->qty_diproses > 0)
                                                    <span class="badge"
                                                        style="background:#FEF3D6; color:#B45309; font-size:0.75rem; padding:3px 10px; border-radius:14px; font-weight:600;">Sebagian
                                                        ({{ $detail->qty_diproses }})
                                                    </span>
                                                @else
                                                    <span class="badge"
                                                        style="background:#FFEDD5; color:#C2410C; font-size:0.75rem; padding:3px 10px; border-radius:14px; font-weight:600;">Belum
                                                        Dikeluarkan</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Bottom Row: Catatan di Kiri & Action Buttons di Kanan -->
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:10px; flex-wrap:wrap;">
                            <div>
                                @if ($permintaan->catatan)
                                    <div
                                        style="padding:6px 10px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; font-size:0.8rem; color:#475569; max-width:480px;">
                                        <strong style="color:#1E293B;">Catatan:</strong> {{ $permintaan->catatan }}
                                    </div>
                                @endif
                                @if ($permintaan->status === 'ditolak' && $permintaan->alasan_penolakan)
                                    <div
                                        style="padding:6px 10px; background:#FEE2E2; border:1px solid #FCA5A5; border-radius:6px; font-size:0.8rem; color:#991B1B; max-width:480px; margin-top:{{ $permintaan->catatan ? '4px' : '0' }};">
                                        <strong>Alasan Penolakan:</strong> {{ $permintaan->alasan_penolakan }}
                                    </div>
                                @endif
                            </div>

                            <div style="display:flex; align-items:center; gap:8px; margin-left:auto;">
                                @if ($permintaan->status === 'baru')
                                    <button type="button" class="btn btn-danger"
                                        @click="openTolak({{ $permintaan->id }}, @js($permintaan->nomor_pr))"
                                        style="min-height:34px; padding:5px 14px; font-size:0.82rem; font-weight:600; border-radius:6px; background:#DC2626; color:#ffffff; border:none; cursor:pointer;">
                                        Tolak Permintaan
                                    </button>
                                @endif

                                @if (in_array($permintaan->status, ['baru', 'diproses'], true) && ($permintaan->has_unallocated_details ?? true))
                                    <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan', 'permintaan_id' => $permintaan->id]) }}"
                                        class="btn"
                                        style="min-height:34px; padding:5px 16px; font-size:0.82rem; font-weight:700; border-radius:6px; background:#047857; color:#ffffff; text-decoration:none; display:inline-flex; align-items:center; gap:4px; border:none; box-shadow:0 1px 2px rgba(0,0,0,0.08);">
                                        ⚡ Proses Pengeluaran (Otomatis Isi)
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card"
                    style="margin-bottom:0 !important; text-align:center; padding:28px; color:var(--text-muted); border-radius:8px;">
                    Tidak ada data permintaan barang yang sesuai dengan filter.
                </div>
            @endforelse

            @if ($permintaans->hasPages())
                <div style="padding:4px 0;">{{ $permintaans->links() }}</div>
            @endif
        </div>

        <!-- Modal Tolak Permintaan -->
        <div class="modal-backdrop" x-show="modalTolakOpen" x-cloak x-transition.opacity>
            <div class="modal-card" style="max-width:440px;">
                <div class="modal-header">
                    <h3 class="modal-title">Tolak Permintaan <span x-text="activePermintaan.nomor_pr"></span></h3>
                    <button type="button" class="modal-close" @click="modalTolakOpen = false">&times;</button>
                </div>
                <form :action="'{{ url('/store/pengeluaran/permintaan') }}/' + activePermintaan.id + '/tolak'"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label" for="alasan-penolakan">Alasan penolakan (wajib)</label>
                        <textarea id="alasan-penolakan" name="alasan_penolakan" class="form-control" required rows="3"
                            maxlength="1000" placeholder="Ketik alasan penolakan..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" @click="modalTolakOpen = false">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Permintaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let searchTimer = null;

        function debounceSearch(input) {
            clearTimeout(searchTimer);
            const val = input.value.trim();
            if (val.length >= 3 || val.length === 0) {
                searchTimer = setTimeout(() => {
                    input.form.submit();
                }, 450);
            }
        }

        if (!window._sigmaPermintaanSearchFocusInstalled) {
            window._sigmaPermintaanSearchFocusInstalled = true;
            document.addEventListener('turbo:load', function() {
                const searchInput = document.getElementById('searchInput');
                if (searchInput && searchInput.value) {
                    searchInput.focus();
                    const len = searchInput.value.length;
                    searchInput.setSelectionRange(len, len);
                }
            });
        }
    </script>
@endsection
