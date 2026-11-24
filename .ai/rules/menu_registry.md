# ATURAN & PROTOKOL REVISI MENU CEPAT (ZERO-SCAN & INSTANT CONTINUITY)

## 1. ATURAN WAJIB REVISI MENU
1. **Dilarang Scanning Repositori dari Awal:**
   Jangan pernah menjalankan perintah scan/pencarian besar (`grep`, `Get-ChildItem -Recurse`, atau `php artisan route:list`) saat pengguna meminta revisi atau analisa menu.
2. **Langsung Rujuk ke Master Menu Knowledge Base:**
   Gunakan file `.ai/MENU_KNOWLEDGE_BASE.md` atau tabel fast-lookup di bawah ini untuk langsung membaca controller, blade view, dan model yang bersangkutan pada turn pertama.
3. **Jaga Kontinuitas Analisa ("Selalu Nyambung"):**
   Setiap kali ada revisi, perubahan alur, atau penambahan fitur pada suatu menu, perbarui ringkasan di `.ai/MENU_KNOWLEDGE_BASE.md` (bagian 6: Log Analisa & Catatan Khusus Per Menu). Dengan demikian, pada sesi chat selanjutnya informasi revisi sebelumnya sudah tersimpan dan langsung nyambung.

---

## 2. PETA CEPAT MENU SIGMA (FAST LOOKUP TABLE)

| Nama Menu UI | Route Name | Controller Utama | File Blade Utama | Model Utama |
|---|---|---|---|---|
| **Verifikasi Pengeluaran & Penerimaan** | `unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index` | `AdminGURekonsiliasiPengeluaranController` | `unit-bisnis/admin-gudang-umum/rekonsiliasi-pengeluaran/index.blade.php` | `PengeluaranUnit`, `PengeluaranUnitDetail` |
| **Verifikasi Penerimaan** | `unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index` | `AdminGURekonsiliasiPenerimaanController` | `unit-bisnis/admin-gudang-umum/rekonsiliasi-penerimaan/index.blade.php` | `PenerimaanUnit`, `PenerimaanUnitDetail` |
| **Stok Opname Bulanan** | `unit-bisnis.admin-gu.stok-opname.index` | `AdminGUStokOpnameController` | `unit-bisnis/admin-gudang-umum/stok-opname/index.blade.php` | `StokOpname`, `StokOpnameDetail` |
| **Rekapitulasi Laporan Matriks** | `unit-bisnis.admin-gu.laporan.index` | `AdminGUReportController` | `unit-bisnis/admin-gudang-umum/reports/index.blade.php` | `Barang`, `PengeluaranUnitDetail`, `PenerimaanUnitDetail` |
| **Permintaan Barang ke Store** | `unit-bisnis.admin-gu.permintaan.index` | `AdminGUPermintaanBarangController` | `unit-bisnis/admin-gudang-umum/permintaan/index.blade.php` | `PermintaanBarang`, `PermintaanBarangDetail` |
| **Penerimaan Fisik Barang** | `unit-bisnis.recording-gu.penerimaan.index` | `RecordingGUPenerimaanController` | `unit-bisnis/recording-gudang-umum/penerimaan/index.blade.php` | `PenerimaanUnit`, `TransferBarang` |
| **Pengeluaran & Pengembalian (BPB)** | `unit-bisnis.recording-gu.pengeluaran.index` | `RecordingGUPengeluaranController` | `unit-bisnis/recording-gudang-umum/pengeluaran/index.blade.php` | `PengeluaranUnit`, `PengembalianUnit`, `DivisiPeminta` |
| **Stok Barang (Unit Bisnis)** | `unit-bisnis.stok.index` | `UnitBisnisStokController` | `unit-bisnis/stok/index.blade.php` | `Barang`, `MasterBarang`, `StokAwal` |
| **Master Barang & Katalog** | `master.barang.index` | `MasterBarangController` | `master-data/index.blade.php` | `MasterBarang`, `KategoriBarang`, `Satuan` |
| **Stok Gudang Store** | `store.stok.index` | `StokStoreController` | `store/stok/index.blade.php` | `Barang`, `MasterBarang`, `StokAwal` |
| **Penerimaan Barang (Store)** | `store.penerimaan.index` | `PenerimaanStoreController` | `store/penerimaan/index.blade.php` | `PenerimaanBarangStore` |
| **Pengeluaran Barang (Store)** | `store.pengeluaran.index` | `PengeluaranStoreController` | `store/pengeluaran/pemindahan.blade.php` | `TransferBarang`, `PermintaanBarang` |
| **Permintaan Masuk (Store)** | `store.permintaan.index` | `PengeluaranStoreController` | `store/permintaan/index.blade.php` | `PermintaanBarang` |
| **Laporan Gudang Store** | `store.laporan.index` | `LaporanStoreController` | `store/laporan/index.blade.php` | `Barang`, `TransferBarang` |
| **Kelola User** | `admin.users.index` | `UserController` | `super-admin/users/index.blade.php` | `User`, `Role`, `Gudang` |
| **Role & Permission Matrix** | `admin.roles.index` | `RoleController` | `super-admin/roles/index.blade.php` | `Role`, `Permission` |
| **Master Lokasi Gudang** | `admin.gudang.index` | `GudangController` | `super-admin/gudang/index.blade.php` | `Gudang` |
| **Log Audit Aktivitas** | `admin.activity-log.index` | `ActivityLogController` | `super-admin/activity-log/index.blade.php` | `AuditLog` |

---

## 3. STRICT SINGLE-SCOPE & IMPACT CONFIRMATION PROTOCOL

1. **Fokus Tunggal (Target A Tetap A):**
   - Saat pengguna meminta revisi pada target A, AI HANYA BOLEH menyentuh file yang berhubungan langsung dengan A.
   - Dilarang keras melakukan refactoring atau modifikasi sampingan pada fitur lain tanpa instruksi eksplisit.

2. **Wajib Konfirmasi Jika Berdampak ke B (No Silent / Cascading Edits):**
   - Jika perubahan pada target A mempengaruhi atau memerlukan penyesuaian pada fitur/menu B:
     1. AI DILARANG LANGSUNG MENGUBAH file B secara sepihak atau diam-diam.
     2. AI WAJIB berhenti dan meminta konfirmasi kepada pengguna terlebih dahulu:
        - Jelaskan dampak: *"Perubahan pada [A] mempengaruhi [B] karena [alasan/dependensi]."*
        - Ajukan pertanyaan: *"Apakah Anda ingin saya juga menyesuaikan [B] sekarang, atau tetap fokus pada [A] saja?"*
     3. Lakukan modifikasi pada B HANYA setelah pengguna memberikan persetujuan eksplisit.


