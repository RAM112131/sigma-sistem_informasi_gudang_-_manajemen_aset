@push('styles')
    @vite('resources/css/pages/unit-bisnis/stok/realtime.css')
@endpush

<div x-show="currentTab === 'stok_saat_ini'" class="physical-stock-page">
    <section class="physical-stock-metrics" aria-label="Ringkasan stok gudang">
        <article class="physical-stock-metric is-primary">
            <div>
                <span class="physical-stock-metric-title">Total Barang Terdaftar</span>
                <div class="physical-stock-metric-number">
                    {{ number_format($stockSummary['total_barang'], 0, ',', '.') }}
                    <small>Barang</small>
                </div>
                <span class="physical-stock-metric-note">Aktif di {{ $activeGudang?->nama }}</span>
            </div>
            <span class="physical-stock-metric-icon">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                    <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
                </svg>
            </span>
        </article>
        <article class="physical-stock-metric is-warning">
            <div>
                <span class="physical-stock-metric-title">Perlu Restock (Menipis)</span>
                <div class="physical-stock-metric-number">
                    {{ number_format($stockSummary['stok_menipis'], 0, ',', '.') }}
                    <small>Barang</small>
                </div>
                <span class="physical-stock-metric-note is-warning">
                    <svg aria-hidden="true" width="12" height="12" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path d="M12 3 2.8 19a1.8 1.8 0 0 0 1.6 2.7h15.2a1.8 1.8 0 0 0 1.6-2.7L12 3Z" />
                        <path d="M12 9v4m0 3h.01" />
                    </svg>
                    Stok menyentuh batas minimum
                </span>
            </div>
            <span class="physical-stock-metric-icon">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 3 2.8 19a1.8 1.8 0 0 0 1.6 2.7h15.2a1.8 1.8 0 0 0 1.6-2.7L12 3Z" />
                    <path d="M12 9v4m0 3h.01" />
                </svg>
            </span>
        </article>
        <article class="physical-stock-metric is-critical">
            <div>
                <span class="physical-stock-metric-title">Stok Kosong (Habis)</span>
                <div class="physical-stock-metric-number">
                    {{ number_format($stockSummary['stok_habis'], 0, ',', '.') }}
                    <small>Barang</small>
                </div>
                <span class="physical-stock-metric-note is-critical">
                    <svg aria-hidden="true" width="12" height="12" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m9 9 6 6m0-6-6 6" />
                    </svg>
                    Saldo fisik 0
                </span>
            </div>
            <span class="physical-stock-metric-icon">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9" />
                    <path d="m9 9 6 6m0-6-6 6" />
                </svg>
            </span>
        </article>
    </section>

    <section class="physical-stock-filter" aria-label="Pencarian dan filter stok">
        <form x-ref="stockSearchForm" action="{{ route('unit-bisnis.stok.index') }}" method="GET"
            class="physical-stock-search">
            <input type="hidden" name="tab" value="stok_saat_ini">
            @if ($activeGudang)
                <input type="hidden" name="gudang_id" value="{{ $activeGudang->id }}">
            @endif
            <label for="physical-stock-search" class="sr-only">Cari kode barang atau nama barang</label>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
            </svg>
            <input id="physical-stock-search" type="search" name="search" value="{{ $search }}"
                placeholder="Cari Kode Barang atau Nama Barang..." autocomplete="off"
                @input.debounce.500ms="searchUnitStock($event.target.value)"
                @keydown.enter.prevent="searchUnitStock($event.target.value)">
        </form>

        <form x-ref="stockFilterForm" action="{{ route('unit-bisnis.stok.index') }}" method="GET"
            class="physical-stock-controls">
            <input type="hidden" name="tab" value="stok_saat_ini">
            @if ($activeGudang)
                <input type="hidden" name="gudang_id" value="{{ $activeGudang->id }}">
            @endif
            @if ($search)
                <input type="hidden" name="search" value="{{ $search }}">
            @endif
            <label for="physical-stock-category" class="sr-only">Filter kategori</label>
            <select id="physical-stock-category" name="kategori_id" class="physical-stock-category"
                onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach ($kategoriList as $kat)
                    <option value="{{ $kat->id }}"
                        {{ (string) $kategoriId === (string) $kat->id ? 'selected' : '' }}>{{ $kat->nama }}
                    </option>
                @endforeach
            </select>
            <label for="physical-stock-status-filter" class="sr-only">Filter status stok</label>
            <select id="physical-stock-status-filter" name="status" class="physical-stock-status-filter"
                onchange="this.form.submit()">
                <option value="">Semua Status Stok</option>
                <option value="aman" {{ $statusFilter === 'aman' ? 'selected' : '' }}>Aman</option>
                <option value="warning" {{ $statusFilter === 'warning' ? 'selected' : '' }}>Menipis</option>
                <option value="kritis" {{ $statusFilter === 'kritis' ? 'selected' : '' }}>Habis</option>
            </select>
        </form>
    </section>

    <div class="physical-stock-search-state" aria-live="polite">
        <span x-show="stockSearchLoading" role="status">Memuat stok...</span>
        <span x-show="stockSearchMessage" x-text="stockSearchMessage" role="alert"></span>
    </div>
    @error('search')
        <p class="form-error" role="alert">{{ $message }}</p>
    @enderror
    @error('q')
        <p class="form-error" role="alert">{{ $message }}</p>
    @enderror
    @error('barang')
        <p class="form-error" role="alert">{{ $message }}</p>
    @enderror

    <section id="unit-stock-results" class="physical-stock-table-card" aria-label="Daftar stok barang fisik">
        <div class="physical-stock-table-wrap">
            <table class="physical-stock-table">
                <thead>
                    <tr>
                        <th scope="col" style="width:46px;text-align:center;">No.</th>
                        <th scope="col">Kode &amp; Nama Barang</th>
                        <th scope="col">Kategori &amp; Satuan</th>
                        <th scope="col" style="text-align:right;">Stok Saat Ini</th>
                        <th scope="col" style="text-align:right;">HPP Berlaku</th>
                        <th scope="col" style="text-align:center;">Status</th>
                        <th scope="col" style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barangs as $item)
                        @php
                            $brg = $item['model'];
                            $stokAktual = (float) $item['stok_aktual'];
                            $minimumStok = (float) $brg->minimum_stok;
                            $statusCode =
                                $stokAktual <= 0 ? 'habis' : ($stokAktual <= $minimumStok ? 'menipis' : 'aman');
                            $statusLabel =
                                $statusCode === 'habis' ? 'Habis' : ($statusCode === 'menipis' ? 'Menipis' : 'Aman');
                            $statusClass =
                                $statusCode === 'habis'
                                    ? 'is-empty'
                                    : ($statusCode === 'menipis'
                                        ? 'is-low'
                                        : 'is-safe');
                            $quantityDecimals = fmod(abs($stokAktual), 1.0) > 0 ? 2 : 0;
                            $minimumDecimals = fmod(abs($minimumStok), 1.0) > 0 ? 2 : 0;
                        @endphp
                        <tr class="physical-stock-row" data-category="{{ $brg->kategori?->nama ?? 'Umum' }}"
                            data-name="{{ $brg->nama }}" data-sku="{{ $brg->kode }}"
                            data-status="{{ $statusCode }}">
                            <td class="physical-stock-number" style="text-align:center;color:var(--text-muted);">
                                {{ $barangs->firstItem() + $loop->index }}</td>
                            <td>
                                <span class="physical-stock-item-code">{{ $brg->kode }}</span>
                                <span class="physical-stock-item-name">{{ $brg->nama }}</span>
                            </td>
                            <td>
                                <span
                                    class="physical-stock-category-chip">{{ $brg->kategori?->nama ?? 'Umum' }}</span>
                                <span aria-hidden="true" style="padding:0 3px;color:var(--text-muted);">•</span>
                                <span class="physical-stock-unit">{{ $brg->satuan?->nama ?? '—' }}</span>
                            </td>
                            <td class="physical-stock-number" style="text-align:right;">
                                <span
                                    class="physical-stock-current">{{ number_format($stokAktual, $quantityDecimals, ',', '.') }}</span>
                                <span class="physical-stock-minimum">Min:
                                    {{ number_format($minimumStok, $minimumDecimals, ',', '.') }}</span>
                            </td>
                            <td class="physical-stock-number" style="text-align:right;">
                                @if ((float) $brg->harga_satuan > 0)
                                    <span class="physical-stock-price">Rp
                                        {{ number_format((float) $brg->harga_satuan, 0, ',', '.') }}</span>
                                @else
                                    <span class="physical-stock-minimum">Belum diatur</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <span class="physical-stock-status {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td>
                                <div class="physical-stock-actions">
                                    <button class="physical-stock-action" type="button" title="Lihat Kartu Stok"
                                        aria-label="Lihat kartu stok {{ $brg->nama }}"
                                        @click="openStockCard('{{ route('unit-bisnis.stok.kartu', ['barang' => $brg->id, 'gudang_id' => $activeGudang?->id]) }}')">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path d="M6 3h9l4 4v14H6z" />
                                            <path d="M14 3v5h5M9 12h7m-7 4h7" />
                                        </svg>
                                        <span class="sr-only">Kartu Stok</span>
                                    </button>
                                    @if ($canEditStok)
                                        <button class="physical-stock-action" type="button"
                                            data-id="{{ $brg->id }}"
                                            data-action="{{ route('unit-bisnis.stok.update', ['barang' => $brg->id, 'gudang_id' => $activeGudang?->id]) }}"
                                            data-kode="{{ $brg->kode }}" data-nama="{{ $brg->nama }}"
                                            data-kategori-id="{{ $brg->kategori_id ?? '' }}"
                                            data-satuan-id="{{ $brg->satuan_id ?? '' }}"
                                            data-minimum-stok="{{ $brg->minimum_stok }}"
                                            data-harga-satuan="{{ $brg->harga_satuan }}"
                                            data-deskripsi="{{ $brg->deskripsi }}"
                                            aria-label="Edit data {{ $brg->nama }}" title="Edit data barang"
                                            @click="openStockEdit($event.currentTarget)">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8">
                                                <path
                                                    d="m14 5 5 5M4 20l4.2-.8L19 8.4a2.1 2.1 0 0 0-3-3L5.2 16.2 4 20Z" />
                                            </svg>
                                            <span class="sr-only">Edit</span>
                                        </button>
                                    @endif
                                    @if ($canDeleteStok)
                                        <button class="physical-stock-action is-delete" type="button"
                                            data-action="{{ route('unit-bisnis.stok.destroy', ['barang' => $brg->id, 'gudang_id' => $activeGudang?->id]) }}"
                                            data-name="{{ $brg->nama }}"
                                            aria-label="Hapus {{ $brg->nama }} dari daftar stok"
                                            title="Hapus barang dari daftar stok"
                                            @click="openStockDelete($event.currentTarget)">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8">
                                                <path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                                            </svg>
                                            <span class="sr-only">Hapus</span>
                                        </button>
                                    @endif
                                    @if ($statusCode !== 'aman' && $u?->can('permintaan_barang.create'))
                                        <a class="physical-stock-action is-request"
                                            href="{{ route('unit-bisnis.admin-gu.permintaan.index') }}"
                                            aria-label="Minta stok {{ $brg->nama }}" title="Minta stok">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8">
                                                <path d="M3 3h2l2.4 12.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6" />
                                                <circle cx="10" cy="21" r="1" />
                                                <circle cx="18" cy="21" r="1" />
                                                <path d="M12 8v6m-3-3h6" />
                                            </svg>
                                            <span class="sr-only">Minta Stok</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="physical-stock-empty">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.6">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M8 12h8" />
                                </svg>
                                <div>Tidak ada barang yang sesuai dengan pencarian atau filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="physical-stock-pagination">
            <span>
                Menampilkan <strong>{{ $barangs->firstItem() ?? 0 }}–{{ $barangs->lastItem() ?? 0 }}</strong>
                dari <strong>{{ number_format($barangs->total(), 0, ',', '.') }}</strong> Barang
            </span>
            @if ($barangs->hasPages())
                {{ $barangs->links() }}
            @endif
        </footer>
    </section>

    <div x-show="stockCardOpen" x-cloak x-transition.opacity class="stock-card-backdrop" role="presentation"
        @click.self="closeStockCard()" @keydown.escape.window="closeStockCard()">
        <section class="stock-card-modal" role="dialog" aria-modal="true" aria-labelledby="stock-card-title"
            tabindex="-1">
            <header class="stock-card-modal-header">
                <div>
                    <h2 id="stock-card-title">Kartu Stok</h2>
                    <div class="stock-card-modal-subtitle" x-show="stockCard">
                        <span x-text="stockCard?.barang?.nama"></span>
                        <span aria-hidden="true"> · </span>
                        <span x-text="stockCard?.barang?.kode"></span>
                    </div>
                </div>

                <button x-ref="stockCardClose" class="stock-card-close" type="button" aria-label="Tutup kartu stok"
                    @click="closeStockCard()">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="m18 6-12 12M6 6l12 12" />
                    </svg>
                </button>
            </header>
            <div class="stock-card-modal-body">
                <div x-show="stockCardLoading" class="stock-card-feedback" role="status">Memuat riwayat mutasi...
                </div>
                <div x-show="stockCardError" class="stock-card-feedback is-error" role="alert"
                    x-text="stockCardError"></div>
                <template x-if="stockCard && !stockCardLoading && !stockCardError">
                    <div>
                        <div class="stock-card-modal-summary">
                            <span>Kategori: <strong x-text="stockCard.barang.kategori"></strong></span>
                            <span>Satuan: <strong x-text="stockCard.barang.satuan"></strong></span>
                            <span>Minimum: <strong
                                    x-text="formatStockCardQuantity(stockCard.barang.minimum_stok)"></strong></span>
                            <span>Gudang: <strong x-text="stockCard.gudang"></strong></span>
                        </div>
                        <div class="stock-card-table-wrap">
                            <table class="stock-card-table">
                                <thead>
                                    <tr>
                                        <th>Waktu Mutasi</th>
                                        <th>Jenis Transaksi</th>
                                        <th>No. Dokumen / Referensi</th>
                                        <th>Pihak Terkait</th>
                                        <th>Qty Masuk</th>
                                        <th>Qty Keluar</th>
                                        <th>Saldo Stok</th>
                                        <th>Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="mutation in stockCard.mutasi"
                                        :key="`${mutation.ref}-${mutation.tanggal}`">
                                        <tr>
                                            <td class="physical-stock-number"
                                                x-text="formatStockCardDate(mutation.tanggal)"></td>
                                            <td><span class="badge" :class="mutation.badge"
                                                    x-text="mutation.jenis"></span></td>
                                            <td class="physical-stock-number" x-text="mutation.ref"></td>
                                            <td x-text="mutation.pihak"></td>
                                            <td class="is-in physical-stock-number"
                                                x-text="mutation.masuk > 0 ? '+' + formatStockCardQuantity(mutation.masuk) : '—'">
                                            </td>
                                            <td class="is-out physical-stock-number"
                                                x-text="mutation.keluar > 0 ? '-' + formatStockCardQuantity(mutation.keluar) : '—'">
                                            </td>
                                            <td class="is-balance physical-stock-number"><span
                                                    x-text="formatStockCardQuantity(mutation.saldo)"></span> <span
                                                    x-text="stockCard.barang.satuan"></span></td>
                                            <td x-text="mutation.petugas"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="stockCard.mutasi.length === 0">
                                        <td colspan="8" class="stock-card-feedback">Belum ada data mutasi tercatat
                                            untuk barang ini.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div id="stock-delete-confirm-modal" x-show="stockDeleteOpen" x-cloak x-transition.opacity
        class="stock-card-backdrop" role="presentation" @click.self="closeStockDelete()"
        @keydown.escape.window="closeStockDelete()">
        <section class="stock-card-modal stock-edit-modal" role="dialog" aria-modal="true"
            aria-labelledby="stock-delete-title" aria-describedby="stock-delete-description" tabindex="-1">
            <header class="stock-card-modal-header">
                <div>
                    <h2 id="stock-delete-title">Hapus barang dari daftar stok?</h2>
                    <div class="stock-card-modal-subtitle" x-text="stockDeleteForm.name"></div>
                </div>
                <button x-ref="stockDeleteClose" class="stock-edit-close" type="button"
                    aria-label="Tutup konfirmasi hapus" @click="closeStockDelete()">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="m18 6-12 12M6 6l12 12" />
                    </svg>
                </button>
            </header>
            <div class="stock-card-modal-body">
                <div id="stock-delete-description" class="stock-delete-notice" role="note">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8">
                        <path d="M12 3 2.8 20h18.4L12 3Z" />
                        <path d="M12 9v5m0 3h.01" />
                    </svg>
                    <span>
                        Penghapusan hanya dapat dilakukan jika saldo fisik sudah nol dan tidak ada transaksi yang masih
                        menunggu pencatatan atau verifikasi.
                        Riwayat transaksi yang sudah ada tetap tersimpan dan tidak ikut terhapus.
                    </span>
                </div>
                <form :action="stockDeleteForm.action" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="stock-edit-actions">
                        <button x-ref="stockDeleteCancel" class="stock-edit-button" type="button"
                            @click="closeStockDelete()">Batal</button>
                        <button class="stock-edit-button is-danger" type="submit">Hapus Barang</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

@php
    $stockEditHasErrors =
        $errors->has('kode') ||
        $errors->has('nama') ||
        $errors->has('kategori_id') ||
        $errors->has('satuan_id') ||
        $errors->has('minimum_stok') ||
        $errors->has('harga_satuan') ||
        $errors->has('deskripsi');
    $stockEditOldId = $stockEditHasErrors ? old('barang_id') : null;
@endphp
<div x-show="stockEditOpen" x-cloak x-transition.opacity class="stock-card-backdrop" role="presentation"
    @click.self="closeStockEdit()" @keydown.escape.window="closeStockEdit()"
    @if ($stockEditOldId) data-id="{{ $stockEditOldId }}"
    data-action="{{ route('unit-bisnis.stok.update', ['barang' => $stockEditOldId, 'gudang_id' => $activeGudang?->id]) }}"
    data-kode="{{ old('kode') }}"
    data-nama="{{ old('nama') }}"
    data-kategori-id="{{ old('kategori_id') }}"
    data-satuan-id="{{ old('satuan_id') }}"
    data-minimum-stok="{{ old('minimum_stok') }}"
    data-harga-satuan="{{ old('harga_satuan') }}"
    data-deskripsi="{{ old('deskripsi') }}"
    x-init="openStockEdit($el)" @endif>
    <section class="stock-card-modal stock-edit-modal" role="dialog" aria-modal="true"
        aria-labelledby="stock-edit-title" tabindex="-1">
        <header class="stock-card-modal-header">
            <div>
                <h2 id="stock-edit-title">Edit Data Barang</h2>
                <div class="stock-card-modal-subtitle">Perbarui informasi barang tanpa mengubah saldo fisik.</div>
            </div>
            <button x-ref="stockEditClose" class="stock-edit-close" type="button" aria-label="Tutup form edit"
                @click="closeStockEdit()">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m18 6-12 12M6 6l12 12" />
                </svg>
            </button>
        </header>
        <div class="stock-card-modal-body">
            <div class="stock-edit-notice" role="note">
                Saldo fisik tidak diedit langsung. Gunakan fitur stok opname untuk menyesuaikan saldo.
            </div>
            <form :action="stockEditForm.action" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="barang_id" x-model="stockEditForm.id">

                <div class="stock-edit-grid">
                    <div class="stock-edit-field">
                        <label for="stock-edit-code">Kode Barang</label>
                        <input id="stock-edit-code" x-ref="stockEditCode" name="kode"
                            x-model="stockEditForm.kode" maxlength="50" required>
                        @error('kode')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="stock-edit-field">
                        <label for="stock-edit-name">Nama Barang</label>
                        <input id="stock-edit-name" name="nama" x-model="stockEditForm.nama" maxlength="255"
                            required>
                        @error('nama')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="stock-edit-field">
                        <label for="stock-edit-category">Kategori</label>
                        <select id="stock-edit-category" name="kategori_id" x-model="stockEditForm.kategori_id">
                            <option value="">Tanpa Kategori</option>
                            @foreach ($kategoriList as $kategori)
                                <option value="{{ $kategori->id }}">{{ $kategori->nama }}</option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="stock-edit-field">
                        <label for="stock-edit-unit">Satuan</label>
                        <select id="stock-edit-unit" name="satuan_id" x-model="stockEditForm.satuan_id">
                            <option value="">Tanpa Satuan</option>
                            @foreach ($stockEditSatuanList as $satuan)
                                <option value="{{ $satuan->id }}">{{ $satuan->nama }}</option>
                            @endforeach
                        </select>
                        @error('satuan_id')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="stock-edit-field">
                        <label for="stock-edit-minimum">Stok Minimum</label>
                        <input id="stock-edit-minimum" type="number" name="minimum_stok" min="0"
                            step="1" x-model="stockEditForm.minimum_stok" required>
                        @error('minimum_stok')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="stock-edit-field">
                        <label for="stock-edit-price">HPP Berlaku (Rp)</label>
                        <input id="stock-edit-price" type="number" name="harga_satuan" min="0"
                            step="0.01" x-model="stockEditForm.harga_satuan">
                        @error('harga_satuan')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="stock-edit-field is-full">
                        <label for="stock-edit-description">Deskripsi</label>
                        <textarea id="stock-edit-description" name="deskripsi" x-model="stockEditForm.deskripsi"></textarea>
                        @error('deskripsi')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="stock-edit-actions">
                    <button class="stock-edit-button" type="button" @click="closeStockEdit()">Batal</button>
                    <button class="stock-edit-button is-primary" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </section>
</div>
