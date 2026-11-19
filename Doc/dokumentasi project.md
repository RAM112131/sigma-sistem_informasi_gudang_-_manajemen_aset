# Dokumentasi Analisis & Perancangan Modul Manajemen Stok Gudang
## CV RPA Sukahati

> Dokumen gabungan dari seluruh tahap analisis: pemetaan SOP ideal, gap analysis terhadap realitas lapangan, analisis peran & interaksi sistem, hingga analisis arsitektur teknis pra-coding. Disusun secara berurutan agar dapat dibaca sebagai satu alur pemikiran BA → Sistem.

## Daftar Isi

- [Tahap 1 — Pemetaan Alur Kerja Ideal (SOP Gudang Umum)](#tahap-1--pemetaan-alur-kerja-ideal-sop-gudang-umum)
- [Tahap 2 — Gap Analysis: SOP Ideal vs Realitas Lapangan](#tahap-2--gap-analysis-sop-ideal-vs-realitas-lapangan)
- [Tahap 3 — Analisis Peran & Interaksi Sistem](#tahap-3--analisis-peran--interaksi-sistem)
- [Tahap 4 — Analisis Arsitektur Teknis (Pra-Coding)](#tahap-4--analisis-arsitektur-teknis-pra-coding)

---

# Tahap 1 — Pemetaan Alur Kerja Ideal (SOP Gudang Umum)

> Disusun berdasarkan ekstraksi ketat dari dokumen `SOP.docx` yang diberikan. Tidak ada asumsi tambahan di luar isi dokumen. Bagian yang tidak memiliki keterangan proses di dalam dokumen ditandai secara eksplisit.

---

### 1. Permintaan Bahan Pelengkap

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Melakukan stock opname barang harian | Recording GU | Form Stok Opname Harian |
| 2 | Melakukan pengecekan penerimaan barang BSTB, pengeluaran barang, dan faktur pembelian hari sebelumnya | Recording GU | BSTB, Faktur Pembelian |
| 3 | Merevisi data pengeluaran Permintaan Bahan Pelengkap Gudang ketika ada selisih barang kurang | Recording GU / Admin GU | Data Pengeluaran Permintaan Bahan Pelengkap Gudang (revisi) |
| 4 | Membuat rekap manual permintaan stok teknisi & bahan pelengkap sesuai dengan pengajuan | Recording GU | Rekap Manual Permintaan Stok Teknisi & Bahan Pelengkap |
| 5 | Melaporkan hasil ke Admin Gudang | Recording GU | — |
| 6 | Menginput & mengarsipkan Laporan Stok Opname harian Gudang | Admin GU | Laporan Stok Opname Harian (terarsip) |
| 7 | Membuat PO manual bahan pelengkap | Recording GU | PO Manual Bahan Pelengkap |
| 8 | Membuat PR di Mac.id untuk dibuatkan PO oleh Purchasing | Recording GU | PR (Mac.id) |
| 9 | Menerima barang bahan pelengkap sesuai dengan PO | Recording GU | Barang diterima sesuai PO |

*Dokumen referensi pada bagian ini: Form Stok Opname Harian (kolom: NO, NAMA BARANG, tanggal, QTY dalam KG/PCS).*

---

### 2. Permintaan dan Penerimaan Bahan Bakar

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Melakukan stok opname bahan bakar bulanan | Admin GU | Stok Opname Bahan Bakar Bulanan |
| 2 | Membuat PR bahan bakar | Admin GU | PR Bahan Bakar |
| 3 | Memeriksa kesesuaian bahan bakar yang diterima dengan surat jalan | Admin GU | Surat Jalan (terverifikasi) |
| 4 | Melakukan timbang ulang dan mencatat bahan bakar di Form Penerimaan Bahan Bakar | Admin GU | Form Penerimaan/Timbangan Ulang Bahan Bakar |
| 5 | Menginput hasil timbang ulang bahan bakar di Excel dan Mac.id (bagian purchase receipt) | Admin GU | Data Excel & Mac.id (Purchase Receipt) |
| 6 | Menyerahkan surat jalan dan PO ke bagian keuangan | Admin GU | Surat Jalan & PO |
| 7 | Mengarsipkan copy surat jalan | Admin GU | Copy Surat Jalan (arsip) |

*Dokumen referensi: Form Timbangan Ulang Bahan Bakar (kolom: NO, Jumlah Barang Sesuai Surat Jalan, Jumlah Timbang Ulang).*

---

### 3. Permintaan dan Penerimaan Es

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Menginput hasil timbang Es tube di Excel dan Mac.id (purchase receipt) | Admin GU | Data Excel & Mac.id |
| 2 | Menyerahkan Surat Jalan dan PO ke bagian keuangan | Admin GU | Surat Jalan & PO |
| 3 | Mengarsipkan copy Surat Jalan & PO | Admin GU | Copy Surat Jalan & PO (arsip) |

---

### 4. Permintaan dan Penerimaan Bumbu

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Mengecek & melaporkan persediaan bumbu | Recording GU | Laporan Persediaan Bumbu |
| 2 | Merekomendasikan permintaan bumbu | Recording GU | Rekomendasi Permintaan Bumbu |
| 3 | Membuat surat jalan ke Adm Marketing | Admin GU | Surat Jalan |
| 4 | Menerima Bumbu, SPK, dan Surat Jalan | Admin GU | SPK, Surat Jalan |
| 5 | Mengecek kesesuaian surat jalan dengan fisik bumbu yang diterima | Recording GU | — |
| 6 | Melakukan pelabelan kedatangan bumbu sesuai tanggal penerimaan | Recording GU | Label Bumbu |
| 7 | Melaporkan & menyerahkan Surat Jalan kedatangan bumbu | Recording GU | Surat Jalan Kedatangan Bumbu |
| 8 | Input laporan pemasukan | Admin GU | Laporan Pemasukan |
| 9 | Mengarsipkan Surat Jalan kedatangan bumbu | Admin GU | Surat Jalan Kedatangan Bumbu (arsip) |

*Dokumen referensi: Form Stock Akhir Bumbu (kolom: NO, NAMA CUSTOMER, tanggal, QTY dalam KG/PCS).*

---

### 5. Penerimaan Bahan Pelengkap

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Memeriksa barang bahan pelengkap sesuai PO secara fisik | Recording GU | — |
| 2 | Jika sesuai: mencatat penerimaan barang di buku recording GU; jika tidak sesuai: complain ke Store | Recording GU | Buku Recording GU (bukti serah terima barang) / Komplain ke Store |
| 3 | Update harga barang bahan pelengkap di Mac.id sesuai faktur store | Recording GU | Data Harga di Mac.id |
| 4 | Input penerimaan barang bahan pelengkap di Mac.id | Recording GU | Data Penerimaan Barang (Mac.id) |
| 5 | Menyimpan barang bahan pelengkap sesuai jenis dan kategori di GU | Recording GU | — |
| 6 | Menerima surat jalan/faktur dan menandatangani sebagai bukti barang telah diterima oleh Gudang | Recording GU | Surat Jalan/Faktur (ditandatangani) |
| 7 | Menyerahkan surat jalan dan faktur ke pihak Admin GU dan Adm Keuangan | Recording GU | Surat Jalan & Faktur |
| 8 | Input penerimaan barang bahan pelengkap di Excel | Admin GU | Data Excel Penerimaan Barang |
| 9 | Mengarsipkan faktur | Admin GU | Faktur (arsip) |
| 10 | Mengarsipkan buku penerimaan barang | Recording GU | Buku Penerimaan Barang (arsip) |

*Catatan sistem dari dokumen: hasil pemeriksaan fisik dicocokkan dengan data PO pada menu "Penerimaan Barang Bahan Pelengkap"; jika sesuai → disimpan/centang; jika tidak sesuai → dilakukan "Complain Barang".*
*Dokumen referensi: Form Bukti Penerimaan Barang (kolom: NO, KODE BARANG, NAMA BARANG, QTY, KETERANGAN).*

---

### 6. Pengajuan Pembelian Non-Aset

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Membuat permintaan pengadaan barang | User | Form Permintaan Pembelian Barang Non-Aset |
| 2 | Menganalisa permintaan di lapangan | SPV | — |
| 3 | Jika disetujui, membuat bon permintaan uang | User/Staf | Bon Permintaan Uang |
| 4 | Membuat PR/PO | GU | PR/PO |
| 5 | Melakukan pembayaran & menerima faktur | Purchasing | Faktur |
| 6 | Input penerimaan dan input penjualan | Admin Gudang | Data Input Penerimaan & Penjualan |
| 7 | Membuat laporan biaya | Admin Gudang | Laporan Biaya |

*Dokumen referensi: Form Permintaan Pembelian Barang Non-Aset (kolom: NO, NAMA BARANG, BANYAK, HARGA (SATUAN + JUMLAH), KETERANGAN).*

---

### 7. Pengajuan Pembelian Aset

**Catatan:** Dokumen menandai bagian ini dengan simbol ❌ dan tidak memuat rincian langkah kerja apa pun. Tidak dapat dipetakan tanpa menambah asumsi di luar dokumen sumber.

---

### 8. Pengeluaran Bahan Pelengkap (Jam Operasional)

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Meminta barang | User | — |
| 2 | Menerima permintaan | Operator | — |
| 3 | Menyiapkan dan memberikan barang bahan pelengkap | Operator | — |
| 4 | Mencatat permintaan barang | Recording | — |
| 5 | Menandatangani form permintaan | User | Form Permintaan (ditandatangani) |
| 6 | Menyerahkan form pengeluaran barang harian ke Rec Gudang | Operator | Form Pengeluaran Barang Harian |
| 7 | Membuat BPB untuk kebutuhan akunting | Recording | BPB (Bukti Pemakaian Barang) |
| 8 | Menyerahkan form pengeluaran barang | Operator | Form Pengeluaran Barang |
| 9 | Mengumpulkan seluruh form pengeluaran barang bahan pelengkap harian | Recording | Kumpulan Form Pengeluaran Barang Harian |
| 10 | Menyerahkan laporan harian barang bahan pelengkap GU ke Ka Produksi untuk direview | Recording | Laporan Harian Barang Bahan Pelengkap |
| 11 | Mengarsipkan form permintaan barang harian | Recording | Form Permintaan Barang Harian (arsip) |
| 12 | Input pengeluaran barang pada program | Recording | Data Pengeluaran Barang (program) |
| 13 | Melakukan pencocokan stok manual dengan yang ada di program Mac.id | Recording | — |
| 14 | Input rekap pemakaian barang bahan pelengkap di Excel | Recording | Rekap Pemakaian Barang (Excel) |

*Dokumen referensi: Form Permintaan Pengeluaran Barang Non-Aset (kolom: NO, NAMA BARANG, BANYAK, HARGA (SATUAN + JUMLAH), KETERANGAN).*

---

### 9. Pengeluaran Label Produk

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Membuat label sesuai spek yang tertera di SPK | Recording GU | Label sesuai SPK |
| 2 | Mencetak label sesuai kebutuhan produksi | Recording GU | Label tercetak |
| 3 | Mengarsipkan SPK | Recording GU | SPK (arsip) |

---

### 10. Pengeluaran Bumbu

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Membawa SPK yang dibuat kepala produksi sebagai acuan untuk dibuatkan BPB pengambilan bumbu ke gudang umum | Recording Penyiapan | SPK, BPB |
| 2 | Menyiapkan bumbu sesuai yang tertera di SPK marinasi | Operator Bumbu GU | — |
| 3 | Menimbang ulang bumbu dan menghitung jumlah bumbu yang diperlukan | Operator Bumbu GU | — |
| 4 | Menerima bumbu untuk marinasi | Recording Penyiapan | — |
| 5 | Menerima dan menginput data pengembalian bumbu sisa produksi yang tidak terpakai | Rec GU | Data Pengembalian Bumbu Sisa |
| 6 | Mencatat pengeluaran bumbu | Operator Bumbu GU | Catatan Pengeluaran Bumbu |
| 7 | Menyerahkan form pengeluaran bumbu ke produksi untuk direview dan ditandatangani | Operator Bumbu GU | Form Pengeluaran Bumbu (ditandatangani) |
| 8 | Menyerahkan rekap laporan harian bumbu ke Admin GD | Operator Bumbu GU | Rekap Laporan Harian Bumbu |
| 9 | Input pengeluaran bumbu harian | Admin GU | Data Pengeluaran Bumbu Harian |
| 10 | Merekap pengeluaran bumbu harian | Admin GU | Rekap Pengeluaran Bumbu Harian |
| 11 | Menginformasikan laporan stok bumbu kepada Adm Support Marketing/Marketing | Admin GU | Laporan Stok Bumbu |
| 12 | Mengarsipkan form laporan permintaan bumbu | Admin GU | Form Laporan Permintaan Bumbu (arsip) |

---

### 11. Penyimpanan Bumbu

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Menerima kedatangan bumbu | Operator Bumbu | — |
| 2 | Mengecek kesesuaian bumbu dengan surat jalan | Operator Bumbu | — |
| 3 | Menyimpan kelebihan stok bumbu | Operator Bumbu | — |
| 4 | Menyiapkan plastik untuk packing bumbu | Operator Bumbu | — |
| 5 | Melakukan penimbangan bumbu sesuai SOP | Operator Bumbu | — |
| 6 | Melakukan penimbangan bawang putih per pack | Operator Bumbu | — |
| 7 | Menghitung ulang hasil packing bumbu untuk update stok bumbu | Operator Bumbu | — |
| 8 | Menyimpan bumbu di tempat yang sudah ditentukan sesuai kategori slow moving, fast moving, non moving | Operator Bumbu | — |
| 9 | Melaporkan dan menyerahkan update stok bumbu | Admin GU | Update Stok Bumbu |
| 10 | Mengarsipkan laporan stok bumbu | Admin GU | Laporan Stok Bumbu (arsip) |

---

### 12. Pengeluaran Bahan Pelengkap (Di Luar Jam Operasional)

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Meminta barang | User | — |
| 2 | Menerima permintaan barang | Operator | — |
| 3 | Menyiapkan dan memberikan barang bahan pelengkap sesuai permintaan | Operator | — |
| 4 | Mencatat permintaan barang | Operator | — |
| 5 | Menandatangani form permintaan/pengeluaran barang | User | Form Permintaan/Pengeluaran Barang (ditandatangani) |
| 6 | Menyerahkan form pengeluaran barang bahan pelengkap harian ke Rec GD | Operator | Form Pengeluaran Barang Harian |
| 7 | Membuat BPB untuk kebutuhan akunting | Rec GU | BPB |
| 8 | Menyerahkan form pengeluaran barang bahan pelengkap harian ke Adm GD | Operator | Form Pengeluaran Barang Harian |
| 9 | Mengumpulkan seluruh form permintaan barang bahan pelengkap harian | Adm GU | Kumpulan Form Permintaan Barang Harian |
| 10 | Menyerahkan laporan harian barang bahan pelengkap GU ke bagian produksi untuk direview | Adm GU | Laporan Harian Barang Bahan Pelengkap |
| 11 | Mengarsipkan form permintaan barang harian | Adm GU | Form Permintaan Barang Harian (arsip) |
| 12 | Input pengeluaran barang pada program Mac.id | Adm GU | Data Pengeluaran Barang (Mac.id) |
| 13 | Melakukan persamaan stok manual dengan yang ada di Mac.id | Adm GU | — |
| 14 | Menginput rekap pemakaian barang bahan pelengkap di Excel sebagai acuan laporan biaya produksi | Adm GU | Rekap Pemakaian Barang (Excel) |

---

### 13. Pengeluaran Bahan Bakar

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Mencatat dan menimbang ulang bahan bakar | Operator | — |
| 2 | Mencatat pengeluaran bahan bakar | Operator | Catatan Pengeluaran Bahan Bakar |
| 3 | Menginput pengeluaran bahan bakar di form pengeluaran | Admin GU | Form Pengeluaran Bahan Bakar |
| 4 | Menyerahkan laporan pemakaian bahan bakar kepada admin produksi dan staff akunting | Admin GU | Laporan Pemakaian Bahan Bakar |
| 5 | Mengarsipkan laporan pemakaian bahan bakar | Admin GU | Laporan Pemakaian Bahan Bakar (arsip) |

---

### 14. Pengeluaran Es

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Menyiapkan dan memberikan es sesuai BPB | User | BPB |
| 2 | Menimbang pengeluaran es | User | — |
| 3 | Mencatat pengeluaran es | Recording Es | Catatan Pengeluaran Es |
| 4 | Menganalisa kewajaran laporan pemakaian es | Admin GU | — |
| 5 | Menginput pengeluaran Es Tube di alur Es dan Mac.id | Admin GU | Data Pengeluaran Es (Mac.id) |
| 6 | Menyerahkan laporan pemakaian es ke Ka Produksi | Admin GU | Laporan Pemakaian Es |
| 7 | Menginput pengeluaran es tube di Excel | Admin GU | Data Excel Pengeluaran Es |
| 8 | Mengarsipkan form pemakaian es harian | Admin GU | Form Pemakaian Es Harian (arsip) |

---

### 15. Pengeluaran Bumbu Piket Malam

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Menginput pengeluaran bumbu | Rec Admin/Admin GU Umum | Data Pengeluaran Bumbu |
| 2 | Mengarsipkan form laporan penerimaan bumbu | Rec Admin/Admin GU Umum | Form Laporan Penerimaan Bumbu (arsip) |

---

### 16. Penanganan Bumbu Expired

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Melakukan stock opname bumbu bulanan | Admin GU | Stock Opname Bumbu Bulanan |
| 2 | Merekap bumbu expired per customer | Admin GU | Rekap Bumbu Expired per Customer |
| 3 | Menyerahkan data expired ke QA/QC | Admin GU | Data Expired |
| 4 | Setelah dianalisa QA, mengomunikasikan/meminta konfirmasi data expired ke marketing | Admin GU | — |
| 5 | Melakukan koordinasi tindak lanjut dengan customer | Admin GU | — |
| 6 | Jika expired, mengeluarkan bumbu expired dari data stok | Admin GU | Data Stok (ter-update) |
| 7 | Menyerahkan bumbu expired kepada GA untuk ditindaklanjuti | Admin GU | — |

*Dokumen referensi: Form Stock Akhir Bumbu (NO, NAMA CUSTOMER, tanggal, QTY) dan Rekap Bumbu Expired (No, Nama Customer, QTY per ekor, Marketing, Tanggal Kedatangan, Tanggal Expired, Tindak Lanjut).*

---

### 17. Penanganan Stok Beku

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Melakukan stock opname bulanan | Recording GU / Admin GU | Stock Opname Bulanan |
| 2 | Melakukan klasifikasi barang yang tidak terjual selama >12 bulan dan memisahkannya ke data stok beku | Recording GU / Admin GU | Data Stok Beku |
| 3 | Menindaklanjuti dan membuat keputusan terkait barang yang beku | SPV Gudang Umum | — |
| 4 | Mengajukan estimasi kerugian | SPV Gudang Umum | Estimasi Kerugian |
| 5 | Mereview pengajuan kerugian laporan barang beku, dan menandatangani atau merubah pengajuan keputusan | *(tidak disebutkan secara eksplisit pada dokumen)* | Laporan Barang Beku (ditandatangani) |
| 6 | Melakukan update daftar tindak lanjut stok pada database divisi | Recording GU / Admin GU | Database Divisi (ter-update) |

---

### 18. Stok Opname Bulanan

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Meminta jadwal opname dengan akunting | Admin Gudang | — |
| 2 | Menginput data keluar masuk barang tanggal akhir bulan | Admin Gudang | Data Keluar Masuk Barang |
| 3 | Membuat laporan saldo stok akhir | Admin Gudang | Laporan Saldo Stok Akhir |
| 4 | Menerima Bumbu, SPK dan Surat Jalan | Recording Gudang | SPK, Surat Jalan |
| 5 | Memberikan laporan data stok akhir ke staf yang akan melakukan opname | Admin Gudang | Laporan Data Stok Akhir |
| 6 | Mencatat jumlah fisik sesuai barang yang ada pada Form Stok Opname | Recording Gudang | Form Stok Opname |
| 7 | Menginput hasil stok opname dan biaya bulanan | Admin Gudang | Data Hasil Stok Opname & Biaya Bulanan |
| 8 | Jika tidak sesuai, mencari ulang selisih yang hilang (maksimal 2 hari kerja) | Recording Gudang | — |
| 9 | Jika selisih tidak ditemukan, membuat berita acara kehilangan stok untuk diserahkan ke SPV | Admin Gudang | Berita Acara Kehilangan Stok |
| 10 | Jika selisih ditemukan, menginformasikannya ke SPV | Recording Gudang | — |
| 11 | Melakukan update laporan hasil stok opname & menyerahkan hasilnya ke akunting | Admin Gudang | Laporan Hasil Stok Opname |
| 12 | Membuat laporan hasil stok opname dan menyerahkan print out-nya ke SPV | Admin Gudang | Laporan Hasil Stok Opname (print out) |
| 13 | Mengarsipkan laporan hasil stok opname dan form stok opname | Admin Gudang | Laporan & Form Stok Opname (arsip) |

---

### 19. Gambaran Alur Sistem Pengadaan (Catatan Umum dari Dokumen)

Dokumen menyertakan gambaran umum alur pengadaan sebagai berikut:

| No. Langkah | Aktivitas | Penanggung Jawab | Output/Dokumen Wajib |
|---|---|---|---|
| 1 | Menulis permintaan pembelian | Recording | Permintaan Pembelian (tertulis) |
| 2 | Menyerahkan permintaan pembelian ke Admin GU | Recording | — |
| 3 | Membuat PR/PO | Admin GU | PR/PO |
| 4 | Mengusulkan PO ke SPV | Admin GU | PO (diusulkan) |
| 5 | Menyerahkan ke Purchasing | Admin GU | — |

*Kriteria barang yang menjadi stok (sesuai dokumen): barang retur yang baik, dan barang hasil dari penerimaan.*

---

### Catatan Lain dari Dokumen (di luar tabel alur kerja)

Catatan berikut tercantum dalam dokumen sumber namun bukan berupa langkah proses, sehingga dicantumkan terpisah:

- BPB adalah bukti pemakaian barang.
- Kebutuhan fitur sistem yang disebutkan: reminder ketersediaan stok barang, pembuatan label, konfirmasi kedatangan barang dengan/tanpa surat jalan, bumbu expired, pengembalian barang stok otomatis ke stok, dan user untuk stok opname.
- Terdapat masalah: faktur dari GA sering terhambat.
- Kebutuhan sistem tambahan yang disebutkan: input kode barang otomatis (tanpa pindah sheet), perhitungan otomatis berdasarkan sistem, dan setiap kode barang memiliki ukuran & satuan operasi masing-masing.


---

# Tahap 2 — Gap Analysis: SOP Ideal vs Realitas Lapangan

> Disusun berdasarkan pemetaan SOP ideal sebelumnya, dibandingkan dengan 8 kondisi lapangan yang disampaikan. Setiap baris menautkan kondisi lapangan ke proses SOP terkait, mengidentifikasi celah, dan menilai dampaknya terhadap tiga dimensi: Risiko Hukum/Kepatuhan, Kecepatan Operasional, dan Keselamatan Kerja/Kualitas Hasil.

---

### Matriks Gap Analysis

| No. | Kondisi Lapangan (Ringkasan) | Proses SOP Terkait | Teori SOP (Ideal) | Realitas Lapangan | Gap/Celah Utama | Dampak Compliance | Dampak Efisiensi | Dampak Safety/Quality |
|---|---|---|---|---|---|---|---|---|
| 1 | Perusahaan sudah punya sistem Mac.id terintegrasi (finance, produksi, gudang), lengkap dengan master barang & modul PO | Bagian 1 (Permintaan Bahan Pelengkap – langkah 7–8), Bagian 6 & 19 (Alur Pengadaan) | SOP mengharuskan **PO manual** dibuat terlebih dahulu, baru kemudian PR diinput ke Mac.id untuk dibuatkan PO oleh Purchasing | PO manual tetap dibuat meskipun Mac.id sudah punya modul PO dan master barang yang bisa menghasilkan PO secara sistem | SOP belum disesuaikan dengan kapabilitas sistem yang sudah ada — terjadi **duplikasi proses** (manual + sistem) alih-alih SOP memanfaatkan penuh modul PO Mac.id | **Sedang** — dua sumber dokumen PO (manual vs sistem) berisiko tidak sinkron, menyulitkan audit trail pembelian | **Tinggi** — kerja ganda (input manual lalu input ulang ke sistem) memperlambat proses pengadaan | Rendah — tidak berdampak langsung ke kualitas produk |
| 2 | Admin GU hanya 1 PC; Recording GU 1 PC dipakai bergantian oleh 3 orang dengan tugas berbeda (bumbu, penyiapan & pencatatan harian, administrasi/label) | Bagian 1, 4, 5, 8, 9, 10, 11, 12 (semua yang mensyaratkan "Recording GU" mencatat, menginput, mengarsipkan) | SOP menempatkan peran "Recording GU" sebagai satu peran yang menjalankan pencatatan, input sistem, dan pengarsipan secara berurutan | Peran "Recording GU" pada praktiknya adalah 3 orang berbeda yang berbagi 1 PC secara bergantian untuk tugas yang berbeda-beda | SOP tidak mendefinisikan pembagian tugas (RACI) di dalam peran "Recording GU", dan tidak mempertimbangkan **keterbatasan 1 unit PC** untuk 3 orang | Rendah — tidak secara langsung menimbulkan pelanggaran aturan, namun kabur tanggung jawab jika terjadi kesalahan data | **Tinggi** — antrean penggunaan PC menciptakan bottleneck, terutama saat input data harian menumpuk di jam yang sama | Sedang — risiko re-check/verifikasi terlewat karena beban kerja terbagi ke banyak orang dengan satu titik akses sistem |
| 3 | Admin GU mencatat ulang secara manual di Excel dari kertas hasil pencatatan Recording Gudang (double entry kertas → Excel) | Bagian 1 (langkah 6), Bagian 8 (langkah 14), Bagian 12 (langkah 14), Bagian 18 | SOP mencatat aktivitas "input ke excel" sebagai satu langkah tunggal | Data sebenarnya melalui 2 tahap manual: dicatat di kertas oleh Recording, lalu ditransfer ulang (rekap manual) oleh Admin GU ke Excel | SOP tidak mengungkap adanya **proses transkripsi ganda (double entry)** yang rentan salah salin, hilang, atau terlambat | Sedang — potensi selisih data antara catatan fisik dan Excel menyulitkan pembuktian saat audit stok | **Tinggi** — proses dua tahap (tulis tangan → ketik ulang) memperlambat pelaporan dan rawan keterlambatan saat volume tinggi | Sedang — kesalahan salin data (human error) dapat menyebabkan laporan stok tidak akurat |
| 4 | Pencatatan harian/stok opname harian dipakai sebagai dasar pelaporan | Bagian 1 (langkah 1, 6), Bagian 18 | SOP menjadikan Form Stok Opname Harian sebagai output resmi yang diarsipkan dan dilaporkan | Sesuai — namun sumber data pelaporan ini bergantung pada proses manual di Kondisi 2 & 3 (rawan delay/selisih) | Tidak ada gap langsung pada langkah ini, tetapi **keandalan pelaporan** bergantung pada proses manual sebelumnya yang belum tervalidasi otomatis | Sedang — laporan yang dipakai untuk keputusan/pertanggungjawaban stok berisiko keliru jika sumber datanya (Kondisi 2 & 3) tidak akurat | Sedang — keterlambatan di hulu (pencatatan manual) berdampak ke ketepatan waktu pelaporan | Rendah |
| 5 | Surat jalan dari supplier sering tidak ada saat barang datang, menghambat proses penerimaan | Bagian 5 (Penerimaan Bahan Pelengkap – langkah 2 & 6) | SOP mensyaratkan penerimaan barang dicocokkan dengan PO, dicatat di buku recording sebagai bukti serah terima, dan surat jalan/faktur wajib diterima & ditandatangani sebagai bukti | Barang kerap diterima **tanpa surat jalan**, sehingga proses pencocokan dan tanda tangan bukti serah terima tidak dapat dilakukan sesuai SOP | SOP **tidak memiliki jalur pengecualian (exception path)** untuk penerimaan barang tanpa surat jalan | **Tinggi** — tanpa surat jalan, tidak ada bukti resmi transaksi dari supplier; rawan disengketakan saat rekonsiliasi pembayaran/pajak, dan membuka celah manipulasi jumlah barang | Tinggi — barang tertahan di penerimaan sampai dokumen menyusul, menghambat alur ke produksi | Sedang — tanpa verifikasi dokumen, risiko barang tidak sesuai spesifikasi/kualitas lolos tanpa pengecekan silang yang memadai |
| 6 | Label produk (halal, nama pelanggan, produk, tanggal produksi, jenis ayam/partingan, kode area untuk fresh; expired untuk frozen) dibuat & dicetak manual dari Excel | Bagian 9 (Pengeluaran Label Produk) | SOP menyebutkan label "dibuat sesuai spek SPK" dan "dicetak sesuai kebutuhan produksi", lalu SPK diarsipkan | Pembuatan dan pencetakan label sepenuhnya manual di Excel, mencantumkan informasi kritis (cap halal, tanggal produksi/expired, kode area/partingan) tanpa kontrol sistem/otomasi | SOP tidak mengatur **kontrol validasi** untuk data label kritis (cth: verifikasi ganda tanggal produksi/expired, penomoran seri terkontrol, proteksi file Excel dari perubahan tak sengaja) | **Tinggi** — label mencantumkan klaim halal dan tanggal kedaluwarsa; kesalahan cetak (tanggal, kode area, jenis produk) berisiko pelanggaran ketentuan pelabelan pangan dan klaim halal | Sedang — proses manual cetak-per-label memperlambat kesiapan produk untuk pengiriman, terutama saat volume tinggi | **Tinggi** — kesalahan label (salah tanggal expired, salah pelanggan/produk) berdampak langsung ke keamanan pangan, traceability, dan potensi retur/komplain pelanggan |
| 7 | Tidak ada reminder otomatis untuk stok yang hampir/sudah kosong | Bagian 1, 2, 3, 4 (semua proses permintaan bahan) | SOP mengandalkan stock opname harian/bulanan sebagai dasar mendeteksi kebutuhan restock | Deteksi kekurangan stok baru diketahui saat opname atau saat kebutuhan mendesak muncul, tanpa peringatan dini | Tidak ada **mekanisme reminder/alert** proaktif sebagaimana sudah diidentifikasi sendiri dalam catatan kebutuhan fitur di dokumen SOP | Rendah — tidak menimbulkan pelanggaran aturan, namun kompensasi mendadak (pembelian darurat) berisiko lemah kontrol approval | **Tinggi** — stok kosong mendadak menghentikan/menunda proses produksi hingga barang tersedia kembali | Sedang — kondisi mendesak dapat memicu penggunaan bahan pengganti/di luar spesifikasi demi mengejar target produksi |
| 8 | Pengembalian barang dari produksi sering terjadi (bumbu sisa, plastik sisa, dan lainnya) | Bagian 10 (langkah 5 — hanya mencakup retur bumbu) | SOP hanya mengatur mekanisme retur untuk **bumbu sisa produksi** ("menerima dan menginput data pengembalian bumbu sisa produksi") | Retur juga rutin terjadi untuk barang lain seperti plastik dan bahan pelengkap lain, namun tidak ada proses retur yang terdokumentasi untuk kategori ini | SOP **tidak memiliki alur retur barang non-bumbu** dari produksi ke gudang | Sedang — barang keluar tercatat sebagai terpakai habis padahal ada sisa yang kembali, menyulitkan pertanggungjawaban stok/biaya produksi | Sedang — rekonsiliasi stok manual di luar SOP untuk menutup selisih retur menyita waktu tambahan | Rendah — dampak utama ke akurasi data, bukan langsung ke keselamatan/kualitas produk |

---

### Ringkasan Tingkat Risiko per Kondisi Lapangan

| No. | Kondisi Lapangan | Risiko Tertinggi | Tingkat |
|---|---|---|---|
| 1 | Duplikasi PO manual vs Mac.id | Efisiensi | Tinggi |
| 2 | 1 PC untuk 3 peran Recording GU | Efisiensi | Tinggi |
| 3 | Double entry kertas → Excel | Efisiensi | Tinggi |
| 4 | Pelaporan bergantung data manual hulu | Compliance / Efisiensi | Sedang |
| 5 | Surat jalan sering tidak ada | Compliance | Tinggi |
| 6 | Label manual (halal, expired, kode area) | Compliance / Safety-Quality | Tinggi |
| 7 | Tidak ada reminder stok kosong | Efisiensi | Tinggi |
| 8 | Retur non-bumbu tidak terdokumentasi | Compliance / Efisiensi | Sedang |

*Catatan: penilaian "Tinggi/Sedang/Rendah" merupakan hasil analisis berdasarkan data kondisi lapangan yang diberikan, bukan angka/metrik terukur dari dokumen SOP.*


---

# Tahap 3 — Analisis Peran & Interaksi Sistem

> Dokumen ini memetakan SOP Gudang Umum (hasil ekstraksi Tahap 1) ke dalam kebutuhan sistem, berdasarkan 3 role yang ditetapkan: **Admin Gudang Umum**, **Recording Gudang Umum**, dan **Super Admin (kontrol sistem/user saja)**. Belum ada kode yang dibuat pada tahap ini — murni analisis kebutuhan.

---

### Catatan Metodologi — Pemetaan Aktor SOP ke Role Aplikasi

SOP asli menyebut banyak label aktor (Recording GU, Operator, Operator Bumbu GU, Recording Penyiapan, Rec GU, Admin GU, Adm GU, dst.) yang secara operasional adalah tim di lapangan/meja yang sama. Berdasarkan daftar role final dan kondisi lapangan yang sudah dikonfirmasi sebelumnya, pemetaan yang digunakan dalam dokumen ini adalah:

| Label di SOP | Dipetakan ke Role Aplikasi | Alasan |
|---|---|---|
| Recording, Operator, Operator Bumbu GU, Recording Penyiapan, Recording ES, Rec GU, Recording Gudang | **Recording Gudang Umum** | Aktor lapangan yang menangani fisik barang, timbang, catat harian (sesuai Kondisi Lapangan 2 & 3) |
| Admin GU, Adm GU, Admin Gudang | **Admin Gudang Umum** | Aktor meja/PC yang merekap, menginput ke Excel/Mac.id, membuat PR/PO, dan melapor ke pihak lain |
| User, Operator (di luar tim gudang), SPV, Purchasing, Ka Produksi, QA/QC, Marketing, Adm Marketing, Adm Keuangan, GA | **Di luar scope aplikasi** | Pihak-pihak ini bukan bagian dari 3 role yang ditetapkan; interaksinya dengan sistem hanya sebagai **penerima laporan/notifikasi** atau **pemberi input fisik (tanda tangan basah/approval manual di luar sistem)**, bukan sebagai pengguna aktif |

Asumsi ini dipakai konsisten di seluruh 4 bagian di bawah. Jika ada pihak eksternal (mis. SPV) yang ternyata perlu approval digital, hal ini perlu dikonfirmasi ulang karena tidak termasuk dalam 3 role yang diberikan.

---

### 1. Actor User-Story Matrix

#### Role: Recording Gudang Umum

**Stok & Opname Harian**
- Sebagai Recording Gudang Umum, saya ingin bisa mengisi form stok opname harian per kode barang, sehingga saya bisa melaporkan kondisi stok fisik tanpa menulis manual di kertas.
- Sebagai Recording Gudang Umum, saya ingin sistem menampilkan data penerimaan (BSTB) dan faktur hari sebelumnya, sehingga saya bisa memverifikasi kesesuaian stok tanpa mengecek dokumen kertas satu per satu.
- Sebagai Recording Gudang Umum, saya ingin bisa mengajukan revisi data pengeluaran ketika ditemukan selisih barang kurang, sehingga selisih dapat segera dikoreksi dengan jejak audit yang jelas.

**Permintaan & Penerimaan Barang**
- Sebagai Recording Gudang Umum, saya ingin bisa membuat draft Permintaan/PR bahan pelengkap dari sistem, sehingga tidak perlu direkap manual sebelum diteruskan ke Admin GU.
- Sebagai Recording Gudang Umum, saya ingin bisa mencocokkan barang datang dengan PO/PR yang tercatat di sistem, sehingga saya tahu barang sudah sesuai pesanan sebelum diterima.
- Sebagai Recording Gudang Umum, saya ingin bisa mengunggah foto surat jalan/faktur saat barang diterima, sehingga bukti serah terima tidak hilang seperti yang sering terjadi saat ini.
- Sebagai Recording Gudang Umum, saya ingin bisa menandai penerimaan barang sebagai "diterima tanpa surat jalan" beserta catatan alasan, sehingga barang tetap bisa diproses tanpa kehilangan jejak audit ketika dokumen supplier belum tiba.
- Sebagai Recording Gudang Umum, saya ingin bisa mencatat hasil timbang ulang bahan bakar/es/bumbu langsung di sistem, sehingga tidak perlu ditulis di kertas dulu sebelum diketik ulang oleh Admin GU.

**Pengeluaran Barang**
- Sebagai Recording Gudang Umum, saya ingin bisa mencatat pengeluaran barang harian (bahan pelengkap, bumbu, bahan bakar, es) berdasarkan permintaan dari bagian produksi, sehingga stok berkurang otomatis tanpa rekap manual di Excel.
- Sebagai Recording Gudang Umum, saya ingin sistem mencatat tanda tangan digital/paraf penerima barang, sehingga bukti pengeluaran (BPB) tetap sah tanpa form kertas.
- Sebagai Recording Gudang Umum, saya ingin bisa mencetak label produk (halal, nama pelanggan, tanggal produksi/expired, kode area/partingan) langsung dari sistem, sehingga tidak perlu mengetik ulang data di Excel setiap kali cetak.

**Retur Barang**
- Sebagai Recording Gudang Umum, saya ingin bisa mencatat pengembalian barang dari produksi (bumbu sisa, plastik sisa, atau barang lain), sehingga stok kembali akurat dan tidak tercatat sebagai terpakai habis.

**Stok Opname & Piket Malam**
- Sebagai Recording Gudang Umum, saya ingin bisa mengisi form stok opname bulanan berdasarkan hitungan fisik, sehingga sistem bisa otomatis membandingkan dengan data pencatatan.
- Sebagai Recording Gudang Umum, saya ingin bisa mencatat pengeluaran/penerimaan bumbu saat piket malam, sehingga transaksi di luar jam operasional tetap tercatat di hari yang sama.

#### Role: Admin Gudang Umum

**Pengadaan**
- Sebagai Admin Gudang Umum, saya ingin bisa membuat dan mengirimkan PR/PO ke sistem pengadaan (terintegrasi dengan Mac.id), sehingga tidak perlu membuat PO manual yang terpisah dari sistem yang sudah ada.
- Sebagai Admin Gudang Umum, saya ingin bisa melihat status PR/PO yang sudah diajukan, sehingga saya tahu kapan barang akan datang tanpa harus menanyakan manual ke Purchasing.

**Konsolidasi & Rekonsiliasi Data**
- Sebagai Admin Gudang Umum, saya ingin sistem otomatis merekap data pengeluaran/penerimaan harian yang diinput Recording Gudang Umum, sehingga saya tidak perlu mengetik ulang data dari kertas ke Excel.
- Sebagai Admin Gudang Umum, saya ingin bisa memverifikasi dan mengunci (finalisasi) data harian sebelum dijadikan laporan resmi, sehingga data yang sudah dilaporkan tidak bisa diubah sepihak.
- Sebagai Admin Gudang Umum, saya ingin sistem menampilkan selisih otomatis antara stok opname fisik dan data sistem, sehingga saya tidak perlu menghitung manual saat rekonsiliasi bulanan.

**Pelaporan**
- Sebagai Admin Gudang Umum, saya ingin bisa men-generate laporan stok opname harian/bulanan, laporan pemakaian bahan bakar/es/bumbu, dan laporan biaya secara otomatis, sehingga tidak perlu menyusun laporan manual untuk diserahkan ke akunting/Ka Produksi.
- Sebagai Admin Gudang Umum, saya ingin bisa membuat Berita Acara Kehilangan Stok dari sistem ketika selisih opname tidak ditemukan dalam 2 hari kerja, sehingga dokumen resmi ini konsisten formatnya dan tercatat waktu pembuatannya.

**Penanganan Expired & Stok Beku**
- Sebagai Admin Gudang Umum, saya ingin sistem memberi notifikasi otomatis untuk stok bumbu yang mendekati tanggal expired, sehingga saya tidak perlu mengecek manual satu per satu setiap bulan.
- Sebagai Admin Gudang Umum, saya ingin bisa menandai barang sebagai "Expired" dan mengeluarkannya dari data stok aktif, sehingga stok yang ditampilkan ke pihak lain selalu akurat.
- Sebagai Admin Gudang Umum, saya ingin sistem menandai otomatis barang yang tidak terjual >12 bulan sebagai kandidat "Stok Beku", sehingga saya bisa segera mengajukan estimasi kerugian tanpa menunggu opname manual.

**Notifikasi Stok**
- Sebagai Admin Gudang Umum, saya ingin menerima reminder otomatis ketika stok barang tertentu mendekati batas minimum, sehingga saya bisa mengajukan permintaan pembelian sebelum stok benar-benar kosong.

#### Role: Super Admin

- Sebagai Super Admin, saya ingin bisa membuat, mengedit, dan menonaktifkan akun pengguna (Admin GU/Recording GU), sehingga akses sistem selalu sesuai dengan pegawai yang aktif bekerja.
- Sebagai Super Admin, saya ingin bisa menetapkan/mengubah role seorang pengguna, sehingga hak akses selalu sesuai jabatan aktual.
- Sebagai Super Admin, saya ingin bisa mereset password pengguna, sehingga pengguna yang lupa kata sandi tetap bisa bekerja tanpa saya perlu mengetahui data transaksi mereka.
- Sebagai Super Admin, saya ingin bisa melihat log aktivitas login/akses sistem, sehingga saya bisa memantau keamanan sistem tanpa perlu melihat isi data transaksi gudang.

---

### 2. Privilege & Access Boundaries

| No. | Aksi | Diizinkan untuk | Dilarang Mutlak untuk | Dasar/Alasan dari SOP |
|---|---|---|---|---|
| 1 | Membuat & mengajukan PR/PO pengadaan | Admin Gudang Umum | Recording Gudang Umum | SOP menempatkan pembuatan PO/PR sebagai tugas Admin GU (Bagian 1, 6, 19); Recording hanya merekomendasikan/melaporkan kebutuhan |
| 2 | Mengunci/finalisasi laporan resmi (Laporan Stok Opname, Laporan Biaya, Berita Acara Kehilangan Stok) | Admin Gudang Umum | Recording Gudang Umum | Recording GU adalah sumber data mentah lapangan; finalisasi dokumen ke pihak eksternal (akunting, SPV) adalah tugas Admin GU (Bagian 18) |
| 3 | Menginput/mengoreksi hasil timbang, catatan pengeluaran, dan penerimaan fisik harian | Recording Gudang Umum | — (Admin GU tidak melakukan input fisik langsung — hanya merekap hasil dari Recording) | Recording GU adalah aktor yang memegang barang secara fisik (Bagian 5, 8, 10, 13, 14) |
| 4 | Menandai barang sebagai "Expired" dan mengeluarkannya dari data stok aktif | Admin Gudang Umum | Recording Gudang Umum | Bagian 16: Admin GU yang memutuskan dan mengeluarkan bumbu expired dari data stok setelah koordinasi dengan QA/marketing; Recording hanya melapor kondisi fisik |
| 5 | Menetapkan klasifikasi "Stok Beku" (>12 bulan tidak terjual) sebagai status resmi | Admin Gudang Umum | Recording Gudang Umum | Bagian 17: klasifikasi & tindak lanjut stok beku ada di level Admin GU/SPV, Recording hanya input data opname mentah |
| 6 | Mengubah data transaksi yang sudah difinalisasi/diarsipkan | Admin Gudang Umum (dengan jejak audit revisi) | Recording Gudang Umum | Data yang sudah dikunci mewakili laporan resmi; perubahan sepihak oleh Recording akan menghilangkan validitas dokumen (mencegah manipulasi data lapangan) |
| 7 | Membuat, mengedit, menghapus akun pengguna & menetapkan role | Super Admin | Admin Gudang Umum, Recording Gudang Umum | Sesuai batasan yang ditetapkan: Super Admin hanya mengurus kontrol sistem/user |
| 8 | Melihat/mengubah data transaksi stok, PO, laporan bisnis | Admin Gudang Umum, Recording Gudang Umum (sesuai kewenangan masing-masing) | Super Admin | Super Admin dibatasi murni pada administrasi sistem, tidak pada data operasional gudang |
| 9 | Mencatat retur barang dari produksi (bumbu, plastik, dll.) | Recording Gudang Umum | — | Recording adalah penerima fisik barang retur (Bagian 10 & Gap Analysis Kondisi 8) |
| 10 | Menyetujui/menolak permintaan pembelian non-aset dari user/produksi | Di luar scope 3 role (SPV, sesuai SOP Bagian 6) | Admin Gudang Umum, Recording Gudang Umum | SOP Bagian 6 menempatkan analisa/persetujuan di tangan SPV yang bukan bagian dari 3 role aplikasi; perlu konfirmasi apakah approval ini perlu direpresentasikan di sistem |

---

### 3. Digital Interaction Points

| No. | Titik dalam SOP | Bentuk Interaksi dengan Sistem | Role Terlibat |
|---|---|---|---|
| 1 | Stok opname harian (Bagian 1) | Isi form digital qty fisik per kode barang (pilih dari master barang, bukan ketik bebas) | Recording Gudang Umum |
| 2 | Pengecekan penerimaan vs BSTB & faktur (Bagian 1) | Lihat daftar penerimaan otomatis dari sistem, klik "Sesuai" / "Ada Selisih" | Recording Gudang Umum |
| 3 | Pembuatan PR/PO (Bagian 1, 6, 19) | Isi form PR (kode barang, qty, keterangan) → submit ke modul PO | Admin Gudang Umum |
| 4 | Penerimaan barang bahan pelengkap (Bagian 5) | Checklist kecocokan qty vs PO, tombol "Terima"/"Komplain ke Store", upload foto surat jalan/faktur | Recording Gudang Umum |
| 5 | Penerimaan barang tanpa surat jalan (Gap Kondisi 5) | Tombol "Terima Tanpa Surat Jalan" dengan kolom catatan wajib diisi | Recording Gudang Umum |
| 6 | Timbang ulang bahan bakar/es (Bagian 2, 3) | Form input dua angka: jumlah sesuai surat jalan vs hasil timbang ulang, sistem hitung selisih otomatis | Recording Gudang Umum |
| 7 | Pengeluaran barang harian (Bagian 8, 12) | Form permintaan (pilih barang, qty), tombol submit, tanda tangan digital penerima (signature pad/PIN), generate BPB otomatis | Recording Gudang Umum |
| 8 | Pengeluaran & pencetakan label produk (Bagian 9, Gap Kondisi 6) | Form input data label (pelanggan, produk, tanggal produksi/expired, kode area, jenis ayam/partingan) dengan nomor seri otomatis, tombol "Cetak Label" langsung dari sistem | Recording Gudang Umum |
| 9 | Pengeluaran bumbu & retur sisa (Bagian 10) | Form input qty keluar dan qty retur sisa produksi dalam satu alur | Recording Gudang Umum |
| 10 | Retur barang non-bumbu (Gap Kondisi 8) | Form retur umum: pilih kategori barang, qty, kondisi barang | Recording Gudang Umum |
| 11 | Rekap & finalisasi laporan harian (Bagian 8, 12, 18) | Tombol "Verifikasi & Kunci Data Harian", preview laporan sebelum lock | Admin Gudang Umum |
| 12 | Reminder stok minimum (Gap Kondisi 7) | Notifikasi/badge otomatis di dashboard saat stok di bawah ambang batas | Admin Gudang Umum |
| 13 | Reminder bumbu mendekati expired (Bagian 16) | Notifikasi otomatis + daftar barang mendekati expired, tombol "Tandai Expired" | Admin Gudang Umum |
| 14 | Klasifikasi stok beku otomatis (Bagian 17) | Sistem auto-flag barang idle >12 bulan, tombol "Ajukan Estimasi Kerugian" dengan form nominal & keterangan | Admin Gudang Umum |
| 15 | Stok opname bulanan & selisih (Bagian 18) | Form input fisik per item, sistem tampilkan selisih otomatis vs data sistem, tombol "Buat Berita Acara Kehilangan Stok" jika selisih tak ditemukan >2 hari | Recording Gudang Umum (input fisik), Admin Gudang Umum (finalisasi & berita acara) |
| 16 | Manajemen pengguna | Form tambah/edit user, dropdown pilih role, toggle aktif/nonaktif, tombol reset password | Super Admin |
| 17 | Audit/log aktivitas sistem | Halaman log read-only (siapa mengubah apa, kapan) | Super Admin |

---

### 4. Field Data Validation

#### Recording Gudang Umum — wajib diisi saat transaksi

| Field | Aturan Validasi | Tujuan Anti-Manipulasi |
|---|---|---|
| Kode/Nama Barang | Dropdown dari master barang (bukan input teks bebas) | Mencegah barang "siluman" yang tidak terdaftar di master |
| Qty Fisik | Angka, tidak boleh negatif atau nol untuk transaksi masuk/keluar | Mencegah entri kosong/salah ketik yang tidak masuk akal |
| Satuan | Auto-terisi dari master barang (read-only) | Mencegah kesalahan konversi satuan yang disengaja/tidak disengaja |
| Referensi PO/PR/SPK | Wajib pilih dari daftar PO/SPK aktif, atau tandai eksplisit "Tanpa Dokumen Rujukan" + alasan | Mencegah transaksi tanpa dasar dokumen yang tidak tercatat sama sekali |
| Foto bukti fisik (surat jalan/faktur/kondisi barang) | Wajib diunggah untuk kasus tanpa surat jalan atau barang expired/rusak | Menyediakan bukti visual yang tidak mudah hilang seperti form kertas |
| Nama/ID Peminta (untuk pengeluaran) | Wajib pilih dari daftar internal (bukan teks bebas) | Menjamin akuntabilitas siapa yang meminta barang |
| Tanda tangan digital/PIN penerima | Wajib untuk setiap transaksi pengeluaran | Menggantikan tanda tangan basah sebagai bukti serah terima yang sah |
| Waktu transaksi | Timestamp otomatis oleh sistem (tidak bisa diedit user) | Mencegah manipulasi waktu pencatatan (backdating) |

#### Admin Gudang Umum — wajib diisi saat memproses/finalisasi

| Field | Aturan Validasi | Tujuan Anti-Manipulasi |
|---|---|---|
| Nomor PR/PO | Auto-generate oleh sistem atau tertaut ke nomor dari Mac.id | Mencegah nomor ganda/duplikat yang bisa disalahgunakan |
| Catatan/alasan revisi data | Wajib diisi setiap kali mengubah data yang sudah diinput Recording GU | Menjamin jejak audit atas setiap koreksi manual |
| Nominal estimasi kerugian (stok beku/expired) | Angka wajib diisi beserta keterangan dasar perhitungan | Mencegah pengajuan kerugian tanpa dasar yang jelas |
| Status finalisasi laporan | Hanya bisa diubah oleh Admin GU, dengan log siapa & kapan mengunci data | Mencegah laporan diubah setelah dianggap final tanpa jejak |
| Referensi Recording GU pengisi data | Otomatis tertaut ke akun Recording GU yang menginput data asal (read-only) | Memastikan Admin GU tidak bisa mengklaim data lapangan sebagai hasil kerjanya sendiri |
| Tanggal & periode opname/pelaporan | Wajib dipilih dari kalender sistem (bukan ketik manual) | Mencegah kesalahan/rekayasa tanggal pelaporan |

#### Super Admin — wajib diisi saat mengelola user

| Field | Aturan Validasi | Tujuan Anti-Manipulasi |
|---|---|---|
| Nama & identitas pengguna | Wajib diisi lengkap, tidak boleh duplikat | Memastikan setiap akun terhubung ke satu individu yang jelas untuk keperluan audit |
| Role yang ditetapkan | Wajib pilih salah satu dari daftar role tetap (Admin GU/Recording GU) — tidak bisa membuat role baru bebas | Mencegah hak akses custom yang tidak sesuai struktur organisasi |
| Status akun (aktif/nonaktif) | Wajib diperbarui saat karyawan resign/mutasi | Mencegah akun tidak aktif tetap bisa login dan bertransaksi |


---

# Tahap 4 — Analisis Arsitektur Teknis (Pra-Coding)

> Belum ada kode yang ditulis. Dokumen ini memverifikasi asumsi tech stack, lalu memetakan hasil Tahap 1–3 (SOP, Gap Analysis, Actor-Role Mapping, Master Satuan) ke rencana entitas database, service layer, dan Filament Resource — sebagai dasar sebelum coding dimulai.

---

### 1. Validasi Context Lock (Tech Stack)

Sebelum masuk ke desain, berikut verifikasi klaim fitur yang disebutkan, karena beberapa nama method terdengar sangat baru:

| Klaim | Status Verifikasi | Catatan |
|---|---|---|
| Laravel 13 punya `whereVectorSimilarTo()` native + pgvector | **Terverifikasi** | Laravel 13 (rilis Maret 2026) memang menambahkan native vector search terintegrasi PostgreSQL + pgvector, termasuk method `Schema::ensureVectorExtensionExists()` dan cast `AsVector` di model. Hanya berjalan di atas PostgreSQL (atau MariaDB), **tidak tersedia di MySQL/SQLite** — jadi pilihan PostgreSQL 17 di context lock ini sudah tepat. |
| Laravel AI SDK untuk generate embedding otomatis | **Terverifikasi** | Laravel 13 memasukkan Laravel AI SDK first-party (sebelumnya eksperimental) untuk text generation, embeddings, dan vector-store integration. |
| `Cache::touch()` untuk memperpanjang TTL tanpa fetch ulang | **Terverifikasi** | Method ini ada di Laravel 13, berlaku di semua driver termasuk Redis (pakai `EXPIRE` di level Redis, jadi memang efisien dibanding get-then-put). |
| PHP 8.4 Property Hooks & Asymmetric Visibility (`public private(set)`) | **Terverifikasi** | Keduanya fitur asli PHP 8.4 (dirilis Nov 2024), bukan klaim Laravel. Laravel 13 sendiri hanya mensyaratkan minimum PHP 8.3, jadi pakai PHP 8.4 aman dan justru dapat fitur tambahan. |
| PostgreSQL 17 sebagai database utama | Valid, meski **PostgreSQL 18 sudah dirilis** di tahun yang sama dengan Laravel 13. PG 17 tetap didukung penuh — tidak masalah dipakai, hanya informasi tambahan jika suatu saat ingin upgrade. |

**Kesimpulan:** Context lock ini secara teknis akurat dan bisa dijadikan dasar desain, bukan asumsi yang salah kaprah.

---

### 2. Pemetaan Kebutuhan ke Entitas Database (Rencana, Belum Migration)

Berdasarkan Tahap 1 (SOP), Tahap 2 (Gap Analysis), dan Tahap 3 (Actor-Role Mapping), berikut kandidat tabel inti yang diperlukan:

| No. | Tabel | Fungsi | Sumber Kebutuhan |
|---|---|---|---|
| 1 | `users` | Akun login, dikaitkan ke role (Admin GU/Recording GU/Super Admin) — kemungkinan pakai Filament Shield/Spatie Permission untuk role | Tahap 3 – semua user story |
| 2 | `satuan` | Master satuan tetap (bh, bks, box, bti, buku, dus, gln, kg, klg, lbr, lmbr, ltr, mtr, pack, pcs, psg, rim, set) | Validasi satuan barang |
| 3 | `kategori_barang` | Pengelompokan (bahan pelengkap, bumbu, bahan bakar, es, label, dll.) sesuai jenis proses SOP yang berbeda-beda | SOP Bagian 1–14 |
| 4 | `barang` | Master barang: kode, nama, kategori, satuan tetap, stok minimum (untuk reminder), kolom `deskripsi_embedding` (vector) untuk pencarian semantik nama barang | Gap Kondisi 1 & 7 |
| 5 | `stok` | Saldo stok berjalan per barang (bisa jadi computed dari transaksi, atau tabel snapshot ter-cache) | SOP Bagian 1, 18 |
| 6 | `permintaan_barang` (PR) | Draft permintaan dari Recording GU sebelum jadi PO | SOP Bagian 1, 6, 19 |
| 7 | `purchase_order` | Referensi PO — jika Mac.id sudah punya modul PO sendiri, tabel ini kemungkinan hanya **referensi/sinkronisasi** (nomor PO, status), bukan sumber utama data pengadaan | Gap Kondisi 1 |
| 8 | `penerimaan_barang` | Transaksi penerimaan, dengan flag `tanpa_surat_jalan` + kolom alasan + lampiran foto | Gap Kondisi 5 |
| 9 | `pengeluaran_barang` | Transaksi keluar (bahan pelengkap, bumbu, bahan bakar, es), termasuk kolom tanda tangan digital/PIN | SOP Bagian 8, 10, 12, 13, 14 |
| 10 | `retur_barang` | Retur dari produksi (bumbu, plastik, dll.) — tabel umum, tidak spesifik bumbu saja | Gap Kondisi 8 |
| 11 | `label_produk` | Data cetak label (pelanggan, produk, tanggal produksi/expired, kode area/partingan, nomor seri) | Gap Kondisi 6 |
| 12 | `stok_opname` | Header + detail opname harian/bulanan, kolom selisih otomatis | SOP Bagian 1, 18 |
| 13 | `berita_acara_kehilangan` | Dibuat otomatis dari sistem saat selisih opname tak ditemukan >2 hari | SOP Bagian 18 |
| 14 | `barang_expired` / status expired pada `stok` | Penandaan status expired oleh Admin GU | SOP Bagian 16 |
| 15 | `stok_beku` | Flag otomatis barang idle >12 bulan + estimasi kerugian | SOP Bagian 17 |
| 16 | `notifikasi` | Reminder stok minimum & mendekati expired (bisa memanfaatkan Laravel Notifications + Redis queue) | Gap Kondisi 7, SOP Bagian 16 |
| 17 | `audit_log` | Jejak siapa mengubah data apa (penting untuk Privilege Boundaries Tahap 3) | Tahap 3 – Privilege & Access Boundaries |

**Catatan desain penting:** field `satuan_id` pada tabel `barang` sebaiknya **tetap (fixed per barang)**, bukan dipilih ulang setiap transaksi — sesuai konfirmasi bahwa satuan adalah atribut master barang, bukan input bebas per transaksi. Ini otomatis memenuhi aturan "Field Data Validation" di Tahap 3 (satuan auto-terisi read-only dari master).

---

### 3. Rencana Struktur Modul (Service Layer & Filament)

Mengikuti aturan Anti-Messy (poin 3: jangan query berat di komponen UI Filament):

**Service Layer (murni PHP, tidak tahu apa-apa soal Filament):**
- `StokService` — hitung saldo stok, validasi stok minimum, trigger reminder
- `PengadaanService` — buat PR, sinkronisasi status PO dari Mac.id
- `PenerimaanService` — proses penerimaan (dengan/tanpa surat jalan), update stok
- `PengeluaranService` — proses pengeluaran, generate BPB, kurangi stok
- `ReturService` — proses retur, tambah kembali stok
- `LabelService` — generate nomor seri label, render data cetak
- `OpnameService` — hitung selisih opname vs sistem, trigger Berita Acara jika lewat 2 hari
- `ExpiredStokBekuService` — deteksi barang mendekati expired & idle >12 bulan
- `BarangSearchService` — wrapper untuk `whereVectorSimilarTo()` pencarian semantik nama barang (mencegah duplikasi master data akibat penamaan yang berbeda-beda)

**Filament Resource per role** (dibatasi lewat Filament Policy/Shield, bukan hardcode di UI):
- `Admin Gudang Umum`: akses penuh ke Barang (master), Permintaan/PO, Laporan, Expired, Stok Beku, konfigurasi reminder
- `Recording Gudang Umum`: hanya akses form transaksi harian (opname, penerimaan, pengeluaran, retur, label) — tanpa akses finalisasi laporan atau PO
- `Super Admin`: **hanya** User Resource + halaman Activity Log — resource transaksi/bisnis lain sengaja **tidak didaftarkan** di panel Super Admin sama sekali (bukan cuma disembunyikan lewat policy, supaya benar-benar tidak muncul di navigasi)

---

### 4. Rencana Pemakaian Fitur Spesifik

| Fitur | Dipakai di Mana | Alasan |
|---|---|---|
| **Native Vector Search** (`whereVectorSimilarTo`) | Pencarian nama barang saat Admin/Recording input transaksi | Mencegah munculnya barang duplikat karena penamaan tidak konsisten (mis. "Dada Ayam" vs "Ayam Dada Fillet") — embedding di-generate otomatis saat `barang` disimpan |
| **PHP 8.4 Property Hooks** | Model `Barang` — computed property `stok_saat_ini` (get) yang membaca dari tabel `stok`/service, bukan kolom yang bisa ditulis langsung | Mencegah stok diubah langsung tanpa melalui transaksi tercatat (selaras dengan prinsip anti-manipulasi di Tahap 3) |
| **Asymmetric Visibility** (`public private(set)`) | Kolom seperti `status_finalisasi` pada `stok_opname`/`pengeluaran_barang` — bisa dibaca publik, tapi hanya bisa di-set dari dalam Service Layer (mis. `OpnameService`), bukan langsung dari Filament form | Menegakkan aturan "hanya Admin GU yang boleh mengunci/finalisasi data" secara teknis di level model, bukan hanya di level UI |
| **Redis + `Cache::touch()`** | Cache daftar master barang & satuan (sering dibaca, jarang berubah), serta cache hasil agregasi dashboard reminder stok minimum | `touch()` dipakai supaya cache yang sering diakses (mis. dashboard yang dibuka berkali-kali sehari oleh Admin GU) diperpanjang TTL-nya tanpa perlu query ulang penuh |

---

### 5. Hal yang Perlu Dikonfirmasi Sebelum Coding

1. **Integrasi Mac.id** — apakah ada API/webhook resmi dari Mac.id yang bisa dipanggil (untuk sinkronisasi PO/master barang), atau modul ini berjalan berdampingan tanpa integrasi otomatis (Admin GU tetap input manual referensi nomor PO Mac.id)? Ini menentukan apakah tabel `purchase_order` berisi data sinkron otomatis atau input manual.
2. **Konversi satuan** — sudah ditanyakan sebelumnya: apakah 1 barang selalu 1 satuan tetap dari terima sampai keluar, atau ada barang yang butuh konversi (dus → pcs)? Ini menentukan apakah tabel `satuan` cukup sebagai lookup sederhana, atau perlu tabel `konversi_satuan`.
3. **"lbr" vs "lmbr"** — perlu kepastian sebelum di-seed ke tabel `satuan`, supaya tidak ada 2 nilai yang sebetulnya sama.
4. **Approval SPV** (Tahap 3, Privilege Boundary poin 10) — apakah proses approval SPV untuk pengajuan pembelian non-aset perlu direpresentasikan sebagai status di sistem (walau SPV bukan user aktif Laravel), atau benar-benar di luar sistem.
5. **Redis untuk Queue** — context lock menyebut Redis untuk cache & queue; perlu dikonfirmasi proses mana yang perlu antrian asinkron (mis. generate embedding vector saat simpan barang, kirim notifikasi reminder) supaya tidak memperlambat response saat input data oleh Recording GU.

Setelah 5 poin di atas dikonfirmasi, desain ERD detail (kolom, tipe data, relasi, index) bisa disusun sebagai langkah berikutnya sebelum masuk ke penulisan migration & model sesungguhnya.