@extends('layouts.app')

@php
    $issueGudangIdentifier = strtoupper(
        preg_replace('/[^A-Z0-9]/', '', (string) ($activeGudang?->kode ?: $activeGudang?->nama)) ?? '',
    );
    $issueUnitPrefix = str_contains($issueGudangIdentifier, 'SUKAHATI')
        ? 'SHT'
        : (substr($issueGudangIdentifier, 0, 3) ?:
        'UNT');
    $isAdminGU = auth()
        ->user()
        ?->hasAnyRole(['Super Admin', 'super_admin', 'Admin Gudang Umum', 'admin_gudang_umum']);
    $canCreatePengeluaran =
        auth()
            ->user()
            ?->hasAnyRole([
                'Super Admin',
                'super_admin',
                'Admin Gudang Umum',
                'admin_gudang_umum',
                'Recording Gudang Umum',
                'recording_gudang_umum',
            ]) ||
        auth()->user()?->can('pengeluaran.create') ||
        auth()->user()?->can('pengeluaran.input');
    $canReadPengeluaran =
        auth()
            ->user()
            ?->hasAnyRole([
                'Super Admin',
                'super_admin',
                'Admin Gudang Umum',
                'admin_gudang_umum',
                'Recording Gudang Umum',
                'recording_gudang_umum',
            ]) || auth()->user()?->can('pengeluaran.read');
    $canRetur =
        auth()
            ->user()
            ?->hasAnyRole([
                'Super Admin',
                'super_admin',
                'Admin Gudang Umum',
                'admin_gudang_umum',
                'Recording Gudang Umum',
                'recording_gudang_umum',
            ]) ||
        auth()->user()?->can('retur.read') ||
        auth()->user()?->can('retur.create') ||
        auth()->user()?->can('retur.input');
    $initialView = request('view') ?: ($isAdminGU ? 'history' : ($canCreatePengeluaran ? 'input' : 'history'));
@endphp

@section('title', 'Pengeluaran Barang — ' . ($isAdminGU ? 'Admin' : 'Recording') . ' Gudang Umum — ' .
    config('app.name', 'SIGMA'))

@section('content')
    <div class="gu-issue-page" data-draft-scope="{{ auth()->id() }}-{{ $activeGudang?->getKey() ?? 'unit' }}"
        data-operator-name="{{ auth()->user()?->name ?? 'Recording GU' }}" data-unit-prefix="{{ $issueUnitPrefix }}"
        data-store-url="{{ route('unit-bisnis.recording-gu.pengeluaran.store') }}"
        data-requester-store-url="{{ route('unit-bisnis.recording-gu.pengeluaran.requester.store') }}"
        data-borrowed-items-url="{{ route('unit-bisnis.recording-gu.pengeluaran.borrowed-items') }}"
        data-return-options-url="{{ route('unit-bisnis.recording-gu.pengeluaran.return-options') }}"
        data-return-store-url="{{ route('unit-bisnis.recording-gu.pengeluaran.pengembalian.store') }}"
        data-is-admin-gu="{{ $isAdminGU ? '1' : '0' }}" x-data="recordingGuIssue($el.dataset.draftScope, $el.dataset.operatorName, $el.dataset.unitPrefix, {{ \Illuminate\Support\Js::from($dynamicItems ?? []) }}, {{ \Illuminate\Support\Js::from($transactions ?? []) }}, $el.dataset.storeUrl, {{ \Illuminate\Support\Js::from($knownRequesters ?? []) }}, $el.dataset.requesterStoreUrl, {{ \Illuminate\Support\Js::from($returnDepartmentOptions ?? []) }}, {{ \Illuminate\Support\Js::from($initialReturnRecords ?? []) }}, $el.dataset.borrowedItemsUrl, $el.dataset.returnOptionsUrl, $el.dataset.returnStoreUrl)" x-init="init();
        activeView = '{{ $initialView }}';
        historyStatus = '{{ request('status', '') }}';"
        @keydown.window.ctrl.s.prevent="activeView === 'input' && saveIssue()">
        <header class="receipt-page-heading gu-issue-page-header">
            <div>
                <span
                    class="receipt-eyebrow gu-issue-eyebrow">{{ $isAdminGU ? 'Admin Gudang Umum' : 'Recording Gudang Umum' }}
                    <span aria-hidden="true">/</span> Operasional &amp; Verifikasi</span>
                <h1>Pengeluaran &amp; Verifikasi Barang</h1>
                <p class="gu-issue-page-description">{{ $activeGudang?->nama ?? 'Gudang Unit aktif' }} <span
                        aria-hidden="true">·</span>
                    {{ $isAdminGU ? 'Periksa, cocokkan nota fisik bon, dan sahkan pengeluaran barang' : 'Catat barang saat keluar; dokumen fisik dicocokkan Admin GU' }}.
                </p>
            </div>
            <div class="gu-issue-header-actions">
                <span class="receipt-demo-label gu-issue-preview-label"
                    style="background:#ecfdf5; border-color:#a7f3d0; color:#065f46;">● Sistem Terhubung</span>
                <a href="{{ $isAdminGU ? route('unit-bisnis.admin-gu.dashboard') : route('unit-bisnis.recording-gu.dashboard') }}"
                    class="gu-issue-link-button">Dashboard Tugas</a>
            </div>
        </header>

        <nav class="receipt-tabs gu-issue-tabs" role="tablist" aria-label="Bagian pengeluaran barang">
            @if ($isAdminGU)
                {{-- Admin GU melihat tab antrean verifikasi terlebih dahulu --}}
                <button id="issue-tab-history" type="button" role="tab" aria-controls="issue-panel-history"
                    :aria-selected="activeView === 'history'"
                    :class="{ 'active': activeView === 'history', 'is-active': activeView === 'history' }"
                    @click="setView('history')">
                    <svg class="icon-inline" width="15" height="15" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.2" style="margin-right:4px;">
                        <path d="M9 11l3 3L22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                    Verifikasi &amp; Riwayat BPB
                </button>
                <button id="issue-tab-input" type="button" role="tab" aria-controls="issue-panel-input"
                    :aria-selected="activeView === 'input'"
                    :class="{ 'active': activeView === 'input', 'is-active': activeView === 'input' }"
                    @click="setView('input')">
                    Catat Pengeluaran Baru
                </button>
                <button id="issue-tab-return" type="button" role="tab" aria-controls="issue-panel-return"
                    :aria-selected="activeView === 'return'"
                    :class="{ 'active': activeView === 'return', 'is-active': activeView === 'return' }"
                    @click="setView('return')">
                    Catat Pengembalian (Retur)
                </button>
            @else
                {{-- Recording GU fokus pada alur input fisik harian --}}
                @if ($canCreatePengeluaran)
                    <button id="issue-tab-input" type="button" role="tab" aria-controls="issue-panel-input"
                        :aria-selected="activeView === 'input'"
                        :class="{ 'active': activeView === 'input', 'is-active': activeView === 'input' }"
                        @click="setView('input')">
                        Catat Pengeluaran
                    </button>
                @endif
                @if ($canRetur)
                    <button id="issue-tab-return" type="button" role="tab" aria-controls="issue-panel-return"
                        :aria-selected="activeView === 'return'"
                        :class="{ 'active': activeView === 'return', 'is-active': activeView === 'return' }"
                        @click="setView('return')">
                        Catat Pengembalian
                    </button>
                @endif
                @if ($canReadPengeluaran)
                    <button id="issue-tab-history" type="button" role="tab" aria-controls="issue-panel-history"
                        :aria-selected="activeView === 'history'"
                        :class="{ 'active': activeView === 'history', 'is-active': activeView === 'history' }"
                        @click="setView('history')">
                        Riwayat Catatan
                    </button>
                @endif
            @endif
        </nav>

        <div x-show="notice" x-cloak class="gu-issue-notice" :class="`is-${noticeType}`" role="status" aria-live="polite">
            <span class="gu-issue-notice-icon" aria-hidden="true">
                <svg x-show="noticeType === 'success'" x-cloak class="recording-inline-icon" viewBox="0 0 24 24">
                    <path d="m5 12 4 4L19 6" />
                </svg>
                <svg x-show="noticeType === 'error'" x-cloak class="recording-inline-icon" viewBox="0 0 24 24">
                    <path d="M12 9v4m0 4h.01" />
                    <circle cx="12" cy="12" r="9" />
                </svg>
                <svg x-show="noticeType !== 'success' && noticeType !== 'error'" x-cloak class="recording-inline-icon"
                    viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 11v5m0-8h.01" />
                </svg>
            </span>
            <span x-text="notice"></span>
            <button type="button" @click="notice = ''" aria-label="Tutup pemberitahuan">×</button>
        </div>

        <section x-show="activeView === 'input'" x-cloak role="tabpanel" class="gu-issue-panel"
            aria-label="Form input pengeluaran">
            <section class="gu-issue-card gu-issue-single-card">
                <div class="gu-issue-document-bar">
                    <div class="gu-issue-document-fields">
                        <div class="gu-issue-field gu-issue-requester-field">
                            <label for="issue-requester">Bagian Peminta <b
                                    style="color:var(--status-critical, #c1443c);">*</b></label>
                            <div class="gu-issue-requester-control">
                                <input id="issue-requester" class="form-control" type="search" x-model="requesterQuery"
                                    @input="requester = ''; searchRequesters()" @focus="searchRequesters()"
                                    placeholder="Pilih atau cari bagian peminta..." autocomplete="off"
                                    aria-autocomplete="list" aria-controls="issue-requester-options">
                                <div id="issue-requester-options" x-show="requesterResults.length > 0" x-cloak
                                    class="gu-issue-suggestions" role="listbox">
                                    <template x-for="departmentOption in requesterResults" :key="departmentOption">
                                        <button type="button" role="option" @click="chooseRequester(departmentOption)">
                                            <strong x-text="departmentOption"></strong>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div class="gu-issue-quick-add"
                                x-show="requesterQuery.trim() && !requester && !requesterResults.some(option => option.toLocaleLowerCase('id') === requesterQuery.trim().toLocaleLowerCase('id'))">
                                <button type="button" class="gu-issue-text-button" @click="addRequester()"
                                    :disabled="savingRequester">
                                    <span x-show="savingRequester">Menyimpan area...</span>
                                    <span x-show="!savingRequester">Gunakan bagian “<span
                                            x-text="requesterQuery.trim()"></span>”</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tanggal & Waktu Pencatatan (Bisa Real-Time atau Susulan/Kemarin Sesuai Kertas Bon Fisik) -->
                        <div class="gu-issue-time-meta" style="min-width:280px;">
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                <span class="gu-issue-time-meta-label"
                                    style="font-size:0.75rem; font-weight:700; color:#475569;">
                                    Tanggal &amp; Waktu Pengeluaran <b style="color:var(--status-critical, #c1443c);">*</b>
                                </span>
                                <template x-if="isBackdate">
                                    <span
                                        style="font-size:0.7rem; font-weight:700; color:#B45309; background:#FEF3D6; padding:1px 6px; border-radius:4px;">
                                        Pencatatan Susulan (Kemarin/Lampau)
                                    </span>
                                </template>
                            </div>

                            <div style="display:flex; gap:6px; align-items:center;">
                                <input type="date" class="form-control" x-model="date" @change="onDateChange()"
                                    style="height:38px; font-size:0.83rem; font-weight:600; border:1px solid #CBD5E1; border-radius:6px; background:#FFFFFF; flex:1.2;">

                                <input type="time" class="form-control" x-model="occurredTime"
                                    @change="entryMode = 'late-paper'"
                                    style="height:38px; font-size:0.83rem; font-weight:600; border:1px solid #CBD5E1; border-radius:6px; background:#FFFFFF; width:95px;">

                                <template x-if="entryMode !== 'live'">
                                    <button type="button" class="btn btn-outline" @click="setToday()"
                                        title="Kembalikan ke tanggal & waktu saat ini"
                                        style="height:38px; padding:0 8px; font-size:0.75rem; font-weight:700; border-radius:6px; white-space:nowrap; border:1px solid #CBD5E1; background:#F8FAFC; color:#047857; cursor:pointer;">
                                        Hari Ini
                                    </button>
                                </template>
                            </div>

                            <small style="color:#64748B; font-size:0.72rem; margin-top:4px; display:block;">
                                Pilih tanggal kertas bon fisik jika mencatat pengeluaran kemarin yang baru diterima admin.
                            </small>
                        </div>
                    </div>
                </div>

                <div x-show="existingTransactionForSelectedDateAndRequester" x-cloak class="gu-issue-accumulate-alert"
                    style="margin-top:10px;">
                    <span class="gu-issue-accumulate-dot"></span>
                    <span>Bagian <strong x-text="department || requester"></strong> sudah memiliki transaksi pada tanggal
                        <strong x-text="formatDateIndo(date)"></strong> (<span class="gu-issue-mono"
                            x-text="existingTransactionForSelectedDateAndRequester?.bpb"></span>). Item yang diinput akan
                        <strong>otomatis diakumulasikan ke rekap tanggal tersebut</strong> (seperti sistem kolom
                        Excel).</span>
                </div>

                <!-- Tabel Rincian Barang Keluar -->
                <div class="gu-issue-table-wrap" style="margin-top:14px;">
                    <table class="gu-issue-entry-table">
                        <thead>
                            <tr>
                                <th style="width:5%;">No.</th>
                                <th style="width:34%;">Nama Barang</th>
                                <th style="width:13%;" class="is-number">Banyak</th>
                                <th style="width:9%;">Satuan</th>
                                <th style="width:29%;">Catatan opsional</th>
                                <th style="width:10%; text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="row.uid">
                                <tr :class="{ 'has-warning': rowException(row) }">
                                    <td data-label="No." class="gu-issue-row-number"
                                        x-text="String(index + 1).padStart(2, '0')"></td>
                                    <td data-label="Nama Barang">
                                        <div class="gu-issue-item-search">
                                            <input class="form-control" type="search" :x-ref="`item-${index}`"
                                                x-model="row.query" @input="row.item = null; searchItems(index)"
                                                @focus="searchItems(index)"
                                                @keydown.enter.prevent="row.results.length && selectItem(index, row.results[0])"
                                                placeholder="Cari nama / kode barang..." autocomplete="off"
                                                :aria-label="`Cari barang baris ${index + 1}`">
                                            <div x-show="row.searching && row.results.length" x-cloak
                                                class="gu-issue-suggestions" role="listbox">
                                                <template x-for="item in row.results" :key="item.id">
                                                    <button type="button" role="option"
                                                        @click="selectItem(index, item)">
                                                        <strong x-text="item.name"></strong>
                                                        <small><span x-text="item.code"></span> · <span
                                                                x-text="item.category"></span> · Stok <span
                                                                x-text="`${item.stock} ${item.unit}`"></span></small>
                                                    </button>
                                                </template>
                                            </div>
                                            <div x-show="row.item" class="gu-issue-stock-line">
                                                <span
                                                    x-text="`${row.item?.code} · Stok saat ini ${row.item?.stock} ${row.item?.unit}`"></span>
                                                <button type="button" @click="clearItem(index)">Ganti</button>
                                            </div>
                                            <div x-show="rowException(row)" x-cloak class="gu-issue-row-warning">
                                                <span class="gu-issue-warning-mark" aria-hidden="true">!</span>
                                                <span
                                                    x-text="`Melebihi stok (tersedia ${row.item?.stock} ${row.item?.unit}) · tetap tercatat untuk dicocokkan Admin GU`"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Banyak">
                                        <input class="form-control gu-issue-number" type="number" min="0.001"
                                            step="0.001" x-model="row.qty"
                                            @keydown.enter="handleRowEnter(index, $event)" :x-ref="`qty-${index}`"
                                            :aria-label="`Jumlah keluar baris ${index + 1}`" placeholder="0.000">
                                    </td>
                                    <td data-label="Satuan"><span class="gu-issue-unit"
                                            x-text="row.item?.unit || '—'"></span></td>
                                    <td data-label="Catatan opsional">
                                        <input class="form-control" type="text" x-model="row.note"
                                            placeholder="Catatan singkat" :aria-label="`Catatan baris ${index + 1}`">
                                    </td>
                                    <td data-label="Aksi" style="text-align:center;">
                                        <button type="button" class="gu-issue-remove-button" @click="removeRow(index)"
                                            :disabled="rows.length === 1"
                                            :aria-label="`Hapus barang baris ${index + 1}`">×</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Total item: <strong x-text="issueLineCount"></strong></td>
                                <td class="is-number">—</td>
                                <td colspan="3">Qty ditampilkan per satuan pada setiap baris</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="gu-issue-add-row-wrap" style="margin-top:10px;">
                    <button type="button" class="gu-issue-add-row-button" @click="addRow()">＋ Tambah Baris
                        Barang</button>
                </div>

                <div class="gu-issue-stock-note" style="margin-top:10px;">
                    <span class="gu-issue-warning-mark" aria-hidden="true">!</span>
                    <span>Catat barang yang sudah diserahkan untuk dicocokkan Admin GU dengan dokumen fisik. Proses
                        verifikasi tidak mengubah jumlah stok.</span>
                </div>

                <label class="gu-issue-notes-field" style="margin-top:14px;">
                    <span>Catatan singkat transaksi <small>(opsional)</small></span>
                    <input class="form-control" type="text" x-model="note"
                        placeholder="Keterangan umum atau catatan penting pengeluaran ini">
                </label>
            </section>

            <div class="gu-issue-draft-status" aria-live="polite">
                <span class="gu-issue-draft-dot"></span>
                <span
                    x-text="draftSavedAt ? `Draft sementara tersimpan di browser ${draftSavedAt}` : 'Draft tersimpan sementara di browser saat form berubah'"></span>
                <span class="gu-issue-key-hint">Ctrl + S untuk menambahkan</span>
            </div>

            <!-- Flash Banner Notifikasi Tersimpan -->
            <div x-show="savedFlashMessage" x-cloak x-transition.opacity.duration.300ms class="gu-issue-saved-flash">
                <div class="gu-issue-saved-flash-content">
                    <span class="gu-issue-saved-flash-icon" aria-hidden="true"><svg class="recording-inline-icon"
                            viewBox="0 0 24 24">
                            <path d="m5 12 4 4L19 6" />
                        </svg></span>
                    <span x-text="savedFlashMessage"></span>
                </div>
                <button type="button" class="gu-issue-saved-flash-close" @click="savedFlashMessage = ''"
                    aria-label="Tutup">×</button>
            </div>

            <footer class="gu-issue-sticky-actions">
                <button type="button" class="gu-issue-cancel-button"
                    @click="resetForm(); setNotice('Form dikosongkan. Draft lokal dihapus.', 'info')">Kosongkan</button>
                <div>
                    <button type="button" class="gu-issue-primary-button" @click="saveIssue()" :disabled="saving">
                        <span
                            x-text="saving ? 'Menyimpan...' : (isBackdate ? `+ Simpan Susulan (${formatDateIndo(date)})` : '+ Simpan Pengeluaran')"></span>
                    </button>
                </div>
            </footer>
        </section>

        <section x-show="activeView === 'return'" x-cloak role="tabpanel" class="gu-issue-panel"
            aria-label="Form pengembalian barang">
            <section class="gu-issue-card gu-issue-single-card">
                <div class="gu-issue-document-bar">
                    <div class="gu-issue-document-fields">
                        <div class="gu-issue-field gu-issue-requester-field">
                            <label for="return-department">Pilih Bagian Peminta <b
                                    style="color:var(--status-critical, #c1443c);">*</b></label>
                            <div class="gu-issue-requester-control">
                                <select id="return-department" class="form-control gu-issue-select-large"
                                    x-model="selectedReturnDepartment" @change="onReturnDepartmentChange()"
                                    :disabled="loadingReturnOptions">
                                    <option value=""
                                        x-text="loadingReturnOptions ? 'Memuat daftar bagian...' : '-- Pilih Bagian Peminta (Contoh: Produksi, Teknisi) --'">
                                    </option>
                                    <template x-for="dept in departmentReturnOptions" :key="dept.name">
                                        <option :value="dept.name" x-text="dept.label"></option>
                                    </template>
                                </select>
                                <template x-if="loadingReturnOptions">
                                    <span style="font-size:0.72rem; color:#64748B; margin-top:4px; display:block;">⟳ Memuat
                                        opsi dari server…</span>
                                </template>
                            </div>
                            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">
                                Pilih bagian peminta untuk melihat semua barang yang belum dikembalikan dari seluruh riwayat
                                pengeluaran.
                            </small>
                        </div>
                        <div class="gu-issue-field" style="min-width:220px;">
                            <label for="return-date">Tanggal Pengembalian <b
                                    style="color:var(--status-critical, #c1443c);">*</b></label>
                            <input id="return-date" type="date" class="form-control" x-model="returnDate" required
                                style="height:42px; font-size:0.88rem; font-weight:600; border:1px solid #CBD5E1; border-radius:6px; background:#FFFFFF;">
                            <small style="color:var(--text-muted); font-size:0.75rem; margin-top:4px; display:block;">
                                Tanggal barang dikembalikan ke gudang.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Jika Belum Memilih Bagian -->
                <template x-if="!selectedReturnDepartment">
                    <div class="gu-issue-empty-borrowed" style="margin-top:14px;">
                        <div class="gu-issue-empty-icon" aria-hidden="true"><svg class="recording-inline-icon"
                                viewBox="0 0 24 24">
                                <path d="M3 21V5l9-3 9 3v16" />
                                <path d="M8 21v-5h8v5M7 8h.01M12 8h.01M17 8h.01M7 12h.01M12 12h.01M17 12h.01" />
                            </svg></div>
                        <strong>Pilih Bagian Peminta Terlebih Dahulu</strong>
                        <p>Pilih bagian pada dropdown di atas untuk melihat barang yang sedang dibawa dan mencatat sisa
                            pengembalian.</p>
                    </div>
                </template>

                <!-- Jika Bagian Dipilih -->
                <template x-if="selectedReturnDepartment">
                    <div class="gu-issue-return-active-area" style="margin-top:14px;">
                        <!-- Jika Tidak Ada Barang yang Belum Dikembalikan -->
                        <template x-if="borrowedItemsForSelectedDepartment.length === 0">
                            <div class="gu-issue-empty-borrowed">
                                <div class="gu-issue-empty-icon" aria-hidden="true"><svg class="recording-inline-icon"
                                        viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="m8 12 2.5 2.5L16 9" />
                                    </svg></div>
                                <strong>Tidak Ada Sisa Barang untuk Dikembalikan</strong>
                                <p>
                                    Semua barang yang pernah dikeluarkan untuk bagian <strong
                                        x-text="selectedReturnDepartment"></strong> sudah dikembalikan.
                                </p>
                            </div>
                        </template>

                        <!-- Jika Ada Barang yang Dibawa -->
                        <template x-if="borrowedItemsForSelectedDepartment.length > 0">
                            <div>
                                <div class="gu-issue-table-wrap">
                                    <table class="gu-issue-dynamic-return-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">NO</th>
                                                <th style="width: 28%;">NAMA BARANG</th>
                                                <th style="width: 14%;" class="is-number">TOTAL DIAMBIL</th>
                                                <th style="width: 22%;">QTY SISA / RETUR</th>
                                                <th style="width: 15%;" class="is-number">PEMAKAIAN RIEL FINAL</th>
                                                <th style="width: 16%;">KETERANGAN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, idx) in borrowedItemsForSelectedDepartment"
                                                :key="item.name">
                                                <tr>
                                                    <td data-label="NO" class="gu-issue-row-number"
                                                        x-text="String(idx + 1).padStart(2, '0')"></td>
                                                    <td data-label="NAMA BARANG">
                                                        <strong class="gu-issue-item-title" x-text="item.name"></strong>
                                                        <small style="color:var(--text-muted); font-size:0.7rem;"
                                                            x-text="item.category"></small>
                                                        <template x-if="item.totalReturned > 0">
                                                            <span class="gu-issue-returned-note"
                                                                x-text="`Sudah kembali: ${item.totalReturned} ${item.unit}`"></span>
                                                        </template>
                                                    </td>
                                                    <td data-label="TOTAL DIAMBIL" class="is-number">
                                                        <span class="gu-issue-total-taken"
                                                            x-text="`${item.totalTaken} ${item.unit}`"></span>
                                                    </td>
                                                    <td data-label="QTY SISA / RETUR">
                                                        <div class="gu-issue-return-input-group">
                                                            <input class="form-control gu-issue-return-input"
                                                                type="number" min="0"
                                                                :max="item.availableToReturn" step="any"
                                                                x-model="returnInputs[item.name]"
                                                                @input="validateReturnInput(item)" placeholder="0">
                                                            <span class="gu-issue-input-unit" x-text="item.unit"></span>
                                                        </div>
                                                        <small x-show="item.availableToReturn < item.totalTaken"
                                                            class="gu-issue-subtle-hint"
                                                            x-text="`Maksimal sisa: ${item.availableToReturn} ${item.unit}`"></small>
                                                    </td>
                                                    <td data-label="PEMAKAIAN RIEL FINAL" class="is-number">
                                                        <strong class="gu-issue-final-usage"
                                                            x-text="`${calculateFinalUsage(item.totalTaken, item.totalReturned, returnInputs[item.name])} ${item.unit}`"></strong>
                                                    </td>
                                                    <td data-label="KETERANGAN">
                                                        <span class="gu-issue-state-badge is-success"
                                                            style="font-size:0.68rem;">Stok Otomatis Kembali</span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="gu-issue-return-action-bar"
                                    style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
                                    <span x-show="loadingBorrowedItems"
                                        style="font-size:0.78rem; color:#2563EB; font-weight:600;">
                                        ⏳ Memuat data barang dari server...
                                    </span>
                                    <span x-show="!loadingBorrowedItems"></span>
                                    <button type="button" class="gu-issue-primary-button gu-issue-save-return-btn"
                                        :disabled="savingReturn" @click="saveDepartmentReturns()"
                                        :style="savingReturn ? 'opacity:0.7; cursor:not-allowed;' : ''">
                                        <span x-show="!savingReturn">Simpan Pengembalian Sisa Barang</span>
                                        <span x-show="savingReturn">Menyimpan ke Database...</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </section>
        </section>

        <section x-show="activeView === 'history'" x-cloak role="tabpanel" class="gu-issue-panel">
            <section class="gu-issue-card">
                <div class="gu-issue-card-heading">
                    <div>
                        <h2>Riwayat Gudang Umum</h2>
                        <p>Telusuri riwayat transaksi pengeluaran barang serta catatan pengembalian sisa.</p>
                    </div>
                    <button type="button" class="gu-issue-primary-button gu-issue-small-primary"
                        @click="setView('input')">＋ Input Pengeluaran</button>
                </div>

                <div class="gu-issue-filters">
                    <label class="gu-issue-search-filter">
                        <span class="sr-only">Cari riwayat</span>
                        <input class="form-control" type="search" x-model="search"
                            placeholder="Cari BPB, bagian, nama barang...">
                    </label>
                    <label>
                        <span class="sr-only">Filter tanggal</span>
                        <input class="form-control" type="date" x-model="historyDate"
                            aria-label="Filter tanggal transaksi">
                    </label>
                    <label x-show="historySubView === 'transactions'">
                        <span class="sr-only">Filter status</span>
                        <select class="form-control" x-model="historyStatus">
                            <option value="">Semua status</option>
                            <option>Menunggu Rekonsiliasi</option>
                            <option>Tercatat</option>
                            <option>Final</option>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Filter bagian peminta</span>
                        <select class="form-control" x-model="historyDepartment" aria-label="Filter bagian peminta">
                            <option value="">Semua bagian</option>
                            <template x-for="department in historyDepartments" :key="department">
                                <option :value="department" x-text="department"></option>
                            </template>
                        </select>
                    </label>
                    <label x-show="historySubView === 'transactions'" class="gu-issue-check gu-issue-filter-check">
                        <input type="checkbox" x-model="exceptionsOnly">
                        <span class="gu-issue-checkbox-ui"></span>
                        <span>Hanya pengecualian</span>
                    </label>
                </div>

                <!-- VIEW 1: TRANSAKSI PENGELUARAN (Card Layout Sesuai Contoh UI Gambar) -->
                <div x-show="historySubView === 'transactions'">
                    <!-- Empty State -->
                    <template x-if="filteredTransactions.length === 0">
                        <div
                            style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:8px; padding:32px 16px; text-align:center; color:#64748B;">
                            <p style="margin:0; font-size:0.9rem;">Tidak ada transaksi pengeluaran yang sesuai dengan
                                filter pencarian.</p>
                        </div>
                    </template>

                    <!-- Tight & Compact List of Cards (Sesuai Contoh UI Gambar) -->
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <template x-for="transaction in filteredTransactions" :key="transaction.id">
                            <div class="card"
                                style="margin-bottom:0 !important; border:1px solid var(--surface-border, #E2E8F0); border-radius:8px; padding:10px 14px; box-shadow:0 1px 2px rgba(0,0,0,0.02); background:#FFFFFF;">

                                <!-- Card Header Row -->
                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                                    <!-- Left: Meta Info -->
                                    <div>
                                        <h3 style="font-size:0.93rem; font-weight:700; color:#0F172A; margin:0 0 2px 0;">
                                            Rincian Pengeluaran <span style="color:var(--brand-primary, #0284C7);"
                                                x-text="transaction.bpb"></span>
                                        </h3>
                                        <p style="font-size:0.8rem; color:#64748B; margin:0;">
                                            Diajukan/Dicatat oleh <strong style="color:#334155;"
                                                x-text="transaction.enteredBy || 'Petugas Recording'"></strong>
                                            ke/untuk bagian <strong style="color:#334155;"
                                                x-text="transaction.department || transaction.requester"></strong>
                                            pada <span
                                                x-text="`${formatDateIndo(transaction.date)}, ${transaction.occurredTime}`"></span>
                                        </p>
                                    </div>

                                    <!-- Right: Status Badge & Accordion Toggle Button -->
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <!-- Status Badge -->
                                        <template x-if="transaction.status === 'Menunggu Rekonsiliasi'">
                                            <span class="badge"
                                                style="background:#FEF3D6; color:#B45309; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;">
                                                Menunggu Rekonsiliasi
                                            </span>
                                        </template>
                                        <template x-if="transaction.status !== 'Menunggu Rekonsiliasi'">
                                            <span class="badge"
                                                :style="transaction.status === 'Ada Selisih' ?
                                                    'background:#FEE2E2; color:#B91C1C; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;' :
                                                    'background:#DCFCE7; color:#15803D; border:none; padding:3px 10px; font-weight:600; font-size:0.78rem; border-radius:14px;'"
                                                x-text="transaction.status || 'Tercatat'">
                                            </span>
                                        </template>

                                        <!-- Button Accordion Detail Barang -->
                                        <button type="button" class="btn btn-outline"
                                            @click="toggleTransactionDetails(transaction)"
                                            style="min-height:30px; padding:2px 10px; font-size:0.78rem; font-weight:600; border-radius:6px; display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                            <span>Detail Barang</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                :style="expandedTransactionId === transaction.id ?
                                                    'transform: rotate(180deg); transition: transform 0.2s;' :
                                                    'transition: transform 0.2s;'">
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Accordion Item Table & Actions -->
                                <div x-show="expandedTransactionId === transaction.id" x-cloak
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
                                                    <th style="padding:8px 12px; text-align:center;">QTY KELUAR</th>
                                                    <th style="padding:8px 12px; text-align:center;">QTY KEMBALI</th>
                                                    <th style="padding:8px 12px; text-align:center;">PEMAKAIAN RIEL FINAL
                                                    </th>
                                                    <th style="padding:8px 12px; text-align:right;">HPP (RP)</th>
                                                    <th style="padding:8px 12px; text-align:center;">STATUS ITEM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(item, index) in transaction.items"
                                                    :key="`${transaction.id}-detail-${index}`">
                                                    <tr style="border-bottom:1px solid #F1F5F9; font-size:0.83rem;">
                                                        <td style="padding:8px 12px;">
                                                            <strong style="color:#1E293B;" x-text="item.name"></strong>
                                                            <template x-if="item.logs && item.logs.length > 1">
                                                                <div
                                                                    style="display:flex; gap:4px; margin-top:3px; flex-wrap:wrap;">
                                                                    <template x-for="(log, logIdx) in item.logs"
                                                                        :key="logIdx">
                                                                        <span
                                                                            style="font-size:0.68rem; background:#F1F5F9; color:#475569; padding:1px 6px; border-radius:4px;"
                                                                            x-text="log"></span>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </td>
                                                        <td style="padding:8px 12px;">
                                                            <span
                                                                style="display:inline-block; background:#EFF6FF; color:#1D4ED8; font-size:0.72rem; font-weight:600; padding:2px 9px; border-radius:12px; white-space:nowrap;"
                                                                x-text="item.category || item.kategori || 'Bahan & Logistik'"></span>
                                                        </td>
                                                        <td style="padding:8px 12px; text-align:center;">
                                                            <span
                                                                style="display:inline-block; background:#F1F5F9; color:#475569; font-size:0.72rem; font-weight:600; padding:2px 9px; border-radius:12px; white-space:nowrap;"
                                                                x-text="item.unit"></span>
                                                        </td>
                                                        <td class="tabular"
                                                            style="padding:8px 12px; text-align:center; font-weight:700; color:#334155;"
                                                            x-text="item.qty"></td>
                                                        <td class="tabular"
                                                            style="padding:8px 12px; text-align:center; font-weight:600; color:#475569;"
                                                            x-text="(item.qty_kembali !== undefined && Number(item.qty_kembali) > 0) ? item.qty_kembali : (returnedQty(transaction.id, index) > 0 ? returnedQty(transaction.id, index) : '0')">
                                                        </td>
                                                        <td class="tabular"
                                                            style="padding:8px 12px; text-align:center; font-weight:700; color:#047857; font-size:0.92rem;"
                                                            x-text="item.pemakaian_riil !== undefined ? item.pemakaian_riil : finalUsageQty(transaction.id, index)">
                                                        </td>
                                                        <td class="tabular"
                                                            style="padding:8px 12px; text-align:right; font-weight:600; color:#1E293B;"
                                                            x-text="item.hpp > 0 ? `Rp ${Number(item.hpp).toLocaleString('id-ID')}` : 'Rp 0'">
                                                        </td>
                                                        <td style="padding:8px 12px; text-align:center;">
                                                            <template
                                                                x-if="(item.qty_kembali !== undefined && Number(item.qty_kembali) > 0) || returnedQty(transaction.id, index) > 0">
                                                                <span class="badge"
                                                                    style="background:#DCFCE7; color:#15803D; font-size:0.75rem; padding:3px 10px; border-radius:14px; font-weight:600;">
                                                                    Ada Sisa / Retur
                                                                </span>
                                                            </template>
                                                            <template
                                                                x-if="!(item.qty_kembali !== undefined && Number(item.qty_kembali) > 0) && returnedQty(transaction.id, index) <= 0">
                                                                <span class="badge"
                                                                    style="background:#FFEDD5; color:#C2410C; font-size:0.75rem; padding:3px 10px; border-radius:14px; font-weight:600;">
                                                                    Belum Dikeluarkan Sisa
                                                                </span>
                                                            </template>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Bottom Row: Catatan di Kiri & Action Buttons di Kanan -->
                                    <div
                                        style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:10px; flex-wrap:wrap;">
                                        <div>
                                            <template x-if="transaction.note || transaction.items.some(i => i.note)">
                                                <div
                                                    style="padding:6px 10px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:6px; font-size:0.8rem; color:#475569; max-width:520px;">
                                                    <strong style="color:#1E293B;">Catatan:</strong>
                                                    <span
                                                        x-text="transaction.note || transaction.items.filter(i => i.note).map(i => `${i.name}: ${i.note}`).join('; ')"></span>
                                                </div>
                                            </template>
                                        </div>

                                        <div
                                            style="display:flex; align-items:center; gap:8px; margin-left:auto; flex-wrap:wrap;">
                                            <button type="button" class="gu-issue-primary-button"
                                                @click="setView('return'); selectedReturnDepartment = transaction.department || transaction.requester;">
                                                <svg class="recording-inline-icon" aria-hidden="true"
                                                    viewBox="0 0 24 24">
                                                    <path d="M4 7h12a4 4 0 0 1 4 4v1" />
                                                    <path d="m16 8 4 4 4-4" />
                                                    <path d="M20 17H8a4 4 0 0 1-4-4v-1" />
                                                </svg> Catat Pengembalian Sisa
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>

                    <footer class="gu-issue-table-footer" style="margin-top:14px;">
                        <span
                            x-text="`Menampilkan ${filteredTransactions.length ? 1 : 0}–${filteredTransactions.length} dari ${filteredTransactions.length} transaksi`"></span>
                        <div><button type="button" disabled
                                aria-label="Halaman sebelumnya">‹</button><span>1</span><button type="button" disabled
                                aria-label="Halaman berikutnya">›</button></div>
                    </footer>
                </div>
            </section>
        </section>




    </div>

    <style>
        .gu-issue-page {
            --issue-bg: #f7f6f3;
            --issue-card: #fff;
            --issue-border: #e4e2dc;
            --issue-text: #1f2523;
            --issue-muted: #5b6b79;
            --issue-green: #145c54;
            --issue-green-dark: #0f4a44;
            --issue-accent: #c97a2b;
            --issue-critical: #c1443c;
            --issue-warning: #b8791a;
            --issue-success: #3f7a52;
            --issue-info: #e8f1ef;
            display: grid;
            gap: 14px;
            min-width: 0;
            padding-bottom: 76px;
            color: var(--issue-text);
            font-size: .875rem;
        }

        .gu-issue-page [x-cloak] {
            display: none !important;
        }

        .receipt-page-heading,
        .gu-issue-page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .receipt-eyebrow,
        .gu-issue-eyebrow {
            display: block;
            margin: 0 0 4px;
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 600;
        }

        .receipt-eyebrow span,
        .gu-issue-eyebrow span {
            padding: 0 4px;
            color: #98a09d;
        }

        .receipt-page-heading h1,
        .gu-issue-page-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .receipt-page-heading p,
        .gu-issue-page-description {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: .875rem;
        }

        .gu-issue-header-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .receipt-demo-label,
        .gu-issue-preview-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border: 1px solid #ead8b8;
            border-radius: 5px;
            background: #fff8e9;
            color: #80531b;
            font-size: .75rem;
            font-weight: 650;
            white-space: normal;
        }

        .gu-issue-link-button,
        .gu-issue-outline-button,
        .gu-issue-primary-button,
        .gu-issue-cancel-button {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px solid var(--issue-green);
            border-radius: 6px;
            background: #fff;
            color: var(--issue-green);
            font: inherit;
            font-size: .78rem;
            font-weight: 650;
            line-height: 1.25;
            text-decoration: none;
            cursor: pointer;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease;
        }

        .gu-issue-link-button:hover,
        .gu-issue-outline-button:hover {
            border-color: var(--issue-green-dark);
            background: #f3f8f6;
            color: var(--issue-green-dark);
        }

        .gu-issue-primary-button {
            border-color: var(--issue-accent);
            background: var(--issue-accent);
            color: #1f2523;
        }

        .gu-issue-primary-button:hover {
            border-color: #b66c25;
            background: #b66c25;
        }

        .gu-issue-primary-button:disabled,
        .gu-issue-outline-button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .gu-issue-cancel-button {
            border-color: transparent;
            background: transparent;
            color: var(--issue-muted);
        }

        .gu-issue-cancel-button:hover {
            background: #f0efeb;
            color: var(--issue-text);
        }

        .gu-issue-page button:focus-visible,
        .gu-issue-page input:focus-visible,
        .gu-issue-page select:focus-visible,
        .gu-issue-page textarea:focus-visible,
        .gu-issue-page a:focus-visible {
            outline: 2px solid var(--issue-accent);
            outline-offset: 2px;
        }

        .gu-issue-notice {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 10px 12px;
            border: 1px solid #cedbd8;
            border-radius: 6px;
            background: var(--issue-info);
            color: #274c46;
            font-size: .8rem;
            white-space: pre-line;
        }

        .gu-issue-notice.is-error {
            border-color: #e7c9c6;
            background: #fff7f6;
            color: #8b342e;
        }

        .gu-issue-notice.is-success {
            border-color: #cbdccc;
            background: #f2f8f1;
            color: #315a38;
        }

        .gu-issue-notice-icon {
            display: grid;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            place-items: center;
            border: 1px solid currentColor;
            border-radius: 50%;
            font-size: .7rem;
            font-weight: 800;
        }

        .gu-issue-notice>span:nth-child(2) {
            flex: 1;
        }

        .gu-issue-notice>button:last-child {
            width: 28px;
            height: 28px;
            margin: -5px -6px 0 0;
            border: 0;
            background: transparent;
            color: inherit;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .gu-issue-notice .gu-issue-notice-action {
            width: auto;
            min-height: 30px;
            flex: 0 0 auto;
            margin: 0;
            padding: 4px 8px;
            border: 1px solid currentColor;
            border-radius: 4px;
            font-size: .68rem;
            font-weight: 700;
        }

        .gu-issue-status {
            width: max-content;
            max-width: 100%;
            padding: 3px 7px;
            border-radius: 999px;
            background: #edf0f2;
            color: #465561;
            font-size: .7rem !important;
        }

        .gu-issue-status.is-pending {
            background: #fff1d8;
            color: #6f4709;
        }

        .gu-issue-status.is-final {
            background: var(--issue-success);
            color: #fff;
        }

        .receipt-tabs,
        .gu-issue-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 16px;
            padding: 0;
            border: 0;
            border-bottom: 2px solid var(--surface-border);
            background: transparent;
        }

        .receipt-tabs button,
        .gu-issue-tabs button {
            display: inline-flex;
            min-height: 40px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 18px;
            border: 1px solid var(--surface-border);
            border-bottom: 0;
            border-radius: 6px 6px 0 0;
            background: #fff;
            color: var(--text-muted);
            font: inherit;
            font-size: .875rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        .receipt-tabs button:hover,
        .gu-issue-tabs button:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .receipt-tabs button.active,
        .receipt-tabs button.is-active,
        .gu-issue-tabs button.active,
        .gu-issue-tabs button.is-active {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .receipt-tabs button:focus-visible,
        .gu-issue-tabs button:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .gu-issue-preview-tag {
            padding: 2px 5px;
            border-radius: 999px;
            background: #edf0f2;
            color: #52616a;
            font-size: .58rem;
            font-weight: 650;
        }

        .gu-issue-panel {
            display: grid;
            min-width: 0;
            gap: 10px;
        }

        .gu-issue-card {
            min-width: 0;
            padding: 16px;
            border: 1px solid var(--surface-border, #e2dfd9);
            border-radius: var(--radius-lg, 12px);
            background: #fff;
            box-shadow: 0 1px 3px rgba(31, 37, 35, .03);
        }

        .gu-issue-single-card {
            padding: 16px;
            border: 1px solid var(--surface-border, #e2dfd9);
            border-radius: var(--radius-lg, 12px);
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .03);
        }

        .gu-issue-document-bar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding-bottom: 2px;
        }

        .gu-issue-document-fields {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) auto;
            gap: 16px;
            flex: 1;
            align-items: start;
        }

        .gu-issue-time-meta {
            display: grid;
            gap: 5px;
            align-content: start;
        }

        .gu-issue-time-meta-label {
            font-size: .8125rem;
            font-weight: 650;
            color: var(--text-muted);
            display: block;
            white-space: nowrap;
        }

        .gu-issue-time-meta-label small {
            font-weight: 400;
            color: var(--text-muted);
        }

        .gu-issue-time-meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 40px;
            height: 40px;
            padding: 0 14px;
            border: 1px solid #dce5e2;
            border-radius: var(--radius, 8px);
            background: #f4f7f5;
            color: var(--text-primary);
            font-size: .84rem;
            font-weight: 600;
            user-select: none;
            white-space: nowrap;
        }

        .gu-issue-time-val {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .82rem;
            color: #1e293b;
            font-weight: 650;
        }

        .gu-issue-time-sep {
            color: #94a3b8;
            font-weight: 700;
        }

        .gu-issue-document-fields label,
        .gu-issue-field>span:first-child,
        .gu-issue-field>label {
            font-size: .8125rem;
            font-weight: 650;
            color: var(--text-muted);
            margin-bottom: 5px;
            display: block;
        }

        .gu-issue-document-fields .form-control {
            min-height: 40px;
            height: 40px;
            padding: 7px 11px;
            border: 1px solid #dce5e2;
            border-radius: var(--radius, 8px);
            background: #f4f7f5;
            color: var(--text-primary);
            font-size: .84rem;
            width: 100%;
            box-sizing: border-box;
            transition: all .15s ease;
        }

        .gu-issue-document-fields .form-control:focus {
            border-color: var(--brand-primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(20, 92, 84, .12);
            outline: none;
        }

        .gu-issue-copy-btn {
            min-height: 40px;
            height: 40px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #c9d8d2;
            border-radius: var(--radius, 8px);
            background: #fff;
            color: var(--brand-primary);
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s ease;
        }

        .gu-issue-copy-btn:hover {
            border-color: var(--brand-primary);
            background: #f4f8f6;
        }

        .gu-issue-card-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .gu-issue-card-heading h2 {
            margin: 0;
            font-size: .98rem;
            font-weight: 720;
            letter-spacing: -.01em;
        }

        .gu-issue-card-heading p {
            margin: 4px 0 0;
            color: var(--issue-muted);
            font-size: .74rem;
            line-height: 1.45;
        }

        .gu-issue-state-badge {
            display: inline-flex;
            width: max-content;
            max-width: 100%;
            align-items: center;
            margin: 2px 4px 2px 0;
            padding: 4px 7px;
            border-radius: 999px;
            font-size: .65rem;
            font-weight: 700;
            line-height: 1.25;
            white-space: normal;
        }

        .gu-issue-state-badge.is-neutral {
            background: #5b6b79;
            color: #fff;
        }

        .gu-issue-state-badge.is-critical {
            background: var(--issue-critical);
            color: #fff;
        }

        .gu-issue-state-badge.is-warning {
            background: var(--issue-warning);
            color: #1f2523;
        }

        .gu-issue-state-badge.is-success {
            background: var(--issue-success);
            color: #fff;
        }

        .gu-issue-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px 12px;
        }

        .gu-issue-field {
            display: grid;
            min-width: 0;
            align-content: start;
            gap: 4px;
        }

        .gu-issue-field b,
        .gu-issue-requester-field label b {
            color: var(--issue-critical);
        }

        .gu-issue-field small {
            color: var(--issue-muted);
            font-size: .67rem;
            font-weight: 400;
            line-height: 1.35;
        }

        .gu-issue-field .form-control,
        .gu-issue-requester-control .form-control,
        .gu-issue-quick-add .form-control {
            width: 100%;
            min-width: 0;
            min-height: 40px;
            padding: 7px 11px;
            border: 1px solid #dce5e2;
            border-radius: var(--radius, 8px);
            background: #f4f7f5;
            color: var(--issue-text);
            font-size: .84rem;
        }

        .gu-issue-field .form-control:focus,
        .gu-issue-requester-control .form-control:focus {
            border-color: var(--brand-primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(20, 92, 84, .12);
            outline: none;
        }

        .gu-issue-auto-time {
            background: #f1f4f3 !important;
            color: #1e293b !important;
            cursor: default;
            border-color: #cbd5e1 !important;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-weight: 600;
            font-size: .82rem;
        }

        .gu-issue-accumulate-alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 9px 12px;
            border: 1px solid #bfdbfe;
            border-radius: 7px;
            background: #eff6ff;
            color: #1e40af;
            font-size: .74rem;
            line-height: 1.4;
        }

        .gu-issue-accumulate-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #2563eb;
            flex: 0 0 7px;
            margin-top: 4px;
        }

        .gu-issue-item-logs {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .gu-issue-log-chip {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            font-size: .63rem;
            font-weight: 600;
        }

        .gu-issue-field textarea.form-control {
            min-height: 58px;
            resize: vertical;
        }

        .gu-issue-field-full {
            grid-column: 1/-1;
        }

        .gu-issue-requester-field {
            position: relative;
        }

        .gu-issue-requester-field>label {
            display: block;
        }

        .gu-issue-requester-control,
        .gu-issue-item-search {
            position: relative;
            min-width: 0;
        }

        .gu-issue-suggestions {
            position: absolute;
            z-index: 50;
            top: calc(100% + 4px);
            right: 0;
            left: 0;
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        }

        .gu-issue-suggestions button {
            display: grid;
            width: 100%;
            gap: 3px;
            padding: 10px 12px;
            border: 0;
            border-bottom: 1px solid #edf1ef;
            background: #fff;
            color: var(--issue-text);
            text-align: left;
            cursor: pointer;
            transition: background .12s ease;
        }

        .gu-issue-suggestions button:last-child {
            border-bottom: 0;
        }

        .gu-issue-suggestions button:hover,
        .gu-issue-suggestions button:focus-visible {
            background: #f1f6f3;
            outline: none;
        }

        .gu-issue-suggestions button strong {
            font-size: .84rem;
            color: #1a2924;
        }

        .gu-issue-suggestions button small {
            color: var(--issue-muted);
            font-size: .72rem;
        }

        .gu-issue-quick-add {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 6px;
            margin-top: 5px;
        }

        .gu-issue-text-button {
            min-height: 30px;
            padding: 4px 8px;
            border: 1px solid #cce0d8;
            border-radius: 4px;
            background: #f5faf7;
            color: var(--issue-green);
            font: inherit;
            font-size: .72rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
        }

        .gu-issue-text-button:hover {
            background: var(--issue-green);
            color: #fff;
        }

        .gu-issue-toggle-field {
            align-content: start;
        }

        .gu-issue-check {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            gap: 7px;
            color: var(--issue-text);
            font-size: .72rem;
            font-weight: 550;
            cursor: pointer;
        }

        .gu-issue-check input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            clip-path: inset(50%);
            white-space: nowrap;
        }

        .gu-issue-checkbox-ui {
            position: relative;
            display: inline-grid;
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            place-items: center;
            border: 1px solid #8c9b95;
            border-radius: 3px;
            background: #fff;
        }

        .gu-issue-check input:checked+.gu-issue-checkbox-ui {
            border-color: var(--issue-green);
            background: var(--issue-green);
        }

        .gu-issue-check input:checked+.gu-issue-checkbox-ui:after {
            width: 7px;
            height: 4px;
            border-bottom: 2px solid #fff;
            border-left: 2px solid #fff;
            content: "";
            transform: translateY(-1px) rotate(-45deg);
        }

        .gu-issue-check input:focus-visible+.gu-issue-checkbox-ui {
            outline: 2px solid var(--issue-accent);
            outline-offset: 2px;
        }

        .gu-issue-optional {
            color: var(--issue-muted);
            font-size: .65rem;
            font-weight: 400;
        }

        .gu-issue-items-card {
            padding-bottom: 12px;
        }

        .gu-issue-items-heading {
            align-items: center;
        }

        .gu-issue-add-row-wrap {
            display: flex;
            justify-content: flex-start;
            margin-top: 10px;
        }

        .gu-issue-add-row-button {
            display: flex;
            width: 100%;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1.5px dashed #cbd5d8;
            border-radius: 7px;
            background: #fff;
            color: var(--brand-primary);
            font-size: .78rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s ease;
        }

        .gu-issue-add-row-button:hover {
            border-color: var(--brand-primary);
            background: #f6faf8;
        }

        .gu-issue-heading-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 6px;
        }

        .gu-issue-outline-button {
            min-height: 34px;
            padding: 6px 9px;
            font-size: .72rem;
        }

        .gu-issue-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e1e9e6;
            border-radius: 7px;
            background: #fff;
        }

        .gu-issue-entry-table,
        .gu-issue-history-table,
        .gu-issue-review-table {
            width: 100%;
            border-collapse: collapse;
        }

        .gu-issue-entry-table {
            min-width: 820px;
            table-layout: fixed;
            font-size: .78rem;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-entry-table th {
            padding: 9px 10px;
            background: #f4f6f7;
            border-bottom: 2px solid #e1e8e5;
            color: #4e5f59;
            font-size: .68rem;
            font-weight: 750;
            letter-spacing: .03em;
            text-align: left;
            text-transform: uppercase;
        }

        .gu-issue-history-table th,
        .gu-issue-review-table th {
            padding: 8px 9px;
            border-bottom: 1px solid var(--issue-border);
            background: #f5f4f1;
            color: #4b5a56;
            font-size: .66rem;
            font-weight: 750;
            letter-spacing: .035em;
            text-align: left;
            text-transform: uppercase;
        }

        .gu-issue-entry-table td,
        .gu-issue-history-table td,
        .gu-issue-review-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #edf0f1;
            color: var(--issue-text);
            font-size: .75rem;
            vertical-align: middle;
        }

        .gu-issue-entry-table tbody tr:last-child td,
        .gu-issue-history-table tbody tr:last-child td,
        .gu-issue-review-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-issue-entry-table tbody tr:hover,
        .gu-issue-history-table tbody tr:hover,
        .gu-issue-review-table tbody tr:hover {
            background: #faf9f6;
        }

        .gu-issue-entry-table tbody tr.has-warning td:first-child {
            border-left: 3px solid var(--issue-warning);
        }

        .gu-issue-entry-table tbody tr.has-warning td:first-child {
            padding-left: 5px;
        }

        .gu-issue-row-number {
            color: var(--issue-muted) !important;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            text-align: center;
        }

        .gu-issue-unit {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 5px;
            background: #f1f4f3;
            color: #3b4d47 !important;
            font-size: .72rem;
            font-weight: 650;
        }

        .gu-issue-number {
            width: 100%;
            min-width: 72px;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-entry-table .form-control {
            min-height: 35px;
            height: 35px;
            padding: 6px 9px;
            border: 1px solid #dce5e2;
            border-radius: 6px;
            font-size: .78rem;
            background: #fff;
            width: 100%;
            box-sizing: border-box;
        }

        .gu-issue-entry-table .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 2px rgba(20, 92, 84, .1);
            outline: none;
        }

        .gu-issue-entry-table .is-number {
            text-align: right;
        }

        .gu-issue-entry-table tfoot td {
            border-top: 1px solid var(--issue-border);
            border-bottom: 0;
            background: #faf9f6;
            color: var(--issue-muted);
            font-size: .68rem;
            padding: 8px 10px;
        }

        .gu-issue-entry-table tfoot td strong {
            color: var(--issue-text);
        }

        .gu-issue-stock-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 5px;
            margin-top: 3px;
            color: var(--issue-muted);
            font-size: .65rem;
        }

        .gu-issue-stock-line button {
            border: 0;
            background: transparent;
            color: var(--issue-green);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .gu-issue-row-warning {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            margin-top: 5px;
            color: #744a0a;
            font-size: .65rem;
            line-height: 1.35;
        }

        .gu-issue-warning-mark {
            display: inline-grid;
            width: 15px;
            height: 15px;
            flex: 0 0 15px;
            place-items: center;
            border-radius: 50%;
            background: var(--issue-warning);
            color: #1f2523;
            font-size: .62rem;
            font-weight: 800;
        }

        .gu-issue-remove-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            min-height: 28px;
            padding: 0;
            border: 1px solid #e6ceca;
            border-radius: 5px;
            background: #fff;
            color: #963b35;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            transition: all .15s ease;
        }

        .gu-issue-remove-button:hover {
            background: #fff6f5;
            border-color: #dfb2ad;
        }

        .gu-issue-remove-button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .gu-issue-stock-note {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin-top: 10px;
            color: var(--issue-muted);
            font-size: .68rem;
            line-height: 1.4;
        }

        .gu-issue-notes-field {
            display: grid;
            gap: 4px;
            margin-top: 12px;
            color: #354640;
            font-size: .72rem;
            font-weight: 650;
        }

        .gu-issue-notes-field small {
            color: var(--issue-muted);
            font-weight: 400;
        }

        .gu-issue-notes-field textarea {
            min-height: 54px;
            padding: 7px 9px;
            border: 1px solid var(--issue-border);
            border-radius: 5px;
            font: inherit;
            font-size: .75rem;
            resize: vertical;
        }

        .gu-issue-draft-status {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-draft-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #6b897c;
        }

        .gu-issue-key-hint {
            margin-left: auto;
        }

        .gu-issue-sticky-actions {
            position: sticky;
            z-index: 10;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 0 -12px -12px;
            padding: 10px 12px;
            border: 1px solid var(--issue-border);
            border-radius: 8px 8px 0 0;
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 -3px 12px rgba(31, 37, 35, .06);
        }

        .gu-issue-sticky-actions>div {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 7px;
        }

        /* Saved Flash Alert */
        .gu-issue-saved-flash {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
            padding: 10px 14px;
            border: 1px solid #86efac;
            border-radius: 8px;
            background: #f0fdf4;
            color: #14532d;
            font-size: .76rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(22, 101, 52, .1);
        }

        .gu-issue-saved-flash-content {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .gu-issue-saved-flash-icon {
            display: inline-grid;
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            place-items: center;
            border-radius: 50%;
            background: #22c55e;
            color: #fff;
            font-size: .75rem;
            font-weight: 800;
        }

        .gu-issue-saved-flash-close {
            border: 0;
            background: transparent;
            color: #166534;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            padding: 0 4px;
            line-height: 1;
        }

        /* Section Pengeluaran Tersimpan Hari Ini */
        .gu-issue-today-section {
            margin-top: 18px;
            border-top: 3px solid var(--issue-green);
        }

        .gu-issue-today-heading-text h2 {
            margin: 0;
            font-size: .88rem;
            font-weight: 750;
        }

        .gu-issue-today-heading-text p {
            margin: 3px 0 0;
            color: var(--issue-muted);
            font-size: .69rem;
        }

        .gu-issue-today-empty {
            padding: 24px 16px;
            text-align: center;
            color: var(--issue-muted);
        }

        .gu-issue-today-empty-icon {
            font-size: 1.8rem;
            margin-bottom: 6px;
            opacity: .7;
        }

        .gu-issue-today-empty h4 {
            margin: 0 0 4px;
            color: #2c3a35;
            font-size: .82rem;
            font-weight: 700;
        }

        .gu-issue-today-empty p {
            margin: 0;
            font-size: .72rem;
        }

        .gu-issue-today-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
        }

        .gu-issue-today-table th {
            padding: 8px 10px;
            border-bottom: 1px solid var(--issue-border);
            background: #f5f4f1;
            color: #4b5a56;
            font-size: .66rem;
            font-weight: 750;
            letter-spacing: .035em;
            text-align: left;
            text-transform: uppercase;
        }

        .gu-issue-today-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #efeee9;
            color: var(--issue-text);
            font-size: .73rem;
            vertical-align: middle;
        }

        .gu-issue-today-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-issue-today-table tbody tr:hover {
            background: #faf9f6;
        }

        .gu-issue-today-row.is-just-saved {
            background: #f0fdf4 !important;
            border-left: 3px solid #22c55e;
            transition: background .4s ease;
        }

        .gu-issue-today-dept {
            color: var(--issue-green);
            font-weight: 750;
            font-size: .77rem;
        }

        .gu-issue-today-time {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-weight: 650;
            font-size: .72rem;
            color: #1e293b;
        }

        .gu-issue-today-items-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .gu-issue-today-item-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 7px;
            border-radius: 5px;
            background: #fff;
            border: 1px solid #d2ded9;
            font-size: .68rem;
            color: #2d3748;
        }

        .gu-issue-today-item-badge strong {
            color: #0f172a;
        }

        .gu-issue-today-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .gu-issue-today-btn-view {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 4px 9px;
            border: 1px solid #216b52;
            border-radius: 5px;
            background: #f4fbf8;
            color: #216b52;
            font: inherit;
            font-size: .68rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .15s ease;
        }

        .gu-issue-today-btn-view:hover {
            background: #216b52;
            color: #fff;
        }

        .gu-issue-history-table tbody tr.is-active-row {
            background: #f0f7f4;
        }

        .gu-issue-history-subnav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--issue-border);
            padding-bottom: 10px;
        }

        .gu-issue-subnav-btn {
            padding: 7px 15px;
            border-radius: 6px;
            border: 1px solid #d2ded9;
            background: #fff;
            color: #3e544c;
            font: inherit;
            font-size: .74rem;
            font-weight: 650;
            cursor: pointer;
            transition: all .15s ease;
        }

        .gu-issue-subnav-btn:hover {
            background: #f4f8f6;
            border-color: var(--issue-green);
            color: var(--issue-green);
        }

        .gu-issue-subnav-btn.is-active {
            background: var(--issue-green);
            border-color: var(--issue-green);
            color: #fff;
            font-weight: 750;
            box-shadow: 0 2px 6px rgba(33, 107, 82, .2);
        }

        .gu-issue-history-table {
            min-width: 620px;
        }

        .gu-issue-history-table td>strong,
        .gu-issue-history-table td>small {
            display: block;
        }

        .gu-issue-history-table td>small {
            margin-top: 3px;
            color: var(--issue-muted);
            font-size: .64rem;
        }

        .gu-issue-mono {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .68rem;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-history-item {
            display: block;
            padding: 2px 0;
        }

        .gu-issue-history-table tr.gu-issue-accordion-row {
            display: none;
        }

        .gu-issue-history-table tr.gu-issue-accordion-row.is-open {
            display: table-row;
        }

        .gu-issue-accordion-toggle {
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px 10px;
            border: 1px solid #bfd1c9;
            border-radius: 5px;
            background: #fff;
            color: var(--issue-green);
            font: inherit;
            font-size: .7rem;
            font-weight: 700;
            cursor: pointer;
        }

        .gu-issue-accordion-toggle:hover {
            background: #f3f8f6;
        }

        .gu-issue-accordion-toggle:focus-visible {
            outline: 2px solid var(--issue-green);
            outline-offset: 2px;
        }

        .gu-issue-accordion-cell {
            padding: 0 !important;
            background: #fafaf8;
        }

        .gu-issue-accordion-content {
            display: grid;
            gap: 14px;
            padding: 14px;
        }

        .gu-issue-accordion-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .gu-issue-accordion-heading h3,
        .gu-issue-accordion-content h4 {
            margin: 0;
            font-size: .84rem;
            font-weight: 720;
        }

        .gu-issue-accordion-heading p {
            margin: 4px 0 0;
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-accordion-items {
            display: grid;
            gap: 8px;
        }

        .gu-issue-item-list-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .gu-issue-item-list-heading p {
            margin: 4px 0 0;
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-item-list-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--issue-border);
            border-radius: 6px;
            background: #fff;
        }

        .gu-issue-item-list {
            width: 100%;
            min-width: 990px;
            border-collapse: collapse;
        }

        .gu-issue-item-list th {
            padding: 8px;
            border-bottom: 1px solid var(--issue-border);
            background: #f5f4f1;
            color: #4b5a56;
            font-size: .65rem;
            font-weight: 750;
            text-align: left;
            text-transform: uppercase;
        }

        .gu-issue-item-list td {
            min-width: 75px;
            padding: 8px;
            border-bottom: 1px solid #efeee9;
            color: var(--issue-text);
            font-size: .7rem;
            vertical-align: top;
        }

        .gu-issue-item-list tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-issue-item-list td>strong,
        .gu-issue-item-list td>small {
            display: block;
        }

        .gu-issue-item-list td>small {
            margin-top: 4px;
            color: var(--issue-muted);
            font-size: .64rem;
        }

        .gu-issue-item-list td.is-number {
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-item-list td[data-label="Barang"] {
            min-width: 175px;
        }

        .gu-issue-item-list td[data-label="Qty kembali"] {
            min-width: 110px;
        }

        .gu-issue-item-list td[data-label="Status"] {
            min-width: 175px;
        }

        .gu-issue-item-list td[data-label="Catatan"] {
            min-width: 145px;
        }

        .gu-issue-item-list td[data-label="Status"] .gu-issue-state-badge {
            display: inline-flex;
            margin: 0 3px 3px 0;
        }

        .gu-issue-item-history-note {
            padding-bottom: 4px;
            border-bottom: 1px solid #efeee9;
            overflow-wrap: anywhere;
        }

        .gu-issue-accordion-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 3px 10px;
            padding: 8px 10px;
            border: 1px solid var(--issue-border);
            border-radius: 5px;
            background: #fff;
            font-size: .72rem;
        }

        .gu-issue-accordion-item>span {
            color: var(--issue-text);
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-accordion-item>small,
        .gu-issue-accordion-note {
            grid-column: 1/-1;
            color: var(--issue-muted);
            font-size: .66rem;
        }

        .gu-issue-accordion-note {
            margin: 0;
        }

        .gu-issue-item-returns {
            display: grid;
            grid-column: 1/-1;
            gap: 6px;
            padding-top: 7px;
            border-top: 1px solid #edf0ed;
        }

        .gu-issue-item-returns>strong {
            color: var(--issue-muted);
            font-size: .66rem;
        }

        .gu-issue-return-entry {
            display: grid;
            gap: 4px;
            padding: 9px 10px;
            border: 1px solid var(--issue-border);
            border-radius: 5px;
            background: #fff;
        }

        .gu-issue-return-entry>div {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
        }

        .gu-issue-return-entry>div>strong {
            font-size: .72rem;
        }

        .gu-issue-return-entry p,
        .gu-issue-return-entry small {
            margin: 0;
            color: var(--issue-muted);
            font-size: .66rem;
        }

        .gu-issue-return-update {
            display: grid;
            max-width: 320px;
            gap: 4px;
            color: var(--issue-muted);
            font-size: .66rem;
        }

        .gu-issue-return-update .form-control {
            min-height: 36px;
        }

        .gu-issue-accordion-empty {
            margin: 0;
            color: var(--issue-muted);
            font-size: .7rem;
        }

        /* Tab Retur & Sisa Baru (Penyederhanaan & Dynamic Table) */
        .gu-issue-return-header-bar {
            margin-bottom: 14px;
            padding: 12px;
            border: 1px solid #dbe6e1;
            border-radius: 8px;
            background: #fbfdfc;
        }

        .gu-issue-return-dept-field {
            max-width: 440px;
            margin: 0;
        }

        .gu-issue-select-large {
            min-height: 42px;
            font-size: .84rem;
            font-weight: 600;
            color: #1e2d27;
            border-color: #99b8ac;
            background-color: #fff;
        }

        .gu-issue-select-large:focus {
            border-color: var(--issue-green);
            box-shadow: 0 0 0 3px rgba(33, 107, 82, .14);
        }

        .gu-issue-return-active-area {
            display: grid;
            gap: 12px;
            margin-bottom: 18px;
            padding: 14px;
            border: 1px solid #cce0d8;
            border-radius: 8px;
            background: #f7faf8;
        }

        .gu-issue-return-table-header {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dce8e2;
        }

        .gu-issue-return-table-title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
        }

        .gu-issue-return-table-title h3 {
            margin: 0;
            font-size: .88rem;
            font-weight: 750;
            color: #1e2d27;
            letter-spacing: -.01em;
        }

        .gu-issue-return-table-title h3 strong {
            color: var(--issue-green);
        }

        .gu-issue-return-table-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 7px;
            border-radius: 4px;
            background: #e1ece7;
            color: #235745;
            font-size: .66rem;
            font-weight: 750;
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        }

        .gu-issue-return-table-header p {
            margin: 0;
            color: var(--issue-muted);
            font-size: .72rem;
        }

        .gu-issue-dynamic-return-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--issue-border);
        }

        .gu-issue-dynamic-return-table th {
            padding: 10px 12px;
            background: #eff4f1;
            border-bottom: 2px solid #d3e1db;
            color: #314941;
            font-size: .68rem;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .gu-issue-dynamic-return-table td {
            padding: 11px 12px;
            border-bottom: 1px solid #f0f2f1;
            color: var(--issue-text);
            font-size: .76rem;
            vertical-align: middle;
        }

        .gu-issue-dynamic-return-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .gu-issue-dynamic-return-table tbody tr:hover td {
            background: #fafcfb;
        }

        .gu-issue-row-number {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-weight: 700;
            color: var(--issue-muted);
            font-size: .72rem;
            text-align: center;
        }

        .gu-issue-item-title {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            color: #1a2823;
        }

        .gu-issue-returned-note {
            display: inline-block;
            margin-top: 2px;
            padding: 2px 6px;
            border-radius: 3px;
            background: #eff6f3;
            color: #2d6954;
            font-size: .65rem;
            font-weight: 600;
        }

        .gu-issue-total-taken {
            display: inline-block;
            font-weight: 700;
            font-size: .8rem;
            color: #20312b;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-return-input-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .gu-issue-return-input {
            width: 88px !important;
            min-height: 36px;
            padding: 4px 8px;
            font-size: .82rem;
            font-weight: 700;
            text-align: right;
            border: 1.5px solid #238b67 !important;
            background: #fcfffd !important;
            border-radius: 5px;
        }

        .gu-issue-return-input:focus {
            box-shadow: 0 0 0 3px rgba(35, 139, 103, .2) !important;
        }

        .gu-issue-input-unit {
            font-size: .73rem;
            font-weight: 650;
            color: #40534c;
        }

        .gu-issue-subtle-hint {
            display: block;
            margin-top: 3px;
            color: #785217;
            font-size: .65rem;
            font-weight: 600;
        }

        .gu-issue-final-usage {
            font-size: .86rem;
            font-weight: 800;
            color: #18543f;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-return-action-bar {
            display: flex;
            justify-content: flex-end;
            padding-top: 8px;
        }

        .gu-issue-save-return-btn {
            min-height: 40px;
            padding: 8px 18px;
            font-size: .78rem;
            font-weight: 750;
            letter-spacing: .01em;
            box-shadow: 0 2px 8px rgba(27, 94, 72, .22);
        }

        .gu-issue-save-return-btn:hover {
            box-shadow: 0 4px 12px rgba(27, 94, 72, .32);
            transform: translateY(-1px);
        }

        .gu-issue-empty-borrowed {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 32px 16px;
            border: 1.5px dashed #cfded7;
            border-radius: 8px;
            background: #fff;
            text-align: center;
        }

        .gu-issue-empty-icon {
            font-size: 1.8rem;
            margin-bottom: 4px;
        }

        .gu-issue-empty-borrowed strong {
            color: #273d35;
            font-size: .8rem;
        }

        .gu-issue-empty-borrowed p {
            margin: 0;
            color: var(--issue-muted);
            font-size: .7rem;
            max-width: 440px;
        }

        .gu-issue-return-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 7px;
            border-radius: 4px;
            background: #e8f4ee;
            color: #1c694e;
            font-size: .7rem;
            font-weight: 750;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-return-history-heading {
            margin: 0;
            font-size: .82rem;
            font-weight: 720;
            margin-top: 18px;
        }

        .gu-issue-row-actions {
            display: flex;
            gap: 4px;
        }

        .gu-issue-row-actions button,
        .gu-issue-table-footer button {
            min-height: 30px;
            padding: 4px 7px;
            border: 1px solid var(--issue-border);
            border-radius: 4px;
            background: #fff;
            color: var(--issue-green);
            font: inherit;
            font-size: .66rem;
            font-weight: 650;
            cursor: pointer;
        }

        .gu-issue-row-actions button:hover {
            background: #f3f8f6;
        }

        .gu-issue-filters {
            display: grid;
            grid-template-columns: minmax(200px, 1.5fr) repeat(3, minmax(125px, .7fr)) auto;
            align-items: center;
            gap: 8px;
            margin-bottom: 11px;
        }

        .gu-issue-filters .form-control {
            width: 100%;
            min-width: 0;
            min-height: 36px;
            padding: 6px 9px;
            border: 1px solid var(--issue-border);
            border-radius: 5px;
            background: #fff;
            color: var(--issue-text);
            font-size: .73rem;
        }

        .gu-issue-filter-check {
            white-space: nowrap;
        }

        .gu-issue-table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 10px;
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-table-footer>div {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .gu-issue-table-footer>div>span {
            display: grid;
            min-width: 28px;
            height: 28px;
            place-items: center;
            border-radius: 4px;
            background: var(--issue-green);
            color: #fff;
            font-weight: 700;
        }

        .gu-issue-table-footer button:disabled {
            color: #9aa39f;
            cursor: not-allowed;
        }

        .gu-issue-empty {
            padding: 22px !important;
            color: var(--issue-muted) !important;
            text-align: center;
        }

        .gu-issue-day-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
            padding: 12px 14px;
            border: 1px solid #d2e0dc;
            border-radius: 6px;
            background: var(--issue-info);
        }

        .gu-issue-day-banner>div:first-child,
        .gu-issue-day-count {
            display: grid;
            gap: 3px;
        }

        .gu-issue-day-banner strong {
            font-size: .82rem;
        }

        .gu-issue-day-banner span {
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-day-count {
            text-align: right;
        }

        .gu-issue-info-box {
            display: grid;
            gap: 3px;
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #d4e1dc;
            border-radius: 6px;
            background: #f5f8f6;
            color: #31584f;
            font-size: .72rem;
        }

        .gu-issue-info-box.gu-issue-info-box-warning {
            border-color: #e5d4b6;
            background: #fff8ec;
            color: #765012;
        }

        .gu-issue-info-box span {
            color: var(--issue-muted);
            line-height: 1.4;
        }

        .gu-issue-card-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }

        .gu-issue-admin-preview-note {
            display: flex;
            flex-wrap: wrap;
            gap: 5px 8px;
            padding: 9px 11px;
            border: 1px solid var(--issue-border);
            border-radius: 6px;
            background: #fff;
            color: var(--issue-muted);
            font-size: .7rem;
        }

        .gu-issue-admin-preview-note strong {
            color: var(--issue-text);
        }

        .gu-issue-admin-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
            padding: 11px;
            border: 1px solid #d2e0dc;
            border-radius: 6px;
            background: var(--issue-info);
        }

        .gu-issue-admin-summary>div {
            display: grid;
            gap: 3px;
        }

        .gu-issue-admin-summary span {
            color: var(--issue-muted);
            font-size: .66rem;
        }

        .gu-issue-admin-summary strong {
            font-size: .8rem;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-admin-summary strong.is-warning {
            color: #744a0a;
        }

        .gu-issue-review-table {
            min-width: 700px;
        }

        .gu-issue-review-table tr.has-critical td:first-child {
            border-left: 3px solid var(--issue-critical);
            padding-left: 6px;
        }

        .gu-issue-review-table tr.has-warning td:first-child {
            border-left: 3px solid var(--issue-warning);
            padding-left: 6px;
        }

        .gu-issue-muted {
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-review-sticky {
            margin-top: 0;
        }

        .gu-issue-modal-backdrop {
            position: fixed;
            z-index: 1000;
            inset: 0;
            display: grid;
            place-items: center;
            padding: 16px;
            background: rgba(20, 32, 29, .42);
        }

        .gu-issue-modal {
            width: min(100%, 460px);
            padding: 18px;
            border: 1px solid var(--issue-border);
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(20, 32, 29, .2);
        }

        .gu-issue-modal h2 {
            margin: 0;
            font-size: 1rem;
        }

        .gu-issue-modal p {
            margin: 8px 0 16px;
            color: var(--issue-muted);
            font-size: .78rem;
            line-height: 1.5;
        }

        .gu-issue-modal>div {
            display: flex;
            justify-content: flex-end;
            gap: 7px;
        }

        .gu-issue-workflow-modal {
            width: min(100%, 620px);
            max-height: 90vh;
            overflow-y: auto;
        }

        .gu-issue-modal-heading {
            align-items: flex-start;
            justify-content: space-between !important;
            margin-bottom: 12px;
        }

        .gu-issue-modal-heading p {
            margin: 4px 0 0;
        }

        .gu-issue-modal-field {
            margin-top: 10px;
        }

        .gu-issue-modal-confirm {
            align-items: flex-start;
            margin-top: 12px;
            line-height: 1.4;
        }

        .gu-issue-modal-error {
            padding: 8px 10px;
            border-radius: 5px;
            background: #fff3f2;
            color: #8b342e !important;
        }

        .gu-issue-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 7px;
            margin-top: 14px;
        }

        .gu-issue-finalize-actions {
            align-items: flex-end;
            gap: 10px;
        }

        .gu-issue-finalize-actions>.gu-issue-field {
            width: min(100%, 220px);
        }

        .gu-issue-subheading {
            margin: 18px 0 8px;
            font-size: .82rem;
        }

        .gu-issue-bpb-modal {
            width: min(100%, 720px);
        }

        .gu-issue-bpb-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-bpb-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--issue-text);
        }

        .gu-issue-bpb-header p {
            margin: 0 0 5px;
            font-size: .72rem;
            font-weight: 750;
        }

        .gu-issue-bpb-header h2 {
            margin: 0;
            font-size: 1rem;
        }

        .gu-issue-bpb-header span {
            display: block;
            margin-top: 4px;
            color: var(--issue-muted);
            font-size: .68rem;
        }

        .gu-issue-bpb-header>strong {
            font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            font-size: .72rem;
        }

        .gu-issue-bpb-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 9px 18px;
            padding: 14px 0;
        }

        .gu-issue-bpb-meta>div,
        .gu-issue-bpb-signatures>div {
            display: grid;
            gap: 3px;
        }

        .gu-issue-bpb-meta span,
        .gu-issue-bpb-signatures span {
            color: var(--issue-muted);
            font-size: .66rem;
        }

        .gu-issue-bpb-meta strong,
        .gu-issue-bpb-signatures strong {
            font-size: .75rem;
        }

        .gu-issue-bpb-table {
            width: 100%;
            border-collapse: collapse;
        }

        .gu-issue-bpb-table th,
        .gu-issue-bpb-table td {
            padding: 7px;
            border: 1px solid var(--issue-border);
            font-size: .7rem;
            text-align: left;
        }

        .gu-issue-bpb-table th {
            background: #f5f4f1;
            font-size: .65rem;
            text-transform: uppercase;
        }

        .gu-issue-bpb-table .is-number {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .gu-issue-bpb-signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin: 22px 0;
        }

        .gu-issue-bpb-signatures>div {
            min-height: 70px;
            align-content: end;
            padding-top: 22px;
            border-top: 1px solid var(--issue-border);
        }

        .gu-issue-bpb-signatures small {
            color: var(--issue-muted);
            font-size: .64rem;
        }

        .gu-issue-bpb-print-button {
            display: flex;
            margin-left: auto;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media(max-width:1000px) {
            .gu-issue-document-fields {
                grid-template-columns: 1fr auto;
            }

            .gu-issue-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gu-issue-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media(max-width:700px) {
            .gu-issue-document-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .gu-issue-document-fields {
                grid-template-columns: 1fr;
                gap: 9px;
            }

            .gu-issue-time-meta-badge {
                width: 100%;
                box-sizing: border-box;
                justify-content: flex-start;
            }

            .gu-issue-copy-btn {
                width: 100%;
                justify-content: center;
            }

            .gu-issue-page {
                gap: 10px;
                padding-bottom: 110px;
            }

            .gu-issue-page-header {
                flex-direction: column;
            }

            .gu-issue-header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .receipt-tabs,
            .gu-issue-tabs {
                display: flex;
                gap: 6px;
                border: 0;
                border-bottom: 2px solid var(--surface-border);
            }

            .receipt-tabs button,
            .gu-issue-tabs button {
                min-width: 0;
                min-height: 44px;
                flex: 1 1 auto;
                padding: 8px 10px;
                border: 1px solid var(--surface-border);
                border-bottom: 0;
                border-radius: 6px 6px 0 0;
                background: #fff;
                font-size: .8125rem;
                line-height: 1.3;
            }

            .receipt-tabs button.active,
            .receipt-tabs button.is-active,
            .gu-issue-tabs button.active,
            .gu-issue-tabs button.is-active {
                border-color: var(--brand-primary);
                background: var(--brand-primary);
                color: #fff;
            }

            .gu-issue-card {
                padding: 11px;
            }

            .gu-issue-form-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 9px;
            }

            .gu-issue-field-full {
                grid-column: auto;
            }

            .gu-issue-field .form-control,
            .gu-issue-requester-control .form-control {
                min-height: 42px;
                font-size: .82rem;
            }

            .gu-issue-items-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .gu-issue-heading-buttons {
                width: 100%;
                justify-content: flex-start;
            }

            .gu-issue-heading-buttons button {
                min-height: 40px;
            }

            .gu-issue-items-card .gu-issue-table-wrap {
                overflow: visible;
                border: 0;
                background: transparent;
            }

            .gu-issue-entry-table {
                display: block;
                min-width: 0;
            }

            .gu-issue-entry-table thead {
                display: none;
            }

            .gu-issue-entry-table tbody {
                display: grid;
                gap: 7px;
            }

            .gu-issue-entry-table tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(80px, .65fr);
                gap: 7px 9px;
                padding: 9px;
                border: 1px solid var(--issue-border);
                border-radius: 6px;
                background: #fff;
            }

            .gu-issue-entry-table tbody tr.has-warning td:first-child {
                border-left: 0;
                padding-left: 8px;
            }

            .gu-issue-entry-table tbody td {
                display: grid;
                min-width: 0;
                align-content: start;
                gap: 3px;
                padding: 0;
                border: 0;
            }

            .gu-issue-entry-table tbody td:before {
                color: var(--issue-muted);
                content: attr(data-label);
                font-size: .66rem;
                font-weight: 700;
            }

            .gu-issue-entry-table tbody td[data-label="Nama Barang"],
            .gu-issue-entry-table tbody td[data-label="Catatan opsional"],
            .gu-issue-entry-table tbody td[data-label="Aksi"] {
                grid-column: 1/-1;
            }

            .gu-issue-entry-table tbody td[data-label="No."] {
                display: none;
            }

            .gu-issue-entry-table tbody td[data-label="Satuan"] {
                align-content: center;
            }

            .gu-issue-entry-table tbody td[data-label="Aksi"] {
                justify-items: start;
            }

            .gu-issue-entry-table tfoot {
                display: none;
            }

            .gu-issue-stock-note {
                font-size: .66rem;
            }

            .gu-issue-filters {
                grid-template-columns: minmax(0, 1fr);
            }

            .gu-issue-filter-check {
                min-height: 36px;
            }

            .gu-issue-history-table,
            .gu-issue-review-table {
                min-width: 0;
            }

            .gu-issue-history-table thead,
            .gu-issue-review-table thead {
                display: none;
            }

            .gu-issue-history-table,
            .gu-issue-history-table tbody,
            .gu-issue-history-table tr,
            .gu-issue-history-table td,
            .gu-issue-review-table,
            .gu-issue-review-table tbody,
            .gu-issue-review-table tr,
            .gu-issue-review-table td {
                display: block;
                width: 100%;
            }

            .gu-issue-history-table tbody,
            .gu-issue-review-table tbody {
                display: grid;
                gap: 7px;
                padding: 7px;
            }

            .gu-issue-history-table tr,
            .gu-issue-review-table tr {
                padding: 8px;
                border: 1px solid var(--issue-border);
                border-radius: 6px;
            }

            .gu-issue-history-table td,
            .gu-issue-review-table td {
                display: grid;
                grid-template-columns: minmax(95px, .7fr) minmax(0, 1.3fr);
                gap: 8px;
                padding: 5px 4px;
                border: 0;
                text-align: left;
            }

            .gu-issue-history-table tr.gu-issue-accordion-row {
                display: none;
                padding: 0;
                border: 0;
                background: transparent;
            }

            .gu-issue-history-table tr.gu-issue-accordion-row.is-open {
                display: block;
            }

            .gu-issue-history-table td.gu-issue-accordion-cell {
                display: block;
                padding: 0 !important;
            }

            .gu-issue-history-table td.gu-issue-accordion-cell:before {
                display: none;
            }

            .gu-issue-accordion-content {
                padding: 10px;
            }

            .gu-issue-accordion-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .gu-issue-accordion-heading .gu-issue-outline-button {
                min-height: 40px;
            }

            .gu-issue-item-list-heading {
                align-items: stretch;
                flex-direction: column;
            }

            .gu-issue-item-list-wrap {
                overflow: visible;
                border: 0;
                background: transparent;
            }

            .gu-issue-item-list {
                min-width: 0;
            }

            .gu-issue-item-list thead {
                display: none;
            }

            .gu-issue-item-list tbody {
                display: grid;
                gap: 8px;
            }

            .gu-issue-item-list tbody tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                padding: 9px;
                border: 1px solid var(--issue-border);
                border-radius: 6px;
                background: #fff;
            }

            .gu-issue-item-list tbody td {
                display: grid;
                min-width: 0 !important;
                align-content: start;
                gap: 4px;
                padding: 0;
                border: 0;
                overflow-wrap: anywhere;
            }

            .gu-issue-item-list tbody td:before {
                color: var(--issue-muted);
                content: attr(data-label);
                font-size: .65rem;
                font-weight: 700;
            }

            .gu-issue-item-list tbody td[data-label="Barang"],
            .gu-issue-item-list tbody td[data-label="Status"],
            .gu-issue-item-list tbody td[data-label="Catatan"] {
                grid-column: 1/-1;
            }

            .gu-issue-dynamic-return-table {
                min-width: 0;
            }

            .gu-issue-dynamic-return-table thead {
                display: none;
            }

            .gu-issue-dynamic-return-table tbody {
                display: grid;
                gap: 8px;
            }

            .gu-issue-dynamic-return-table tbody tr {
                display: grid;
                grid-template-columns: 1fr;
                gap: 6px;
                padding: 10px;
                border: 1px solid var(--issue-border);
                border-radius: 6px;
                background: #fff;
            }

            .gu-issue-dynamic-return-table tbody td {
                display: grid;
                grid-template-columns: minmax(120px, .9fr) minmax(0, 1.1fr);
                gap: 6px;
                padding: 4px 0;
                border: 0;
            }

            .gu-issue-dynamic-return-table tbody td:before {
                color: var(--issue-muted);
                content: attr(data-label);
                font-size: .65rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .gu-issue-dynamic-return-table td[data-label="NO"] {
                display: none;
            }

            .gu-issue-save-return-btn {
                width: 100%;
                justify-content: center;
                min-height: 44px;
            }

            .gu-issue-history-table td:before,
            .gu-issue-review-table td:before {
                color: var(--issue-muted);
                content: attr(data-label);
                font-size: .65rem;
                font-weight: 700;
            }

            .gu-issue-history-table tr.has-critical td:first-child,
            .gu-issue-review-table tr.has-critical td:first-child {
                border-left: 3px solid var(--issue-critical);
                padding-left: 7px;
            }

            .gu-issue-history-table tr.has-warning td:first-child,
            .gu-issue-review-table tr.has-warning td:first-child {
                border-left: 3px solid var(--issue-warning);
                padding-left: 7px;
            }

            .gu-issue-history-table td[data-label="Aksi"],
            .gu-issue-review-table td[data-label="Aksi"] {
                grid-template-columns: minmax(95px, .7fr) minmax(0, 1.3fr);
            }

            .gu-issue-row-actions {
                flex-wrap: wrap;
            }

            .gu-issue-row-actions button {
                min-height: 34px;
            }

            .gu-issue-day-banner {
                align-items: flex-start;
            }

            .gu-issue-admin-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .gu-issue-modal-backdrop {
                align-items: end;
                padding: 8px;
            }

            .gu-issue-workflow-modal {
                max-height: 92vh;
                padding: 14px;
                border-radius: 10px 10px 6px 6px;
            }

            .gu-issue-modal-heading {
                flex-direction: row !important;
            }

            .gu-issue-finalize-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .gu-issue-finalize-actions>.gu-issue-field {
                width: 100%;
            }

            .gu-issue-sticky-actions {
                position: fixed;
                right: 0;
                bottom: 0;
                left: 0;
                z-index: 100;
                margin: 0;
                padding: 8px max(10px, env(safe-area-inset-right)) calc(8px + env(safe-area-inset-bottom)) max(10px, env(safe-area-inset-left));
                border-radius: 0;
            }

            .gu-issue-sticky-actions>div {
                gap: 5px;
            }

            .gu-issue-sticky-actions .gu-issue-primary-button,
            .gu-issue-sticky-actions .gu-issue-outline-button {
                min-height: 42px;
                padding: 7px 9px;
                font-size: .7rem;
            }

            .gu-issue-bpb-header {
                flex-direction: column;
            }

            .gu-issue-bpb-meta {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media(max-width:390px) {
            .gu-issue-page-header h1 {
                font-size: 1.2rem;
            }

            .gu-issue-header-actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .gu-issue-preview-label {
                font-size: .65rem;
            }

            .receipt-tabs button,
            .gu-issue-tabs button {
                min-height: 46px;
                flex-basis: auto;
                padding: 8px 6px;
                font-size: .75rem;
            }

            .gu-issue-card-heading {
                flex-direction: column;
            }

            .gu-issue-heading-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .gu-issue-sticky-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .gu-issue-sticky-actions>div {
                width: 100%;
            }

            .gu-issue-sticky-actions>div button {
                flex: 1;
            }

            .gu-issue-key-hint {
                display: none;
            }
        }

        @media(prefers-reduced-motion:reduce) {
            .gu-issue-page * {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            .gu-issue-bpb-backdrop,
            .gu-issue-bpb-backdrop * {
                visibility: visible !important;
            }

            .gu-issue-bpb-backdrop {
                position: absolute !important;
                inset: 0 !important;
                display: block !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .gu-issue-bpb-modal {
                width: 100% !important;
                max-width: none !important;
                padding: 20px !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            .gu-issue-bpb-controls,
            .gu-issue-bpb-print-button {
                display: none !important;
            }
        }
    </style>
@endsection
