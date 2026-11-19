# Flowchart Alur Sistem Berdasarkan Role

Dokumen ini menggambarkan alur yang terlihat pada implementasi saat ini, terutama route di `routes/web.php`, controller, dan seeder role/permission. Empat role yang dibahas adalah **Super Admin**, **Admin Gudang Store**, **Admin Gudang Umum**, dan **Recording Gudang Umum**.

> Diagram menunjukkan fungsi yang tersedia di aplikasi, bukan berarti seluruh proses gudang harus selalu melewati semua role. Beberapa proses dapat berjalan terpisah, dan sejumlah route lama/kompatibilitas masih tersedia.

### Notasi visual

Flowchart memakai simbol standar seperti contoh gambar: **oval** untuk Mulai/Selesai, **jajar genjang** untuk menu atau keluaran/status, **persegi panjang** untuk proses, dan **belah ketupat** untuk keputusan. Label **Ya/Tidak** pada panah menunjukkan cabang keputusan. Visual utama menggunakan gambar SVG agar tetap terlihat pada pratinjau Markdown yang tidak mendukung Mermaid.

## 1. Super Admin

Super Admin mengatur akses dan data dasar organisasi serta memantau aktivitas. Ia memiliki seluruh permission yang didaftarkan oleh seeder; perannya bukan tahapan wajib dalam transaksi gudang sehari-hari.

![Flowchart Super Admin](./flowcharts/super-admin.svg)

**Keterkaitan:** mengatur siapa yang dapat menggunakan aplikasi dan gudang mana yang menjadi cakupan pengguna; memantau aktivitas melalui dashboard dan audit log. Super Admin juga dapat menjalankan modul lain karena permission-nya penuh.

## 2. Admin Gudang Store

Admin Gudang Store mengelola persediaan gudang Store dan menangani permintaan barang yang diajukan Admin Gudang Umum. Alur pengiriman baru mengurangi stok Store ketika pengiriman dikonfirmasi.

![Flowchart Admin Gudang Store](./flowcharts/admin-gudang-store.svg)

**Keterkaitan:** permintaan barang dibuat oleh Admin Gudang Umum, kemudian dialokasikan atau ditolak di Store. Setelah dikirim, Recording Gudang Umum di lokasi tujuan mencatat penerimaan fisik transfer. Admin Store juga memiliki penerimaan barang supplier, impor stok awal, kartu stok, dan laporan.

## 3. Admin Gudang Umum

Admin Gudang Umum mengelola kebutuhan stok unit, mengajukan permintaan ke Store, dan memantau persediaan serta kiriman ke unit.

![Flowchart Admin Gudang Umum](./flowcharts/admin-gudang-umum.svg)

**Keterkaitan:** Admin GU mengirim permintaan barang ke Store dan menerima informasi hasilnya. Admin GU memantau transfer, sedangkan Recording GU mencocokkan dan mencatat penerimaan fisik di unit. Recording GU juga mencatat BPB; perubahan stok unit mengikuti transaksi yang dicatat.

## 4. Recording Gudang Umum

Recording Gudang Umum mencatat kejadian fisik di gudang unit: penerimaan, penerimaan transfer, pengeluaran, dan pengembalian. Catatan transaksi memperbarui pergerakan stok unit.

![Flowchart Recording Gudang Umum](./flowcharts/recording-gudang-umum.svg)

**Keterkaitan:** menerima transfer yang dikirim Admin Store; membuat catatan penerimaan/pengeluaran yang kemudian dapat diperiksa Admin GU. Route aplikasi juga menyediakan pencatatan barang supplier langsung dan pengembalian. Stok yang ditampilkan mengikuti transaksi dan status yang dikelola service domain.

## 5. Rangkaian lintas-role: permintaan Store sampai diterima unit

1. **Admin Gudang Umum** membuka menu **Permintaan Barang ke Store**, mengisi barang, jumlah, dan tujuan, lalu mengajukan permintaan.
2. **Admin Gudang Store** membuka menu **Pengeluaran Barang** dan meninjau permintaan yang masuk.
3. Jika ditolak, status permintaan menjadi ditolak dan dapat ditinjau Admin GU. Jika diterima, Store mengalokasikan barang dan membuat dokumen pemindahan.
4. Setelah barang benar-benar dikirim, Admin Store mengonfirmasi pengiriman. Pada titik ini stok Store dipotong dan transfer menunggu penerimaan unit.
5. **Recording Gudang Umum** membuka menu **Penerimaan Fisik Barang**, mencocokkan barang fisik dengan transfer, lalu mengonfirmasi penerimaan.
6. Admin GU dapat memantau status pemindahan dan stok unit berdasarkan pencatatan penerimaan.

## 6. Ringkasan hubungan antar-role

| Role | Tanggung jawab utama | Serah-terima ke role lain |
|---|---|---|
| Super Admin | Pengguna, role/permission, gudang, dashboard, audit | Menyiapkan akses dan struktur; memantau aktivitas seluruh modul |
| Admin Gudang Store | Penerimaan dan stok Store, pemenuhan permintaan, pengiriman, laporan | Menerima permintaan dari Admin GU; menyerahkan transfer kepada Recording GU |
| Admin Gudang Umum | Permintaan ke Store, pemantauan transfer, dan stok unit | Mengajukan kebutuhan ke Store; memantau status kiriman |
| Recording Gudang Umum | Pencatatan penerimaan, konfirmasi transfer, pengeluaran, pengembalian | Mencatat kondisi fisik dan transaksi yang menjadi sumber pergerakan stok |

## Catatan pembacaan implementasi

- Alur di atas merujuk pada route/controller yang ada saat ini. Status transaksi dan perubahan stok dijalankan oleh service domain, sehingga detail status akhir dapat mengikuti validasi dan aturan service.
- Tidak semua route dikelompokkan hanya berdasarkan nama role. Sebagian menggunakan permission, sebagian menggunakan middleware role, dan beberapa route kompatibilitas masih ada. Seeder memberikan permission berbeda kepada empat role; akses efektif juga bergantung pada permission yang diberikan pada database.
- “Pemohon” atau “bagian peminta” pada transaksi pengeluaran adalah data operasional yang dicatat Recording GU, bukan role aplikasi kelima yang ditambahkan pada diagram.

## 7. Menu yang diakses setiap role

Nama menu di bawah mengikuti label sidebar di `resources/views/components/sidebar.blade.php`. Item yang tampil tetap bergantung pada permission pengguna dan gudang aktif; nama menu bukan pengganti pemeriksaan akses pada route.

| Role | Menu sidebar utama |
|---|---|
| Super Admin | **Dashboard**; **Kelola User**; **Role & Permission Matrix**; **Master Lokasi Gudang**; **Log Audit Aktivitas**; **Master Barang**; **Stok Gudang Store**; **Penerimaan Barang**; **Pengeluaran Barang**; **Laporan Gudang Store**; **Stok Barang Fisik**; **Permintaan Barang ke Store**; **Penerimaan Fisik Barang**; **Pengeluaran & Pengembalian (BPB)**. |
| Admin Gudang Store | **Dashboard Store**; **Master Barang**; **Stok Gudang Store**; **Penerimaan Barang**; **Pengeluaran Barang**; **Laporan Gudang Store**. |
| Admin Gudang Umum | **Dashboard Admin GU**; **Master Barang**; **Permintaan Barang ke Store**; **Stok Barang Fisik**. |
| Recording Gudang Umum | **Dashboard Tugas Harian**; **Master Barang**; **Stok Barang Fisik**; **Penerimaan Fisik Barang**; **Pengeluaran & Pengembalian (BPB)**. |

**Catatan menu:** seeder saat ini memberikan permission terdaftar kepada Super Admin dan permission domain berbeda kepada tiga role operasional. Sidebar juga mempertimbangkan jenis gudang aktif: misalnya, bagian operasional Unit Bisnis disembunyikan untuk pengguna yang sedang berada pada gudang bertipe Store. Menu yang tampak bisa berbeda jika role/permission di database telah diubah. Admin GU mengajukan kebutuhan melalui **Permintaan Barang ke Store** dan mencatat persediaan pada **Stok Barang Fisik**; penerimaan fisik dan pengeluaran dicatat oleh Recording GU.

## 8. Sumber Mermaid (opsional)

Gambar utama untuk setiap role sudah ditampilkan pada bagian 1–4. Sumber Mermaid berikut disimpan sebagai alternatif yang dapat diedit; buka bagian ini hanya jika ingin melihat atau menyalin kode diagram.

<details>
<summary>Tampilkan kode sumber diagram Mermaid</summary>

### 8.1 Super Admin

```mermaid
flowchart TD
    SA0((Mulai / Login)) --> SA1[/Menu: Dashboard/]
    SA1 --> SA2{Pilih menu}
    SA2 -->|Kelola User| SA3[Tambah, ubah, atau nonaktifkan akun]
    SA2 -->|Role & Permission Matrix| SA4[Atur role dan permission]
    SA2 -->|Master Lokasi Gudang| SA5[Tambah atau ubah gudang]
    SA2 -->|Master Barang| SA6[Kelola katalog barang]
    SA2 -->|Log Audit Aktivitas| SA7[Filter dan tinjau aktivitas]
    SA7 --> SA8{Perlu tindak lanjut?}
    SA8 -->|Ya| SA9[Perbaiki akun, akses, atau data terkait]
    SA9 --> SA1
    SA8 -->|Tidak| SA10[/Ekspor atau tutup log/]
    SA2 -->|Menu Store / Unit Bisnis| SA11[Masuk ke modul operasional]
    SA3 --> SA12[/Perubahan tersimpan/]
    SA4 --> SA12
    SA5 --> SA12
    SA6 --> SA12
    SA12 --> SA1
    SA11 --> SA13[/Transaksi mengikuti alur role operasional/]
    SA13 --> SA1
    SA10 --> SA14((Selesai))
    SA2 -->|Keluar| SA14
```

### 8.2 Admin Gudang Store

```mermaid
flowchart TD
    ST0((Mulai / Login)) --> ST1[/Menu: Dashboard Store/]
    ST1 --> ST2{Pilih menu}
    ST2 -->|Stok Gudang Store| ST3[Lihat stok, kartu stok, atau impor stok awal]
    ST2 -->|Penerimaan Barang| ST4[Catat barang dari supplier]
    ST4 --> ST5[/Penerimaan tersimpan; stok Store bertambah/]
    ST2 -->|Pengeluaran Barang| ST6{Pilih tab}
    ST6 -->|Permintaan| ST7[Tinjau permintaan dari Admin GU]
    ST7 --> ST8{Jumlah tersedia dan permintaan dapat dipenuhi?}
    ST8 -->|Tidak| ST9[Tolak permintaan]
    ST9 --> ST10[/Status ditolak tercatat/]
    ST8 -->|Ya| ST11[Alokasikan jumlah barang]
    ST11 --> ST12[Buat dokumen pemindahan]
    ST6 -->|Pemindahan langsung| ST13[Pilih gudang tujuan, barang, dan jumlah]
    ST13 --> ST12
    ST12 --> ST14{Barang sudah dikirim?}
    ST14 -->|Tidak| ST15[/Menunggu konfirmasi kirim/]
    ST15 --> ST14
    ST14 -->|Ya| ST16[Konfirmasi pengiriman]
    ST16 --> ST17[/Stok Store dipotong; transfer menunggu penerimaan unit/]
    ST17 --> ST18[Recording GU cocokkan dan konfirmasi barang tiba]
    ST6 -->|Histori| ST19[/Tinjau dokumen dan status/]
    ST2 -->|Laporan Gudang Store| ST20[/Lihat atau ekspor laporan bila berhak/]
    ST3 --> ST1
    ST5 --> ST1
    ST10 --> ST1
    ST18 --> ST1
    ST19 --> ST1
    ST20 --> ST1
    ST2 -->|Keluar| ST21((Selesai))
```

### 8.3 Admin Gudang Umum

```mermaid
flowchart TD
    GU0((Mulai / Login)) --> GU1[/Menu: Dashboard Admin GU/]
    GU1 --> GU2{Pilih menu}
    GU2 -->|Permintaan Barang ke Store| GU3[Pilih barang, jumlah, dan tujuan gudang]
    GU3 --> GU4{Validasi permintaan lolos?}
    GU4 -->|Tidak| GU5[Perbaiki data permintaan]
    GU5 --> GU3
    GU4 -->|Ya| GU6[Ajukan permintaan ke Store]
    GU6 --> GU7[/Menunggu proses Store/]
    GU7 --> GU8{Hasil proses Store}
    GU8 -->|Ditolak| GU9[/Tinjau status dan riwayat permintaan/]
    GU8 -->|Dikirim| GU10[/Recording GU mengonfirmasi penerimaan fisik/]
    GU10 --> GU11[/Pantau status pemindahan/]
    GU2 -->|Stok Barang Fisik| GU12[/Lihat saldo atau kartu stok/]
    GU2 -->|Master Barang| GU13[Kelola katalog barang]
    GU9 --> GU1
    GU11 --> GU1
    GU12 --> GU1
    GU13 --> GU1
    GU2 -->|Keluar| GU24((Selesai))
```

### 8.4 Recording Gudang Umum

```mermaid
flowchart TD
    RG0((Mulai / Login)) --> RG1[/Menu: Dashboard Tugas Harian/]
    RG1 --> RG2{Pilih menu}
    RG2 -->|Penerimaan Fisik Barang| RG3{Asal barang?}
    RG3 -->|Supplier| RG4[Isi supplier, dokumen, barang, kuantitas, dan kondisi]
    RG4 --> RG5{Data valid?}
    RG5 -->|Tidak| RG6[Perbaiki data penerimaan]
    RG6 --> RG4
    RG5 -->|Ya| RG7[Simpan penerimaan langsung]
    RG7 --> RG8[/Stok unit diperbarui/]
    RG3 -->|Transfer dari Store| RG10[Cocokkan barang fisik dan dokumen transfer]
    RG10 --> RG11{Barang dan jumlah cocok?}
    RG11 -->|Tidak| RG12[Tindak lanjuti selisih]
    RG12 --> RG10
    RG11 -->|Ya| RG13[Konfirmasi penerimaan transfer]
    RG13 --> RG14[/Transfer tercatat diterima di unit/]
    RG2 -->|Pengeluaran & Pengembalian BPB| RG15{Pilih transaksi}
    RG15 -->|Barang keluar| RG16[Isi bagian peminta, barang, jumlah, dan pemakai]
    RG16 --> RG17[Simpan BPB]
    RG17 --> RG20[/BPB tercatat; stok unit diperbarui/]
    RG15 -->|Pengembalian| RG22[Pilih barang pinjaman atau sisa dan jumlah kembali]
    RG22 --> RG23[Simpan pengembalian]
    RG23 --> RG24[/Riwayat dan saldo mengikuti transaksi/]
    RG2 -->|Stok Barang Fisik| RG25[/Lihat stok atau kartu stok/]
    RG14 --> RG1
    RG20 --> RG1
    RG24 --> RG1
    RG25 --> RG1
    RG2 -->|Keluar| RG26((Selesai))
```

</details>

## 9. Alur lintas-role dan titik serah-terima

1. **Permintaan:** Admin GU mengajukan kebutuhan melalui menu **Permintaan Barang ke Store**.
2. **Pemrosesan:** Admin Store meninjau permintaan melalui **Pengeluaran Barang**, lalu menolak atau mengalokasikan barang.
3. **Pengiriman:** Admin Store membuat pemindahan, mengirim barang, lalu mengonfirmasi kirim. Stok Store berkurang dan transfer menunggu penerimaan.
4. **Penerimaan:** Recording GU mencocokkan barang fisik dan mengonfirmasi melalui **Penerimaan Fisik Barang**.
5. **Pengeluaran unit:** Recording GU mencatat BPB melalui **Pengeluaran & Pengembalian (BPB)**; transaksi tersebut tercermin pada pergerakan stok unit.

### Cara membaca alur dan batas akses

1. Pengguna login, lalu sidebar menampilkan menu berdasarkan role, permission, dan konteks gudang aktif.
2. Memilih menu membuka halaman modul; tindakan simpan/verifikasi tetap melewati validasi dan middleware route. Menu yang terlihat saja tidak menjamin semua aksi di dalamnya diizinkan.
3. Setiap garis serah-terima menunjukkan data/status yang menjadi masukan kerja role berikutnya, bukan persetujuan otomatis.
4. Pada permintaan Store, alokasi belum sama dengan pengiriman: konfirmasi kirim adalah titik pemotongan stok Store. Penerimaan fisik di unit dilakukan Recording GU.
5. Pada pengeluaran unit, Recording GU membuat BPB; pergerakan stok unit mengikuti catatan transaksi, tanpa tahap verifikasi Admin GU.
