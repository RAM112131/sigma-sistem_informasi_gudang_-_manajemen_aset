# SIGMA - MASTER MENU KNOWLEDGE BASE & ARCHITECTURAL REGISTRY
> **DOKUMEN INI ADALAH MEMORI PERMANEN & PETA ARSITEKTUR SETIAP MENU DI SIGMA.**
> **PANDUAN AI:** Jangan pernah melakukan scanning seluruh direktori (`grep`, recursive search luas, atau listing rute ulang) saat pengguna meminta perbaikan/revisi menu. Langsung rujuk entri menu di bawah ini untuk membuka controller, blade view, dan model yang tepat pada turn pertama.

---

## 📑 DAFTAR ISI & KATEGORI MENU
1. [Master Data & Katalog](#1-master-data--katalog)
2. [Gudang Store (Armada / Holding)](#2-gudang-store-armada--holding)
3. [Unit Bisnis - Recording Gudang Umum (Operasional)](#3-unit-bisnis---recording-gudang-umum-operasional)
4. [Unit Bisnis - Admin Gudang Umum (Manajerial & Verifikasi)](#4-unit-bisnis---admin-gudang-umum-manajerial--verifikasi)
5. [Super Admin (Sistem & Master Lokasi)](#5-super-admin-sistem--master-lokasi)
6. [Log Analisa & Catatan Khusus Per Menu](#6-log-analisa--catatan-khusus-per-menu)

---

## 1. MASTER DATA & KATALOG

### 1.1 Master Data Hub (Barang, Kategori, Satuan, Divisi/Area)
- **Nama di Sidebar / UI:** `Master Data & Katalog` -> `Master Barang`
- **URL & Route Names:**
  - Index Hub: `master.barang.index` (`/master/barang`)
  - Kategori: `master.kategori.*` (`/master/kategori`, `store`, `update`, `toggle`)
  - Satuan: `master.satuan.*` (`/master/satuan`, `store`, `update`, `toggle`, `destroy`)
  - Area / Divisi: `master.area.*` (`/master/area`, `store`, `update`, `toggle`, `destroy`)
  - Import Barang: `master.barang.import.*` (`/master/barang/import`, `template`, `excel`, `analyze`, `confirm`)
  - Quick Add API: `/api/quick-add/check-similarity`, `/api/quick-add/barang`, `/api/quick-add/kategori`, `/api/quick-add/satuan`
- **Controllers & Methods:**
  - `App\Http\Controllers\MasterData\MasterBarangController` (`index`, `store`, `update`, `register`, `toggle`, `destroy`, `importIndex`, `downloadImportTemplate`, `parseImportExcel`, `analyzeImport`, `confirmImport`)
  - `App\Http\Controllers\MasterData\KategoriController` (`index`, `store`, `update`, `toggle`)
  - `App\Http\Controllers\MasterData\SatuanController` (`index`, `store`, `update`, `toggle`, `destroy`)
  - `App\Http\Controllers\MasterData\DivisiPemintaController` (`store`, `update`, `toggle`, `destroy`)
  - `App\Http\Controllers\Api\QuickAddController` (`checkSimilarity`, `storeBarang`, `storeKategori`, `storeSatuan`)
- **Blade Views:**
  - `resources/views/master-data/index.blade.php` (Unified multi-tab container: `#tab-barang`, `#tab-kategori`, `#tab-satuan`, `#tab-area`)
  - `resources/views/master-data/area/_panel.blade.php`
  - `resources/views/master-data/import/_form.blade.php`
  - `resources/views/components/quick-add-modal.blade.php`
- **Models & Database Tables:**
  - `App\Models\MasterBarang` (`master_barang`)
  - `App\Models\KategoriBarang` (`kategori_barang`)
  - `App\Models\Satuan` (`satuan`)
  - `App\Models\SatuanKonversi` (`satuan_konversi`)
  - `App\Models\DivisiPeminta` (`divisi_peminta`)
- **Workflow & Logika Penting:**
  - MasterBarang adalah entitas global katalog. Barang di gudang lokal merujuk ke MasterBarang.
  - Tab navigasi menggunakan hash/Alpine. Quick-add modal memungkinkan penambahan kategori/satuan tanpa meninggalkan form barang.

---

## 2. GUDANG STORE (ARMADA / HOLDING)

### 2.1 Dashboard Store
- **Nama di Sidebar / UI:** `Dashboard Store`
- **URL & Route:** `store.dashboard` (`/store/dashboard`)
- **Controller:** `App\Http\Controllers\Store\DashboardStoreController` (`__invoke`)
- **Blade View:** `resources/views/store/dashboard.blade.php`
- **Logika:** Menampilkan ringkasan stok holding, permintaan masuk dari unit, dan transfer aktif.

### 2.2 Stok Gudang Store
- **Nama di Sidebar / UI:** `Stok Gudang Store`
- **URL & Route Names:** `store.stok.index` (`/store/stok`), `store.stok.kartu`, `store.stok.import-template`, `store.stok.import-analyze`, `store.stok.import-process`
- **Controller & Methods:** `App\Http\Controllers\Store\StokStoreController` (`index`, `kartuStok`, `downloadImportTemplate`, `analyzeImport`, `processImport`)
- **Blade View:** `resources/views/store/stok/index.blade.php`
- **Models & Tables:** `App\Models\Barang` (`barang`), `App\Models\MasterBarang` (`master_barang`), `App\Models\StokAwal` (`stok_awal`)
- **Logika:** Filter gudang store (`holding`, `global_master`, `gudang_store`). Import data awal dengan preview validasi.

### 2.3 Penerimaan Barang (Store)
- **Nama di Sidebar / UI:** `Penerimaan Barang` (Store)
- **URL & Route Names:** `store.penerimaan.index` (`/store/penerimaan`), `store.penerimaan.store`
- **Controller & Methods:** `App\Http\Controllers\Store\PenerimaanStoreController` (`index`, `store`)
- **Blade View:** `resources/views/store/penerimaan/index.blade.php`
- **Models & Tables:** `App\Models\PenerimaanBarangStore` (`penerimaan_barang_store`), `App\Models\PenerimaanBarangStoreDetail` (`penerimaan_barang_store_detail`), `Barang`
- **Logika:** Penerimaan dari supplier masuk ke gudang utama (Store). Otomatis menambah saldo stok barang store.

### 2.4 Permintaan Barang (Store)
- **Nama di Sidebar / UI:** `Permintaan Barang` (Store)
- **URL & Route Names:** `store.permintaan.index` (`/store/permintaan`)
- **Controller & Methods:** `App\Http\Controllers\Store\PengeluaranStoreController` (`permintaanIndex`)
- **Blade View:** `resources/views/store/permintaan/index.blade.php`
- **Models & Tables:** `App\Models\PermintaanBarang` (`permintaan_barang`), `App\Models\PermintaanBarangDetail` (`permintaan_barang_detail`)
- **Logika:** Menampilkan daftar pengajuan permintaan barang dari Unit Bisnis yang menunggu persetujuan store.

### 2.5 Pengeluaran Barang / Distribusi Pemindahan (Store)
- **Nama di Sidebar / UI:** `Pengeluaran Barang` (Store)
- **URL & Route Names:**
  - Index: `store.pengeluaran.index` (`/store/pengeluaran?tab=pemindahan|permintaan|histori`)
  - Search: `store.pengeluaran.barang-search`
  - Buat Pemindahan Langsung: `store.pengeluaran.pemindahan.store`
  - Proses Permintaan: `store.pengeluaran.permintaan.proses`
  - Tolak Permintaan: `store.pengeluaran.permintaan.tolak`
  - Konfirmasi Kirim: `store.pengeluaran.pemindahan.kirim`
- **Controller & Methods:** `App\Http\Controllers\Store\PengeluaranStoreController` (`index`, `cariBarangPemindahan`, `buatPemindahanLangsung`, `prosesPermintaan`, `tolakPermintaan`, `konfirmasiKirim`)
- **Blade Views:**
  - `resources/views/store/pengeluaran/pemindahan.blade.php`
  - `resources/views/store/pengeluaran/permintaan.blade.php`
  - `resources/views/store/pengeluaran/histori.blade.php`
  - `resources/views/store/pengeluaran/partials/print_script.blade.php`
- **Models & Tables:** `App\Models\TransferBarang` (`transfer_barang`), `App\Models\PermintaanBarang`, `Barang`
- **Logika:** Pemindahan barang dari Store ke Unit. Mengurangi stok store dan membuat record transfer untuk diterima unit.

### 2.6 Laporan Gudang Store
- **Nama di Sidebar / UI:** `Laporan Gudang Store`
- **URL & Route Names:** `store.laporan.index` (`/store/laporan`), `store.laporan.export.excel`, `store.laporan.export.pdf`, `store.laporan.export.preview`, `store.laporan.export.generate`
- **Controllers & Methods:**
  - `App\Http\Controllers\Store\LaporanStoreController` (`index`, `exportExcel`, `exportPdf`)
  - `App\Http\Controllers\Store\LaporanExportController` (`preview`, `export`)
- **Blade Views:**
  - `resources/views/store/laporan/index.blade.php`
  - `resources/views/store/laporan/_preview.blade.php`
  - `resources/views/store/laporan/pdf.blade.php`

---

## 3. UNIT BISNIS - RECORDING GUDANG UMUM (OPERASIONAL)

### 3.1 Dashboard Recording GU
- **Nama di Sidebar / UI:** `Dashboard Tugas Harian`
- **URL & Route:** `unit-bisnis.recording-gu.dashboard` (`/unit-bisnis/recording-gu/dashboard`)
- **Controller:** `App\Http\Controllers\UnitBisnis\RecordingGudangUmum\DashboardController` (`__invoke`)
- **Blade View:** `resources/views/unit-bisnis/recording-gudang-umum/dashboard.blade.php`
- **Logika:** Fokus pada tugas input harian recording: penerimaan fisik baru & pencatatan pengeluaran.

### 3.2 Penerimaan Fisik Barang
- **Nama di Sidebar / UI:** `Penerimaan Fisik Barang`
- **URL & Route Names:**
  - `unit-bisnis.recording-gu.penerimaan.index` (`/unit-bisnis/recording-gu/penerimaan`)
  - Simpan Penerimaan: `unit-bisnis.recording-gu.penerimaan.store`
  - Konfirmasi Transfer: `unit-bisnis.recording-gu.penerimaan.confirm-transfer`
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\RecordingGudangUmum\PenerimaanController` (`index`, `store`, `confirmTransfer`)
- **Blade View:** `resources/views/unit-bisnis/recording-gudang-umum/penerimaan/index.blade.php`
- **Models & Tables:**
  - `App\Models\PenerimaanUnit` (`penerimaan_unit`)
  - `App\Models\PenerimaanUnitDetail` (`penerimaan_unit_detail`)
  - `App\Models\TransferBarang` (`transfer_barang`)
  - `App\Models\PenerimaanPemindahanUnit` (`penerimaan_pemindahan_unit`)
- **Logika Penting:**
  - Petugas recording mencatat barang fisik yang masuk (baik dari transfer store maupun pembelian lokal/langsung).
  - Status verifikasi default adalah `belum_verifikasi` menunggu rekonsiliasi oleh Admin GU.

### 3.3 Pengeluaran & Pengembalian (BPB)
- **Nama di Sidebar / UI:** `Pengeluaran & Pengembalian (BPB)`
- **URL & Route Names:**
  - `unit-bisnis.recording-gu.pengeluaran.index` (`/unit-bisnis/recording-gu/pengeluaran`)
  - Search Items: `unit-bisnis.recording-gu.pengeluaran.items`
  - Requesters: `unit-bisnis.recording-gu.pengeluaran.requesters`
  - Borrowed Items: `unit-bisnis.recording-gu.pengeluaran.borrowed-items`
  - Simpan Pengeluaran: `unit-bisnis.recording-gu.pengeluaran.store`
  - Simpan Peminta: `unit-bisnis.recording-gu.pengeluaran.requester.store`
  - Simpan Pengembalian: `unit-bisnis.recording-gu.pengeluaran.pengembalian.store`
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\RecordingGudangUmum\PengeluaranController` (`index`, `items`, `requesters`, `borrowedItems`, `returnOptions`, `store`, `storeRequester`, `storePengembalian`)
- **Blade View:** `resources/views/unit-bisnis/recording-gudang-umum/pengeluaran/index.blade.php`
- **Models & Tables:**
  - `App\Models\PengeluaranUnit` (`pengeluaran_unit`)
  - `App\Models\PengeluaranUnitDetail` (`pengeluaran_unit_detail`)
  - `App\Models\PengembalianUnit` (`pengembalian_unit`)
  - `App\Models\DivisiPeminta` (`divisi_peminta`)
  - `App\Models\Barang` (`barang`)
- **Logika Penting:**
  - Input BPB (Bukti Pengeluaran Barang) per divisi peminta.
  - Mendukung pengeluaran habis pakai atau barang dipinjam (bisa dikembalikan).
  - Mengurangi stok unit saat pengeluaran dicatat.

### 3.4 Stok Barang (Unit Bisnis)
- **Nama di Sidebar / UI:** `Stok Barang`
- **URL & Route Names:**
  - `unit-bisnis.stok.index` (`/unit-bisnis/stok`)
  - Export: `unit-bisnis.stok.export`
  - Edit/Update/Delete: `unit-bisnis.stok.edit`, `update`, `destroy`
  - Kartu Stok: `unit-bisnis.stok.kartu`
  - Import Stok Awal: `unit-bisnis.stok.import-template`, `import-analyze`, `import-process`
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\StokController` (`index`, `downloadExport`, `edit`, `update`, `destroy`, `kartuStok`, `downloadImportTemplate`, `analyzeImport`, `processImport`)
- **Blade Views:**
  - `resources/views/unit-bisnis/stok/index.blade.php`
  - `resources/views/unit-bisnis/stok/edit.blade.php`
  - `resources/views/unit-bisnis/stok/realtime.blade.php`
- **Models & Tables:** `App\Models\Barang` (`barang`), `App\Models\MasterBarang`, `App\Models\StokAwal`

---

## 4. UNIT BISNIS - ADMIN GUDANG UMUM (MANAJERIAL & VERIFIKASI)

### 4.1 Dashboard Admin GU
- **Nama di Sidebar / UI:** `Dashboard Admin GU`
- **URL & Route:** `unit-bisnis.admin-gu.dashboard` (`/unit-bisnis/admin-gu/dashboard`)
- **Controller:** `App\Http\Controllers\UnitBisnis\AdminGudangUmum\DashboardController` (`__invoke`)
- **Blade View:** `resources/views/unit-bisnis/admin-gudang-umum/dashboard.blade.php`

### 4.2 Permintaan Barang ke Store
- **Nama di Sidebar / UI:** `Permintaan Barang ke Store`
- **URL & Route Names:**
  - `unit-bisnis.admin-gu.permintaan.index` (`/unit-bisnis/admin-gu/permintaan`)
  - Cari Barang: `unit-bisnis.admin-gu.permintaan.barang-search`
  - Simpan Permintaan: `unit-bisnis.admin-gu.permintaan.store`
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\AdminGudangUmum\PermintaanBarangController` (`index`, `cariBarang`, `store`)
- **Blade View:** `resources/views/unit-bisnis/admin-gudang-umum/permintaan/index.blade.php`
- **Models & Tables:** `App\Models\PermintaanBarang` (`permintaan_barang`), `App\Models\PermintaanBarangDetail` (`permintaan_barang_detail`), `Barang`
- **Logika:** Unit bisnis membuat Surat Pengajuan Barang ke Store. Tabel dinamis Alpine.js.

### 4.3 Verifikasi Penerimaan (Rekonsiliasi Penerimaan)
- **Nama di Sidebar / UI:** `Verifikasi Penerimaan`
- **URL & Route Names:**
  - `unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index` (`/unit-bisnis/admin-gu/rekonsiliasi-penerimaan`)
  - Verifikasi: `unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify` (`POST /{penerimaan}/verifikasi`)
  - Tindak Lanjut: `unit-bisnis.admin-gu.rekonsiliasi-penerimaan.follow-up` (`POST /{penerimaan}/tindak-lanjut`)
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\AdminGudangUmum\RekonsiliasiPenerimaanController` (`index`, `verify`, `recordFollowUp`)
- **Blade View:** `resources/views/unit-bisnis/admin-gudang-umum/rekonsiliasi-penerimaan/index.blade.php`
- **Models & Tables:** `App\Models\PenerimaanUnit`, `App\Models\PenerimaanUnitDetail`
- **Logika:** Admin GU memverifikasi kuantitas & harga fisik barang masuk yang dicatat Recording GU vs surat jalan/faktur supplier/store. Menetapkan status `sesuai` atau `selisih`.

### 4.4 Verifikasi Pengeluaran & Penerimaan (Rekonsiliasi Pengeluaran)
- **Nama di Sidebar / UI:** `Verifikasi Pengeluaran & Penerimaan`
- **URL & Route Names:**
  - `unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index` (`/unit-bisnis/admin-gu/rekonsiliasi-pengeluaran`)
  - Simpan Draf: `unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.draft` (`POST /{pengeluaran}/draft`)
  - Finalisasi Verifikasi: `unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.verify` (`POST /{pengeluaran}/verifikasi`)
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\AdminGudangUmum\RekonsiliasiPengeluaranController` (`index`, `saveDraft`, `verify`)
- **Blade View:** `resources/views/unit-bisnis/admin-gudang-umum/rekonsiliasi-pengeluaran/index.blade.php`
- **Models & Tables:** `App\Models\PengeluaranUnit`, `App\Models\PengeluaranUnitDetail`, `Barang`
- **Logika:**
  - Admin GU mencocokkan fisik bukti BPB yang ditandatangani peminta dengan pencatatan Recording GU.
  - Workspace item rekonsiliasi menggunakan JSON state (`#reconciliation-workspace-items`) dan Alpine.js.
  - Status verifikasi: `belum_verifikasi` -> `draf` -> `terverifikasi` (atau `selisih`).

### 4.5 Stok Opname Bulanan
- **Nama di Sidebar / UI:** `Stok Opname Bulanan`
- **URL & Route Names:**
  - `unit-bisnis.admin-gu.stok-opname.index` (`/unit-bisnis/admin-gu/stok-opname`)
  - Daftar Draf: `unit-bisnis.admin-gu.stok-opname.drafts`
  - Simpan Draf: `unit-bisnis.admin-gu.stok-opname.draft`
  - Reset Opname: `unit-bisnis.admin-gu.stok-opname.reset`
  - Terapkan Opname: `unit-bisnis.admin-gu.stok-opname.apply`
  - Cetak Berita Acara: `unit-bisnis.admin-gu.stok-opname.pdf`
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\AdminGudangUmum\StokOpnameController` (`index`, `drafts`, `saveDraft`, `reset`, `apply`, `pdf`)
- **Blade Views:**
  - `resources/views/unit-bisnis/admin-gudang-umum/stok-opname/index.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/stok-opname/drafts.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/stok-opname/pdf.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/stok-opname/_tabs.blade.php`
- **Models & Tables:** `App\Models\StokOpname` (`stok_opname`), `App\Models\StokOpnameDetail` (`stok_opname_detail`), `Barang`
- **Logika:** Perbandingan stok sistem vs hitungan fisik. Terapkan opname mengupdate saldo stok dan mencatat history selisih.

### 4.6 Rekapitulasi Laporan Matriks
- **Nama di Sidebar / UI:** `Rekapitulasi Laporan Matriks`
- **URL & Route Names:**
  - Index: `unit-bisnis.admin-gu.laporan.index` (`/unit-bisnis/admin-gu/laporan`)
  - Export Excel: `unit-bisnis.admin-gu.laporan.export-excel`
  - Export PDF: `unit-bisnis.admin-gu.laporan.export-pdf`
- **Controller & Methods:** `App\Http\Controllers\UnitBisnis\AdminGudangUmum\ReportController` (`index`, `exportExcel`, `exportPdf`)
- **Blade Views:**
  - `resources/views/unit-bisnis/admin-gudang-umum/reports/index.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/reports/_tab-matriks.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/reports/_tab-mutasi.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/reports/_tab-pemasukan.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/reports/_tab-selisih.blade.php`
  - `resources/views/unit-bisnis/admin-gudang-umum/reports/pdf/monthly-report.blade.php`
- **Logika:** 4 sub-tab laporan:
  1. Matriks Pengeluaran Barang per Divisi Peminta.
  2. Mutasi Stok (Stok Awal, Masuk, Keluar, Saldo Akhir).
  3. Rekap Pemasukan Barang (Faktur, Supplier, Nilai).
  4. Analisa Selisih Opname Bulanan.

---

## 5. SUPER ADMIN (SISTEM & MASTER LOKASI)

### 5.1 Dashboard Super Admin
- **Nama di Sidebar / UI:** `Dashboard` (Super Admin)
- **URL & Route:** `admin.dashboard` (`/admin/dashboard`)
- **Controller:** `App\Http\Controllers\SuperAdmin\DashboardController` (`__invoke`)
- **Blade View:** `resources/views/super-admin/dashboard.blade.php`

### 5.2 Kelola User
- **Nama di Sidebar / UI:** `Kelola User`
- **URL & Route Names:** `admin.users.*` (`index`, `create`, `store`, `edit`, `update`, `destroy`)
- **Controller & Methods:** `App\Http\Controllers\SuperAdmin\UserController`
- **Blade Views:** `resources/views/super-admin/users/index.blade.php`, `form.blade.php`
- **Models & Tables:** `App\Models\User` (`users`), `Role`, `Gudang`

### 5.3 Role & Permission Matrix
- **Nama di Sidebar / UI:** `Role & Permission Matrix`
- **URL & Route Names:** `admin.roles.*` (`index`, `create`, `store`, `edit`, `update`, `destroy`, `cache-sync`)
- **Controller & Methods:** `App\Http\Controllers\SuperAdmin\RoleController`
- **Blade Views:** `resources/views/super-admin/roles/index.blade.php`, `create.blade.php`
- **Models:** Spatie `Role`, `Permission`

### 5.4 Master Lokasi Gudang
- **Nama di Sidebar / UI:** `Master Lokasi Gudang`
- **URL & Route Names:** `admin.gudang.*` (`index`, `create`, `store`, `edit`, `update`, `destroy`)
- **Controller & Methods:** `App\Http\Controllers\SuperAdmin\GudangController`
- **Blade Views:** `resources/views/super-admin/gudang/index.blade.php`, `form.blade.php`
- **Models & Tables:** `App\Models\Gudang` (`gudang`)

### 5.5 Log Audit Aktivitas
- **Nama di Sidebar / UI:** `Log Audit Aktivitas`
- **URL & Route Names:** `admin.activity-log.*` (`index`, `export`, `show`, `destroy-all`)
- **Controller & Methods:** `App\Http\Controllers\SuperAdmin\ActivityLogController`
- **Blade View:** `resources/views/super-admin/activity-log/index.blade.php`
- **Models & Tables:** `App\Models\AuditLog` (`audit_logs`)

---

## 6. LOG ANALISA & CATATAN KHUSUS PER MENU
> **ATURAN WAJIB SETIAP REVISI:** Saat AI menyelesaikan revisi pada menu tertentu, catat perubahan dan keputusan teknis di bagian ini agar percakapan berikutnya langsung terhubung ("nyambung") tanpa perlu analisa ulang.

- **Menu Rekonsiliasi Pengeluaran (`rekonsiliasi-pengeluaran`):**
  - Menggunakan Alpine.js x-data items dari JSON script `#reconciliation-workspace-items`.
  - Format status verifikasi: `belum_verifikasi`, `draf`, `terverifikasi`, `sesuai`, `selisih`.
  - Input catatan verifikasi admin tersimpan di kolom `catatan_verifikasi`.
- **Menu Stok Opname (`stok-opname`):**
  - Menggunakan session / database draft opname per unit gudang aktif.
  - Perhitungan selisih = Fisik - Sistem.
  - Berita acara otomatis memuat tanda tangan petugas opname dan kepala unit.
- **Menu Pengeluaran BPB (`pengeluaran`):**
  - Mendukung penambahan divisi peminta langsung via modal.
  - Validasi stok sebelum pengeluaran agar tidak minus.
- **Menu Permintaan ke Store (`permintaan`):**
  - Menghubungkan unit bisnis dengan inventaris master armada/store.
- **Quick Add Modal (`quick-add-modal.blade.php`) — Bug Fix 2026-11-25:**
  - **Bug 1 Fix:** `PenerimaanStoreService::getListingData()` — `$kategoriList` query diubah dari filter hanya `gudang_id = $gudangStore->id` menjadi `gudang_id = X OR gudang_id IS NULL`. Ini agar kategori global (dibuat via QuickAdd dengan `gudang_id=null`) ikut muncul di dropdown.
  - **Bug 2 Fix:** Label field di baris 353 diubah dari `Kode Perusahaan (opsional)` → `Kode Barang (opsional)`.
  - **Bug 3 Fix:** `submitBarang()` — fetch chain diubah ke pattern `res.json().then(data => ({ ok: res.ok, data }))` agar HTTP 422/500 tidak silent fail. Pesan error validasi dari `data.errors` kini diparsing dan ditampilkan.
  - **Bug 4 Fix:** `submitKategori()` dan `submitSatuan()` — ditambah proper error handling (sebelumnya silent fail jika server error), guard `categoryCreating`/`satuanCreating`, dan reset `errorMessage` sebelum submit.
  - **File yang diubah:** `app/Services/Store/PenerimaanStoreService.php`, `resources/views/components/quick-add-modal.blade.php`.

