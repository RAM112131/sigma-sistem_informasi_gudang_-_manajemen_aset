# Entity Relationship Diagram SIGMA

Dokumen ini merangkum skema database SIGMA berdasarkan migration di `database/migrations`. Diagram menampilkan primary key (PK), foreign key (FK), unique key (UK), dan atribut bisnis utama; bukan daftar lengkap seluruh kolom. Relasi mengikuti constraint database, sehingga relasi polimorfik dan kolom yang hanya terhubung di level aplikasi dijelaskan terpisah.

## 1. Identitas, akses, dan master data

```mermaid
erDiagram
    USERS {
        bigint id PK
        varchar username UK
        varchar name
        boolean is_aktif
    }
    GUDANG {
        bigint id PK
        varchar kode UK
        varchar nama
        varchar jenis
        boolean is_aktif
    }
    USER_GUDANG {
        bigint user_id PK, FK
        bigint gudang_id PK, FK
    }
    DIVISI_PEMINTA {
        bigint id PK
        bigint gudang_id FK
        varchar kode
        varchar nama
        boolean is_aktif
    }
    SATUAN {
        bigint id PK
        bigint gudang_id FK
        varchar kode UK
        varchar nama
        boolean is_aktif
    }
    KATEGORI_BARANG {
        bigint id PK
        bigint gudang_id FK
        varchar nama
        boolean is_aktif
    }
    MASTER_BARANG {
        bigint id PK
        varchar kode UK
        bigint kategori_id FK
        bigint satuan_id FK
        varchar nama
        decimal harga_satuan
        boolean is_aktif
    }
    BARANG {
        bigint id PK
        bigint gudang_id FK
        bigint master_barang_id FK
        bigint kategori_id FK
        bigint satuan_id FK
        varchar kode
        varchar nama
        integer minimum_stok
    }
    SATUAN_KONVERSI {
        bigint id PK
        bigint barang_id FK
        bigint satuan_turunan_id FK
        decimal rasio_konversi
    }
    ROLES {
        bigint id PK
        varchar name
        varchar guard_name
    }
    PERMISSIONS {
        bigint id PK
        varchar name
        varchar guard_name
    }
    MODEL_HAS_ROLES {
        bigint role_id PK, FK
        bigint model_id PK
        varchar model_type PK
    }
    MODEL_HAS_PERMISSIONS {
        bigint permission_id PK, FK
        bigint model_id PK
        varchar model_type PK
    }
    ROLE_HAS_PERMISSIONS {
        bigint role_id PK, FK
        bigint permission_id PK, FK
    }
    AUDIT_LOG {
        bigint id PK
        bigint causer_id
        varchar causer_type
        bigint subject_id
        varchar subject_type
        varchar action
        json changes
    }

    USERS ||--o{ USER_GUDANG : terdaftar
    GUDANG ||--o{ USER_GUDANG : memiliki
    GUDANG ||--o{ DIVISI_PEMINTA : memiliki
    GUDANG o|--o{ SATUAN : memiliki
    GUDANG o|--o{ KATEGORI_BARANG : mengelompokkan
    KATEGORI_BARANG o|--o{ MASTER_BARANG : mengelompokkan
    SATUAN o|--o{ MASTER_BARANG : satuan_default
    GUDANG o|--o{ BARANG : menyimpan
    MASTER_BARANG o|--o{ BARANG : menjadi_template
    KATEGORI_BARANG o|--o{ BARANG : mengelompokkan
    SATUAN o|--o{ BARANG : satuan_default
    BARANG ||--o{ SATUAN_KONVERSI : memiliki_konversi
    SATUAN ||--o{ SATUAN_KONVERSI : satuan_turunan
    ROLES ||--o{ MODEL_HAS_ROLES : diberikan_ke_model
    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : diberikan_ke_model
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : mencakup
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : diberikan_ke_role
```

Spatie Permission memakai `model_type` dan `model_id` untuk mengaitkan role/permission dengan model apa pun; pasangan ini bukan foreign key biasa ke `users`. `audit_logs.causer_*` dan `subject_*` juga merupakan relasi polimorfik tanpa constraint foreign key.

## 2. Stok, permintaan, transfer, dan penerimaan gudang Store

```mermaid
erDiagram
    GUDANG {
        bigint id PK
        varchar kode UK
        varchar nama
    }
    USERS {
        bigint id PK
        varchar username UK
        varchar name
    }
    BARANG {
        bigint id PK
        bigint gudang_id FK
        bigint satuan_id FK
        varchar kode
        varchar nama
    }
    SATUAN {
        bigint id PK
        varchar kode UK
        varchar nama
    }
    STOK_AWAL {
        bigint id PK
        bigint gudang_id FK
        bigint barang_id FK
        bigint diinput_oleh FK
        decimal qty
        date tanggal_acuan
    }
    STOK_OPNAME {
        bigint id PK
        bigint gudang_id FK
        bigint dibuat_oleh FK
        bigint diterapkan_oleh FK
        date periode
        date tanggal_efektif
        varchar status
    }
    STOK_OPNAME_DETAIL {
        bigint id PK
        bigint stok_opname_id FK
        bigint barang_id FK
        decimal qty_sistem
        decimal qty_fisik
    }
    PERMINTAAN_BARANG {
        bigint id PK
        bigint gudang_peminta_id FK
        bigint gudang_tujuan_id FK
        bigint dibuat_oleh FK
        varchar nomor_pr UK
        varchar status
    }
    PERMINTAAN_BARANG_DETAIL {
        bigint id PK
        bigint permintaan_barang_id FK
        bigint barang_id FK
        bigint satuan_id FK
        integer qty_diminta
        integer qty_diproses
    }
    TRANSFER_BARANG {
        bigint id PK
        bigint permintaan_barang_id FK
        bigint gudang_asal_id FK
        bigint gudang_tujuan_id FK
        bigint barang_id FK
        bigint dikirim_oleh FK
        bigint diterima_oleh FK
        varchar nomor_sj UK
        varchar status
    }
    PENERIMAAN_PEMINDAHAN_UNIT {
        bigint id PK
        bigint transfer_barang_id FK, UK
        bigint gudang_id FK
        bigint barang_id FK
        bigint diterima_oleh FK
        bigint diverifikasi_oleh FK
        varchar status_verifikasi
    }
    PENERIMAAN_BARANG_STORE {
        bigint id PK
        bigint gudang_id FK
        bigint barang_id FK
        bigint satuan_id FK
        bigint diterima_oleh FK
        varchar nomor_penerimaan UK
        varchar sumber_supplier
        timestamp tanggal_diterima
    }
    PENERIMAAN_BARANG_STORE_DETAIL {
        bigint id PK
        bigint penerimaan_barang_store_id FK
        bigint barang_id FK
        bigint satuan_id FK
        integer qty_diterima
        integer qty_rusak
        integer qty_kurang
        integer qty_lebih
    }

    GUDANG o|--o{ BARANG : menyimpan
    GUDANG o|--o{ STOK_AWAL : saldo_awal
    BARANG ||--o{ STOK_AWAL : memiliki_saldo
    USERS o|--o{ STOK_AWAL : menginput
    GUDANG ||--o{ STOK_OPNAME : menjalankan
    USERS o|--o{ STOK_OPNAME : membuat
    USERS o|--o{ STOK_OPNAME : menerapkan
    STOK_OPNAME ||--o{ STOK_OPNAME_DETAIL : memiliki
    BARANG ||--o{ STOK_OPNAME_DETAIL : dihitung
    GUDANG ||--o{ PERMINTAAN_BARANG : mengajukan
    GUDANG ||--o{ PERMINTAAN_BARANG : menjadi_tujuan
    USERS o|--o{ PERMINTAAN_BARANG : membuat
    PERMINTAAN_BARANG ||--o{ PERMINTAAN_BARANG_DETAIL : memiliki
    BARANG ||--o{ PERMINTAAN_BARANG_DETAIL : diminta
    SATUAN o|--o{ PERMINTAAN_BARANG_DETAIL : satuan
    PERMINTAAN_BARANG o|--o{ TRANSFER_BARANG : dipenuhi
    GUDANG ||--o{ TRANSFER_BARANG : gudang_asal
    GUDANG o|--o{ TRANSFER_BARANG : gudang_tujuan
    BARANG ||--o{ TRANSFER_BARANG : ditransfer
    USERS o|--o{ TRANSFER_BARANG : mengirim
    USERS o|--o{ TRANSFER_BARANG : menerima
    TRANSFER_BARANG ||--o| PENERIMAAN_PEMINDAHAN_UNIT : dikonfirmasi
    GUDANG ||--o{ PENERIMAAN_PEMINDAHAN_UNIT : menerima
    BARANG ||--o{ PENERIMAAN_PEMINDAHAN_UNIT : diterima
    USERS o|--o{ PENERIMAAN_PEMINDAHAN_UNIT : menerima
    USERS o|--o{ PENERIMAAN_PEMINDAHAN_UNIT : memverifikasi
    GUDANG ||--o{ PENERIMAAN_BARANG_STORE : menerima
    BARANG o|--o{ PENERIMAAN_BARANG_STORE : barang_legacy
    SATUAN o|--o{ PENERIMAAN_BARANG_STORE : satuan_legacy
    USERS o|--o{ PENERIMAAN_BARANG_STORE : dicatat_oleh
    PENERIMAAN_BARANG_STORE ||--o{ PENERIMAAN_BARANG_STORE_DETAIL : memiliki
    BARANG ||--o{ PENERIMAAN_BARANG_STORE_DETAIL : diterima
    SATUAN o|--o{ PENERIMAAN_BARANG_STORE_DETAIL : satuan
```

Saldo awal unik untuk kombinasi gudang dan barang. Stok opname unik untuk gudang dan periode, sedangkan detail opname unik untuk opname dan barang. Header penerimaan Store menyimpan `barang_id` dan `satuan_id` nullable untuk kompatibilitas; rincian penerimaan beberapa barang berada di `penerimaan_barang_store_details`.

## 3. Penerimaan unit, pengeluaran, pengembalian, dan catatan KWh

```mermaid
erDiagram
    GUDANG {
        bigint id PK
        varchar kode UK
        varchar nama
    }
    USERS {
        bigint id PK
        varchar username UK
        varchar name
    }
    DIVISI_PEMINTA {
        bigint id PK
        bigint gudang_id FK
        varchar nama
    }
    BARANG {
        bigint id PK
        bigint gudang_id FK
        varchar kode
        varchar nama
    }
    PENERIMAAN_UNIT {
        bigint id PK
        bigint gudang_id FK
        bigint diterima_oleh FK
        bigint diverifikasi_oleh FK
        varchar nomor_rcv UK
        date tanggal_terima
        varchar status_verifikasi
    }
    PENERIMAAN_UNIT_DETAIL {
        bigint id PK
        bigint penerimaan_unit_id FK
        bigint barang_id FK
        decimal qty_diterima
        decimal qty_rusak
        decimal qty_kurang
        decimal qty_lebih
    }
    PENGELUARAN_UNIT {
        bigint id PK
        bigint gudang_id FK
        bigint divisi_peminta_id FK
        bigint dicatat_oleh FK
        bigint diverifikasi_oleh FK
        varchar nomor_bpb UK
        date tanggal
        varchar status_verifikasi
    }
    PENGELUARAN_UNIT_DETAIL {
        bigint id PK
        bigint pengeluaran_unit_id FK
        bigint barang_id FK
        decimal qty
        decimal qty_kembali
    }
    PENGEMBALIAN_UNIT {
        bigint id PK
        bigint gudang_id FK
        bigint pengeluaran_unit_id FK
        bigint pengeluaran_unit_detail_id FK
        bigint barang_id FK
        bigint dicatat_oleh FK
        decimal qty_kembali
        date tanggal_kembali
    }
    KWH_CATATAN {
        bigint id PK
        bigint gudang_id FK
        bigint pencatat_id FK
        date tanggal
        decimal total_biaya
    }

    GUDANG ||--o{ PENERIMAAN_UNIT : menerima
    USERS o|--o{ PENERIMAAN_UNIT : mencatat_penerimaan
    USERS o|--o{ PENERIMAAN_UNIT : memverifikasi_penerimaan
    PENERIMAAN_UNIT ||--o{ PENERIMAAN_UNIT_DETAIL : memiliki
    BARANG o|--o{ PENERIMAAN_UNIT_DETAIL : diterima
    GUDANG ||--o{ PENGELUARAN_UNIT : mengeluarkan
    DIVISI_PEMINTA o|--o{ PENGELUARAN_UNIT : peminta
    USERS o|--o{ PENGELUARAN_UNIT : mencatat_pengeluaran
    USERS o|--o{ PENGELUARAN_UNIT : memverifikasi_pengeluaran
    PENGELUARAN_UNIT ||--o{ PENGELUARAN_UNIT_DETAIL : memiliki
    BARANG o|--o{ PENGELUARAN_UNIT_DETAIL : dikeluarkan
    GUDANG ||--o{ PENGEMBALIAN_UNIT : menerima_pengembalian
    PENGELUARAN_UNIT o|--o{ PENGEMBALIAN_UNIT : terkait_transaksi
    PENGELUARAN_UNIT_DETAIL o|--o{ PENGEMBALIAN_UNIT : terkait_detail
    BARANG o|--o{ PENGEMBALIAN_UNIT : dikembalikan
    USERS o|--o{ PENGEMBALIAN_UNIT : mencatat_pengembalian
    GUDANG ||--o{ KWH_CATATAN : memiliki_catatan
    USERS o|--o{ KWH_CATATAN : mencatat
```

Detail penerimaan dan pengeluaran menyimpan snapshot teks barang/kategori/satuan selain FK barang opsional. Karena itu, detail historis tetap memiliki nilai snapshot walaupun relasi barang tidak tersedia.

## Catatan skema

- FK nullable digambarkan sebagai relasi opsional pada sisi record anak. Tindakan penghapusan (cascade, restrict, atau set null) tetap mengikuti migration masing-masing.
- Tabel `user_gudang` adalah pivot many-to-many antara pengguna dan gudang. `roles`–`permissions` juga many-to-many melalui `role_has_permissions`; penetapan ke model dilakukan oleh tabel polimorfik `model_has_roles` dan `model_has_permissions`.
- Tabel teknis Laravel seperti `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, dan `password_reset_tokens` tidak digambarkan sebagai entitas domain. Tabel-tabel tersebut tidak mengubah alur relasi inventori di atas.
- Fitur semantic search dapat menambahkan kolom embedding pada entitas yang mendukungnya. Kolom vector bukan relasi antar-entitas dan ketersediaannya bergantung pada konfigurasi database/pgvector.
