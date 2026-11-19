# Menu & Isi Fitur — Recording GU dan Admin GU (Gudang Umum Sukahati)

> Turunan dari SOP Gudang Umum (Tahap 1), Gap Analysis (Tahap 2), dan PRD terbaru. Tujuan sistem: **mempermudah, mengefisienkan, dan mempercepat** kerja gudang. Tanda ★ = fitur baru (belum ada di PRD sebelumnya). Gelombang 1/2/3 = urutan prioritas pembangunan.

## Prinsip yang berlaku di semua menu

1. **Input sekali, rekonsiliasi kemudian.** Recording GU mencatat barang saat benar-benar keluar; Admin GU mencocokkan catatan digital dengan dokumen fisik yang diserahkan, bukan mengetik ulang transaksi ke Excel.
2. **Satu transaksi = satu header + banyak baris**, bisa diisi lewat keyboard (Tab/Enter pindah kolom) tanpa mouse.
3. **Kolom barang menggunakan typeahead.** Quick-Add inline barang/kategori/satuan hanya tersedia jika diizinkan oleh permission khusus dan tata kelola master data; edit/nonaktifkan katalog hanya Super Admin.
4. **Form besar autosave sebagai draft** (PC dipakai bergantian 3 orang).
5. **Tablet-friendly**: target sentuh minimal 40px, tombol besar, tanpa hover-only.
6. **Performa**: submit < 1 detik, operasi berat (laporan, embedding, notifikasi) lewat queue; interaksi ringan lewat Alpine.js.
7. Ekspor laporan **hanya Excel**, rapi tanpa perlu diedit ulang.
8. **Pemisahan saldo per gudang**: barang dapat merujuk katalog yang sama, tetapi saldo Gudang Store dan saldo Gudang Unit dihitung dan ditampilkan terpisah. Recording GU hanya melihat saldo Gudang Unit aktif; jumlah kiriman Store menjadi referensi penerimaan, bukan saldo Store yang dapat diedit Recording.
9. **Permintaan dan pengadaan**: Recording GU tidak membuat permintaan barang ke Store maupun PR/PO. Recording dapat melihat stok menipis dan mengonfirmasikan atau melaporkan kebutuhan kepada Admin GU; Admin GU yang menentukan tindak lanjut dan membuat permintaan ke Store atau PR/PO.

---

## A. ROLE: RECORDING GU

### Ringkasan Sidebar

| No | Menu | Fungsi |
|---|---|---|
| 1 | Dashboard Tugas Harian | Apa yang belum dikerjakan hari ini |
| 2 | Stok Opname | Input stok fisik harian/bulanan |
| 3 | Penerimaan Barang | Barang datang (supplier eksternal / kiriman Gudang Store) |
| 4 | Pengeluaran Barang | Catat barang saat keluar, pantau dokumen susulan, retur/sisa, dan rekonsiliasi Admin GU |
| 5 | Retur Barang | Pengembalian sisa dari produksi |
| 6 | Cetak Label Produk | Label dengan nomor seri otomatis |
| 7 | Piket Malam | Transaksi bumbu di luar jam operasional |
| 8 | Cek Stok (read-only) | Lihat stok saat ini |

Tidak ada menu Permintaan Barang untuk Recording GU. Komponen global (bukan menu): **ganti user cepat dengan PIN ★**, **notifikasi termasuk stok menipis Gudang Unit aktif**, **pencarian global ★**.

### 1. Dashboard Tugas Harian

| Isi | Detail |
|---|---|
| Daftar tugas hari ini | Opname harian belum diisi, kiriman Store yang ditujukan ke gudang unit menunggu konfirmasi penerimaan, draft transaksi yang belum disubmit |
| Pintasan aksi cepat | Tombol langsung ke "Terima Barang", "Catat Pengeluaran", "Opname Hari Ini" |
| Ringkasan hari ini | Jumlah penerimaan, pengeluaran, retur yang sudah dicatat |
| Draft tersimpan ★ | Daftar draft form yang belum selesai (autosave), klik untuk melanjutkan |
| Status shift ★ | Nama user aktif saat ini (hasil ganti user via PIN) |
| Peringatan stok menipis | Menampilkan barang Gudang Unit aktif yang mencapai/di bawah ambang minimum; Recording dapat mengonfirmasi atau melaporkan kondisi ke Admin GU, tanpa membuat permintaan atau PR/PO |

### 2. Stok Opname

| Isi | Detail |
|---|---|
| Form opname harian | Daftar barang terisi otomatis; Recording hanya mengisi **qty fisik** |
| Kolom otomatis | Qty sistem (read-only), selisih dihitung otomatis, selisih ≠ 0 disorot (`status-critical`) |
| Urutan mengikuti lokasi rak ★ | Daftar disusun per rak/zona sehingga hitung fisik sekali jalan |
| Filter | Kategori, "hanya yang belum diisi", "hanya yang selisih" |
| Referensi hari sebelumnya | Panel samping: penerimaan/BSTB dan faktur kemarin untuk verifikasi |
| Opname bulanan | Mode yang sama dengan skala semua barang; mencatat jumlah fisik sesuai Form Stok Opname |
| Pencarian ulang selisih | Penanda "sedang dicari ulang" dengan countdown maksimal 2 hari kerja (SOP) |
| Status | draft → submitted (setelah submit tidak bisa diedit; revisi lewat jalur resmi Admin GU) |
| Autosave draft ★ | Tersimpan tiap beberapa detik |

### 3. Penerimaan Barang

Toggle sumber di header: **Dari Supplier (PO)** / **Kiriman Gudang Store**.

| Isi | Dari Supplier | Kiriman Gudang Store |
|---|---|---|
| Referensi | Pilih PO/PR, baris barang terisi otomatis dari PO | Pilih kiriman berstatus "dikirim", baris terisi otomatis |
| Baris barang | Typeahead; Quick-Add hanya jika diaktifkan dengan permission khusus dan kebijakan master data yang disetujui; qty diterima | Barang dan qty kiriman ditampilkan dari dokumen pemindahan; Recording hanya mencatat qty/kondisi yang benar-benar diterima |
| Aksi cepat | Tombol **"Sesuai Semua"**, lalu hanya edit baris yang selisih ★ | Sama |
| Bukti | Foto surat jalan/faktur dari kamera | Foto kondisi barang (opsional) |
| Pengecualian | Toggle **"Tanpa Surat Jalan"** + alasan wajib | Tidak berlaku |
| Timbang ulang | Untuk bahan bakar/es/bumbu: kolom "Jumlah sesuai surat jalan" vs "Hasil timbang ulang", selisih otomatis | Sama |
| Akibat simpan | Saldo Gudang Unit aktif bertambah | Konfirmasi penerimaan memperbarui saldo Gudang Unit aktif berdasarkan qty aktual yang diterima; saldo Store tetap terpisah dan sudah berkurang saat kiriman dikonfirmasi dikirim |
| Validasi | Semua baris divalidasi bersamaan (all-or-nothing); baris bermasalah ditandai tanpa menghapus baris lain | Sama |
| Riwayat | Daftar penerimaan, filter tanggal/sumber/status | Sama |

### 4. Pengeluaran Barang (ke produksi)

Modul ini mengikuti waktu barang **benar-benar keluar**. Recording GU membuat catatan singkat dari barang yang diserahkan; tidak perlu menunggu bundel form selesai dan tidak perlu mengulang input saat menyerahkan dokumen sore hari atau keesokan harinya. Form perusahaan dan tanda tangan basah tetap menjadi bukti fisik. Admin GU memeriksa bukti terhadap transaksi digital yang sama, bukan membuat transaksi kedua atau mengetik ulang ke Excel. Peminta pada transaksi adalah **bagian peminta** (misalnya Produksi, HRD, atau Teknisi), bukan nama karyawan; penginput sistem dan waktu input tercatat otomatis secara terpisah.

> **Status implementasi:** UI Pengeluaran saat ini masih prototipe frontend. Transaksi, serah-terima, rekonsiliasi, dan retur belum tersimpan ke server; hanya draft form yang tersimpan di browser.

| Isi | Detail |
|---|---|
| Header transaksi | Tanggal dan jam kejadian, satu bagian peminta per transaksi, catatan singkat opsional, serta penginput dan waktu input sistem yang dicatat terpisah. Bagian berbeda dibuat sebagai transaksi terpisah |
| Baris barang | Pencarian barang; jumlah aktual wajib lebih dari nol dan satuan mengikuti katalog. Satu transaksi memuat beberapa barang |
| Waktu pencatatan | Recording mencatat segera setelah barang diserahkan. Jika sistem tidak tersedia atau transaksi terlewat, Admin GU dapat memasukkan dokumen susulan dengan tanggal/jam kejadian pada dokumen; waktu input saat itu tetap tercatat terpisah |
| Stok dan pengecualian | Catatan pengeluaran langsung mengurangi stok pada pratinjau. Qty yang melebihi stok tetap dapat dicatat karena barang sudah keluar, tetapi ditandai agar Admin GU meninjau selisih |
| BPB | Nomor BPB pratinjau diterbitkan saat catatan dibuat dan dapat dibuka/cetak untuk diperiksa; format serta penyimpanan final menunggu integrasi backend |
| Status transaksi | `Menunggu Rekonsiliasi` → `Tercatat` setelah Admin GU menerima dan mencocokkan bukti → `Final` setelah seluruh transaksi pada tanggal tersebut selesai diverifikasi |
| Serah-terima dokumen | Recording memilih tanggal kejadian, mengisi jumlah lembar fisik, serta catatan/waktu serah-terima. Dokumen dapat diserahkan sore hari atau menyusul keesokan hari; jumlah lembar yang berbeda dari jumlah transaksi digital dicatat sebagai selisih untuk ditinjau, bukan menghalangi serah-terima |
| Rekonsiliasi | Admin GU membuka antrean, membandingkan rincian transaksi yang sudah ada dengan bundel fisik, menandai bukti diterima/cocok, dan mencatat pengecualian bila ada. Rekonsiliasi mengubah status transaksi yang sama, tidak menambah transaksi |
| Finalisasi harian | Hanya Admin GU yang dapat memfinalisasi tanggal; finalisasi ditolak selama masih ada transaksi menunggu rekonsiliasi. Data final hanya dapat direvisi Admin GU melalui proses beralasan dan berjejak audit |
| Riwayat dan laporan per bagian | Filter tanggal, bagian peminta, barang, status, BPB, dan pengecualian. Tabel ringkas menampilkan BPB, bagian peminta, waktu input, dan tombol Detail. Accordion bersifat baca-saja: berisi waktu kejadian, daftar barang yang keluar, serta catatan pengembalian/sisa yang sudah dicatat |
| Rekap dan Mac.id | Rekap pemakaian dibentuk dari transaksi pengeluaran bersih setelah retur. Ekspor Excel dan tampilan siap-salin ke Mac.id bukan integrasi otomatis |

#### Alur pencatatan dan verifikasi

1. Peminta mengajukan kebutuhan dan petugas gudang menyerahkan barang; bukti perusahaan tetap mengikuti SOP fisik.
2. Recording memilih bagian peminta (misalnya Produksi, HRD, atau Teknisi), lalu mencatat tanggal/jam kejadian serta barang/jumlah aktual yang keluar. Satu transaksi mewakili satu penyerahan untuk satu bagian. Jika bagian yang sama menyusulkan permintaan pada waktu/penyerahan berbeda, buat transaksi baru untuk bagian tersebut; transaksi itu tetap masuk rekap bagian yang sama. Form tidak perlu menunggu petugas lapangan mengembalikan bundel ke Admin GU.
3. Sistem membuat satu transaksi/BPB, mencatat siapa dan kapan input dilakukan, serta menandai transaksi **Menunggu Rekonsiliasi**. Stok pratinjau langsung berkurang karena barang telah keluar.
4. Saat bundel siap—biasanya sore hari atau kadang keesokan harinya—Recording menandai serah-terima, mengisi jumlah lembar dan catatan. Jika jumlah lembar tidak sama dengan transaksi, selisih ditampilkan untuk ditinjau Admin GU.
5. Admin GU membuka antrean berdasarkan tanggal, mencocokkan bukti fisik dengan rincian pada BPB, dan mengonfirmasi dokumen diterima. Admin tidak mengetik ulang rincian yang sudah dicatat Recording; ketidaksesuaian dicatat untuk tindak lanjut.
6. Jika transaksi digital belum sempat dibuat, Admin GU mencari transaksi terlebih dahulu lalu memasukkan dokumen susulan satu kali dengan tanggal/jam kejadian asli. Timestamp input Admin tetap tercatat; transaksi masuk ke alur verifikasi tanpa membuat duplikat.
7. Setelah semua dokumen dan pengecualian pada tanggal tersebut selesai ditinjau, Admin GU memfinalisasi hari. Transaksi final terkunci; revisi memerlukan alasan dan jejak audit.

Pencatatan per bagian **tidak mewajibkan setiap karyawan mempunyai akun atau nama individu dicatat di aplikasi**. Recording GU atau Admin GU (untuk dokumen susulan) memilih bagian peminta; laporan dapat difilter atau diringkas berdasarkan bagian. Akun yang melakukan input tetap dicatat tersendiri sebagai penginput sistem.

#### Retur dan sisa produksi

Retur/sisa dicatat pada menu **Retur & Sisa** dengan memilih transaksi/BPB asal agar catatan tetap terhubung ke bagian dan barang yang keluar. Pada accordion Riwayat Pengeluaran, setiap barang ditampilkan dalam satu baris dengan barang, qty keluar, satuan, qty kembali, status, dan catatan. Qty kembali dihitung otomatis dari catatan pengembalian yang sudah disimpan melalui menu Retur & Sisa; sisa yang masih berada di produksi tidak dihitung sebagai qty kembali sampai statusnya diperbarui menjadi benar-benar kembali. Accordion hanya untuk melihat dan tidak menyediakan input retur. Jumlah yang dilaporkan tidak boleh melebihi jumlah yang keluar dikurangi seluruh retur/sisa yang sudah dicatat. Catat waktu barang kembali atau waktu sisa diketahui secara terpisah dari waktu pencatatan sistem.

| Kondisi atau status | Perlakuan stok |
|---|---|
| Kembali dan layak pakai | Menambah stok Gudang Unit setelah barang benar-benar kembali |
| Kembali rusak/tidak layak | Dicatat untuk pertanggungjawaban, tidak menambah stok layak pakai |
| Masih berada di produksi | Dicatat sebagai sisa yang belum kembali; tidak menambah stok. Saat barang kembali, status diperbarui ke layak pakai atau rusak dan stok hanya bertambah jika layak pakai |

#### Aksi tambahan

| Aksi | Detail |
|---|---|
| Draft lokal | Autosave form pengeluaran di browser untuk melanjutkan pencatatan; bukan penyimpanan transaksi di server |
| Salin transaksi kemarin | Salin bagian peminta dan rincian barang sebagai draft; tanggal/waktu serta jumlah harus diperiksa sebelum dicatat |
| Accordion detail pengeluaran | Tampilkan waktu kejadian dan satu baris per barang berisi qty keluar, satuan, qty kembali otomatis, status, serta catatan. Qty kembali/status/catatan berasal dari catatan di menu Retur & Sisa. Accordion hanya untuk melihat |
| Antrean Admin GU | Tampilkan transaksi belum dicocokkan, umur keterlambatan dokumen, status serah-terima, dan pengecualian |

### 5. Retur Barang

| Isi | Detail |
|---|---|
| Form retur/sisa | Di tab **Retur & Sisa**, pilih transaksi/BPB asal, barang, jumlah, waktu kembali/sisa diketahui, status (kembali layak pakai, kembali rusak/tidak layak, atau masih di produksi), dan keterangan opsional. Form tidak berada di accordion riwayat |
| Batas jumlah | Jumlah retur dan sisa kumulatif tidak boleh melebihi qty yang keluar dari baris BPB terkait |
| Akibat stok | Hanya barang yang benar-benar kembali dan layak pakai menambah stok. Barang rusak dan sisa yang masih di produksi tetap dicatat tanpa menambah stok layak pakai |
| Perubahan status sisa | Sisa yang masih berada di bagian peminta dapat diperbarui menjadi kembali layak pakai atau rusak melalui tab Retur & Sisa; stok ditambah satu kali hanya jika dinyatakan layak pakai |
| Riwayat | Daftar retur/sisa tertaut bagian peminta dan BPB, kondisi, qty, waktu kejadian kembali dan waktu dicatat sistem, serta keterangan. Catatan ditampilkan baca-saja pada accordion BPB asal |

### 6. Cetak Label Produk

| Isi | Detail |
|---|---|
| Field wajib | Nama pelanggan, produk, tanggal produksi, jenis ayam/partingan, cap halal |
| Kondisional | **Kode area** wajib untuk on-fresh; **tanggal expired** wajib untuk frozen |
| Preset per pelanggan/produk ★ | Pilih preset → field terisi otomatis |
| Expired otomatis ★ | Tanggal produksi + masa simpan produk → tanggal expired terisi (masih bisa dikoreksi dengan alasan) |
| Nomor seri otomatis | Unik, tidak bisa diketik manual |
| Preview | Tampilan label sebelum cetak |
| Cetak batch ★ | Jumlah label N → nomor seri berurutan otomatis dalam sekali cetak |
| Riwayat | Label yang sudah dicetak tidak bisa diedit; koreksi = buat label baru |

### 7. Piket Malam

| Isi | Detail |
|---|---|
| Form | Pengeluaran/penerimaan bumbu di luar jam operasional |
| Tanggal | Catat tanggal kejadian untuk rekap; waktu input sistem tersimpan otomatis |
| Pola | Mengikuti pencatatan pengeluaran dari form kertas: TTD fisik diverifikasi dengan checklist, bukan tanda tangan digital; BPB otomatis saat transaksi tercatat |
| Hak akses | Pengeluaran di luar jam operasional dibuat Admin GU; input Recording hanya bila kebijakan mengizinkan dengan keterangan "diinput atas nama" |

### 8. Cek Stok (read-only)

| Isi | Detail |
|---|---|
| Tabel stok | Saldo Gudang Unit aktif, satuan, kategori, status (aman/warning/kritis), dan penanda stok menipis |
| Pencarian | Typeahead cepat untuk cek ketersediaan Gudang Unit sebelum menyiapkan pengeluaran produksi; tidak menampilkan atau menggabungkan saldo Gudang Store |
| Tindak lanjut | Recording mengonfirmasi/melaporkan stok menipis kepada Admin GU; pembuatan permintaan ke Store dan PR/PO hanya dilakukan Admin GU |

### Komponen global Recording GU

| Komponen | Detail |
|---|---|
| Ganti user dengan PIN ★ | 3 orang berbagi 1 PC: ganti pengguna tanpa logout penuh, semua transaksi tercatat atas nama yang benar |
| Autosave draft ★ | Draft tersimpan walau PC direbut orang lain |
| Pencarian global ★ | Satu kolom: barang, BPB, PO, label |
| Scan QR ★ | Pindai kode di label/dokumen lewat kamera tablet/HP (jika perangkat tersedia) |

---

## B. ROLE: ADMIN GU

### Ringkasan Sidebar

| No | Menu | Fungsi |
|---|---|---|
| 1 | Dashboard | Reminder & ringkasan harian |
| 2 | Katalog Barang (read-only) | Lihat katalog global |
| 3 | Stok Gudang | Stok real-time + kartu stok |
| 4 | Permintaan Barang | Admin GU mencatat kebutuhan unit, lalu menentukan jalur ke Gudang Store atau PR/PO eksternal |
| 5 | Verifikasi & Finalisasi Harian | Kunci data harian |
| 6 | Review Stok Opname | Selisih & tindak lanjut |
| 7 | Expired & Stok Beku | Penanganan barang bermasalah |
| 8 | Berita Acara Kehilangan Stok | Dokumen resmi selisih |
| 9 | Laporan | Excel otomatis |

### 1. Dashboard

| Isi | Detail |
|---|---|
| Widget stok menipis | Barang Gudang Unit ≤ ambang minimum; tombol **"Buat Draft PR"** untuk Admin GU ★. Peringatan yang dikonfirmasi Recording menjadi bahan tindak lanjut, bukan permintaan otomatis |
| Widget bumbu mendekati expired | Daftar + sisa hari |
| Widget kandidat stok beku | Barang idle > 12 bulan |
| Widget perlu perhatian ★ | Jumlah transaksi bermasalah hari ini (selisih, tanpa surat jalan) |
| Widget kiriman Store menunggu | Kiriman "dikirim" yang belum dikonfirmasi diterima |
| Pengaturan ambang | Ambang hari reminder expired (default, bisa diatur) |

### 2. Katalog Barang (read-only)

| Isi | Detail |
|---|---|
| Tabel katalog | Kode, nama, kategori, merk, satuan, status |
| Aksi | Hanya lihat/cari; **tidak ada** Tambah/Edit/Nonaktifkan (tambah baru lewat Quick-Add di form transaksi) |
| Atur stok minimum | Ambang stok minimum untuk gudangnya (jika diputuskan boleh, lihat open question) |

### 3. Stok Gudang

| Isi | Detail |
|---|---|
| Tabel real-time | Qty, satuan, kategori, status |
| Kartu stok per barang | Riwayat mutasi kronologis dengan saldo berjalan |
| Filter & export | Kategori, status; ekspor Excel |

### 4. Permintaan Barang

| Isi | Detail |
|---|---|
| Konfirmasi kebutuhan dari Recording GU | Daftar konfirmasi/laporan stok menipis dan catatan kebutuhan operasional; bukan draft permintaan atau PR/PO yang dibuat Recording |
| Pilih jalur | **Ke Gudang Store (internal)** atau **PR/PO eksternal** |
| Saran dari stok menipis ★ | Centang barang menipis → otomatis jadi baris draft |
| Referensi PO Mac.id | Input nomor PO (manual, belum integrasi API); status diajukan / sebagian diterima / selesai |
| Pantau status | Admin GU membuat dan memantau permintaan ke Store (diminta/diproses/dikirim/diterima) serta PR/PO eksternal; Recording tidak membuat atau mengajukan permintaan |

### 5. Verifikasi & Finalisasi Harian

| Isi | Detail |
|---|---|
| Rekap harian otomatis | Semua transaksi hari itu (penerimaan, pengeluaran, retur, piket malam) |
| Antrean serah-terima | Menampilkan hari berstatus menunggu verifikasi, jumlah transaksi, jumlah form kertas, dan selisih lembar fisik |
| Mode "Pengecualian" ★ | Filter melebihi stok, Tanpa Form, nomor form ganda, selisih timbang besar, dan jumlah lembar fisik tidak cocok |
| Verifikasi | Admin GU membuka detail BPB dan membandingkannya dengan form kertas serta rekap timbang sebelum finalisasi |
| Kunci data | Aksi **Verifikasi & Kunci** memfinalisasi transaksi pada tanggal tersebut; data menjadi read-only bagi Recording GU |
| Revisi/pembatalan | Hanya Admin GU dengan alasan wajib; stok disesuaikan melalui mutasi balik/perubahan tercatat dan audit, bukan hard delete |

### 6. Review Stok Opname

| Isi | Detail |
|---|---|
| Daftar opname | Harian/bulanan, status, pengisi |
| Selisih otomatis | Qty fisik vs sistem, hanya yang selisih disorot |
| Countdown 2 hari kerja | Batas pencarian ulang selisih menurut SOP |
| Finalisasi opname | Kunci hasil opname (hanya Admin GU) |
| Lanjut ke Berita Acara | Tombol muncul bila selisih tak ditemukan setelah 2 hari kerja |

### 7. Expired & Stok Beku

| Isi | Detail |
|---|---|
| Daftar mendekati expired | Per customer/barang, tanggal kedatangan, tanggal expired |
| Tandai Expired | Mengeluarkan dari stok aktif (hanya Admin GU), tercatat audit |
| Kandidat stok beku | Otomatis dari barang tanpa transaksi > 12 bulan |
| Estimasi kerugian | Form nominal + keterangan dasar perhitungan |
| Status tindak lanjut | candidate → diajukan → ditindaklanjuti |

### 8. Berita Acara Kehilangan Stok

| Isi | Detail |
|---|---|
| Draft otomatis ★ | Barang, qty selisih, tanggal opname terisi dari data opname; Admin menambah keterangan |
| Waktu pembuatan | Tercatat otomatis, tidak bisa diedit |
| Cetak/unduh | Format dokumen konsisten |

### 9. Laporan

Ekspor **hanya Excel (.xlsx)**, diproses di latar belakang, format rapi (font, border, lebar kolom, freeze header). Pola struktur sama dengan modul laporan Store (1 file per bulan, 1 sheet per tanggal untuk laporan transaksi).

| Laporan | Isi |
|---|---|
| Stok Opname (harian/bulanan) | Qty fisik, sistem, selisih |
| Pemakaian Barang | Rekap per hari/jenis/barang: qty keluar, retur, qty bersih; bumbu dapat direkap per pelanggan/SPK |
| Laporan Biaya | Rekap pemakaian sebagai acuan biaya produksi |
| Barang Masuk | Penerimaan per tanggal |
| Barang Keluar | Rekap pengeluaran dan retur per tanggal/jenis, nomor BPB, bagian peminta, penginput, serta status/pengecualian; bahan bakar/es mencakup qty dokumen, hasil timbang, dan qty keluar |
| Stok Sisa & Reminder | Saldo dan status ambang minimum |

Rekap pengeluaran juga menyediakan tampilan siap-salin untuk Mac.id tanpa klaim integrasi. Format Excel mengikuti template Admin GU setelah template perusahaan tersedia.

### Komponen global Admin GU

| Komponen | Detail |
|---|---|
| Pencarian global ★ | Barang, PO, BPB, label, berita acara |
| Notifikasi | Stok menipis, expired, kandidat stok beku, kiriman Store |

---

## C. Pembagian Wewenang Singkat (Recording GU vs Admin GU)

| Aksi | Recording GU | Admin GU |
|---|---|---|
| Input opname, penerimaan, retur, label | Ya | Tidak (merekap, bukan input fisik) |
| Mencatat pengeluaran saat barang keluar | Ya | Ya, sebagai input dokumen susulan bila transaksi digital belum ada atau sesuai SOP |
| Menandai serah-terima bundel form fisik | Ya | Menerima dan mencocokkan bundel |
| Mencocokkan dokumen dengan transaksi digital | Tidak | Ya; tanpa mengetik ulang transaksi yang sudah dicatat |
| Finalisasi, revisi, atau pembatalan pengeluaran | Tidak | Ya (revisi/pembatalan wajib alasan dan audit) |
| Melihat stok menipis Gudang Unit dan mengonfirmasi/melaporkan kebutuhan ke Admin GU | Ya | Ya |
| Membuat permintaan barang ke Gudang Store | Tidak | Ya |
| Membuat/mengajukan PR/PO & mencatat nomor PO Mac.id | Tidak | Ya |
| Finalisasi/kunci data harian dan opname | Tidak | Ya |
| Tandai expired, ajukan stok beku | Tidak | Ya |
| Berita Acara Kehilangan | Tidak | Ya |
| Ubah data yang sudah terkunci | Tidak | Ya (dengan alasan + audit) |
| Quick-Add barang/kategori/satuan | Tidak secara default; hanya jika kelak disetujui dan diberi permission khusus | Ya, sesuai permission |
| Edit/nonaktifkan katalog | Tidak | Tidak (khusus Super Admin) |

## D. Prioritas Pembangunan

| Gelombang | Fitur |
|---|---|
| 1 | Input sekali dari form kertas → BPB dan rekap otomatis, tutup input harian, form multi-baris keyboard-first, opname terisi otomatis, penerimaan dari PO, reminder, finalisasi berbasis pengecualian |
| 2 | Ganti user PIN, autosave draft, mode tablet, template bumbu per SPK, label preset + expired otomatis, retur dari pengeluaran |
| 3 | Scan QR, barang sering dipakai, ulangi transaksi, pencarian global, saran kebutuhan/PR untuk Admin GU, urutan opname per rak |

## E. Open Questions

1. Apakah Recording GU diizinkan Quick-Add barang baru/kategori/satuan? Belum diputuskan. Secara default Quick-Add tidak tersedia sampai permission khusus dan tata kelola master data disetujui; Quick-Add tidak memberi hak edit/nonaktifkan master.
2. Apakah tersedia tablet/HP di gudang? Menentukan prioritas mode tablet dan scan QR.
3. Apakah masa simpan produk (untuk expired otomatis di label) baku per jenis produk?
4. Apakah gudang punya penomoran lokasi rak/zona (untuk urutan opname)?
5. Retur berkondisi "rusak": masuk stok layak pakai, atau dicatat terpisah?
6. Apakah Admin GU boleh mengatur stok minimum, atau itu wewenang Super Admin bersama edit katalog?
7. Kolom harga di penerimaan Sukahati (SOP mencatat harga di Mac.id): perlu di sistem ini atau tetap di Mac.id?
8. Daftar laporan Admin GU di atas diturunkan dari SOP (Tahap 1), belum dikonfirmasi apakah mengikuti pola 4 laporan Store.
9. Apa template Excel dan format cetak BPB perusahaan yang harus diikuti?
10. Siapa yang menginput hasil rekap ke Mac.id, dan apakah tersedia format impor/API? Sampai diputuskan, sistem hanya menyediakan tampilan siap-salin.
11. Berapa ambang selisih timbang untuk bumbu, bahan bakar, dan es (angka tetap atau persentase)?
12. Bagian peminta yang dicatat adalah nama bagian (misalnya Produksi, HRD, Teknisi), bukan nama individu. Daftar master bagian perlu dikonfirmasi Admin GU sebelum integrasi backend.
13. Jika Admin GU berhalangan, apakah Recording boleh mencatat pengeluaran di luar jam operasional dengan keterangan "diinput atas nama"?
14. Apakah pengeluaran darurat tanpa form kertas diizinkan, dengan alasan wajib dan pencatatan pada hari yang sama?
15. Apakah form kertas sudah memiliki nomor unik? Jika belum, apakah nomor lembar harian perlu disediakan sistem?
16. Apakah detail bumbu dan takaran untuk template SPK tersedia dalam data SPK yang bisa dirujuk?
17. Apakah input langsung saat serah-terima akan diaktifkan pada fase berikutnya? Jika ya, siapa petugas yang menginput dan bagaimana stok kurang ditangani?