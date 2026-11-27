@extends('layouts.app')

@section('title', 'Penerimaan Barang — Recording Gudang Umum — ' . config('app.name', 'SIGMA'))

@section('content')
    @php
        $normalizedGudangCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $activeGudang?->kode) ?? '');
        $normalizedGudangName = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $activeGudang?->nama) ?? '');
        $unitIdentifier =
            preg_replace('/^(?:RPA|UNIT|GUDANG)/', '', $normalizedGudangCode ?: $normalizedGudangName) ?? '';
        $unitReceiptCode = match (true) {
            str_contains($normalizedGudangCode, 'SUKAHATI') || str_contains($normalizedGudangName, 'SUKAHATI') => 'SHT',
            str_contains($normalizedGudangCode, 'TAMANSARI') || str_contains($normalizedGudangName, 'TAMANSARI')
                => 'TMS',
            default => substr($unitIdentifier, 0, 3) ?: 'UNT',
        };
    @endphp

    @php
        $canRecordPhysicalReceipt =
            auth()
                ->user()
                ?->hasAnyRole(['Super Admin', 'super_admin', 'Recording Gudang Umum', 'recording_gudang_umum']) ??
            false;
        $canDirectReceipt = $canRecordPhysicalReceipt;
        $canStoreReceipt = $canRecordPhysicalReceipt;
        $initialTab = $canDirectReceipt ? 'direct' : ($canStoreReceipt ? 'store' : 'direct');
        $receiptInitialData = [
            'directItemCatalog' => $directItemCatalog ?? [],
            'storeRequests' => $storeRequests ?? [],
            'storeReceipts' => $storeReceipts ?? [],
            'nextDirectReceiptNumber' => $nextDirectReceiptNumber ?? null,
            'directSubmitUrl' => route('unit-bisnis.recording-gu.penerimaan.store'),
            'storeSubmitUrl' => route('unit-bisnis.recording-gu.penerimaan.confirm-transfer'),
            'csrfToken' => csrf_token(),
        ];
    @endphp

    <div class="receipt-workspace" data-unit-receipt-code="{{ $unitReceiptCode }}" x-data="recordingGuReceipt($el.dataset.unitReceiptCode, {{ Js::from($receiptInitialData) }})"
        x-init="activeTab = '{{ $initialTab }}'">
        <header class="receipt-page-heading">
            <div>
                <h1>Penerimaan Barang</h1>
                <p>{{ $activeGudang?->nama ?? 'Gudang Unit aktif' }} <span aria-hidden="true">·</span> Catat penerimaan
                    barang Recording GU</p>
            </div>
            <span class="receipt-picker-status-pill pill-sudah_diterima">● Sistem Aktif Terhubung</span>
        </header>

        <nav class="receipt-tabs" role="tablist" aria-label="Sumber penerimaan barang">
            @if ($canDirectReceipt)
                <button id="receipt-tab-direct" type="button" role="tab" aria-controls="receipt-panel-direct"
                    :aria-selected="activeTab === 'direct'" :class="{ 'active': activeTab === 'direct' }"
                    @click="activeTab = 'direct'">
                    Supplier Langsung
                </button>
            @endif
            @if ($canStoreReceipt)
                <button id="receipt-tab-store" type="button" role="tab" aria-controls="receipt-panel-store"
                    :aria-selected="activeTab === 'store'" :class="{ 'active': activeTab === 'store' }"
                    @click="activeTab = 'store'">
                    Dari Permintaan Store
                </button>
            @endif
        </nav>

        <section id="receipt-panel-direct" x-show="activeTab === 'direct'" x-cloak role="tabpanel"
            aria-labelledby="receipt-tab-direct" class="receipt-tab-panel receipt-direct-panel">

            <div x-show="receiptNotice" x-cloak class="receipt-action-notice" role="status" x-text="receiptNotice"></div>

            <section class="receipt-card receipt-direct-card receipt-direct-document-card">
                <div class="receipt-direct-document-fields">
                    <div class="receipt-field">
                        <label for="direct-origin">Nama Supplier <span>*</span></label>
                        <input id="direct-origin" class="form-control" x-model="directVendor" @input="receiptNotice = ''"
                            placeholder="Nama PT, CV, atau toko supplier" required>
                    </div>
                    <div class="receipt-field">
                        <label for="direct-receipt-number">No. RCV Otomatis</label>
                        <input id="direct-receipt-number" class="form-control receipt-direct-input-mono" type="text"
                            :value="directReceiptNumber" readonly>
                    </div>
                    <div class="receipt-field">
                        <label for="direct-receipt-date">Tanggal &amp; waktu penerimaan</label>
                        <input id="direct-receipt-date" class="form-control receipt-direct-input-mono" type="datetime-local"
                            x-model="directReceiptDate" readonly style="pointer-events: none; cursor: default;"
                            title="Tanggal &amp; waktu penerimaan dibuat otomatis oleh sistem">
                    </div>
                    <div class="receipt-field">
                        <label>Surat Jalan</label>
                        <div class="receipt-sj-options" role="radiogroup" aria-label="Status Surat Jalan">
                            <label class="receipt-sj-option" :class="{ 'active': hasDeliveryNoteDirect === true }">
                                <input type="radio" name="direct_has_sj" :value="true"
                                    x-model="hasDeliveryNoteDirect" class="receipt-sj-radio">
                                <span>Ada</span>
                            </label>
                            <label class="receipt-sj-option" :class="{ 'active': hasDeliveryNoteDirect === false }">
                                <input type="radio" name="direct_has_sj" :value="false"
                                    x-model="hasDeliveryNoteDirect" class="receipt-sj-radio">
                                <span>Tidak Ada</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div x-show="hasDeliveryNoteDirect === false" x-cloak class="receipt-field" style="margin-top: 10px;">
                    <label for="direct-no-sj-reason" style="color: #b91c1c; font-weight: 700;">Alasan Tanpa Surat Jalan
                        <span style="color:#b91c1c;">*</span></label>
                    <input id="direct-no-sj-reason" class="form-control" x-model="deliveryNoteReason"
                        placeholder="Wajib: jelaskan alasan fisik barang tiba tanpa surat jalan (misal: surat jalan tertinggal di vendor/ekspedisi)">
                </div>

                <div class="receipt-table-wrap receipt-direct-table-wrap">
                    <table class="receipt-detail-table receipt-direct-table">
                        <colgroup>
                            <col style="width:22%;">
                            <col style="width:14%;">
                            <col style="width:18%;">
                            <col style="width:17%;">
                            <col style="width:25%;">
                            <col style="width:4%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Qty Diterima</th>
                                <th>Kondisi</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in directItems" :key="index">
                                <tr>
                                    <td class="receipt-direct-item-cell" data-label="Nama Barang">
                                        <template x-if="!row.selected">
                                            <div class="receipt-direct-search">
                                                <input type="search" class="form-control"
                                                    :aria-label="`Cari barang baris ${index + 1}`"
                                                    placeholder="Cari nama/kode barang" x-model="row.query"
                                                    @input="searchDirectItem(index); receiptNotice = ''"
                                                    @focus="searchDirectItem(index)" autocomplete="off">
                                                <div x-show="row.hasSearched" x-cloak
                                                    class="receipt-direct-search-results">
                                                    <template x-if="row.results.length > 0">
                                                        <div>
                                                            <template x-for="item in row.results" :key="item.id">
                                                                <button type="button"
                                                                    class="receipt-direct-search-result"
                                                                    @click="selectDirectItem(index, item)">
                                                                    <strong
                                                                        x-html="highlightDirectItem(item.name, row.query)"></strong>
                                                                    <small>Kode: <span
                                                                            x-html="highlightDirectItem(item.code, row.query)"></span><template
                                                                            x-if="item.category"><span> · <span
                                                                                    class="receipt-direct-search-category"
                                                                                    x-html="highlightDirectItem(item.category, row.query)"></span></span></template></small>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <div x-show="row.results.length === 0"
                                                        class="receipt-direct-search-empty">Tidak ada barang yang cocok
                                                        dengan kata kunci pencarian.</div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="row.selected">
                                            <div class="receipt-direct-selected-item">
                                                <div><strong x-text="row.name"></strong><small>Kode: <span
                                                            x-text="row.code"></span></small></div>
                                                <button type="button" @click="resetDirectItem(index)">Ganti</button>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="receipt-direct-category-cell" data-label="Kategori"><span
                                            class="receipt-direct-category-chip" x-text="row.category"></span></td>
                                    <td class="receipt-direct-qty-cell" data-label="Qty Diterima">
                                        <div class="receipt-direct-qty-group">
                                            <input class="form-control receipt-direct-quantity" type="number"
                                                min="0.001" step="0.001" x-model.number="row.qtyReceived"
                                                @input="receiptNotice = ''"
                                                :aria-label="`Qty diterima baris ${index + 1}`" placeholder="Qty"
                                                required>
                                            <input class="form-control receipt-direct-unit" type="text"
                                                x-model="row.unit" @blur="row.unit = abbreviatedUnit(row.unit)"
                                                list="receipt-direct-unit-options" placeholder="Satuan"
                                                :aria-label="`Satuan singkat baris ${index + 1}`">
                                        </div>
                                    </td>
                                    <td class="receipt-direct-condition-cell" data-label="Kondisi">
                                        <div class="receipt-direct-condition-fields">
                                            <select class="form-control receipt-direct-condition" x-model="row.condition"
                                                @change="if (!['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(row.condition)) row.qtyDamaged = 0; if (!['Kurang', 'Rusak dan Kurang'].includes(row.condition)) row.qtyShort = 0; if (!['Lebih', 'Rusak dan Lebih'].includes(row.condition)) row.qtyMore = 0"
                                                :aria-label="`Kondisi barang baris ${index + 1}`">
                                                <option value="Baik">Baik</option>
                                                <option value="Rusak">Rusak</option>
                                                <option value="Kurang">Kurang</option>
                                                <option value="Rusak dan Kurang">Rusak &amp; Kurang</option>
                                                <option value="Lebih">Lebih</option>
                                                <option value="Rusak dan Lebih">Rusak &amp; Lebih</option>
                                            </select>
                                            <input
                                                x-show="['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(row.condition)"
                                                x-cloak class="form-control receipt-condition-qty" type="number"
                                                min="0.001" step="0.001" :max="row.qtyReceived"
                                                x-model.number="row.qtyDamaged"
                                                :required="['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(row.condition)"
                                                placeholder="Qty rusak" :aria-label="`Qty rusak baris ${index + 1}`">
                                            <input x-show="['Kurang', 'Rusak dan Kurang'].includes(row.condition)" x-cloak
                                                class="form-control receipt-condition-qty" type="number" min="0.001"
                                                step="0.001" x-model.number="row.qtyShort"
                                                :required="['Kurang', 'Rusak dan Kurang'].includes(row.condition)"
                                                placeholder="Qty kurang" :aria-label="`Qty kurang baris ${index + 1}`">
                                            <input x-show="['Lebih', 'Rusak dan Lebih'].includes(row.condition)" x-cloak
                                                class="form-control receipt-condition-qty" type="number" min="0.001"
                                                step="0.001" x-model.number="row.qtyMore"
                                                :required="['Lebih', 'Rusak dan Lebih'].includes(row.condition)"
                                                placeholder="Qty lebih" :aria-label="`Qty lebih baris ${index + 1}`">
                                        </div>
                                    </td>
                                    <td data-label="Keterangan"><input class="form-control receipt-direct-note"
                                            type="text" x-model="row.keterangan"
                                            placeholder="Tambahkan keterangan (opsional)"
                                            :aria-label="`Keterangan barang baris ${index + 1}`"></td>
                                    <td class="receipt-direct-action-cell" data-label="Aksi">
                                        <button type="button" class="receipt-remove-row"
                                            @click="removeDirectItem(index)" :disabled="directItems.length === 1"
                                            :aria-label="`Hapus barang baris ${index + 1}`">×</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <p class="receipt-stock-impact-note">
                    <strong>Dampak stok:</strong> perkiraan masuk stok per barang = qty diterima − qty rusak (minimum 0).
                    Qty kurang/lebih adalah selisih terhadap permintaan, bukan pengurang stok tambahan. Nilai persediaan
                    belum ditetapkan pada pencatatan fisik; cost ditinjau terpisah.
                </p>
                <datalist id="receipt-direct-unit-options">
                    <option value="pcs"></option>
                    <option value="roll"></option>
                    <option value="kg"></option>
                    <option value="l"></option>
                    <option value="g"></option>
                    <option value="ml"></option>
                    <option value="pack"></option>
                    <option value="dus"></option>
                    <option value="sak"></option>
                    <option value="lbr"></option>
                    <option value="m"></option>
                    <option value="ton"></option>
                </datalist>
                <button type="button" class="receipt-add-row receipt-add-direct-row" @click="addDirectItem()">＋ Tambah
                    Barang</button>
            </section>


            <footer class="receipt-direct-action-bar">
                <div class="receipt-direct-status">
                    <span class="receipt-status-pulse" aria-hidden="true"></span>
                    <div><strong>Pencatatan Lapangan Langsung</strong><span>Simpan penerimaan supplier langsung ke database
                            unit.</span></div>
                </div>
                <div class="receipt-direct-actions">
                    <button type="button" class="receipt-secondary-button" @click="cancelDirectReceipt()">Kosongkan
                        Form</button>
                    <button type="button" class="receipt-primary-action" :disabled="isSubmittingDirect"
                        @click="submitDirectReceipt()">
                        <span x-show="!isSubmittingDirect"><svg class="recording-inline-icon" aria-hidden="true"
                                viewBox="0 0 24 24">
                                <path d="M5 3h12l4 4v14H3V3z" />
                                <path d="M7 3v6h10V3M7 21v-8h10v8" />
                            </svg> Simpan Penerimaan</span>
                        <span x-show="isSubmittingDirect" x-cloak>Menyimpan ke Database...</span>
                    </button>
                </div>
            </footer>

            @if (!empty($penerimaans) && count($penerimaans) > 0)
                <section class="receipt-card receipt-direct-card" style="margin-top: 14px;">
                    <div class="receipt-card-heading">
                        <div>
                            <h2 style="font-size: 0.95rem; font-weight: 750;">Riwayat Penerimaan Supplier Langsung</h2>
                            <p style="font-size: 0.78rem; color: var(--receipt-muted); margin-top: 2px;">Pencatatan
                                penerimaan barang dari vendor eksternal di {{ $activeGudang?->nama ?? 'gudang unit aktif' }}
                            </p>
                        </div>
                    </div>
                    <div class="receipt-table-wrap">
                        <table class="receipt-detail-table">
                            <thead>
                                <tr>
                                    <th>No. RCV</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>Barang</th>
                                    <th>Total Qty</th>
                                    <th>Surat Jalan</th>
                                    <th>Petugas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($penerimaans as $rcv)
                                    <tr>
                                        <td><strong class="receipt-direct-input-mono"
                                                style="color:var(--brand-primary); font-size:0.75rem;">{{ $rcv['id'] }}</strong>
                                        </td>
                                        <td>{{ $rcv['tanggal'] }}</td>
                                        <td><strong>{{ $rcv['sumber_asal'] }}</strong></td>
                                        <td>{{ $rcv['barang'] }}</td>
                                        <td><strong>{{ number_format((float) $rcv['qty'], 0) }}</strong>
                                            {{ $rcv['satuan'] }}</td>
                                        <td>
                                            @if ($rcv['tanpa_sj'])
                                                <span style="color:#b91c1c; font-size:0.72rem; font-weight:700;">Tanpa
                                                    SJ</span>
                                                @if (!empty($rcv['alasan']))
                                                    <small
                                                        style="display:block; color:#6b7280; font-size:0.67rem;">{{ Str::limit($rcv['alasan'], 30) }}</small>
                                                @endif
                                            @else
                                                <span style="color:#059669; font-size:0.72rem; font-weight:600;">Ada
                                                    SJ</span>
                                            @endif
                                        </td>
                                        <td>{{ $rcv['petugas'] }}</td>
                                        <td>
                                            <span class="receipt-picker-status-pill pill-menunggu_diterima">Menunggu
                                                Rekonsiliasi</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </section>

        <section id="receipt-panel-store" x-show="activeTab === 'store'" x-cloak role="tabpanel"
            aria-labelledby="receipt-tab-store" class="receipt-tab-panel receipt-store-panel">
            <div x-show="storeReceiptNotice" x-cloak class="receipt-action-notice" role="status"
                x-text="storeReceiptNotice"></div>

            <!-- Section Pemilihan Permintaan Store Berdasarkan Status -->
            <section class="receipt-card receipt-store-picker-card">
                <div class="receipt-picker-top">
                    <div class="receipt-picker-header-text">
                        <span class="receipt-picker-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                                <path d="M8 4H5a2 2 0 0 0-2 2v14h15v-3" />
                                <path d="M8 2h8v4H8zM7 11h7m-7 4h5" />
                                <path d="M14 14h7v7h-7z" />
                            </svg></span>
                        <div>
                            <h2 class="receipt-picker-title">Pilih Kiriman Store yang Tiba</h2>
                            <p class="receipt-picker-desc">Pilih nomor pengiriman / surat jalan dari Gudang Store yang
                                telah dikirim dan akan diperiksa fisiknya di gudang unit.</p>
                        </div>
                    </div>

                    <!-- Filter Status Tab -->
                    <div class="receipt-picker-filters" role="group" aria-label="Status kiriman store">
                        <span class="receipt-picker-filter-btn filter-waiting active" style="cursor:default;">
                            <span class="status-dot dot-waiting"></span> Menunggu Diterima (<span
                                x-text="storeRequests.length"></span>)
                        </span>
                    </div>
                </div>

                <!-- Dropdown Selector (Jika ada kiriman aktif) -->
                <div class="receipt-picker-selector-row" x-show="storeRequests.length > 0">
                    <div class="receipt-picker-select-wrapper">
                        <label for="store-request-picker" class="receipt-picker-label">
                            <span>Pilih Dokumen Surat Jalan / Kiriman Store</span>
                            <span class="receipt-picker-count"
                                x-text="`${storeRequests.length} kiriman siap diperiksa`"></span>
                        </label>
                        <div class="receipt-picker-select-box">
                            <select id="store-request-picker" class="form-control receipt-picker-select"
                                x-model="selectedStoreRequestId" @change="storeReceiptNotice = ''">
                                <template x-for="request in storeRequests" :key="request.id">
                                    <option :value="request.id"
                                        x-text="`${request.deliveryNote} ${request.poNumber && request.poNumber !== request.deliveryNote ? '[' + request.poNumber + ']' : ''}  ·  ${request.items.length} Barang  ·  Tgl Kirim: ${request.requestDate}`">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pesan jika tidak ada kiriman menunggu diterima -->
                <div x-show="storeRequests.length === 0"
                    style="padding:16px 20px; border-radius:8px; background:#f8faf9; border:1px dashed #cbd5e1; color:#64748b; font-size:0.85rem; display:flex; align-items:center; gap:12px; margin: 10px 0 4px;">
                    <span style="font-size:1.6rem; line-height:1;">📦</span>
                    <div>
                        <strong style="display:block; color:#334155; font-size:0.9rem; margin-bottom:2px;">Tidak ada
                            kiriman Store yang menunggu diterima</strong>
                        <span>Saat ini seluruh kiriman barang dari Store telah selesai diterima atau Store belum
                            memberangkatkan pengiriman baru. Surat Jalan akan muncul otomatis di sini ketika Store mengirim
                            barang.</span>
                    </div>
                </div>
            </section>

            <!-- Formulir & Rincian Penerimaan Barang -->
            <section class="receipt-card receipt-direct-card receipt-store-document-card"
                x-show="storeRequests.length > 0">
                <div class="receipt-store-document-fields">
                    <div class="receipt-field">
                        <label for="store-request-display">Nomor permintaan Store</label>
                        <input id="store-request-display" class="form-control receipt-direct-input-mono" type="text"
                            :value="selectedStoreRequest.id" readonly
                            style="pointer-events: none; background:#eef3f1; font-weight:700;"
                            title="Nomor permintaan dipilih pada panel di atas">
                    </div>
                    <div class="receipt-field">
                        <label for="store-receipt-number">No. RCV Otomatis</label>
                        <input id="store-receipt-number" class="form-control receipt-direct-input-mono" type="text"
                            :value="storeReceiptNumber" readonly>
                    </div>
                    <div class="receipt-field">
                        <label for="store-receipt-date">Tanggal &amp; waktu penerimaan</label>
                        <input id="store-receipt-date" class="form-control receipt-direct-input-mono"
                            type="datetime-local" x-model="storeReceiptDate" readonly
                            style="pointer-events: none; cursor: default;"
                            title="Tanggal &amp; waktu penerimaan dibuat otomatis oleh sistem">
                    </div>
                    <div class="receipt-field">
                        <label>Surat Jalan</label>
                        <div class="receipt-sj-options" role="radiogroup" aria-label="Status Surat Jalan">
                            <label class="receipt-sj-option" :class="{ 'active': hasDeliveryNoteStore === true }">
                                <input type="radio" name="store_has_sj" :value="true"
                                    x-model="hasDeliveryNoteStore" class="receipt-sj-radio">
                                <span>Ada</span>
                            </label>
                            <label class="receipt-sj-option" :class="{ 'active': hasDeliveryNoteStore === false }">
                                <input type="radio" name="store_has_sj" :value="false"
                                    x-model="hasDeliveryNoteStore" class="receipt-sj-radio">
                                <span>Tidak Ada</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="receipt-store-table-actions"
                    style="display:flex; justify-content:flex-end; margin-top:14px; margin-bottom:8px;">
                    <button type="button" class="receipt-secondary-button" @click="markStoreRequestComplete()">Isi
                        sesuai permintaan</button>
                </div>

                <div class="receipt-request-table-wrap">
                    <table class="receipt-request-table">
                        <colgroup>
                            <col style="width:4%">
                            <col style="width:19%">
                            <col style="width:7%">
                            <col style="width:8%">
                            <col style="width:5%">
                            <col style="width:7%">
                            <col style="width:22%">
                            <col style="width:28%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="receipt-request-match-heading">Sesuai</th>
                                <th>Nama Barang</th>
                                <th>Qty Request</th>
                                <th>Qty Diterima</th>
                                <th>Satuan</th>
                                <th>Selisih</th>
                                <th>Kondisi</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in selectedStoreRequest.items" :key="item.id">
                                <tr>
                                    <td class="receipt-request-match-cell" data-label="Sesuai">
                                        <input type="checkbox" class="receipt-request-match-checkbox"
                                            :checked="item.matchesRequest"
                                            @change="toggleStoreItemMatch(item, $event.target.checked)"
                                            :aria-label="`Tandai ${item.name} sesuai permintaan`">
                                    </td>
                                    <td class="receipt-request-item-cell" data-label="Nama Barang">
                                        <strong x-text="item.name"></strong>
                                        <small x-text="`Kode: ${item.sku}`"></small>
                                    </td>
                                    <td class="receipt-request-quantity-cell" data-label="Qty Request">
                                        <strong x-text="item.requestQty"></strong>
                                    </td>
                                    <td class="receipt-request-quantity-cell" data-label="Qty Diterima">
                                        <input class="form-control receipt-request-received-input" type="number"
                                            min="0" step="0.01" x-model.number="item.receivedQty"
                                            @input="updateStoreReceivedQuantity(item)"
                                            :aria-label="`Qty diterima ${item.name}`">
                                        <span class="receipt-request-mobile-unit"
                                            x-text="abbreviatedUnit(item.unit)"></span>
                                    </td>
                                    <td class="receipt-request-unit-cell" data-label="Satuan"
                                        x-text="abbreviatedUnit(item.unit)"></td>
                                    <td class="receipt-request-difference-cell" data-label="Selisih">
                                        <strong
                                            :class="storeRequestDifference(item) === 0 && Number(item.damagedQty || 0) === 0 ?
                                                'receipt-text-success' : 'receipt-text-warning'"
                                            x-text="`${storeRequestDifference(item) > 0 ? '+' : ''}${storeRequestDifference(item)} ${abbreviatedUnit(item.unit)}`"></strong>
                                    </td>
                                    <td class="receipt-request-condition-cell" data-label="Kondisi">
                                        <div class="receipt-request-condition-fields">
                                            <select class="form-control receipt-request-condition-select"
                                                x-model="item.condition" @change="setStoreItemCondition(item)"
                                                :aria-label="`Kondisi ${item.name}`">
                                                <option value="Baik">Baik</option>
                                                <option value="Kurang">Kurang</option>
                                                <option value="Lebih">Lebih</option>
                                                <option value="Rusak">Rusak</option>
                                                <option value="Rusak dan Kurang">Rusak &amp; Kurang</option>
                                                <option value="Rusak dan Lebih">Rusak &amp; Lebih</option>
                                            </select>
                                            <div class="receipt-request-condition-quantities">
                                                <input
                                                    x-show="['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'].includes(item.condition)"
                                                    x-cloak class="form-control receipt-request-condition-quantity"
                                                    type="text" inputmode="numeric" pattern="[0-9]*"
                                                    x-model.number="item.damagedQty"
                                                    @input="updateStoreDamagedQuantity(item)"
                                                    @blur="normalizeStoreDamagedQuantity(item)" placeholder="Rusak"
                                                    :aria-label="`Qty rusak ${item.name}`">
                                                <input x-show="['Kurang', 'Rusak dan Kurang'].includes(item.condition)"
                                                    x-cloak class="form-control receipt-request-condition-quantity"
                                                    type="text" inputmode="numeric" pattern="[0-9]*"
                                                    x-model.number="item.shortQty" @input="updateStoreShortQuantity(item)"
                                                    @blur="normalizeStoreShortQuantity(item)" placeholder="Kurang"
                                                    :aria-label="`Qty kurang ${item.name}`">
                                                <input x-show="['Lebih', 'Rusak dan Lebih'].includes(item.condition)"
                                                    x-cloak class="form-control receipt-request-condition-quantity"
                                                    type="text" inputmode="numeric" pattern="[0-9]*"
                                                    x-model.number="item.moreQty" @input="updateStoreMoreQuantity(item)"
                                                    placeholder="Lebih" :aria-label="`Qty lebih ${item.name}`">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="receipt-request-note-cell" data-label="Catatan">
                                        <textarea class="form-control" rows="1" x-model="item.note"
                                            @input="storeReceiptNotice = ''; $el.style.height = 'auto'; $el.style.height = `${$el.scrollHeight}px`"
                                            placeholder="Catatan (opsional)" :aria-label="`Catatan ${item.name}`"></textarea>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="receipt-request-summary">
                    <div><span>Total jenis barang</span><strong x-text="selectedStoreRequest.items.length"></strong></div>
                    <div><span>Total qty request</span><strong
                            x-text="selectedStoreRequest.items.reduce((total, item) => total + Number(item.requestQty || 0), 0)"></strong>
                    </div>
                    <div><span>Total qty diterima</span><strong x-text="storeReceivedTotal()"></strong></div>
                    <div><span>Sesuai permintaan</span><strong x-text="storeRequestCountByDifference('match')"></strong>
                    </div>
                    <div><span>Kurang / lebih</span><strong
                            x-text="`${storeRequestCountByDifference('short')} / ${storeRequestCountByDifference('over')}`"></strong>
                    </div>
                    <div><span>Ada barang rusak</span><strong x-text="storeRequestCountByDifference('damaged')"></strong>
                    </div>
                </div>
                <p class="receipt-store-explainer">Qty Request Store menjadi pembanding utama. Selisih = Qty diterima − Qty
                    request. Perkiraan masuk stok per barang = qty diterima − qty rusak (minimum 0); nilai barang tidak
                    dihitung pada tahap pencatatan fisik.</p>
            </section>

            <footer class="receipt-direct-action-bar" x-show="storeRequests.length > 0">
                <div class="receipt-direct-status">
                    <span class="receipt-status-pulse" aria-hidden="true"></span>
                    <div><strong>Pencocokan Kiriman Store</strong><span>Konfirmasi penerimaan fisik dan masukkan ke stok
                            gudang unit.</span></div>
                </div>
                <div class="receipt-direct-actions">
                    <button type="button" class="receipt-secondary-button" @click="resetStoreRequest()">Reset
                        Form</button>
                    <button type="button" class="receipt-primary-action"
                        :disabled="isSubmittingStore || selectedStoreRequest.status === 'sudah_diterima' || selectedStoreRequest
                            .status === 'dalam_pengajuan'"
                        @click="submitStoreReceipt()">
                        <span x-show="!isSubmittingStore && selectedStoreRequest.status === 'menunggu_diterima'"><svg
                                class="recording-inline-icon" aria-hidden="true" viewBox="0 0 24 24">
                                <path d="m5 12 4 4L19 6" />
                            </svg> Konfirmasi Penerimaan</span>
                        <span x-show="!isSubmittingStore && selectedStoreRequest.status === 'sudah_diterima'"><svg
                                class="recording-inline-icon" aria-hidden="true" viewBox="0 0 24 24">
                                <path d="m5 12 4 4L19 6" />
                            </svg> Sudah Diterima</span>
                        <span x-show="!isSubmittingStore && selectedStoreRequest.status === 'dalam_pengajuan'">⏳ Menunggu
                            Kiriman Store</span>
                        <span x-show="isSubmittingStore" x-cloak>Memproses...</span>
                    </button>
                </div>
            </footer>

            <section class="receipt-card receipt-direct-card" style="margin-top: 14px;" x-show="storeReceipts.length > 0">
                <div class="receipt-card-heading">
                    <div>
                        <h2 style="font-size: 0.95rem; font-weight: 750;">Riwayat Penerimaan dari Gudang Store</h2>
                        <p style="font-size: 0.78rem; color: var(--receipt-muted); margin-top: 2px;">Daftar pengiriman
                            barang dari Store yang telah selesai diperiksa dan diterima di
                            {{ $activeGudang?->nama ?? 'gudang unit aktif' }}</p>
                    </div>
                </div>
                <div class="receipt-table-wrap">
                    <table class="receipt-detail-table">
                        <thead>
                            <tr>
                                <th>No. Surat Jalan</th>
                                <th>Referensi Permintaan</th>
                                <th>Tanggal Terima</th>
                                <th>Pengirim / Asal</th>
                                <th>Barang</th>
                                <th>Total Qty</th>
                                <th>Kondisi / Discrepancy</th>
                                <th>Penerima</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="rcv in storeReceipts" :key="rcv.id">
                                <tr>
                                    <td><strong class="receipt-direct-input-mono"
                                            style="color:var(--brand-primary); font-size:0.75rem;" x-text="rcv.nomor_sj"></strong>
                                    </td>
                                    <td x-text="rcv.nomor_pr || '—'"></td>
                                    <td x-text="rcv.tanggal_terima"></td>
                                    <td>
                                        <strong x-text="rcv.gudang_asal"></strong>
                                        <template x-if="rcv.supir">
                                            <div><small style="color:#6b7280;" x-text="'Supir: ' + rcv.supir"></small></div>
                                        </template>
                                    </td>
                                    <td x-text="rcv.barang"></td>
                                    <td><strong x-text="Number(rcv.qty_diterima).toLocaleString('id-ID')"></strong>
                                        <span x-text="rcv.satuan"></span></td>
                                    <td>
                                        <template x-if="Number(rcv.qty_rusak) > 0">
                                            <span style="color:#b91c1c; font-size:0.72rem; font-weight:700;" x-text="'Rusak: ' + rcv.qty_rusak"></span>
                                        </template>
                                        <template x-if="!Number(rcv.qty_rusak) || Number(rcv.qty_rusak) <= 0">
                                            <span style="color:#059669; font-size:0.72rem; font-weight:600;">Sesuai</span>
                                        </template>
                                        <template x-if="rcv.catatan">
                                            <small style="display:block; color:#6b7280; font-size:0.67rem;" x-text="rcv.catatan"></small>
                                        </template>
                                    </td>
                                    <td x-text="rcv.penerima"></td>
                                    <td>
                                        <span class="receipt-picker-status-pill pill-sudah_diterima">Selesai
                                            Diterima</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>

    @push('styles')
    @vite('resources/css/pages/unit-bisnis/recording-gudang-umum/penerimaan/index.css')
@endpush

@endsection
