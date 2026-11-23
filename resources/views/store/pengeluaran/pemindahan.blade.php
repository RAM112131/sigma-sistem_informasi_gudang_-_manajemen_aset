@extends('layouts.app')

@section('title', 'Form Pengeluaran Barang — SIGMA Store')

@section('content')
    @include('store.pengeluaran.partials.print_script')

    @if (isset($printDoc) && $printDoc)
        <script>
            (function() {
                const printAfterVisit = function() {
                    setTimeout(function() {
                        window.printSuratJalanDirect(@js($printDoc));
                    }, 350);
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', printAfterVisit, {
                        once: true
                    });
                } else {
                    document.addEventListener('turbo:load', printAfterVisit, {
                        once: true
                    });
                }
            })();
        </script>
    @endif

    <div x-data="{
        mode: @js(($tab ?? '') === 'langsung' || request()->query('tab') === 'langsung' ? 'langsung' : ($activePermintaan ? 'permintaan' : old('mode', 'permintaan'))),
        penerima: @js(old('penerima', '')),
        petugas: @js(old('petugas', auth()->user()->name ?? '')),
        selectedPrId: @js($activePermintaan ? (string) $activePermintaan->id : old('permintaan_id', '')),
        selectedGudangId: @js(old('gudang_tujuan_id', '')),
        unitUsersMap: @js($unitUsersMap->map(fn($users) => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values())->toArray()),
    
        get receiverOptions() {
            return this.unitUsersMap[String(this.selectedGudangId)] ?? [];
        },
    
        onPrChange(e) {
            const val = e.target.value;
            const targetUrl = new URL('{{ route('store.pengeluaran.index') }}', window.location.origin);
            targetUrl.searchParams.set('tab', 'pemindahan');
    
            if (val) {
                targetUrl.searchParams.set('permintaan_id', val);
            } else {
                targetUrl.searchParams.delete('permintaan_id');
            }
    
            window.Turbo.visit(targetUrl.toString());
        }
    }">
        <div class="page-header mb-4">
            <div>
                <h1 class="page-title">Form Pengeluaran Barang</h1>
                <p class="page-subtitle">Proses dokumen pengeluaran dan alokasi stok barang keluar dari Gudang Store.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-toast alert-toast-danger mb-4" role="alert">
                <strong>Proses pengeluaran belum berhasil.</strong>
                <ul style="margin:6px 0 0 20px;">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Navigation Tab Switcher -->
        <style>
            .expense-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                border-bottom: 2px solid var(--surface-border);
                margin-bottom: 20px;
            }

            .expense-tabs .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                min-height: 40px;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
        </style>
        <div class="expense-tabs" role="tablist">
            <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan']) }}" class="btn" role="tab"
                :class="mode === 'permintaan' ? 'btn-primary' : 'btn-outline'" :aria-selected="mode === 'permintaan'"
                @click.prevent="mode = 'permintaan'; const u = new URL(window.location.href); u.searchParams.set('tab', 'pemindahan'); window.history.replaceState({}, '', u.toString());">
                <span aria-hidden="true">▤</span> Berdasarkan Permintaan
            </a>
            <a href="{{ route('store.pengeluaran.index', ['tab' => 'langsung']) }}" class="btn" role="tab"
                :class="mode === 'langsung' ? 'btn-primary' : 'btn-outline'" :aria-selected="mode === 'langsung'"
                @click.prevent="mode = 'langsung'; const u = new URL(window.location.href); u.searchParams.set('tab', 'langsung'); window.history.replaceState({}, '', u.toString());">
                <span aria-hidden="true">➜</span> Pemindahan tanpa Permintaan
            </a>
            <a href="{{ route('store.pengeluaran.index', ['tab' => 'histori']) }}" class="btn btn-outline" role="tab"
                aria-selected="false">
                <span aria-hidden="true">🕒</span> Histori Pengeluaran
            </a>
        </div>

        <!-- FORM MODE 1: BERDASARKAN PERMINTAAN -->
        <div x-show="mode === 'permintaan'" x-cloak>
            <div class="card mb-4" style="padding:22px;">
                @if ($activePermintaan)
                    <form action="{{ route('store.pengeluaran.permintaan.proses', $activePermintaan) }}" method="POST">
                        @csrf
                        <input type="hidden" name="mode" value="permintaan">
                    @else
                        <form action="{{ route('store.pengeluaran.index') }}" method="GET">
                @endif

                <div style="display:grid; grid-template-columns:1fr 1.5fr; gap:16px; margin-bottom:20px;">
                    <!-- Nomor Keluar (auto-generated by server, left side) -->
                    <div>
                        <label class="form-label" for="nomor-keluar-input">No. Dokumen Keluar <span
                                style="color:#DC2626;">*</span></label>
                        <input id="nomor-keluar-input" type="text" name="nomor_keluar" class="form-control"
                            value="{{ $nomorKeluar }}"
                            style="font-family:monospace; font-size:0.84rem; font-weight:700; letter-spacing:0.02em; background:#F1F5F9; color:#0F172A; cursor:default;"
                            readonly tabindex="-1">
                        <span style="font-size:0.78rem; color:var(--text-muted); display:block; margin-top:4px;">Nomor
                            dokumen keluar digenerate otomatis oleh sistem.</span>
                    </div>

                    <!-- Referensi Permintaan (right side) -->
                    <div>
                        <label class="form-label" for="referensi-pr-select">Referensi Permintaan <span
                                style="color:#DC2626;">*</span></label>
                        <select id="referensi-pr-select" name="permintaan_id" class="form-control" x-model="selectedPrId"
                            @change="onPrChange($event)" required>
                            <option value="">Pilih permintaan yang akan diproses</option>
                            @foreach ($approvedPermintaans as $approvedPr)
                                <option value="{{ $approvedPr->id }}" @selected($activePermintaan && $activePermintaan->id === $approvedPr->id)>
                                    {{ $approvedPr->nomor_pr }} — {{ $approvedPr->gudangPeminta?->nama }} (Pemohon:
                                    {{ $approvedPr->peminta?->name ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                        <span style="font-size:0.78rem; color:var(--text-muted); display:block; margin-top:4px;">
                            Pilih permintaan untuk memuat detail informasi dan alokasi stok.
                        </span>
                    </div>
                </div>

                @if ($activePermintaan)
                    <!-- Ringkasan Meta Permintaan Otomatis -->
                    <div
                        style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:14px 18px; margin-bottom:20px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                            <div>
                                <span
                                    style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:700; display:block;">No.
                                    Permintaan</span>
                                <strong
                                    style="font-size:1.05rem; color:var(--brand-primary);">{{ $activePermintaan->nomor_pr }}</strong>
                            </div>
                            <div>
                                <span
                                    style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:700; display:block;">Unit
                                    Peminta</span>
                                <strong style="color:#1E293B;">{{ $activePermintaan->gudangPeminta?->nama }}</strong>
                            </div>
                            <div>
                                <span
                                    style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:700; display:block;">Pemohon
                                    / Penerima</span>
                                <strong style="color:#1E293B;">{{ $activePermintaan->peminta?->name ?? '—' }}</strong>
                            </div>
                            <div>
                                <span
                                    style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--text-muted); font-weight:700; display:block;">Petugas
                                    Store</span>
                                <strong style="color:#1E293B;">{{ auth()->user()->name }}</strong>
                            </div>
                        </div>
                        @if ($activePermintaan->catatan)
                            <div
                                style="margin-top:10px; padding-top:10px; border-top:1px dashed #CBD5E1; font-size:0.83rem; color:#475569;">
                                <strong>Catatan Permintaan:</strong> {{ $activePermintaan->catatan }}
                            </div>
                        @endif
                    </div>

                    <!-- Tabel Item Barang yang Dikeluarkan (Otomatis Terisi) -->
                    <div style="border-top:1px solid var(--surface-border); padding-top:16px;">
                        <h3 style="font-size:1rem; font-weight:700; margin-bottom:12px; color:#1E293B;">
                            Rincian Barang yang Dikeluarkan
                        </h3>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr style="background:#F8FAFC;">
                                        <th>Nama Barang</th>
                                        <th style="text-align:center;">Kategori</th>
                                        <th style="text-align:center;">Satuan</th>
                                        <th style="text-align:center;">Qty Diminta</th>

                                        <th style="text-align:center;">Stok Tersedia</th>
                                        <th style="text-align:center; width:160px;">Jumlah Dikeluarkan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allocationDetails as $detail)
                                        @php $satuanNama = $detail->satuan?->nama ?? $detail->barang?->satuan?->nama; @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $detail->barang?->nama ?? 'Barang' }}</strong>
                                            </td>
                                            <td style="text-align:center;">
                                                @if ($detail->barang?->kategori?->nama)
                                                    <span
                                                        style="display:inline-block; background:#EFF6FF; color:#1D4ED8; font-size:0.72rem; font-weight:600; padding:2px 9px; border-radius:12px; white-space:nowrap;">
                                                        {{ $detail->barang->kategori->nama }}
                                                    </span>
                                                @else
                                                    <span style="color:#94A3B8; font-size:0.78rem;">—</span>
                                                @endif
                                            </td>
                                            <td style="text-align:center;">
                                                @if ($satuanNama)
                                                    <span
                                                        style="display:inline-block; background:#F1F5F9; color:#475569; font-size:0.72rem; font-weight:600; padding:2px 9px; border-radius:12px; white-space:nowrap;">
                                                        {{ $satuanNama }}
                                                    </span>
                                                @else
                                                    <span style="color:#94A3B8; font-size:0.78rem;">—</span>
                                                @endif
                                            </td>
                                            <td class="tabular" style="text-align:center;">
                                                <strong>{{ $detail->qty_diminta }}</strong>
                                            </td>

                                            <td class="tabular"
                                                style="text-align:center; font-weight:700; color: {{ $detail->stok_store_tersedia < $detail->qty_sisa ? '#DC2626' : '#166534' }};">
                                                {{ $detail->stok_store_tersedia }}
                                            </td>
                                            <td style="text-align:center;">
                                                <input type="number" name="qty_dialokasikan[{{ $detail->id }}]"
                                                    class="form-control tabular qty-keluar-input" min="0"
                                                    max="{{ min($detail->qty_sisa, $detail->stok_store_tersedia) }}"
                                                    data-nama="{{ $detail->barang?->nama ?? 'Barang' }}"
                                                    data-max="{{ min($detail->qty_sisa, $detail->stok_store_tersedia) }}"
                                                    value="{{ min($detail->qty_sisa, $detail->stok_store_tersedia) }}"
                                                    style="width:120px; font-weight:700; margin:0 auto; text-align:center;"
                                                    required>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7"
                                                style="text-align:center; padding:24px; color:var(--text-muted);">Tidak ada
                                                rincian barang.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Action Bar -->
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:14px; border-top:1px solid var(--surface-border);">
                        <span style="font-size:0.82rem; color:var(--text-muted);">Semua meta informasi terisi otomatis dari
                            data permintaan.</span>
                        <div style="display:flex; gap:10px;">
                            <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan']) }}"
                                class="btn btn-outline">Reset</a>
                            <button type="submit" class="btn btn-primary" style="font-weight:700;"
                                @disabled($allocationDetails->isEmpty())>
                                Kirim Barang & Cetak Surat Jalan
                            </button>
                        </div>
                    </div>
                @endif
                </form>
            </div>
        </div>

        <!-- FORM MODE 2: LANGSUNG (TANPA PERMINTAAN / KEBUTUHAN MENDADAK) -->
        <div x-show="mode === 'langsung'" x-cloak>
            <div class="card mb-4" style="padding:22px;" x-data="{
                nomorKeluar: @js($nomorKeluar),
                nomorReqLangsung: @js($nomorReqLangsung),
                selectedGudangId: @js(old('gudang_tujuan_id', '')),
                pemohon: @js(old('pemohon', '')),
                petugas: @js(old('petugas', auth()->user()->name ?? '')),
                items: [
                    { id: null, query: '', code: '', name: '', kategori: '—', satuan: '—', stok_tersedia: 0, qty: 1, catatan: '', results: [], selected: false, loading: false, hasSearched: false }
                ],
                addItem() {
                    this.items.push({ id: null, query: '', code: '', name: '', kategori: '—', satuan: '—', stok_tersedia: 0, qty: 1, catatan: '', results: [], selected: false, loading: false, hasSearched: false });
                },
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                async searchBarang(index) {
                    const item = this.items[index];
                    const q = item.query.trim();
                    if (q.length < 1) {
                        item.results = [];
                        item.hasSearched = false;
                        return;
                    }
                    item.loading = true;
                    item.hasSearched = true;
                    try {
                        const res = await fetch('{{ route('store.pengeluaran.barang-search') }}?q=' + encodeURIComponent(q), {
                            headers: { Accept: 'application/json' }
                        });
                        if (!res.ok) throw new Error('Search failed');
                        const payload = await res.json();
                        item.results = payload.data || [];
                    } catch (e) {
                        item.results = [];
                    } finally {
                        item.loading = false;
                    }
                },
                selectBarang(index, b) {
                    // allow 0 stock selection
                    const item = this.items[index];
                    item.id = b.id;
                    item.code = b.kode;
                    item.name = b.nama;
                    item.kategori = b.kategori || '—';
                    item.satuan = b.satuan || '—';
                    item.stok_tersedia = b.stok_tersedia;
                    item.qty = b.stok_tersedia > 0 ? 1 : 0;
                    item.query = b.nama;
                    item.selected = true;
                    item.results = [];
                    item.hasSearched = false;
                },
                resetBarang(index) {
                    const item = this.items[index];
                    item.id = null;
                    item.code = '';
                    item.name = '';
                    item.kategori = '—';
                    item.satuan = '—';
                    item.stok_tersedia = 0;
                    item.qty = 1;
                    item.query = '';
                    item.selected = false;
                    item.results = [];
                    item.hasSearched = false;
                },
                highlight(text, query) {
                    return window.highlightSearchText ? window.highlightSearchText(text, query) : text;
                },
                get canSubmit() {
                    if (!this.pemohon.trim() || !this.petugas.trim()) return false;
                    const validItems = this.items.filter(i => i.id && i.qty > 0 && i.qty <= i.stok_tersedia);
                    return validItems.length === this.items.length && this.items.length > 0;
                }
            }">

                <form action="{{ route('store.pengeluaran.pemindahan.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="mode" value="langsung">

                    <!-- Document Metadata Header (Rasio Lebar Custom di Desktop) -->
                    <style>
                        .meta-row-desktop {
                            display: flex;
                            flex-wrap: wrap;
                            gap: 12px;
                            align-items: flex-start;
                            margin-bottom: 20px;
                        }

                        .meta-col-wide-1 {
                            flex: 1.35 1 0;
                            min-width: 0;
                        }

                        .meta-col-wide-2 {
                            flex: 1.5 1 0;
                            min-width: 0;
                        }

                        .meta-col-short {
                            flex: 0.9 1 0;
                            min-width: 0;
                        }

                        @media (max-width: 991px) {

                            .meta-col-wide-1,
                            .meta-col-wide-2,
                            .meta-col-short {
                                flex: 1 1 calc(50% - 12px);
                                min-width: 180px;
                            }
                        }

                        @media (max-width: 576px) {

                            .meta-col-wide-1,
                            .meta-col-wide-2,
                            .meta-col-short {
                                flex: 1 1 100%;
                            }
                        }
                    </style>

                    <div class="meta-row-desktop">
                        <!-- No. Dokumen Keluar (Lebar) -->
                        <div class="meta-col-wide-1">
                            <label class="form-label" for="nomor-keluar-langsung"
                                style="white-space:nowrap; font-size:0.8rem; font-weight:700;">No. Dokumen Keluar <span
                                    style="color:#DC2626;">*</span></label>
                            <input id="nomor-keluar-langsung" type="text" name="nomor_keluar" class="form-control"
                                :value="nomorKeluar" :title="nomorKeluar"
                                style="font-weight:700; font-size:0.78rem; letter-spacing:-0.01em; background:#F1F5F9; color:#0F172A; padding:7px 9px; text-overflow:ellipsis; white-space:nowrap; overflow:hidden;"
                                readonly tabindex="-1">
                        </div>

                        <!-- No. Permintaan Langsung (Lebar) -->
                        <div class="meta-col-wide-2">
                            <label class="form-label" for="nomor-req-langsung"
                                style="white-space:nowrap; font-size:0.8rem; font-weight:700;">No. Permintaan Langsung
                                <span style="color:#DC2626;">*</span></label>
                            <input id="nomor-req-langsung" type="text" name="nomor_req_langsung" class="form-control"
                                :value="nomorReqLangsung" :title="nomorReqLangsung"
                                style="font-weight:700; font-size:0.78rem; letter-spacing:-0.01em; background:#F1F5F9; color:#0284C7; padding:7px 9px; text-overflow:ellipsis; white-space:nowrap; overflow:hidden;"
                                readonly tabindex="-1">
                        </div>

                        <!-- Unit Peminta (Pendek) -->
                        <div class="meta-col-short">
                            <label class="form-label" for="unit-peminta-input"
                                style="white-space:nowrap; font-size:0.8rem; font-weight:700; display:flex; align-items:center; margin-bottom: 0.5rem; position: relative;"
                                x-data="{ tooltipOpen: false }" @click.outside="tooltipOpen = false">
                                Unit Peminta <span
                                    style="font-weight:400; font-size:0.75rem; color:#64748B; margin-left:3px;">(opsional)</span>
                                <span @click.prevent.stop="tooltipOpen = !tooltipOpen"
                                    style="display:inline-flex; align-items:center; cursor:pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        style="margin-left:4px; color:#64748B;">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </span>

                                <!-- Tooltip Dropdown -->
                                <div x-show="tooltipOpen" x-transition.opacity x-cloak
                                    style="position: absolute; bottom: 100%; left: 0; margin-bottom: 6px; width: max-content; max-width: 250px; background-color: #1E293B; color: #F8FAFC; font-size: 0.75rem; padding: 8px 12px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 50; white-space: normal; line-height: 1.4; font-weight: 500;">
                                    Tidak perlu diisi jika barang yang diminta bersifat perorangan.
                                    <div
                                        style="position: absolute; top: 100%; left: 60px; margin-left: -6px; border-width: 6px; border-style: solid; border-color: #1E293B transparent transparent transparent;">
                                    </div>
                                </div>
                            </label>
                            <input id="unit-peminta-input" type="text" name="unit_peminta_teks" class="form-control"
                                list="gudang-list" placeholder="Ketik nama unit (opsional)..."
                                style="font-size:0.8rem; padding:7px 9px;" value="{{ old('unit_peminta_teks') }}">
                            <datalist id="gudang-list">
                                @foreach ($unitGudangs as $unitGudang)
                                    <option value="{{ $unitGudang->nama }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <!-- Pemohon (Pendek) -->
                        <div class="meta-col-short">
                            <label class="form-label" for="pemohon-input"
                                style="white-space:nowrap; font-size:0.8rem; font-weight:700;">Pemohon <span
                                    style="color:#DC2626;">*</span></label>
                            <input id="pemohon-input" type="text" name="pemohon" class="form-control"
                                x-model="pemohon" placeholder="Nama pemohon..." required
                                style="font-size:0.8rem; padding:7px 9px;">
                        </div>

                        <!-- Petugas Store (Pendek) -->
                        <div class="meta-col-short">
                            <label class="form-label" for="petugas-input"
                                style="white-space:nowrap; font-size:0.8rem; font-weight:700;">Petugas Store <span
                                    style="color:#DC2626;">*</span></label>
                            <input id="petugas-input" type="text" name="petugas" class="form-control"
                                x-model="petugas" placeholder="Nama petugas..." required
                                style="font-size:0.8rem; padding:7px 9px;">
                        </div>
                    </div>

                    <!-- Tabel Barang (Desain Sesuai Gambar UI) -->
                    <div style="border-top:1px solid var(--surface-border); padding-top:16px;">
                        <h3 style="font-size:1rem; font-weight:700; margin-bottom:12px; color:#1E293B;">
                            Daftar Barang yang Dikeluarkan
                        </h3>

                        <div class="table-responsive" style="margin-bottom:12px; overflow-x:visible;">
                            <table class="data-table" style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#F8FAFC; border-bottom:1px solid #E2E8F0;">
                                        <th style="padding:10px 12px; text-align:left; min-width:240px;">Barang</th>
                                        <th style="padding:10px 12px; text-align:center; width:120px;">Kategori</th>
                                        <th style="padding:10px 12px; text-align:center; width:100px;">Satuan</th>
                                        <th style="padding:10px 12px; text-align:center; width:140px;">Stok Tersedia</th>
                                        <th style="padding:10px 12px; text-align:center; width:140px;">Qty Dikeluarkan</th>
                                        <th style="padding:10px 12px; text-align:center; width:45px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <!-- Barang Input & Autocomplete Dropdown -->
                                            <td style="padding:10px 12px; position:relative;">
                                                <template x-if="!item.selected">
                                                    <div>
                                                        <input type="text" class="form-control"
                                                            placeholder="Cari nama/kode barang" x-model="item.query"
                                                            @input.debounce.200ms="searchBarang(index)"
                                                            autocomplete="off">
                                                        <div x-show="item.hasSearched && !item.selected" x-cloak
                                                            style="position:absolute; z-index:50; top:100%; left:12px; right:12px; max-height:240px; overflow-y:auto; background:#FFFFFF; border:1px solid #CBD5E1; border-radius:6px; box-shadow:0 8px 20px rgba(0,0,0,0.15);">
                                                            <div x-show="item.loading"
                                                                style="padding:12px; font-size:0.82rem; color:#64748B; text-align:center;">
                                                                Sedang mencari barang...
                                                            </div>
                                                            <template
                                                                x-if="!item.loading && item.results && item.results.length > 0">
                                                                <div>
                                                                    <template x-for="b in item.results"
                                                                        :key="b.id">
                                                                        <button type="button"
                                                                            @click="selectBarang(index, b)"
                                                                            style="display:block; width:100%; padding:9px 12px; text-align:left; border:0; border-bottom:1px solid #F1F5F9; background:transparent; cursor:pointer;">
                                                                            <div style="font-weight:700; color:#0F172A;"
                                                                                x-html="highlight(b.nama, item.query)">
                                                                            </div>
                                                                            <div style="font-size:0.78rem; color:#64748B;">
                                                                                Kode: <span
                                                                                    x-html="highlight(b.kode, item.query)"></span>
                                                                                <template x-if="b.kategori">
                                                                                    <span> · <span style="color:#1D4ED8;"
                                                                                            x-html="highlight(b.kategori, item.query)"></span></span>
                                                                                </template>
                                                                                · Stok: <strong
                                                                                    :style="b.stok_tersedia < 1 ?
                                                                                        'color:#DC2626' :
                                                                                        'color:#166534'"
                                                                                    x-text="b.stok_tersedia"></strong>
                                                                                <span x-text="b.satuan || ''"></span>
                                                                            </div>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <div x-show="!item.loading && item.results && item.results.length === 0"
                                                                style="padding:12px; font-size:0.82rem; color:#DC2626; text-align:center;">
                                                                Tidak ada barang yang cocok dengan kata kunci pencarian.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="item.selected">
                                                    <div
                                                        style="display:flex; align-items:center; justify-content:space-between; background:#F8FAFC; padding:6px 10px; border:1px solid #E2E8F0; border-radius:6px;">
                                                        <div>
                                                            <strong style="color:#0F172A;" x-text="item.name"></strong>
                                                            <div style="font-size:0.75rem; color:#64748B;">
                                                                Kode: <span x-text="item.code"></span>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline"
                                                            style="padding:2px 8px; font-size:0.75rem;"
                                                            @click="resetBarang(index)">Ganti</button>
                                                    </div>
                                                </template>
                                                <input type="hidden" :name="'items[' + index + '][barang_id]'"
                                                    :value="item.id">
                                            </td>

                                            <!-- Kategori Column -->
                                            <td style="padding:10px 12px; text-align:center; vertical-align:middle;">
                                                <template x-if="item.kategori && item.kategori !== '—'">
                                                    <span
                                                        style="display:inline-block; background:#EFF6FF; color:#1D4ED8; font-size:0.75rem; font-weight:600; padding:3px 10px; border-radius:12px; white-space:nowrap;"
                                                        x-text="item.kategori"></span>
                                                </template>
                                                <template x-if="!item.kategori || item.kategori === '—'">
                                                    <span style="color:#94A3B8; font-size:0.85rem;">—</span>
                                                </template>
                                            </td>

                                            <!-- Satuan Column -->
                                            <td style="padding:10px 12px; text-align:center; vertical-align:middle;">
                                                <template x-if="item.satuan && item.satuan !== '—'">
                                                    <span
                                                        style="display:inline-block; background:#F1F5F9; color:#475569; font-size:0.75rem; font-weight:600; padding:3px 10px; border-radius:12px; white-space:nowrap;"
                                                        x-text="item.satuan"></span>
                                                </template>
                                                <template x-if="!item.satuan || item.satuan === '—'">
                                                    <span style="color:#94A3B8; font-size:0.85rem;">—</span>
                                                </template>
                                            </td>

                                            <!-- Stok Tersedia Column -->
                                            <td class="tabular"
                                                style="padding:10px 12px; text-align:center; vertical-align:middle;">
                                                <template x-if="item.selected">
                                                    <strong
                                                        :style="item.stok_tersedia < 1 ? 'color:#DC2626' : 'color:#166534'"
                                                        x-text="item.stok_tersedia"></strong>
                                                </template>
                                                <template x-if="!item.selected">
                                                    <span style="color:#94A3B8; font-size:0.85rem;">—</span>
                                                </template>
                                            </td>

                                            <!-- Qty Dikeluarkan Column -->
                                            <td style="padding:10px 12px; text-align:center; vertical-align:middle;">
                                                <input type="number" :name="'items[' + index + '][qty]'"
                                                    class="form-control tabular" min="1"
                                                    :max="item.stok_tersedia || 999999" x-model.number="item.qty"
                                                    style="width:110px; margin:0 auto; text-align:center; font-weight:700;"
                                                    placeholder="0" required>
                                            </td>

                                            <!-- Remove Row Action Button -->
                                            <td style="padding:10px 12px; text-align:center; vertical-align:middle;">
                                                <button type="button" @click="removeItem(index)"
                                                    style="border:0; background:transparent; color:#94A3B8; font-size:1.2rem; cursor:pointer; padding:4px 8px; line-height:1;"
                                                    title="Hapus baris" :disabled="items.length <= 1">
                                                    ✕
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- + Tambah Barang Button -->
                        <div style="margin-bottom:20px;">
                            <button type="button" @click="addItem()"
                                style="width:100%; border:2px dashed #CBD5E1; background:#F8FAFC; color:#0284C7; font-weight:700; padding:12px; border-radius:8px; cursor:pointer; transition:all 0.2s ease-in-out;"
                                onmouseover="this.style.background='#F0F9FF'; this.style.borderColor='#0284C7';"
                                onmouseout="this.style.background='#F8FAFC'; this.style.borderColor='#CBD5E1';">
                                + Tambah Barang
                            </button>
                        </div>
                    </div>

                    <!-- Footer Action Bar -->
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px solid var(--surface-border);">
                        <span style="font-size:0.82rem; color:var(--text-muted);">Lengkapi semua field wajib (*) sebelum
                            menyimpan pengeluaran.</span>
                        <div style="display:flex; gap:10px;">
                            <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan']) }}"
                                class="btn btn-outline">Reset</a>
                            <button type="submit" class="btn btn-primary" style="font-weight:700;"
                                :disabled="!canSubmit">Kirim Barang & Cetak Surat Jalan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.highlightSearchText = function(text, query) {
            if (!text) return '';
            if (!query || String(query).trim() === '') return text;
            const q = String(query).trim();
            const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp('(' + escaped + ')', 'gi');
            return String(text).replace(regex,
                '<mark style="background:#FEF08A; color:#854D0E; font-weight:800; padding:1px 3px; border-radius:3px; font-style:normal;">$1</mark>'
            );
        };

        window._sigmaQtyValidationInputs ??= new WeakSet();
        if (!window._sigmaQtyValidationInstalled) {
            window._sigmaQtyValidationInstalled = true;
            document.addEventListener('turbo:load', function() {
                document.querySelectorAll('.qty-keluar-input').forEach(function(input) {
                    if (window._sigmaQtyValidationInputs.has(input)) {
                        return;
                    }

                    window._sigmaQtyValidationInputs.add(input);
                    input.addEventListener('input', function() {
                        const val = parseInt(this.value, 10);
                        const max = parseInt(this.dataset.max, 10);
                        const nama = this.dataset.nama || 'barang ini';

                        if (this.value === '' || isNaN(val)) {
                            this.setCustomValidity('Jumlah harus diisi.');
                        } else if (val < 0) {
                            this.setCustomValidity('Jumlah tidak boleh negatif.');
                        } else if (val > max) {
                            this.setCustomValidity(
                                'Jumlah untuk "' + nama + '" tidak boleh melebihi ' + max +
                                ' (sesuai sisa permintaan & stok tersedia).'
                            );
                        } else {
                            this.setCustomValidity('');
                        }
                    });

                    input.dispatchEvent(new Event('input'));
                });
            });
        }
    </script>
@endpush
