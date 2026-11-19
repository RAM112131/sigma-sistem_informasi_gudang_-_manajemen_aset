@push('styles')
    <style>
        .mbi-page {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 0 96px;
            color: #1f2523;
        }

        .mbi-header,
        .mbi-actions,
        .mbi-section-heading,
        .mbi-progress-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .mbi-header {
            align-items: flex-start;
            margin-bottom: 22px;
        }

        .mbi-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        .mbi-subtitle {
            max-width: 760px;
            margin: 8px 0 0;
            color: #5b6b79;
        }

        .mbi-card {
            margin-bottom: 18px;
            padding: 18px;
            border: 1px solid #e2dfd9;
            border-radius: 8px;
            background: #fff;
        }

        .mbi-card h2 {
            margin: 0;
            font-size: 17px;
        }

        .mbi-card p {
            color: #5b6b79;
        }

        .mbi-targets {
            margin: 0;
        }

        .mbi-targets legend {
            margin-bottom: 8px;
            font-weight: 700;
        }

        .mbi-target-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .mbi-target-option {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid #aeb9b6;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
        }

        .mbi-target-option input {
            width: 18px;
            height: 18px;
            accent-color: #145c54;
        }

        .mbi-target-fixed {
            margin: 0;
        }

        .mbi-actions {
            justify-content: flex-start;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .mbi-action-footer {
            position: fixed;
            z-index: 25;
            right: max(0px, (100vw - var(--sidebar-width) - 1400px) / 2);
            bottom: 0;
            left: calc(var(--sidebar-width) + max(0px, (100vw - var(--sidebar-width) - 1400px) / 2));
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 12px 32px;
            border-top: 1px solid #d7ddda;
            background: rgb(255 255 255 / 96%);
            box-shadow: 0 -5px 18px rgb(26 45 37 / 8%);
            backdrop-filter: blur(8px);
        }

        .mbi-grid-wrap {
            overflow: auto;
            border: 1px solid #d7ddda;
            border-radius: 6px;
            min-height: 280px;
        }

        .mbi-grid {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .mbi-grid th,
        .mbi-grid td {
            border-right: 1px solid #e2e7e4;
            border-bottom: 1px solid #e2e7e4;
            padding: 0;
        }

        .mbi-grid th {
            height: 40px;
            padding: 8px 10px;
            background: #edf2f0;
            text-align: left;
            font-size: 12px;
            color: #31443e;
        }

        .mbi-grid col.col-row {
            width: 48px;
        }

        .mbi-grid col.col-kode {
            width: 110px;
        }

        .mbi-grid col.col-nama {
            width: 220px;
        }

        .mbi-grid col.col-kat {
            width: 130px;
        }

        .mbi-grid col.col-sat {
            width: 90px;
        }

        .mbi-grid col.col-status {
            width: auto;
        }

        .mbi-grid col.col-aksi {
            width: 76px;
        }

        .mbi-grid td:first-child {
            background: #f6f7f6;
            text-align: center;
            color: #6b7773;
            font-variant-numeric: tabular-nums;
            height: 40px;
            vertical-align: middle;
        }

        .mbi-grid tr {
            height: 40px;
        }

        .mbi-grid input {
            width: 100%;
            height: 40px;
            padding: 7px 9px;
            border: 0;
            border-radius: 0;
            outline: 0;
            background: transparent;
            color: #1f2523;
            font: inherit;
            display: block;
            box-sizing: border-box;
        }

        .mbi-grid input:focus {
            position: relative;
            z-index: 1;
            outline: 2px solid #145c54;
            outline-offset: -2px;
            background: #f2faf7;
        }

        .mbi-grid input[readonly] {
            color: #4b5753;
            cursor: default;
        }

        .mbi-grid .mbi-result-cell {
            padding: 6px;
            vertical-align: top;
            min-height: 40px;
        }

        .mbi-grid .mbi-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            background: #eef1f4;
            color: #46525e;
            font-size: 11px;
            line-height: 1.25;
            font-weight: 700;
        }

        .mbi-grid .mbi-status-new,
        .mbi-grid .mbi-status-reused,
        .mbi-grid .mbi-status-imported {
            background: #e7f4eb;
            color: #205b32;
        }

        .mbi-grid .mbi-status-duplicate,
        .mbi-grid .mbi-status-conflict,
        .mbi-grid .mbi-status-invalid {
            background: #fff0e5;
            color: #8a4516;
        }

        .mbi-result-note {
            margin: 4px 0 0;
            color: #7e2520;
            font-size: 11px;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .mbi-preparation-note {
            color: #52625c;
        }

        .mbi-grid td:last-child {
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .mbi-grid td:last-child .btn {
            min-width: 0;
            padding: 5px 8px;
            font-size: 11px;
            line-height: 1.2;
        }

        .mbi-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 16px;
        }

        .mbi-stat {
            min-width: 112px;
            padding: 8px 10px;
            border: 1px solid #e2e7e4;
            border-radius: 6px;
            background: #f8faf9;
        }

        .mbi-stat span {
            display: block;
            color: #5b6b79;
            font-size: 12px;
        }

        .mbi-stat strong {
            display: block;
            margin-top: 3px;
            font-size: 18px;
            font-variant-numeric: tabular-nums;
        }

        .mbi-pagination,
        .mbi-confirm {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
        }

        .mbi-confirm .mbi-muted {
            margin: 0;
        }

        .mbi-pagination button:disabled,
        .mbi-actions button:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .mbi-grid tr:last-child td {
            border-bottom: 0;
        }

        .mbi-muted {
            margin: 8px 0 0;
            color: #5b6b79;
            font-size: 13px;
        }

        .mbi-file {
            display: block;
            width: min(100%, 520px);
            min-height: 44px;
            padding: 9px 10px;
            border: 1px solid #aeb9b6;
            border-radius: 6px;
            background: #fff;
        }

        .mbi-alert {
            display: none;
            margin: 0 0 16px;
            padding: 12px 14px;
            border: 1px solid #e5a8a4;
            border-left: 4px solid #c1443c;
            border-radius: 6px;
            background: #fff5f4;
            color: #7e2520;
        }

        .mbi-alert[aria-hidden="false"] {
            display: block;
        }

        .mbi-success {
            border-color: #a9cbb2;
            border-left-color: #3f7a52;
            background: #f2f9f4;
            color: #205b32;
        }

        .mbi-file-feedback {
            display: none;
            margin: 12px 0 0;
            padding: 10px 12px;
            border: 1px solid #e5a8a4;
            border-left: 4px solid #c1443c;
            border-radius: 6px;
            background: #fff5f4;
            color: #7e2520;
        }

        .mbi-file-feedback[aria-hidden="false"] {
            display: block;
        }

        .mbi-file-feedback.mbi-alert-success {
            border-color: #a9cbb2;
            border-left-color: #3f7a52;
            background: #f2f9f4;
            color: #205b32;
        }

        .mbi-busy {
            display: none;
            margin-top: 14px;
        }

        .mbi-busy[aria-hidden="false"] {
            display: block;
        }

        .mbi-progress {
            width: 100%;
            height: 10px;
            accent-color: #145c54;
        }

        .mbi-required {
            color: #a32e27;
        }

        .mbi-back {
            display: inline-flex;
            align-items: center;
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid #aeb9b6;
            border-radius: 6px;
            font-weight: 600;
        }

        @media (max-width: 720px) {
            .mbi-header {
                flex-direction: column;
            }

            .mbi-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .mbi-confirm {
                align-items: stretch;
                flex-direction: column;
            }
        }

        @media (max-width: 1024px) {
            .mbi-action-footer {
                right: 0;
                left: 0;
            }
        }

        @media (max-width: 640px) {
            .mbi-action-footer {
                padding: 10px 16px;
            }

            .mbi-action-footer .btn {
                flex: 1;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
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
