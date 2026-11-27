@push('styles')
    @vite('resources/css/pages/master-data/import/_form.css')
@endpush

<div class="mbi-page" id="master-barang-import" data-import-mode="input" data-user-id="{{ auth()->id() }}"
    data-fixed-warehouse-ids='@json($fixedWarehouseIds ?? [])' data-fixed-warehouse-names='@json($fixedWarehouseNames ?? [])'
    data-parse-url="{{ route('master.barang.import.excel') }}"
    data-analyze-url="{{ route('master.barang.import.analyze') }}"
    data-confirm-url="{{ route('master.barang.import.confirm') }}"
    data-template-url="{{ route('master.barang.import.template') }}">
    <div class="mbi-alert" data-message role="alert" aria-live="polite" aria-hidden="true"></div>
    <datalist id="mbi-category-suggestions">
        @foreach ($categorySuggestions as $categorySuggestion)
            <option value="{{ $categorySuggestion }}"></option>
        @endforeach
    </datalist>
    <datalist id="mbi-unit-suggestions">
        @foreach ($unitSuggestions as $unitSuggestion)
            <option value="{{ $unitSuggestion }}"></option>
        @endforeach
    </datalist>

    <section class="mbi-card" aria-label="Lokasi barang">
        @if ($canSelectWarehouses)
            <fieldset class="mbi-targets">
                <legend>Lokasi barang tujuan</legend>
                <p class="mbi-muted">Pilih satu atau beberapa gudang. Barang akan didaftarkan ke semua lokasi yang
                    dipilih.</p>
                <div class="mbi-target-options">
                    @foreach ($warehouses as $availableWarehouse)
                        <label class="mbi-target-option">
                            <input type="checkbox" data-warehouse-option value="{{ $availableWarehouse->id }}"
                                data-warehouse-name="{{ $availableWarehouse->nama }}">
                            <span>{{ $availableWarehouse->nama }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($warehouses->isEmpty())
                    <p class="mbi-muted" role="status">Belum ada gudang aktif yang dapat dipilih sebagai tujuan impor.
                    </p>
                @endif
            </fieldset>
        @elseif($fixedWarehouseNames->isNotEmpty())
            <p class="mbi-target-fixed">
                <strong>Lokasi gudang:</strong> {{ $fixedWarehouseNames->join(', ') }}
                <span class="mbi-muted">— ditentukan otomatis berdasarkan role dan penugasan akun.</span>
            </p>
        @else
            <p class="mbi-target-fixed" role="status">
                Gudang tujuan impor belum tersedia untuk akun ini. Hubungi administrator untuk memeriksa role dan
                penugasan gudang.
            </p>
        @endif
    </section>

    <section class="mbi-card" aria-labelledby="excel-heading">
        <div class="mbi-section-heading">
            <div>
                <h2 id="excel-heading">Unggah file Excel</h2>
                <p class="mbi-muted">Pakai template resmi agar nama sheet dan header kolom sesuai. Setelah file dipilih,
                    pemeriksaan dimulai otomatis; data belum disimpan sampai tombol Simpan ditekan.</p>
            </div>
            <a class="btn btn-outline" href="{{ route('master.barang.import.template') }}" data-turbo="false">Unduh
                template .xlsx</a>
        </div>
        <label for="master-barang-excel"><strong>Pilih file .xlsx</strong></label>
        <div class="mbi-actions">
            <input class="mbi-file" id="master-barang-excel" type="file"
                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
            <button class="btn btn-primary" type="button" data-parse-excel>Periksa file</button>
        </div>
        <p class="mbi-muted">Template memakai satu sheet “Master Barang”: panduan di baris 1, header di baris 2, dan
            data mulai baris 3. Kolom dikenali berdasarkan nama header; urutannya bebas. Kolom wajib: kode_barang,
            nama_barang, kategori, satuan.</p>
        <div class="mbi-busy" data-busy aria-hidden="true">
            <div class="mbi-progress-row"><span data-progress-label>Menyiapkan file…</span><span
                    data-progress-value></span></div>
            <progress class="mbi-progress" data-progress max="100" value="0"></progress>
        </div>
        <p class="mbi-file-feedback" data-file-feedback role="status" aria-live="polite" aria-hidden="true"></p>
    </section>

    <section class="mbi-card" aria-labelledby="paste-heading">
        <div class="mbi-section-heading">
            <div>
                <h2 id="paste-heading">Tempel dari Excel</h2>
                <p class="mbi-muted" id="mbi-reference-hint">Saat mengetik kategori atau satuan, pilih saran yang muncul
                    jika tersedia. Nilai baru tetap dapat dimasukkan.</p>
            </div>
        </div>
        <div class="mbi-grid-wrap">
            <table class="mbi-grid" aria-label="Grid tempel Master Barang">
                <colgroup>
                    <col class="col-row">
                    <col class="col-kode">
                    <col class="col-nama">
                    <col class="col-kat">
                    <col class="col-sat">
                    <col class="col-status">
                    <col class="col-aksi">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col" style="text-align:center">Baris</th>
                        <th scope="col">Kode barang <span class="mbi-required">*</span></th>
                        <th scope="col">Nama barang <span class="mbi-required">*</span></th>
                        <th scope="col">Kategori <span class="mbi-required">*</span></th>
                        <th scope="col">Satuan <span class="mbi-required">*</span></th>
                        <th scope="col">Status / catatan pemeriksaan</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody data-grid>
                    {{-- Rows are populated by master-barang-import.js (createGrid). --}}
                    {{-- Static SSR rows removed to avoid a flash-of-cleared-content on JS init. --}}
                </tbody>
            </table>
        </div>
        <section class="mbi-review-results" data-review-results aria-label="Hasil pemeriksaan" hidden>
            <div class="mbi-summary" data-summary aria-live="polite"></div>
            <nav class="mbi-pagination" data-review-pagination aria-label="Halaman data yang diperiksa">
                <button class="btn btn-outline" type="button" data-previous-page disabled>Sebelumnya</button>
                <span data-page-label aria-live="polite">Halaman 1</span>
                <button class="btn btn-outline" type="button" data-next-page disabled>Berikutnya</button>
            </nav>
            <div class="mbi-confirm">
                <p class="mbi-muted" data-import-feedback>Hasil pemeriksaan otomatis akan tampil di sini.</p>
            </div>
            <div class="mbi-busy" data-confirm-progress-wrap aria-hidden="true">
                <div class="mbi-progress-row"><span data-confirm-label>Menyiapkan impor…</span><span
                        data-confirm-value></span></div>
                <progress class="mbi-progress" data-confirm-progress max="100" value="0"></progress>
            </div>
        </section>
        <div class="mbi-busy" data-paste-busy aria-hidden="true">
            <div class="mbi-progress-row"><span data-paste-label>Memeriksa data…</span><span data-paste-value></span>
            </div>
            <progress class="mbi-progress" data-paste-progress max="100" value="0"></progress>
        </div>
    </section>
    <footer class="mbi-action-footer" aria-label="Aksi impor Master Barang">
        <button class="btn btn-outline" type="button" data-clear-grid>Kosongkan grid</button>
        <button class="btn btn-primary" type="button" data-confirm-import disabled>Simpan</button>
    </footer>
</div>
