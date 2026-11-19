# PRD: Sistem Informasi Gudang & Manajemen Aset

**Generated with `/prd "Sistem Informasi Gudang & Manajemen Aset"`**

---
87
### 1. Overview

**Feature / Project Name:** Sistem Informasi Gudang & Manajemen Aset (PT Taman Hati Group)

**Problem Statement:**
Proses gudang saat ini berjalan hampir sepenuhnya manual di atas kertas dan Excel, berdampingan dengan sistem Mac.id yang tidak dimanfaatkan penuh. Di unit bisnis **RPA Sukahati**, **Recording Gudang Umum** mencatat opname, penerimaan, pengeluaran, dan retur secara manual di kertas, yang kemudian diketik ulang (double entry) oleh **Admin Gudang Umum** ke Excel — menimbulkan duplikasi PO manual, selisih data, penerimaan barang tanpa surat jalan tanpa jalur resmi, label produk manual tanpa kontrol validasi, dan tidak ada reminder stok/expired otomatis. Di samping unit bisnis operasional seperti RPA Sukahati, PT Taman Hati Group juga mengoperasikan unit holding yaitu **Gudang Armada (Store)** yang berfungsi mengelola katalog master barang global dan pasokan armada. Struktur peran dan lokasi gudang perlu mengikuti organisasi yang terus berkembang secara dinamis (misal penambahan unit baru seperti RPA Tamansari), sehingga tidak boleh dikunci secara kaku dalam struktur folder atau kode.

**Proposed Solution:**
Membangun aplikasi Laravel dengan **admin panel standar buatan sendiri** (bukan package admin pihak ketiga seperti Filament) — Blade sebagai server-rendered view, Alpine.js untuk interaktivitas sisi klien, Tailwind CSS untuk styling — agar tim punya kendali penuh atas performa dan struktur kode tanpa overhead abstraksi package. Fondasi sistem dirancang dengan **Arsitektur Multi-Unit Bisnis Dinamis**: 
1. **Pemisahan tipe operasional gudang (`gudang_store` vs `gudang_umum`)**: Gudang Store mengelola stok dan pasokan Store serta pemenuhan permintaan unit; Gudang Umum (seperti RPA Sukahati) menjalankan operasional unit dengan role spesifik (**Admin Gudang Umum** & **Recording Gudang Umum**). Katalog barang tetap global.
2. **Controller terpusat (Unified MVC)**: Master barang menggunakan katalog global dan otorisasi permission; data transaksi serta saldo selalu di-scope berdasarkan `gudang_id` aktif yang ditugaskan kepada user.
3. **RBAC Spatie Dinamis**: Role & permission adalah data. Super Admin dapat membuat role baru dan mengatur penugasan user ke lokasi gudang (`user_gudang`) kapan pun tanpa perlu merubah kodingan.

**AI Build Summary:**
> Build a Laravel application with a **custom-built admin interface** — standard Laravel MVC, Blade views, Tailwind CSS, and **Alpine.js** for client-side interactivity. Authorization uses dynamic RBAC with user-to-warehouse mapping (`user_gudang`). Katalog barang is global, while saldo, transactions, reservations, and reports are scoped to an authorized active warehouse. Core modules for **RPA Sukahati**: master data, stok opname, PR/PO reference, penerimaan (normal & no delivery note), pengeluaran (digital signature + auto BPB), retur, label printing, expired/frozen-stock handling, and reminders. Core modules for **Gudang Store**: stock, supplier receipts, and one **Pengeluaran Barang** menu with Permintaan and Pemindahan tabs, connected to unit request and receipt confirmation.

---

### 2. Goals & Success Metrics

**Primary Goal:** Menghilangkan pencatatan ganda dan menyediakan satu sumber data stok yang akurat dan teraudit untuk **kedua lokasi gudang**, dengan fondasi yang tidak perlu dibongkar ulang saat organisasi (peran, lokasi) berkembang lebih jauh — dan tanpa mengorbankan performa saat data bertambah banyak.

**Success Metrics:**
- 100% transaksi harian Gudang Sukahati tercatat langsung di sistem dalam 4 minggu setelah go-live
- Selisih stok opname bulanan Gudang Sukahati turun ke bawah 2%
- 0 kejadian label salah tanggal expired/kode area setelah go-live
- Waktu rekap laporan harian Admin GU turun dari 1–2 jam/hari menjadi <15 menit/hari
- Halaman tabel/dashboard tetap merespons <1.5 detik meski jumlah data (barang, transaksi) bertambah 10x dari volume awal
- Penambahan role, satuan, atau gudang baru oleh Super Admin tidak memerlukan permintaan deploy ke engineering
- Saldo, penerimaan, reservasi, dan pengeluaran Gudang Store tidak tercampur dengan saldo Gudang Sukahati (0 insiden "salah gudang"); katalog barang menjadi referensi global yang dibrowse sesuai hak akses

**Anti-goals:**
- Tidak membangun transfer antar-gudang ad-hoc di luar alur Pengeluaran Barang Gudang Store yang secara eksplisit masuk scope
- Tidak membangun modul Aset dan Distribusi lintas-gudang seperti pada mockup UI lama
- Tidak menggunakan Filament, Livewire, atau SPA framework (React/Vue/Inertia) — UI dibangun dengan Blade + Alpine.js
- Tidak membangun integrasi API otomatis dua arah dengan Mac.id
- Tidak membangun aplikasi mobile terpisah
- SOP Gudang Store tetap perlu divalidasi; alur Pengeluaran yang dijelaskan di PRD adalah workflow aplikasi yang disepakati, bukan pengganti validasi SOP lapangan

---

### 3. Scope & Constraints

**In scope:**
- Master data growable: Master Barang dan Kategori menjadi referensi global; setiap barang didaftarkan ke gudang sebagai record lokal berkode lokal; stok minimum, saldo, dan transaksi tetap scoped per gudang. Satuan menjadi master bersama yang dapat ditambah.
- **RBAC dinamis penuh**: 4 role default di-seed (Admin Gudang Umum, Recording Gudang Umum, **Admin Gudang Store**, Super Admin) dengan permission bawaan; Super Admin dapat membuat role baru dan mengatur permission granular kapan pun
- **Penugasan pengguna ke gudang** (many-to-many) — akses stok, konfigurasi gudang, dan transaksi otomatis terbatas ke gudang yang relevan; katalog global mengikuti permission master
- Pencarian katalog barang (termasuk semantik bila tersedia) pada katalog global; pencarian stok/mutasi selalu dibatasi pada gudang aktif
- Halaman Welcome/Landing publik sebelum login
- **Modul Gudang Sukahati** (detail penuh, dari SOP yang sudah dipetakan): stok opname harian/bulanan, permintaan/PR, penerimaan (dengan/tanpa surat jalan), pengeluaran (tanda tangan digital + BPB), label produk, retur, piket malam, finalisasi, expired, stok beku, reminder stok minimum
- **Modul Gudang Store**: katalog dan stok Store, penerimaan supplier dengan alur/field tersendiri, serta satu menu **Pengeluaran Barang** dengan tab **Permintaan** dan **Pemindahan**; pemindahan ke unit bisnis dapat berasal dari permintaan maupun dibuat langsung tanpa permintaan
- Berita Acara Kehilangan Stok otomatis (Gudang Sukahati)
- Audit log lintas seluruh aksi sensitif di kedua modul

**Out of scope (MVP):**
- Transfer bebas lintas gudang di luar workflow Pengeluaran Barang Store
- Modul Aset, modul Distribusi lintas cabang
- Opname, retur, dan label untuk Gudang Store — **tidak diasumsikan dibutuhkan** kecuali dikonfirmasi (lihat Open Questions). Pengeluaran Store dibatasi pada pemindahan ke gudang unit yang tercatat, melalui permintaan maupun langsung tanpa permintaan; bukan pengeluaran stok tanpa tujuan atau modul BPB Gudang Sukahati
- Integrasi API otomatis dengan Mac.id
- Approval digital SPV/Purchasing/dst.
- Konversi satuan lintas unit
- Aplikasi mobile native

**Technical constraints:**
- Framework: Laravel 13, PHP 8.4 dengan `declare(strict_types=1)`, Property Hooks & Asymmetric Visibility pada Eloquent Model
- **Admin interface: standar Laravel (Controller + Form Request + Blade view), BUKAN Filament, BUKAN Livewire, BUKAN SPA framework**
- **UI interaktivitas: Alpine.js** — komponen kecil dan lokal (`x-data` per elemen), tidak ada state management global sisi klien
- Styling: Tailwind CSS, mengikuti token di Section 17
- Database: PostgreSQL 17 + pgvector aktif
- Pencarian semantik: `whereVectorSimilarTo()` + Laravel AI SDK, dijalankan via queue
- Cache & Queue: Redis 7 dapat digunakan oleh fitur yang memang memerlukannya; impor/ekspor tidak boleh mensyaratkan Redis atau worker
- **RBAC dijalankan dengan Spatie Laravel-Permission dalam mode dinamis** — tidak ada `enum` role di kolom database maupun PHP
- **Satuan bukan `enum`** — master data yang dapat tumbuh
- **`MasterBarang` adalah identitas barang global** — memuat kode perusahaan, nama, kategori, satuan, dan deskripsi. **`Barang` adalah pendaftaran lokal per gudang** yang memiliki `master_barang_id`, `gudang_id`, kode lokal, dan konfigurasi stok minimum. Semua foreign key transaksi tetap mengarah ke `barangs.id`; stok dan saldo dihitung per gudang. Katalog Master Barang menampilkan gudang tempat barang terdaftar, bukan angka stok atau stok minimum.

- Lingkungan pengembangan lokal: Laragon 6.0
- Performance guardrails wajib (Section 16), Design System wajib (Section 17)

---

### 4. Jobs to Be Done (JTBD)

| Priority | Job Statement |
|----------|---------------|
| 1 | Sebelum masuk ke sistem, pengguna baru ingin memahami gambaran modul sebelum diarahkan ke login. |
| 2 | Saat memulai shift, Recording Gudang Umum ingin langsung mencatat opname/transaksi ke sistem tanpa kertas. |
| 3 | Saat barang datang, Recording Gudang Umum ingin mencocokkan dan mencatat penerimaan langsung di sistem, termasuk saat surat jalan belum ada. |
| 4 | Saat ada permintaan produksi, Recording Gudang Umum ingin mencatat pengeluaran dan mendapat bukti (BPB) otomatis. |
| 5 | Saat mencetak label, Recording Gudang Umum ingin data kritis (expired, kode area) terkontrol sistem, bukan diketik manual. |
| 6 | Setiap hari, Admin Gudang Umum ingin melihat rekap otomatis tanpa mengetik ulang dari kertas ke Excel. |
| 7 | Setiap bulan, Admin Gudang Umum ingin sistem menghitung otomatis selisih opname. |
| 8 | Admin Gudang Umum ingin reminder otomatis untuk stok minimum dan bumbu mendekati expired. |
| 9 | Super Admin ingin mengelola akun, role, dan hak akses tanpa perlu melihat data transaksi operasional. |
| 10 | Ketika struktur organisasi berkembang (peran baru, gudang baru seperti Gudang Store), Super Admin ingin menyesuaikan sistem tanpa minta perubahan kode. |
| 11 | Admin Gudang Store ingin mencari referensi barang pada katalog global dan melihat stok/transaksi hanya untuk Gudang Store aktif, agar data barang mudah digunakan tanpa mencampur saldo antar gudang. |
| 12 | Admin Gudang Store ingin memenuhi permintaan unit dan mencatat pengiriman dari satu menu Pengeluaran Barang, agar alokasi, status pemindahan, dan pengurangan stok dapat ditelusuri dalam satu alur. |
| 13 | Admin/Recording unit ingin mengajukan kebutuhan ke Store dan mengonfirmasi barang yang benar-benar diterima, agar saldo unit dan status permintaan sesuai dengan serah terima fisik. |

---

### 5. User Stories

**Master Data & Fondasi Sistem**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US1 | Admin Gudang Umum dengan permission master data | Mengelola katalog barang global: tambah/edit/nonaktifkan barang dan kategori sesuai permission | agar data barang konsisten sebagai rujukan transaksi semua gudang | J6 |
| US2 | Admin Gudang Umum / Recording Gudang Umum / Admin Gudang Store | Mencari barang pada katalog global sesuai permission; pencarian saldo dan transaksi tetap dibatasi ke gudang aktif | agar barang dapat dirujuk lintas gudang tanpa mencampur saldo | J6, J11 |
| US3 | Admin Gudang Umum / Admin Gudang Store | Menambah satuan baru ke master data global jika belum tersedia | agar tidak perlu menunggu update aplikasi | J10 |
| US4 | Pengelola stok dengan permission | Mengatur stok minimum sesuai lingkup kebijakan; gunakan konfigurasi barang-gudang bila ambang berbeda per gudang | agar reminder stok relevan dengan lokasi | J8, J11 |
| US5 | Pengunjung (belum login) | Melihat halaman pengantar sebelum diarahkan ke login | agar paham gambaran sistem, dan tahu harus menghubungi siapa jika belum punya akun | J1 |

**Stok Opname (Gudang Sukahati)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US6 | Recording Gudang Umum | Mengisi form stok opname harian per barang | agar kondisi stok fisik tercatat tanpa kertas | J2 |
| US7 | Recording Gudang Umum | Melihat data penerimaan/faktur hari sebelumnya saat opname | agar kesesuaian bisa diverifikasi tanpa cek dokumen kertas | J2 |
| US8 | Admin Gudang Umum | Melihat selisih otomatis opname fisik vs sistem | agar tidak perlu menghitung manual | J7 |

**Pengadaan (Gudang Sukahati)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US9 | Recording Gudang Umum | Membuat draft permintaan/PR bahan | agar tidak direkap manual dulu | J2 |
| US10 | Admin Gudang Umum | Membuat dan mengajukan PR/PO resmi dengan referensi nomor PO Mac.id | agar tidak membuat PO manual terpisah | J6 |
| US11 | Admin Gudang Umum | Memantau status PR/PO yang diajukan | agar tahu estimasi kedatangan barang | J6 |

**Penerimaan Barang (Gudang Sukahati)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US12 | Recording Gudang Umum | Mencocokkan barang datang dengan PO/PR | agar tahu barang sesuai pesanan | J3 |
| US13 | Recording Gudang Umum | Mengunggah foto surat jalan/faktur saat menerima | agar bukti tidak hilang | J3 |
| US14 | Recording Gudang Umum | Menandai penerimaan "tanpa surat jalan" + alasan wajib | agar tetap ada jejak audit saat dokumen belum tiba | J3 |
| US15 | Recording Gudang Umum | Mencatat hasil timbang ulang bahan bakar/es/bumbu | agar tidak ditulis kertas dulu | J2 |

**Pengeluaran & Label (Gudang Sukahati)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US16 | Recording Gudang Umum | Mencatat pengeluaran barang harian | agar stok berkurang otomatis | J4 |
| US17 | Recording Gudang Umum | Merekam tanda tangan digital penerima barang | agar BPB tetap sah tanpa kertas | J4 |
| US18 | Recording Gudang Umum | Mencetak label produk dengan nomor seri otomatis | agar tidak ketik ulang di Excel | J5 |

**Retur & Piket Malam (Gudang Sukahati)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US19 | Recording Gudang Umum | Mencatat retur barang dari produksi (bumbu, plastik, dll.) | agar stok kembali akurat | J2 |
| US20 | Recording Gudang Umum | Mencatat transaksi bumbu saat piket malam | agar tercatat di hari yang sama | J2 |

**Finalisasi, Pelaporan & Reminder (Gudang Sukahati)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US21 | Admin Gudang Umum | Memverifikasi dan mengunci data transaksi harian | agar tidak diubah sepihak | J6 |
| US22 | Admin Gudang Umum | Men-generate laporan otomatis | agar tidak menyusun manual | J6 |
| US23 | Admin Gudang Umum | Membuat Berita Acara Kehilangan Stok otomatis (selisih >2 hari kerja) | agar dokumen konsisten formatnya | J7 |
| US24 | Admin Gudang Umum | Menerima reminder bumbu mendekati expired | agar tidak cek manual | J8 |
| US25 | Admin Gudang Umum | Menandai barang sebagai "Expired" | agar stok aktif akurat | J8 |
| US26 | Admin Gudang Umum | Melihat kandidat "Stok Beku" (idle >12 bulan) | agar tidak menunggu opname manual | J8 |
| US27 | Admin Gudang Umum | Menerima reminder stok mendekati minimum | agar bisa ajukan pembelian sebelum kosong | J8 |

**Gudang Store dan alur unit peminta/penerima**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US28 | Admin Gudang Store | Mencari dan melihat detail barang pada katalog global secara read-only; menggunakan Quick-Add hanya bila permission transaksi mengizinkan | agar barang dapat dipilih tanpa membuka CRUD master dan stok tetap scoped ke Store | J11 |
| US29 | Admin Gudang Store | Mencatat penerimaan barang dengan alur/field khusus Gudang Store *(field pasti menunggu SOP — lihat Open Questions)* | agar proses penerimaan sesuai kondisi kerja Gudang Store yang berbeda dari Gudang Sukahati | J11 |
| US30 | Admin Gudang Store | Melihat saldo fisik, alokasi, ketersediaan, dan mutasi barang di Gudang Store aktif | agar tidak salah baca stok lintas lokasi | J11 |
| US40 | Admin Gudang Store | Mengelola permintaan unit dan pemindahan barang keluar melalui menu Pengeluaran Barang | agar permintaan, alokasi, dan pengiriman dapat ditelusuri dalam satu alur | J12 |
| US41 | Admin/Recording Gudang Unit | Mengajukan permintaan barang ke Store, menerima kiriman, dan mencatat qty aktual/selisih | agar Store-to-unit dapat ditutup berdasarkan serah terima nyata | J13 |

**Manajemen Sistem & RBAC Dinamis (Super Admin)**

| ID | Role | Action | Benefit | JTBD Ref |
|----|------|--------|---------|----------|
| US31 | Super Admin | Membuat, mengedit, menonaktifkan akun pengguna | agar akses sesuai pegawai aktif | J9 |
| US32 | Super Admin | Menetapkan satu atau lebih role ke pengguna | agar hak akses sesuai jabatan aktual | J9 |
| US33 | Super Admin | Menugaskan pengguna ke satu atau lebih gudang | agar akses data otomatis terbatas ke gudang relevan | J10 |
| US34 | Super Admin | Membuat role baru beserta nama dan deskripsinya | agar struktur organisasi yang berkembang bisa diakomodasi | J10 |
| US35 | Super Admin | Mengatur kombinasi permission granular per role | agar akses tetap terkontrol meski role bertambah | J10 |
| US36 | Super Admin | Menambah gudang/lokasi baru beserta jenis operasionalnya (gudang umum/gudang store) | agar ekspansi lokasi tidak perlu perubahan kode | J10 |
| US37 | Super Admin | Mereset password pengguna | agar pengguna tetap bisa bekerja tanpa Super Admin tahu password lama | J9 |
| US38 | Super Admin | Melihat log aktivitas sistem | agar keamanan terpantau tanpa melihat data transaksi | J9 |

---

### 6. Proposed Experience

**Design Direction:**
Aplikasi web server-rendered standar: setiap halaman adalah Blade view yang dirender penuh oleh Controller, dengan Alpine.js menambahkan interaktivitas lokal (dropdown, modal, toggle form, signature pad) tanpa membangun SPA. Navigasi sidebar per modul, tabel data dengan filter & pencarian, form sebagai halaman terpisah. **Design System:** Section 17.

**Key Screens / States:**

- **Welcome/Landing Page (publik):** navbar, hero dengan CTA "Masuk ke sistem", showcase modul, cara kerja, footer — tidak butuh autentikasi
- **Dashboard (per role, ter-filter otomatis ke gudang yang ditugaskan):** widget stok minimum, expired, stok beku (Gudang Sukahati) atau ringkasan stok (Gudang Store)
- **Selektor Gudang Aktif:** ditampilkan di header bagi pengguna yang ditugaskan ke >1 gudang — memilih konteks data yang sedang dilihat
- **Form Stok Opname, Penerimaan, Pengeluaran, Label, Retur (Gudang Sukahati):** sama seperti rancangan sebelumnya, kini sebagai Blade form standar dengan validasi Form Request di server + Alpine.js untuk interaksi ringan (mis. live-preview kolom selisih tanpa reload penuh via fetch)
- **Master Barang:** browse/search untuk pengguna dengan permission lihat; Super Admin mengelola identitas global, kategori, dan pendaftaran barang ke gudang. Tabel menampilkan gudang tempat barang terdaftar tanpa menampilkan stok, stok minimum, atau lokasi rak.
- **Stok & Penerimaan (Gudang Store):** saldo dan kartu mutasi scoped ke Gudang Store aktif; form/riwayat penerimaan supplier tersendiri
- **Pengeluaran Barang Store:** satu menu dengan tab Permintaan dan Pemindahan; alokasi menahan stok tersedia, pengiriman mengurangi stok Store, dan unit tujuan mencatat qty aktual diterima
- **Halaman Kelola Role & Permission, Kelola Gudang (Super Admin):** matriks permission per role, CRUD lokasi gudang dengan pilihan jenis
- **Empty/Error/Loading state:** ditangani standar Blade (`@if`, session flash message untuk error) + skeleton sederhana via Alpine untuk tabel besar

**Interaction Model:**
1. Pengunjung → Welcome Page → login
2. Pengguna login → server menentukan gudang & permission-nya → dashboard ter-filter otomatis
3. Mengisi form transaksi → Form Request memvalidasi di server → redirect dengan flash message sukses/gagal (pola PRG — Post/Redirect/Get standar, bukan SPA)
4. Interaksi ringan (toggle, modal konfirmasi, live selisih) ditangani Alpine.js di klien tanpa round-trip penuh
5. Admin memverifikasi rekap → mengunci data harian
6. Super Admin mengelola role/gudang/user dari panel terpisah

**Accessibility Notes:**
- Label terhubung ke input (`<label for>`), pesan error server ditampilkan dekat field terkait
- Seluruh interaksi Alpine.js tetap bisa dioperasikan dengan keyboard (fokus terjaga saat modal dibuka Alpine)
- Kontras warna badge status minimal 4.5:1
- `prefers-reduced-motion` dihormati pada transisi Alpine (`x-transition`)

**Figma / Design Link:** [placeholder]

---

### 7. Component Inventory

*(Tidak lagi memakai istilah "Filament Resource" — komponen berikut adalah kombinasi Controller + Blade View + potongan Alpine.js.)*

| Component | Type | Description | Linked Stories |
|-----------|------|--------------|-----------------|
| WelcomeController + welcome.blade.php | Controller + View (publik) | Halaman pengantar sebelum login | US5 |
| BarangController | Controller + View | CRUD katalog barang global bagi pemegang permission master data; Admin Store browse/search/detail read-only | US1, US2, US28 |
| Quick-Add transaksi | Controller/API + Alpine Component | Membuat barang baru hanya dari transaksi yang diizinkan; tidak mengedit master existing | US28 |
| barang-semantic-search.js | Alpine Component | Pencarian katalog sesuai hak lihat; hasil stok/mutasi tetap discope ke gudang aktif | US2, US28 |
| SatuanController | Controller + View | CRUD master satuan (global, growable) | US3 |
| GudangController | Controller + View (Super Admin) | CRUD master lokasi gudang + jenisnya | US36 |
| StokOpnameController | Controller + View | Opname harian & bulanan Gudang Sukahati, kolom selisih dihitung server-side | US6, US7, US8 |
| PermintaanBarangController | Controller + View | Draft & pengajuan PR | US9, US10, US11 |
| PenerimaanBarangController | Controller + View | Penerimaan Gudang Sukahati, termasuk jalur tanpa surat jalan | US12, US13, US14, US15 |
| PenerimaanBarangStoreController | Controller + View | Penerimaan Gudang Store — field menunggu konfirmasi SOP | US29 |
| PengeluaranStoreController + Pengeluaran Store view | Controller + View | Satu menu dengan tab Permintaan dan Pemindahan; alokasi, pengiriman, filter, dan status | US40 |
| Permintaan unit + konfirmasi penerimaan | Controller + View | Ajukan kebutuhan dari unit, catat serah terima aktual dan discrepancy | US41 |
| PengeluaranBarangController | Controller + View | Pengeluaran dengan signature pad (Alpine) & BPB otomatis | US16, US17 |
| signature-pad.js | Alpine Component | Capture tanda tangan digital via canvas | US17 |
| LabelProdukController | Controller + View | Cetak label dengan nomor seri otomatis | US18 |
| ReturBarangController | Controller + View | Retur barang | US19 |
| PiketMalamController | Controller + View | Transaksi bumbu di luar jam operasional | US20 |
| finalisasi-harian.js + FinalisasiHarianController | Alpine Component + Controller | Aksi "Verifikasi & Kunci Data Harian" dengan modal konfirmasi | US21 |
| LaporanController | Controller + View | Generate & export laporan | US22 |
| BeritaAcaraKehilanganController | Controller + View | Dokumen resmi hasil selisih opname | US23 |
| dashboard-widgets (partial views) | View Partials | Stok minimum, expired, stok beku — data dari cache Redis, di-render server-side | US24, US25, US26, US27 |
| UserController | Controller + View (Super Admin) | CRUD akun, assign role & gudang | US31, US32, US33, US37 |
| RoleController | Controller + View (Super Admin) | CRUD role baru & matriks permission | US34, US35 |
| ActivityLogController | Controller + View (Super Admin) | Log aktivitas, read-only | US38 |
---

### 8. Data Models

```php
declare(strict_types=1);

// ============ MASTER DATA (growable — bukan enum, kecuali dicatat) ============

final class Gudang extends Model
{
    // id: uuid (PK)
    // kode: string, unique             // "SUKAHATI", "STORE"
    // nama: string                     // "Gudang Sukahati", "Gudang Store"
    // jenis: enum('gudang_umum','gudang_store') // tipe operasional untuk menentukan alur dan otorisasi gudang
    // is_aktif: boolean
}

final class Satuan extends Model
{
    // id: uuid (PK)
    // kode: string, unique   // di-seed 18 nilai awal, GLOBAL untuk semua gudang (unit fisik tidak spesifik ke satu lokasi)
    // nama: string
    // is_aktif: boolean
}

final class KategoriBarang extends Model
{
    // id: uuid (PK)
    // kategori merupakan metadata katalog global, bukan sumber saldo stok
    // nama: string
}

final class MasterBarang extends Model
{
    // id: bigint (PK)
    // kode: string, unique nullable untuk mempertahankan record legacy tanpa asumsi penggabungan
    // nama, kategori_id, satuan_id, harga_satuan, deskripsi, foto_path, is_aktif
}

final class Barang extends Model
{
    // id: bigint (PK), master_barang_id: FK -> master_barangs.id
    // gudang_id: FK -> gudangs.id; kode: kode lokal unik dalam lingkup gudang
    // minimum_stok: integer; is_aktif: boolean
    // Foreign key transaksi tetap memakai barangs.id agar histori dan saldo lama terjaga.
}

// ============ RBAC DINAMIS (Spatie Laravel-Permission) ============

final class Role extends Model
{
    // id: uuid (PK)
    // name: string, unique   // "Admin Gudang Umum", "Recording Gudang Umum", "Admin Gudang Store", "Super Admin", atau role baru buatan Super Admin
    // guard_name: string
    // is_default: boolean
}

final class Permission extends Model
{
    // id: uuid (PK)
    // name: string, unique   // "barang.manage", "penerimaan.input", "stok_opname.finalisasi", "user.manage", "role.manage", "gudang.manage" — GENERIK, tidak dinamakan per-gudang; scoping lokasi ditangani lewat user_gudang, bukan lewat nama permission
    // guard_name: string
}

final class User extends Authenticatable
{
    // id: uuid (PK)
    // username: string, unique  // Digunakan untuk login, tidak ada email — akun dikelola internal oleh Super Admin
    // nama: string
    // password: string (hashed)
    // is_aktif: boolean
    // relasi many-to-many -> Role (trait HasRoles, TIDAK ADA kolom `role` enum)
    // relasi many-to-many -> Gudang (lewat user_gudang) — scope akses data
}

final class UserGudang extends Model
{
    // id: uuid (PK)
    // user_id: uuid (FK)
    // gudang_id: uuid (FK)
    // Kombinasi role (via HasRoles) + gudang (via tabel ini) menentukan data apa yang terlihat:
    // "Admin Gudang Store" + gudang aktif Store → browse katalog sesuai permission,
    // tetapi hanya melihat stok/transaksi untuk gudang Store yang ditugaskan/aktif
}

// ============ TRANSAKSIONAL — GUDANG SUKAHATI ============

final class StokOpname extends Model
{
    // id: uuid (PK)
    // gudang_id: uuid (FK)  // selalu Gudang Sukahati untuk MVP, tapi kolomnya generik
    // jenis: enum('harian','bulanan')
    // periode: date
    // recording_gu_id: uuid (FK -> users.id)
    // status: enum('draft','submitted','finalized') — asymmetric visibility, hanya OpnameService yang set 'finalized'
    // finalized_by, finalized_at: nullable
}

final class StokOpnameDetail extends Model
{
    // id, stok_opname_id, barang_id
    // qty_fisik, qty_sistem: integer
    // selisih: integer // get-only
}

final class PermintaanBarang extends Model
{
    // id, gudang_id, nomor_pr, dibuat_oleh
    // status: enum('draft','diajukan','po_terbit')
}

final class PurchaseOrderReferensi extends Model
{
    // id, permintaan_barang_id (nullable), nomor_po_macid, dibuat_oleh
    // status: enum('diajukan','diterima_sebagian','selesai')
}

final class PenerimaanBarang extends Model
{
    // id, gudang_id, barang_id, qty_diterima
    // po_referensi_id: nullable
    // tanpa_surat_jalan: boolean
    // alasan_tanpa_surat_jalan: text, nullable
    // foto_bukti_path: string, nullable
    // diterima_oleh: uuid (FK -> users.id)
}

final class PengeluaranBarang extends Model
{
    // id, gudang_id, barang_id, qty_keluar
    // nama_peminta: string
    // tanda_tangan_digital_path: string
    // nomor_bpb: string, auto-generate
    // dicatat_oleh: uuid (FK -> users.id)
}

final class LabelProduk extends Model
{
    // id, gudang_id, nomor_seri (auto, unique)
    // nama_pelanggan, nama_produk, tanggal_produksi
    // tanggal_expired: nullable (wajib untuk frozen)
    // kode_area: nullable (wajib untuk on-fresh)
    // jenis_partingan, dicetak_oleh
}

final class ReturBarang extends Model
{
    // id, gudang_id, kategori_barang_id, qty_retur
    // kondisi_barang: enum('baik','rusak')
    // dicatat_oleh
}

final class BeritaAcaraKehilangan extends Model
{
    // id, stok_opname_detail_id, nominal_estimasi (nullable), keterangan
    // dibuat_oleh, dibuat_pada (otomatis)
}

final class StokBeku extends Model
{
    // id, gudang_id, barang_id, idle_sejak
    // estimasi_kerugian: nullable
    // status: enum('candidate','diajukan','ditindaklanjuti')
}

final class AuditLog extends Model
{
    // id, user_id, aksi, subjek_tipe, subjek_id (polymorphic), perubahan (jsonb), created_at
}

// ============ TRANSAKSIONAL — GUDANG STORE & UNIT ============

final class StokAwal extends Model
{
    // id, gudang_id, barang_id, qty, tanggal_acuan, sumber, file_asal_path, diinput_oleh
    // Menambah saldo awal satu kali per barang/gudang; koreksi memerlukan transaksi audit khusus.
}

final class PenerimaanBarangStore extends Model
{
    // id: uuid (PK)
    // gudang_id: uuid (FK, harus mengarah ke Gudang berjenis 'gudang_store')
    // nomor_penerimaan, supplier, pemesan, pengambil, referensi PO/dokumen, surat jalan,
    // alasan bila tanpa surat jalan, foto/bukti, tanggal_diterima, diterima_oleh
    // Satu header mempunyai banyak detail; detail memuat barang, satuan, qty diterima/rusak/kurang,
    // harga/nilai bila dipakai, kondisi, pemakai, catatan.
    // Saldo masuk berasal dari detail yang dapat digunakan, bukan angka header duplikat.
}

final class PermintaanBarangStore extends Model
{
    // id, nomor_pr, gudang_peminta_id, gudang_tujuan_id (Store), dibuat_oleh, created_at
    // status: enum('baru','diproses','selesai','ditolak'), catatan, alasan_penolakan
    // Detail: barang_id, satuan_id, qty_diminta, catatan;
    // qty dialokasikan dan diterima dihitung dari pemindahan/penerimaan terkait
    // Permintaan ini adalah kebutuhan unit ke Store, bukan permintaan pembelian Gudang Sukahati.
}

final class TransferBarang extends Model
{
    // id, nomor_sj, permintaan_barang_id (nullable), barang_id
    // gudang_asal_id, gudang_tujuan_id (FK -> gudang.id)
    // qty_diminta (kuantitas dialokasikan), qty_dikirim, qty_diterima,
    // status: enum('diminta','dikirim','diterima')
    // supir, bukti_kirim, diminta/dikirim/diterima_oleh, timestamp untuk tiap transisi
    // 'diminta' menahan stok tersedia; 'dikirim' mengurangi stok fisik Store;
    // 'diterima' hanya setelah unit tujuan mengonfirmasi serah terima.
}

final class PenerimaanPemindahanUnit extends Model
{
    // gudang_id tujuan, transfer_barang_id, barang_id, qty_diterima, kondisi/discrepancy,
    // bukti/catatan, diterima_oleh, diterima_at
    // Mencatat penerimaan di gudang tujuan dan menjaga jejak surat jalan asal.
}
```

---

### 9. API / Integration Surface

Tidak ada REST API publik pada MVP — seluruh interaksi lewat Controller + Blade + Alpine (form submit standar / fetch ringan untuk interaksi lokal). Kolom "Akses" memakai nama **permission**, bukan nama role.

| Operation | Service::Method | Description | Akses (Permission) | Effect |
|-----------|-------------------|--------------|----------------------|--------|
| Hitung saldo stok berjalan | `StokService::saldoBerjalan(Barang $barang, Gudang $gudang)` | Agregat transaksi barang pada gudang yang diotorisasi | `stok.view` | Read-only |
| Simpan barang baru + queue embedding | `BarangService::simpan(array $data)` | Simpan ke katalog barang global; stok tetap dicatat melalui transaksi per gudang | Permission master data (default mengikuti kebijakan Admin Gudang Umum; Admin Store hanya Quick-Add bila diberi permission khusus) | Insert/Update `barang` |
| Cari barang mirip | `BarangSearchService::cariMirip(string $query)` | Pencarian pada katalog global; hasil stok/transaksi tetap memerlukan scope gudang aktif | `barang.view` | Read-only |
| Kelola master satuan (global) | `SatuanService::simpan/nonaktifkan()` | Tambah/nonaktifkan satuan | `satuan.manage` | Insert/Update `satuan` |
| Kelola master gudang | `GudangService::simpan()` | Tambah lokasi + jenis | `gudang.manage` (default: Super Admin) | Insert/Update `gudang` |
| Proses penerimaan (Gudang Sukahati) | `PenerimaanService::proses(array $data)` | Validasi qty vs PO, update stok | `penerimaan.input` (default: Recording Gudang Umum) | Insert `penerimaan_barang`, update stok |
| Proses penerimaan (Gudang Store) | `PenerimaanStoreService::proses(array $data)` | Alur & validasi berbeda — detail menunggu SOP | `store.penerimaan` | Insert `penerimaan_barang_store`, update saldo Store |
| Ajukan permintaan unit ke Store | `PermintaanStoreService::ajukan(array $data)` | Validasi gudang peminta dan tujuan; request muncul pada Store yang dituju | Permission pengajuan unit sesuai RBAC unit | Insert permintaan dan detail |
| Alokasikan/kirim pemindahan Store | `PengeluaranStoreService::alokasikan/kirim(...)` | Alokasi berstatus `diminta` mereservasi; `dikirim` mengurangi saldo fisik Store | `store.distribusi` | Insert/update pemindahan dan mutasi Store |
| Konfirmasi penerimaan unit | `PenerimaanPemindahanService::konfirmasi(...)` | Catat kuantitas aktual/discrepancy dan saldo gudang tujuan | Permission penerimaan unit sesuai RBAC unit | Update pemindahan; insert penerimaan tujuan |
| Proses pengeluaran | `PengeluaranService::proses(array $data)` | Validasi stok cukup, generate BPB | `pengeluaran.input` (default: Recording Gudang Umum) | Insert `pengeluaran_barang`, update stok |
| Proses retur | `ReturService::proses(array $data)` | Kembalikan qty ke stok | `retur.input` (default: Recording Gudang Umum) | Insert `retur_barang`, update stok |
| Finalisasi opname/laporan | `OpnameService::finalisasi(...)` | Kunci data, audit log | `stok_opname.finalisasi` (default: Admin Gudang Umum) | Update status |
| Buat Berita Acara Kehilangan | `OpnameService::buatBeritaAcara(...)` | Manual, jika selisih >2 hari kerja | `berita_acara.buat` (default: Admin Gudang Umum) | Insert |
| Deteksi stok beku/expired/minimum | `*Service::deteksiOtomatis()` | Job terjadwal, per gudang | Sistem (scheduled job) | Insert notifikasi |
| Kelola pengguna | `UserService::simpan/nonaktifkan/resetPassword()` | CRUD akun, assign role & gudang | `user.manage` (default: Super Admin) | Insert/Update `users`, `user_gudang` |
| Kelola role & permission | `RoleService::simpan/hapus/aturPermission()` | CRUD role & permission | `role.manage` (default: Super Admin) | Insert/Update `roles`, `permissions` |
**External integrations:** Tidak ada pada MVP.

---

### 10. State Management Map

*(Tanpa Livewire/Filament — state dikelola pola MVC standar + Alpine.js lokal.)*

| State | Location | Persistence | Notes |
|-------|----------|-------------|-------|
| `stokSaatIni` per barang | Computed di Service Layer, di-cache Redis | `Cache::touch()` | Sumber kebenaran tetap agregat transaksi |
| Gudang aktif yang dipilih (jika user ditugaskan >1 gudang) | Session Laravel | Session | Menentukan filter default di semua Controller |
| State form sebelum submit (mis. toggle "tanpa surat jalan") | Alpine `x-data` lokal di komponen form | Hilang saat reload/refresh sebelum submit | Tidak ada state global sisi klien — tiap komponen mandiri |
| Flash message sukses/gagal | Session flash (`session()->flash()`) | Satu request berikutnya | Pola PRG standar Laravel |
| Status finalisasi opname | Database (`stok_opname.status`) | Persistent | Hanya via `OpnameService::finalisasi()` |
| Badge notifikasi reminder | Redis cache, di-refresh job terjadwal | `Cache::touch()` | Dihitung ulang per gudang relevan |
| Master barang/satuan/gudang (dropdown) | Redis cache | Invalidasi saat data berubah | Kandidat utama caching |
| Matriks role-permission | Redis cache | **Wajib** invalidasi otomatis tiap `RoleService::aturPermission()` | Perubahan akses harus langsung berlaku |
| Signature pad sementara | Canvas Alpine di klien | Session (hilang jika dibatalkan) | Disimpan ke server hanya setelah submit sukses |
| Log aktivitas | Database (`audit_log`) | Persistent | Read-only |

---

### 11. Tech Stack Recommendation

| Layer | Choice | Rationale |
|-------|--------|-----------|
| Framework & Bahasa | Laravel 13, PHP 8.4 (strict types) | Property Hooks & Asymmetric Visibility menegakkan aturan anti-manipulasi di level model |
| Admin Interface | **Blade + Controller standar (bukan Filament)** | Kendali penuh atas markup & performa; tidak ada overhead abstraksi package admin panel |
| Interaktivitas UI | **Alpine.js** | Interaktivitas ringan (modal, toggle, signature pad, live preview) tanpa build step SPA atau state management berat |
| Styling | Tailwind CSS | Utility-first, konsisten dengan token Section 17 |
| Database | PostgreSQL 17 + pgvector | Satu-satunya yang didukung `whereVectorSimilarTo()` |
| Pencarian semantik | Laravel AI SDK + `whereVectorSimilarTo()` | Cegah duplikasi pada katalog global; jangan gunakan pencarian katalog sebagai saldo gudang |
| RBAC | Spatie Laravel-Permission (mode dinamis) | Role & permission sebagai data; scoping lokasi lewat `user_gudang`, bukan nama permission per gudang |
| Master & saldo per gudang | `master_barangs` dan `barangs.master_barang_id` + `barangs.gudang_id` | Identitas barang global; record lokal per gudang mempertahankan ID yang digunakan transaksi. Saldo, penerimaan, reservasi, mutasi, dan laporan tetap terpisah per gudang |
| Cache & Queue | Redis 7 (fitur non-impor/ekspor) | Cache master data/matriks permission dan job asinkron seperti embedding; impor/ekspor tidak bergantung pada Redis atau worker |
| Audit Trail | spatie/laravel-activitylog atau `audit_log` kustom | Jejak siapa mengubah apa |
| Notifikasi | Laravel Notifications (database channel) | Reminder stok minimum & expired |
| Monitoring performa (dev) | Laravel Telescope / Debugbar | Deteksi N+1 & query lambat sebelum go-live |
| Lingkungan Lokal | Laragon 6.0 | Ditetapkan wajib |

---

### 12. Ideal & Developer-Friendly File Structure (Domain-Driven Approach)

Untuk memaksimalkan efisiensi tim pengembang (*developer experience*) dan mencegah duplikasi kode (*DRY principle*), struktur folder Laravel 13 dikelompokkan berdasarkan **Domain Fitur (Business Domain)**, bukan berdasarkan Role.

> **Alasan Arsitektural:**
> 1. **Role Bersifat Dinamis:** Role dalam Spatie Permission adalah data dinamis (dapat ditambah Super Admin). Mengunci folder berdasarkan nama role akan membingungkan saat ada role baru.
> 2. **Mencegah Duplikasi Kode (DRY):** Katalog Barang global dikelola melalui satu `BarangController`; saldo dan transaksi memakai konteks gudang aktif yang diotorisasi, bukan filter gudang pada master barang.
> 3. **Navigasi IDE Cepat:** Hirarki folder tidak terlalu dalam, mempermudah pencarian file (Ctrl+P) dan keterbacaan proyek.

```text
app/
├── Enums/                                 # Enumerasi tipe bisnis yang stabil
│   ├── GudangJenisEnum.php                # gudang_umum | gudang_store
│   ├── StokOpnameJenisEnum.php            # harian | bulanan
│   ├── StokOpnameStatusEnum.php           # draft | submitted | finalized
│   └── ReturKondisiEnum.php               # baik | rusak
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                         # 🛠️ Manajemen Sistem & RBAC (Super Admin)
│   │   │   ├── DashboardController.php    # Dashboard Khusus Super Admin
│   │   │   ├── UserController.php         # CRUD Akun Pegawai
│   │   │   ├── RoleController.php         # Matriks Permission & Role Dinamis
│   │   │   ├── GudangController.php       # Master Lokasi Gudang
│   │   │   └── ActivityLogController.php  # Log Audit Akses
│   │   │
│   │   ├── Master/                        # 📦 Data Master & Katalog global sesuai permission
│   │   │   ├── BarangController.php       # CRUD katalog global sesuai permission
│   │   │   ├── KategoriController.php     # Kategori katalog global
│   │   │   └── SatuanController.php       # Master Satuan Global
│   │   │
│   │   ├── Transaksi/                     # 🔄 Operasional Gudang (Sukahati & Store)
│   │   │   ├── StokOpnameController.php   # Stok Opname Fisik vs Sistem
│   │   │   ├── PermintaanBarangController.php # PR & Referensi PO Mac.id
│   │   │   ├── PenerimaanController.php   # Penerimaan Sukahati (Normal & Tanpa Surat Jalan)
│   │   │   ├── PenerimaanStoreController.php # Penerimaan Gudang Store (Form minimal khusus)
│   │   │   ├── PengeluaranController.php  # Pengeluaran + Digital Signature + Auto BPB
│   │   │   ├── LabelProdukController.php  # Cetak Label (Expired & Kode Area)
│   │   │   ├── ReturBarangController.php  # Retur dari Produksi
│   │   │   └── PiketMalamController.php   # Transaksi Bumbu Piket Malam
│   │   │
│   │   ├── Laporan/                       # 📊 Laporan & Berita Acara
│   │   │   ├── LaporanStokController.php  # Rekap Stok & Export PDF/Excel
│   │   │   └── BeritaAcaraController.php  # Dokumen Resmi Selisih Opname
│   │   │
│   │   └── Portal/                        # 🌐 Halaman Utama & Akses Pengguna
│   │       ├── WelcomeController.php      # Landing Page Publik (Tanpa Login)
│   │       ├── LoginController.php        # Form & Sesi Autentikasi Pengguna (Login & Logout)
│   │       ├── ActiveGudangController.php # Switcher Gudang Aktif di Session Header
│   │       └── ProfileController.php      # Profil & Ganti Password Self-service
│   │
│   ├── Middleware/
│   │   ├── EnsureUserHasGudangAccess.php  # Cek otorisasi user ke gudang_id tertentu
│   │   └── SetActiveGudangContext.php     # Otomatis atur active_gudang_id di Session
│   │
│   └── Requests/                          # Form Requests Terisolasi per Domain Fitur
│       ├── Admin/                         # StoreUserRequest, StoreRoleRequest, StoreGudangRequest
│       ├── Master/                        # StoreBarangRequest, StoreSatuanRequest, StoreKategoriRequest
│       └── Transaksi/                     # StoreOpnameRequest, StorePenerimaanRequest, StorePengeluaranRequest, StoreLabelRequest, dll.
│
├── Services/                              # Layer Logika Bisnis (Domain Services)
│   ├── Admin/                             # UserService, RoleService, GudangService
│   ├── Master/                            # BarangService, SatuanService
│   ├── Transaksi/                         # OpnameService, PenerimaanService, PenerimaanStoreService, PengeluaranService, LabelService, ReturService
│   └── Shared/                            # StokService (Hitung saldo berjalan), BarangSearchService (Vector semantic search)
│
├── Models/                                # Eloquent Models (PHP 8.4 Property Hooks & Asymmetric Visibility)
│   ├── User.php
│   ├── Gudang.php
│   ├── UserGudang.php                     # Pivot User <-> Gudang
│   ├── Satuan.php
│   ├── KategoriBarang.php
│   ├── Barang.php                         # Katalog global + kolom vector pgvector
│   ├── StokOpname.php
│   ├── StokOpnameDetail.php
│   ├── PermintaanBarang.php
│   ├── PurchaseOrderReferensi.php
│   ├── PenerimaanBarang.php
│   ├── PenerimaanBarangStore.php
│   ├── PengeluaranBarang.php
│   ├── LabelProduk.php
│   ├── ReturBarang.php
│   ├── BeritaAcaraKehilangan.php
│   ├── StokBeku.php
│   ├── AuditLog.php
│   └── TransferBarang.php                 # Pemindahan dari workflow Pengeluaran Store
│
├── Policies/                              # Model Authorization Policies
│   ├── UserPolicy.php
│   ├── RolePolicy.php
│   ├── GudangPolicy.php
│   ├── BarangPolicy.php
│   ├── StokOpnamePolicy.php
│   ├── PenerimaanBarangPolicy.php
│   ├── PengeluaranBarangPolicy.php
│   └── LabelProdukPolicy.php
│
├── Jobs/                                  # Background jobs untuk fitur yang memerlukan queue
│   ├── GenerateBarangEmbeddingJob.php     # Background embedding vector update
│   ├── DeteksiStokBekuJob.php             # Idle > 12 bulan
│   ├── DeteksiExpiredJob.php              # Bumbu mendekati expired
│   └── CekStokMinimumJob.php              # Ambang batas stok minimum
│
├── Notifications/                         # Database & Mail Notifications
│   ├── StokMinimumNotification.php
│   └── BumbuMendekatiExpiredNotification.php
│
├── Providers/
│   └── AppServiceProvider.php
│
bootstrap/
├── app.php                                # Registrasi Middleware & Exception Handler Laravel 13
└── providers.php

config/
├── app.php
├── auth.php
├── database.php                           # Postgres 17 + pgvector config
├── permission.php                         # Spatie Laravel-Permission config
├── cache.php                              # Cache driver aplikasi; impor/ekspor tidak memakai Redis
└── queue.php                              # Queue untuk fitur background non-impor/ekspor

database/
├── factories/                             # Model Factories untuk Automated Testing
│   ├── UserFactory.php
│   ├── GudangFactory.php
│   ├── BarangFactory.php
│   └── SatuanFactory.php
├── migrations/                            # File Migrasi Terurut Berdasarkan Dependensi
│   ├── 2026_01_01_000001_create_gudang_table.php
│   ├── 2026_01_01_000002_create_satuan_table.php
│   ├── 2026_01_01_000003_create_kategori_barang_table.php
│   ├── 2026_01_01_000004_create_barang_table.php
│   ├── 2026_01_01_000005_create_roles_and_permissions_tables.php
│   ├── 2026_01_01_000006_create_user_gudang_table.php
│   ├── 2026_01_01_000007_create_stok_opname_table.php
│   ├── 2026_01_01_000008_create_stok_opname_detail_table.php
│   ├── 2026_01_01_000009_create_permintaan_barang_table.php
│   ├── 2026_01_01_000010_create_purchase_order_referensi_table.php
│   ├── 2026_01_01_000011_create_penerimaan_barang_table.php
│   ├── 2026_01_01_000012_create_penerimaan_barang_store_table.php
│   ├── 2026_01_01_000013_create_pengeluaran_barang_table.php
│   ├── 2026_01_01_000014_create_label_produk_table.php
│   ├── 2026_01_01_000015_create_retur_barang_table.php
│   ├── 2026_01_01_000016_create_berita_acara_kehilangan_table.php
│   ├── 2026_01_01_000017_create_stok_beku_table.php
│   ├── 2026_01_01_000018_create_audit_log_table.php
│   └── 2026_01_01_000019_create_store_request_transfer_receipt_tables.php # Pengeluaran Store & penerimaan unit (MVP)
└── seeders/
    ├── DatabaseSeeder.php
    ├── GudangSeeder.php                   # Seed "Gudang Sukahati" & "Gudang Store"
    ├── SatuanSeeder.php                   # 18 Satuan fisik default
    ├── PermissionSeeder.php               # Granular permissions
    ├── RoleSeeder.php                     # 4 Role default + default permissions
    └── UserSeeder.php                     # Account Super Admin awal

resources/
├── css/
│   └── app.css                            # Tailwind CSS setup dengan Color Tokens
├── js/
│   ├── alpine/                            # Komponen Interaktif Ringan Alpine.js
│   │   ├── signature-pad.js              # Digital Signature Canvas
│   │   ├── barang-semantic-search.js     # Semantic vector search live input
│   │   ├── finalisasi-harian.js          # Modal konfirmasi lock data harian
│   │   ├── selisih-live-preview.js       # Live preview selisih opname
│   │   └── active-gudang-switcher.js     # Switcher Gudang Aktif dropdown
│   ├── bootstrap.js
│   └── app.js                             # Entry point Alpine.js registration
└── views/
    ├── welcome.blade.php                  # Landing Page Publik
    ├── layouts/
    │   ├── app.blade.php                  # Main layout (Header + Sidebar + Flash Message)
    │   └── guest.blade.php                # Layout Auth / Landing
    ├── components/                        # Reusable Blade View Components
    │   ├── header.blade.php
    │   ├── sidebar.blade.php              # Sidebar dinamis (Item muncul sesuai Spatie Permission)
    │   ├── active-gudang-selector.blade.php
    │   ├── stats-card.blade.php
    │   ├── status-badge.blade.php
    │   ├── modal.blade.php
    │   ├── alert.blade.php
    │   └── table.blade.php
    ├── auth/
    │   └── login.blade.php
    ├── admin/                             # 🛠️ Views Admin (Dashboard, Users, Roles, Gudang, Audit Log)
    │   ├── dashboard.blade.php            # Dashboard khusus Super Admin
    │   ├── users/ (index, create, edit, show)
    │   ├── roles/ (index, create, edit, show)
    │   ├── gudang/ (index, create, edit)
    │   └── activity-log/ (index, show)
    ├── master/                            # 📦 Views Master Data (Barang, Kategori, Satuan)
    │   ├── barang/ (index, create, edit, show)
    │   ├── kategori/ (index, create, edit)
    │   └── satuan/ (index, create, edit)
    ├── transaksi/                         # 🔄 Views Operasional Transaksi Gudang
    │   ├── opname/ (index, create, show)
    │   ├── permintaan/ (index, create, show)
    │   ├── penerimaan/ (index, create, show)
    │   ├── penerimaan-store/ (index, create, show)
    │   ├── pengeluaran/ (index, create, show)
    │   ├── label/ (index, create, print)
    │   ├── retur/ (index, create)
    │   └── piket-malam/ (index, create)
    └── laporan/                           # 📊 Views Laporan & Berita Acara
        ├── stok/ (index, export)
        └── berita-acara/ (index, show, pdf)

routes/
├── web.php                                # Route Groups dikelompokkan per Domain & Spatie Middleware
├── auth.php                               # Authentifikasi routes (login, logout, profile)
└── console.php                            # Scheduled Tasks (deteksi stok beku, expired, min stok)

storage/
└── app/
    └── public/
        ├── signatures/                    # File PNG tanda tangan digital pengeluaran
        └── foto-bukti/                    # Foto surat jalan / faktur penerimaan
```

---


---

### 13. Acceptance Criteria

**US1–US4 — Master data & fondasi**
- [ ] Master Barang menyimpan identitas global; pendaftaran barang lokal dan kode lokal unik dalam gudang masing-masing
- [ ] Daftar Master Barang menunjukkan nama gudang tempat barang terdaftar tanpa menampilkan stok, stok minimum, atau lokasi rak
- [ ] Quick-Add dan import menghubungkan barang lokal ke Master Barang; keduanya tidak mengubah identitas master yang sudah ada secara diam-diam
- [ ] Impor Master Barang mempertahankan wizard pilih file, pemetaan kolom, review, dan konfirmasi. File Excel/CSV diunggah sementara untuk dibaca Laravel Excel secara chunked; file sumber tidak disimpan sebagai arsip setelah analisis
- [ ] Impor Master Barang dibatasi maksimal 25 MB dan 5.000 baris data; pengguna memilih sheet/baris header dan meninjau hasil analisis per baris sebelum mengonfirmasi pemrosesan
- [ ] Analisis dan pemrosesan Impor Master Barang berjalan sinkron tanpa queue/worker; pembacaan dan penulisan dibatasi dalam chunk 250 baris dan tidak mengubah data sebelum pengguna mengonfirmasi
- [ ] Status review impor membedakan duplikat dari error: duplikat adalah kode yang berulang dalam file atau data identik yang sudah terdaftar di gudang tujuan dan akan dilewati; error adalah data tidak valid, master nonaktif/terhapus, atau konflik ketika kode yang sama memiliki nama, kategori, atau satuan berbeda. Master yang identik tetapi belum terdaftar di gudang tujuan siap didaftarkan ulang menggunakan master yang sama
- [ ] Kategori dan satuan global yang belum tersedia untuk Master Barang baru ditandai pada review dan dibuat saat konfirmasi impor, sesuai permission masing-masing (`kategori.create` untuk kategori dan `satuan.create` untuk satuan); kode satuan yang dimasukkan dicocokkan ke kode master aktif agar review dan nama satuan memakai nama lengkap master (termasuk alias `LTR` menjadi `Liter`); pratinjau tidak membuat data, dan kategori/satuan nonaktif atau terhapus tidak diaktifkan atau dibuat duplikat secara diam-diam
- [ ] Master Barang yang sudah ada digunakan kembali tanpa perubahan identitas secara diam-diam; barang lokal baru didaftarkan pada gudang yang diotorisasi untuk pengguna
- [ ] ID `barangs` dan relasi transaksi yang ada tetap dipertahankan; saldo dan transaksi tetap terisolasi berdasarkan gudang
- [ ] Pencarian katalog menggunakan cakupan hak lihat; pencarian stok/mutasi hanya membandingkan data pada gudang aktif
- [ ] Satuan tetap dari master data global, dapat ditambah kapan pun tanpa perubahan kode
- [ ] Stok minimum berada pada record lokal `Barang` sehingga dapat berbeda antar-gudang dan tidak ditampilkan di katalog Master Barang

**US5 — Welcome Page**
- [ ] Dapat diakses tanpa login; tombol "Masuk" mengarah ke halaman login
- [ ] Tidak ada pendaftaran mandiri; teks eksplisit arahkan hubungi admin gudang

**US6–US27 — Modul Gudang Sukahati**
- [ ] (Sama seperti kriteria pada revisi sebelumnya — opname per gudang, penerimaan dengan/tanpa surat jalan, pengeluaran dengan tanda tangan digital, label dengan nomor seri otomatis, retur segala kategori, finalisasi hanya oleh pemegang permission `stok_opname.finalisasi`, reminder & expired per gudang)
- [ ] Seluruh form disubmit lewat POST standar (Form Request validasi server-side), redirect dengan flash message — bukan submit via SPA state

**US28–US30 — Katalog, stok, dan penerimaan Store**
- [ ] Pengguna dengan permission lihat dapat browse/search Master Barang dan melihat gudang tempat tiap master terdaftar; katalog tidak menampilkan jumlah stok maupun stok minimum
- [ ] Super Admin mengelola Master Barang global; kategori tetap dikelola sebagai atribut master melalui halaman Master Barang
- [ ] Saldo, penerimaan, alokasi, pengeluaran, kartu stok, dan laporan Store selalu dibatasi pada Gudang Store aktif yang diotorisasi
- [ ] Saldo Store tidak pernah digabung dengan saldo Gudang Sukahati/unit dalam kalkulasi apa pun
- [ ] Stok fisik dihitung dari Stok Awal + penerimaan layak pakai − pemindahan yang sudah dikirim; alokasi `diminta` hanya mengurangi stok tersedia, bukan saldo fisik
- [ ] Impor Data Awal Store hanya dapat diakses dengan permission `stok_awal.import` dan selalu dibatasi ke Gudang Store yang diotorisasi
- [ ] File Excel/CSV Impor Data Awal Store maksimal 100 MB dan 50.000 baris data, dibaca dengan Laravel Excel secara chunked; salinan upload hanya sementara dan dihapus setelah analisis/pemrosesan berakhir
- [ ] Analisis Impor Data Awal menerima maksimal 50.000 baris dan berjalan sinkron untuk menghasilkan validasi per baris, duplikasi, serta ringkasan sebelum pengguna mengonfirmasi pemrosesan
- [ ] Setelah konfirmasi, baris valid Impor Data Awal diproses sinkron per chunk tanpa `ShouldQueue`, worker, atau ketergantungan Redis; status dan ringkasan hasil tidak bergantung pada cache Redis
- [ ] Impor Data Awal tidak membuat catatan stok awal kedua untuk barang yang sudah memiliki stok awal pada gudang yang sama; catatan hasil impor menandai sumbernya sebagai `import`
- [ ] Form penerimaan Gudang Store memakai Controller & Blade view terpisah dari Gudang Sukahati (`PenerimaanBarangStoreController`), bukan reuse form yang sama
- [ ] Penerimaan supplier menyimpan dokumen dan detail secara atomik; surat jalan `ada`/`belum_ada` eksplisit, alasan wajib saat belum ada, serta kuantitas rusak tidak boleh melebihi fisik diterima
- [ ] Kriteria lapangan penerimaan (field/bukti wajib) divalidasi terhadap SOP Gudang Store — lihat Open Questions #1

**US40 — Pengeluaran Barang Gudang Store**
- [ ] Sidebar memiliki satu menu **Pengeluaran Barang** dengan dua tab: **Permintaan** dan **Pemindahan**
- [ ] Permintaan yang diajukan user unit terikat ke gudang peminta/tujuan; Store hanya melihat permintaan yang ditujukan ke Gudang Store aktif
- [ ] Tab Permintaan menampilkan unit/peminta, catatan, rincian barang, status, sisa permintaan, saldo fisik, reservasi, dan stok tersedia
- [ ] Memproses permintaan mengalokasikan jumlah dan membuat pemindahan berstatus `diminta`; stok belum berkurang pada tahap alokasi
- [ ] Admin Store dapat membuat pemindahan langsung tanpa permintaan ke gudang unit aktif; nomor surat jalan dibuat, `permintaan_barang_id` kosong, dan stok hanya direservasi sampai pengiriman dikonfirmasi
- [ ] Pencarian barang untuk pemindahan langsung dibatasi pada katalog yang tersedia bagi Store dan menampilkan stok setelah reservasi tanpa memuat seluruh katalog ke halaman
- [ ] Alokasi parsial tetap menyisakan kuantitas permintaan yang dapat diproses kembali; total alokasi tidak boleh melampaui sisa permintaan atau stok tersedia yang belum dialokasikan
- [ ] Permintaan hanya dapat ditolak sebelum alokasi dibuat dan wajib menyimpan alasan penolakan; bila pembatalan setelah alokasi diizinkan, pemindahan dan reservasi harus dikoreksi melalui aksi terotorisasi yang diaudit
- [ ] Tab Pemindahan hanya menampilkan transaksi dengan Gudang Store aktif sebagai gudang asal; pemindahan `diminta` dapat dikonfirmasi menjadi `dikirim`
- [ ] Kuantitas yang dikirim tidak boleh melebihi alokasi dan diverifikasi ulang terhadap stok tersedia di server
- [ ] Selisih antara alokasi dan qty aktual dikirim dilepas kembali menjadi sisa permintaan, tidak hilang dari pencatatan
- [ ] Stok Store berkurang saat pemindahan dikonfirmasi `dikirim`, bukan saat dialokasikan; pemindahan yang dikirim tampak sebagai dalam perjalanan sampai diterima unit
- [ ] Menu dan semua aksi Pengeluaran dilindungi permission `store.distribusi`; akses dibatasi pada gudang Store yang ditugaskan/aktif
- [ ] Permintaan tidak dianggap `selesai` hanya karena Store mengirim; seluruh qty harus diterima unit atau diselesaikan melalui keputusan discrepancy yang tercatat

**US41 — Permintaan unit dan konfirmasi penerimaan**
- [ ] Admin/Recording unit membuat permintaan terhadap gudang Store dengan barang, satuan, qty, tujuan, dan catatan yang tervalidasi
- [ ] Penerima unit yang berwenang melihat surat jalan yang ditujukan ke gudangnya dan mencatat qty/kondisi aktual diterima
- [ ] Konfirmasi unit mengubah pemindahan `dikirim` menjadi `diterima` dan membuat/menautkan transaksi penerimaan pada saldo gudang tujuan
- [ ] Qty kurang/rusak dicatat sebagai discrepancy; sistem tidak menyamakan qty diminta/dikirim dengan qty aktual diterima
- [ ] Permintaan berubah `diproses` menjadi `selesai` hanya setelah semua qty terpenuhi/diresolusikan dan tidak ada kiriman terbuka
- [ ] Penerima unit hanya dapat mengonfirmasi transaksi pada gudang yang ditugaskan; aksi dicatat dengan aktor dan waktu
**US31–US38 — RBAC dinamis & manajemen sistem**
- [ ] Super Admin dapat membuat role baru dengan nama unik dan mengatur permission-nya
- [ ] 4 role default (Admin Gudang Umum, Recording Gudang Umum, **Admin Gudang Store**, Super Admin) ter-seed dengan permission bawaan sesuai konfigurasi RBAC yang disepakati
- [ ] Satu pengguna dapat ditugaskan ke satu atau lebih gudang; data yang terlihat otomatis gabungan dari gudang-gudang tersebut
- [ ] Pengguna tanpa role aktif atau tanpa gudang aktif tidak dapat mengakses halaman transaksi manapun
- [ ] Perubahan permission suatu role langsung berlaku (cache di-invalidate otomatis), tanpa perlu logout/login
- [ ] Role bawaan tidak dapat dihapus jika masih dipakai minimal satu pengguna aktif
- [ ] Super Admin tidak memiliki permission apa pun ke data transaksi kecuali ditambahkan eksplisit
- [ ] Gudang baru dapat ditambah dengan memilih `jenis` (gudang_umum/gudang_store); gudang yang sudah punya transaksi tidak dapat dihapus, hanya dinonaktifkan

**Non-Functional — Standar Admin & Alpine.js**
- [ ] Tidak ada dependensi Filament, Livewire, atau SPA framework di codebase
- [ ] Seluruh interaktivitas klien (modal, toggle, signature pad, live preview) memakai Alpine.js dengan `x-data` yang di-scope lokal per komponen, tidak ada state global yang dibagi lintas halaman
- [ ] Seluruh form utama tetap berfungsi penuh meski JavaScript gagal dimuat (progressive enhancement — validasi server tetap jadi sumber kebenaran)

---

### 14. Open Questions & Risks

- **Q1 (validasi operasional US29):** SOP/proses penerimaan barang Gudang Store belum pernah didokumentasikan secara resmi (berbeda dari SOP Gudang Umum yang sudah dipetakan penuh di Tahap 1). Implementasi penerimaan Store sudah tersedia dengan referensi supplier/dokumen dan jalur tanpa surat jalan; Manajemen dan Admin Store tetap perlu mengonfirmasi apakah field, bukti, dan pengecualiannya sesuai praktik lapangan. — *Owner: Manajemen & Admin Gudang Store*
- **Q2:** Pemindahan Store dapat berasal dari permintaan atau dibuat langsung ke gudang unit yang tercatat. Apakah Gudang Store juga membutuhkan opname, retur, atau label? Fitur tambahan tersebut tetap belum diasumsikan masuk scope. — *Owner: Manajemen*
- **Q3 (discrepancy dan pembatalan):** Jika qty yang diterima unit lebih sedikit/rusak, apakah sisa harus tetap menjadi permintaan terbuka, masuk proses klaim, atau dapat ditutup dengan penyesuaian beralasan? Siapa yang boleh membatalkan alokasi/pemindahan `diminta` dan bagaimana alasan serta pelepasan reservasi disetujui? Default aman: jangan tutup/ubah qty tanpa keputusan tercatat. — *Owner: Manajemen, Admin Store, Admin Unit*
- **Q4:** Apakah peran di Gudang Store perlu dipecah seperti Gudang Sukahati (Admin + Recording terpisah), atau cukup satu role gabungan "Admin Gudang Store" seperti diasumsikan PRD ini? — *Owner: Manajemen*
- **Keputusan Q5:** Kategori adalah metadata katalog global, bukan data per gudang. (Selesai)
- **Q6:** Apakah ada API/webhook resmi Mac.id untuk sinkronisasi PO, atau tetap manual? — *Owner: Engineering & Manajemen*
- **Q7:** Nama produk resmi — "SIMS-GU" atau "SIGMA"? Jika SIGMA, apakah modul Aset & Distribusi pada mockup lama memang direncanakan? — *Owner: Manajemen*
- **Q8:** Satu pengguna bisa punya lebih dari satu role sekaligus, atau selalu tepat satu? — *Owner: Manajemen*
- **Q9:** "lbr" vs "lmbr" — sudah tidak butuh migrasi ulang karena satuan adalah master data; tinggal nonaktifkan salah satu kapan pun diputuskan. — *Owner: Admin Gudang Umum*
- **Risk:** Tanpa Filament/Livewire, setiap halaman CRUD (barang, satuan, gudang, role, dst.) harus ditulis manual (Controller + View + Form Request) — volume kode lebih banyak dibanding pakai admin package, tapi lebih ringan saat runtime dan lebih mudah dioptimasi manual. — *Mitigasi: buat 1–2 Blade layout/partial generik (tabel + form) yang dipakai ulang lintas modul, agar tidak menulis markup berulang dari nol.*
- **Risk:** Katalog global yang dipakai bersama dapat menampilkan barang tanpa saldo pada Gudang Store; UI harus membedakan master barang dari saldo gudang dan semua operasi transaksi harus memeriksa gudang aktif.
- **Risk:** RBAC dinamis + multi-gudang berarti kombinasi role×permission×gudang cukup kompleks untuk diaudit manual. — *Mitigasi: halaman Kelola Role menampilkan preview eksplisit "user dengan role X + gudang Y bisa mengakses apa saja" sebelum perubahan disimpan.*
- **Risk:** Proses migrasi dari kertas/Excel berisiko resistensi pengguna. — *Mitigasi: dual-run minimal 2 minggu.*

---

### 15. Rollout & Next Steps

**MVP scope:** US1–US38 dan US40–US41 (workflow Store dan serah terima unit yang dijelaskan di atas)
- Modul Gudang Sukahati: lengkap sesuai SOP yang sudah dipetakan
- Modul Gudang Store: browse katalog global sesuai permission, stok/import terotorisasi, penerimaan supplier (field final menunggu validasi Q1), dan menu Pengeluaran dengan tab Permintaan serta Pemindahan
- Alur unit terkait: mengajukan permintaan dan mengonfirmasi kuantitas yang benar-benar diterima agar saldo unit dan status permintaan dapat ditutup
- RBAC dinamis penuh, 4 role default, penugasan user ke gudang
- Welcome page, admin standar (Blade + Alpine.js, tanpa Filament)
- Tidak termasuk: transfer bebas lintas gudang di luar Pengeluaran Store, pengeluaran stok tanpa tujuan/penerima yang tercatat, modul Aset/Distribusi lintas cabang, integrasi Mac.id API, serta proses Store tambahan (opname, retur, label) sampai dikonfirmasi

**Phase 2+ ideas:**
- Perluasan proses Store di luar browse katalog, stok, penerimaan, dan workflow Pengeluaran Permintaan → Pemindahan jika Q1–Q4 mengonfirmasi kebutuhan tersebut
- Integrasi API Mac.id
- Modul Aset & Distribusi (jika dikonfirmasi nyata)
- Dashboard analitik lintas gudang untuk Manajemen

**Sign-off needed from:**
- [ ] Manajemen/Owner CV RPA Sukahati — konfirmasi Q1–Q4 dan Q6–Q9, terutama kesesuaian alur penerimaan dan Pengeluaran Gudang Store dengan SOP lapangan
- [ ] Admin Gudang Umum, Recording Gudang Umum, Admin Gudang Store — validasi alur harian masing-masing
- [ ] Engineering lead — konfirmasi arsitektur Blade+Alpine (tanpa Filament/Livewire) dan strategi migrasi data

**Next steps:**
1. **Validasi SOP Gudang Store** (setara Tahap 1 untuk Gudang Umum) dan rekonsiliasi dengan alur penerimaan serta Pengeluaran Store yang sudah ditetapkan — *Owner: BA/Product & Admin Store*
2. Penyusunan ERD detail (index, constraint), termasuk index pada seluruh `gudang_id` — *Owner: Engineering*
3. Setup proyek Laravel 13 + PostgreSQL 17 + pgvector + Spatie Permission + Tailwind + Alpine.js di Laragon 6.0 — *Owner: Engineering*
4. Seed data awal: 4 role, 2 gudang (Sukahati, Store), 18 satuan — *Owner: Engineering*
5. Migrasi data master Gudang Sukahati dari Excel existing — *Owner: Admin Gudang Umum & Engineering*
6. Masa uji paralel 2 minggu sebelum go-live penuh — *Owner: Operasional Gudang*

---

### 16. Performance & Anti-Pattern Guardrails (AI-Assisted Development)

Karena admin panel sekarang dibangun manual (bukan Filament), guardrail berikut jadi **lebih kritis** — tidak ada abstraksi package yang otomatis mencegah anti-pattern umum.

**Performance guardrails wajib:**
- Setiap Controller index/list **wajib** pagination Laravel bawaan (`paginate()`), dilarang `->get()` tanpa limit
- Setiap relasi yang ditampilkan di Blade (`@foreach`) wajib eager load (`with()`) di Controller — dilarang N+1 query lewat akses relasi lazy di dalam `@foreach`
- `stokSaatIni` **tidak boleh** query agregat langsung per baris tabel — wajib dari cache Redis
- Generate embedding vector wajib via Queue, tidak sinkron saat form disimpan
- Kolom `deskripsi_embedding` wajib index HNSW/IVFFlat; pencarian katalog global dibatasi permission dan `limit()`, sedangkan pencarian saldo/mutasi wajib memakai gudang aktif
- Kolom yang sering difilter/JOIN wajib index: seluruh FK termasuk `barangs.gudang_id` dan `master_barang_id`, `master_barangs.kode` (unique), kode lokal unik per gudang, dan `stok_opname.periode`
- Matriks permission wajib di-cache dan **di-invalidate otomatis** setiap `RoleService::aturPermission()` dipanggil
- Job background untuk fitur non-impor/ekspor dapat menggunakan Redis Queue; impor/ekspor tidak menggunakan `ShouldQueue` atau worker
- **Blade view wajib memakai `@once` untuk asset/script yang diulang**, dan Alpine `x-data` **wajib berukuran kecil** — jangan menaruh seluruh dataset tabel besar sebagai JSON di `x-data` (bikin payload HTML membengkak); data besar tetap dirender server-side, Alpine hanya untuk state UI lokal (toggle, buka/tutup modal, input sementara)
- Dilarang `SELECT *` implisit pada tabel bervolume besar — wajib `select()` eksplisit
- Upload foto wajib di-resize/kompres sebelum disimpan
- View partial (dashboard widget) di-cache sebagai fragment (`Cache::remember` membungkus hasil render), bukan dihitung ulang tiap request

**Target performa (baseline sebelum go-live):**

| Skenario | Target Waktu Respons | Catatan |
|---|---|---|
| Buka dashboard | < 1.5 detik | Widget dari cache |
| Buka tabel daftar (pagination) | < 1 detik untuk 20–50 baris/halaman | Index pada kolom filter, termasuk `gudang_id` |
| Submit form (POST standar) | < 1 detik dari klik submit ke redirect+flash message | Tidak mencakup impor/ekspor sinkron; ukur terpisah sesuai ukuran data dan batas hosting |
| Pencarian semantik | < 800 ms | Index vector + `limit()` + otorisasi katalog; scope gudang hanya untuk saldo/mutasi |
| Cek akses (permission check) | Tidak terasa pengguna | Dari cache, bukan query join tiap request |
| Interaksi Alpine.js (modal, toggle) | Instan (<100 ms) | Murni client-side, tidak menunggu server |

**Anti-pattern yang wajib dihindari:**
- N+1 query di `@foreach` Blade
- Query tanpa index pada `gudang_id` (kolom paling sering dipakai filter di seluruh aplikasi)
- Operasi berat sinkron tanpa chunking atau batas ukuran; impor/ekspor harus memakai batas dan pembacaan/pemrosesan chunk yang ditetapkan
- Cache master data/permission yang tidak pernah di-invalidate
- Alpine `x-data` membawa dataset besar (ratusan/ribuan baris) ke DOM — gunakan pagination server-side, bukan render-semua-lalu-filter-di-client
- Polling berlebihan untuk badge notifikasi (cukup 30–60 detik)
- Upload file tanpa batas ukuran
- Menggunakan satu batas ukuran/baris untuk semua jenis impor; batas saat ini berbeda menurut alurnya: Master Barang maksimal 25 MB/5.000 baris dan Stok Awal Store maksimal 100 MB/50.000 baris
- Mengandalkan Redis, `ShouldQueue`, worker, atau cache Redis untuk menjalankan impor/ekspor
- Menganggap chunking atau Job sinkron menghilangkan batas timeout request; ukuran chunk dan kapasitas harus diuji pada hosting target
- Menganggap batas unggah aplikasi otomatis berlaku di hosting tanpa memeriksa `upload_max_filesize`, `post_max_size`, dan batas request web server

**Definition of Done tambahan:**
- [ ] Tidak ada query N+1 terdeteksi (Laravel Debugbar/Telescope)
- [ ] Seluruh kolom FK dan `gudang_id` memiliki index (`EXPLAIN ANALYZE`)
- [ ] Job asinkron hanya digunakan oleh fitur non-impor/ekspor yang memerlukannya; impor/ekspor berjalan sinkron tanpa worker
- [ ] Tidak ada Alpine `x-data` yang membawa payload data besar (>1000 baris) ke HTML
- [ ] Load test sederhana (≥50 concurrent user submit transaksi) sebelum go-live

---

### 17. Design System & Visual Guidelines

*(Prinsip sama seperti sebelumnya, disesuaikan untuk stack Blade + Tailwind + Alpine.js.)*

**Color Tokens (base):**

| Token | Hex | Penggunaan |
|---|---|---|
| `brand-primary` | `#145C54` | Navigasi, header |
| `brand-accent` | `#C97A2B` | Tombol aksi utama non-destruktif |
| `surface-base` | `#F7F6F3` | Latar halaman |
| `surface-card` | `#FFFFFF` | Latar panel/tabel |
| `text-primary` | `#1F2523` | Teks utama |
| `text-muted` | `#5B6B79` | Teks sekunder |

**Semantic Status Colors (wajib konsisten):**

| Makna | Token | Hex | Dipakai di |
|---|---|---|---|
| Kritis | `status-critical` | `#C1443C` | Expired, selisih ≠ 0, stok = 0, "Tanpa Surat Jalan" |
| Peringatan | `status-warning` | `#B8791A` | Mendekati ambang minimum/expired, kandidat stok beku |
| Aman | `status-success` | `#3F7A52` | Terfinalisasi, sesuai, selesai |
| Netral | `status-neutral` | `#5B6B79` | Draft, menunggu |

**Typography:** Font sans-serif standar; kolom angka wajib tabular figures (`font-variant-numeric: tabular-nums`); skala 20–24px judul, 14px form/tabel, 12px teks bantu.

**Layout:** Sidebar dikelompokkan per klaster fungsi — **Master Data** (Master Barang global dengan akses kelola kategori, Satuan, Gudang), **Transaksi Gudang Sukahati**, **Gudang Store** (Stok, Penerimaan Barang, satu menu Pengeluaran Barang dengan tab Permintaan dan Pemindahan), **Pengadaan**, **Laporan & Reminder**, **Pengaturan Sistem** (Super Admin). Selektor gudang aktif menonjol di header bagi user multi-gudang.

**Komponen Visual:** Badge status solid fill; tombol destruktif selalu `status-critical` + modal konfirmasi Alpine.

**Motion/Animasi (Alpine `x-transition`):**
- Hanya untuk merespons aksi: buka/tutup modal, toast konfirmasi, skeleton loading
- Dilarang animasi dekoratif tanpa pemicu aksi pengguna
- `prefers-reduced-motion` dihormati — nonaktifkan `x-transition` durasi panjang jika diminta OS/browser

**Definition of Done tambahan:**
- [ ] Tidak ada warna status di luar 4 token semantik
- [ ] Semua kolom angka tabular figures
- [ ] Tidak ada animasi non-fungsional
- [ ] Tidak ada jejak dependensi Filament/Livewire di komponen visual manapun