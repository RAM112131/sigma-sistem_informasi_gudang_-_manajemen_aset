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

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/recording-gudang-umum/pengeluaran/index.css')
@endpush
@endsection
