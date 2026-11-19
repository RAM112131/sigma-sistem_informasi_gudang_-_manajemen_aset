# Rencana Baru: Sistem Impor Master Barang

> Status: fitur inti sudah diimplementasikan dan pemeriksaan terarah lulus. Sistem menggunakan tabel katalog yang sudah ada dan tidak menambah tabel atau migration khusus impor.

Implementasi saat ini mencakup template Excel resmi, pembacaan file per chunk yang diteruskan ke browser sebagai NDJSON, grid paste Excel, tinjauan paginasi, penyimpanan sementara di IndexedDB, pemeriksaan duplikasi/referensi, pembatasan gudang berdasarkan assignment pengguna, dan konfirmasi batch. Jalur paste tetap mengirim data yang telah tersedia di browser; tidak ada pembacaan file Excel pada jalur tersebut.

Verifikasi terakhir: 10 tes fitur impor lulus, Pint selesai, build Vite berhasil, keenam route impor terdaftar, dan Blade view berhasil dikompilasi. Build hanya menampilkan peringatan opsional bahwa package `fontaine` tidak terpasang.

## Tujuan

Membuat alur impor Master Barang yang mudah dipahami dan konsisten untuk dua sumber data:

1. Unggah file Excel yang mengikuti template resmi.
2. Tempel langsung data yang disalin dari Excel ke grid di website.

Kedua cara berakhir pada halaman tinjauan yang sama. Tidak ada perubahan katalog sebelum pengguna menekan tombol konfirmasi.

## Keputusan dan batasan utama

- Tidak membuat tabel, model, atau migration untuk menyimpan proses maupun riwayat impor.
- Tidak menggunakan tabel `jobs` atau membuat status antrean di database untuk alur impor.
- Data hasil konfirmasi tetap disimpan ke tabel katalog yang sudah ada, yaitu `master_barangs` dan `barangs`. Impor tidak mengubah stok atau alur Import Stok Awal.
- File Excel hanya dibaca dari unggahan Excel. Data yang ditempel sudah tersedia di browser dan tidak boleh memicu pembukaan atau pembacaan ulang file.
- Tidak membatasi jumlah total baris dengan angka tetap. Pengiriman dan pemrosesan dilakukan per batch agar ukuran request, memori, dan waktu respons tetap terkendali.
- Daftar tinjauan menampilkan 20 baris per halaman, dengan navigasi halaman. Grid input dapat menambah baris sesuai data yang ditempel; pagination tinjauan bukan batas jumlah data.
- Gunakan Laravel Excel yang sudah menjadi dependency proyek (`maatwebsite/excel ^4.0`); jangan menambah dependency tanpa persetujuan.

## Alur pengguna

1. Pengguna berizin membuka tab **Master Barang**, lalu memilih subtab **Impor Master Barang**.
2. Pengguna memilih **Unduh template**, **Unggah Excel**, atau **Tempel dari Excel**.
3. Setelah memilih atau memasukkan data, pengguna menekan **Tinjau data**.
4. Pengguna berpindah ke halaman tinjauan. Halaman menampilkan ringkasan jumlah data valid, master baru, master yang digunakan kembali, data yang sudah terdaftar, duplikasi, dan kesalahan.
5. Pengguna memperbaiki data yang dapat diedit, atau kembali ke input untuk memperbaikinya. Pesan validasi ditampilkan di bagian atas halaman dengan bahasa Indonesia yang jelas dan mempertahankan isi input.
6. Setelah data siap, pengguna menekan **Impor barang**. Server memvalidasi ulang batch dan konflik katalog sebelum menyimpan.
7. Tampilkan progres per batch dan hasil akhir. Jika suatu batch gagal, sampaikan batch/baris yang bermasalah dan jangan menampilkan status sukses palsu.

## Template Excel

- Sediakan unduhan `.xlsx` dengan sheet **Petunjuk** dan **Master Barang**.
- Sheet **Master Barang** menggunakan header tetap dan urutan: `kode_barang`, `nama_barang`, `kategori`, `satuan`, `deskripsi`.
- Empat kolom pertama wajib; `deskripsi` opsional.
- Tolak file yang sheet data atau header-nya hilang, berubah, tertukar, atau memiliki kolom yang tidak didukung. Tidak ada pemetaan kolom otomatis.
- Buat template mudah dibaca: judul dan instruksi singkat, header tegas, lebar kolom sesuai isi, filter, baris header dibekukan, dan contoh hanya pada sheet petunjuk.
- Abaikan baris yang sepenuhnya kosong. Baris yang terisi sebagian tetap ditinjau dan menunjukkan kolom yang perlu diperbaiki.
- Pembacaan file besar harus menggunakan pembacaan bertahap Laravel Excel; jangan memuat seluruh workbook besar ke memori.

## Grid tempel Excel

- Tampilkan kolom yang sama dengan template, dalam urutan yang sama, dengan label wajib/opsional.
- Mendukung paste rentang multi-kolom dan multi-baris dari clipboard Excel. Isi mulai dari sel aktif dan jumlah kolom yang tidak ditempel tidak boleh tertimpa.
- Tambahkan baris otomatis sesuai jumlah baris yang ditempel, tanpa batas total baris yang ditetapkan aplikasi.
- Sediakan navigasi keyboard, penambahan/penghapusan baris, serta penanda validasi per baris/sel.
- Validasi paste langsung atas data yang sudah ditempel; jangan menunggu antrean, membuka file, atau mengirim seluruh data dalam satu request.
- Untuk data besar, tampilkan/render secara bertahap agar browser tetap responsif.

## Tinjauan, validasi, dan duplikasi

Gunakan satu bentuk data baris yang telah dinormalisasi untuk kedua jalur input, dengan nomor baris sumber yang dipertahankan.

Status tinjauan minimal:

- **Master baru**: kode belum ada; akan membuat master dan mendaftarkannya ke gudang tujuan.
- **Master digunakan kembali**: kode sudah ada dan rincian cocok; gunakan master tersebut.
- **Sudah terdaftar**: barang sudah terhubung ke gudang tujuan; tandai sebagai duplikat dan jangan menimpa data.
- **Duplikat dalam file/tempelan**: hanya kemunculan pertama yang dapat diproses; kemunculan berikutnya ditandai dengan nomor baris terkait.
- **Konflik data master**: kode sudah ada tetapi nama, kategori, atau satuan berbeda; jangan mengubah master global secara otomatis.
- **Data tidak valid**: tunjukkan nomor baris, kolom, dan tindakan yang diperlukan dengan bahasa yang wajar.

Kategori dan satuan harus merujuk ke data aktif yang tersedia. Sebelum implementasi, tetapkan bersama apakah nilai referensi yang belum tersedia hanya ditolak atau boleh dibuat oleh role tertentu. Jangan membuat kategori/satuan secara otomatis tanpa aturan dan otorisasi yang disepakati.

Ringkasan dan status tidak boleh hanya dihitung dari jumlah per batch: duplikasi yang melintasi batas batch juga harus terdeteksi. Saat konfirmasi, server memeriksa ulang kode, hubungan gudang, referensi aktif, dan hak akses agar perubahan katalog lain setelah tinjauan tidak menghasilkan data yang salah.

## Penyimpanan sementara tanpa tabel impor

- Data paste tetap di browser selama proses input/tinjauan, lalu dikirim dalam batch.
- File Excel yang diunggah disimpan sementara pada penyimpanan privat yang sudah tersedia, bukan sebagai rekaman database. Tinjauan memproses file tersebut bertahap.
- Jika proses membutuhkan token sementara, gunakan token acak yang terikat pada pengguna dan sesi yang berwenang; jangan mempercayai path atau identitas gudang dari klien.
- Hapus file sementara setelah impor selesai/gagal, saat dibatalkan, atau ketika masa retensinya berakhir. Sediakan pembersihan untuk file kedaluwarsa dan laporkan kegagalan pembersihan melalui mekanisme logging aplikasi.
- Progres batch dikirim sebagai respons tiap request dan dikelola oleh halaman aktif; jangan menambahkan tabel status impor atau ketergantungan pada worker antrean.
- Tiap batch penyimpanan katalog harus aman untuk dicoba ulang dan berada dalam transaksi. Validasi dan penanganan konflik tetap dilakukan server-side.

## Otorisasi dan gudang tujuan

- Pulihkan permission `barang.import` melalui seeder permission/role proyek, lalu berikan hanya kepada role yang telah disepakati. Jangan menganggap permission lama masih tersedia di database.
- Ikuti pola middleware, policy, pemilihan gudang aktif, dan assignment yang digunakan fitur katalog saat ini.
- Tentukan gudang tujuan berdasarkan role dan assignment server: Admin Gudang Umum memakai gudang unit bisnis yang ditugaskan (Sukahati mengikuti assignment Sukahati), Admin Gudang Store memakai Gudang Store/Armada yang ditugaskan, dan Super Admin dapat memilih satu atau beberapa gudang aktif.
- Tempatkan input impor sebagai subtab pada halaman Master Barang yang sama dengan katalog; jangan tampilkan menu sidebar impor yang terpisah. URL impor lama tetap mengarah ke subtab tersebut untuk menjaga bookmark.
- Validasi ulang setiap gudang yang dipilih pada upload, tinjauan, dan tiap batch konfirmasi; jangan menerima gudang tujuan dari isi spreadsheet. Impor multi-gudang membuat satu Master Barang dan mendaftarkannya ke setiap lokasi terpilih.
- Cocokkan kategori dan satuan aktif pada setiap lokasi terpilih; barang lokal memakai referensi gudang masing-masing walaupun ID referensinya berbeda.
- Periksa hak akses saat membuka halaman, mengunggah, meminta tinjauan, dan setiap batch konfirmasi.
- Pengguna yang tidak berhak tidak dapat mengakses file sementara atau menjalankan batch atas nama pengguna/gudang lain.

## Rencana implementasi bertahap

1. **Pemetaan pola proyek** — tinjau CRUD Master Barang, relasi `master_barangs`/`barangs`, gudang aktif, permission/role, validasi kategori-satuan, serta alur Import Stok Awal yang harus tetap terpisah.
2. **Tetapkan aturan bisnis** — pastikan role yang boleh mengimpor, perilaku kategori/satuan yang belum ada, aturan konflik, dan ukuran batch teknis melalui pengujian/performance check.
3. **Bangun template dan pembaca Excel** — buat exporter template resmi serta parser/importer khusus yang memvalidasi sheet/header dan membaca file secara bertahap.
4. **Bangun grid paste** — implementasikan grid bergaya spreadsheet, paste multi-sel, baris dinamis, dan pemrosesan paste langsung tanpa membaca file.
5. **Bangun tinjauan bersama** — gunakan normalisasi dan layanan analisis yang sama; tampilkan ringkasan, paginasi 20 baris, filter status, serta validasi di atas tabel.
6. **Bangun konfirmasi batch** — simpan perubahan hanya setelah konfirmasi, gunakan transaksi per batch, validasi ulang akses dan konflik, dan sediakan progres/error yang dapat ditindaklanjuti.
7. **Pulihkan menu dan permission** — wire route, controller tipis, Form Request, layanan, view/JavaScript, menu, permission seeder, serta pengelolaan role mengikuti pola aplikasi.
8. **Uji dan verifikasi** — jalankan tes terarah, Pint, build aset bila frontend berubah, dan pastikan route/view aktif. Tidak membuat atau menjalankan migration untuk status/proses impor.

## Kriteria penerimaan

- Template valid dapat diunduh dan diunggah; file dengan header/sheet/kolom yang tidak sesuai ditolak dengan pesan yang jelas.
- Paste multi-baris dan multi-kolom mengisi sel yang tepat, menambah baris otomatis, dan tidak membutuhkan file Excel di server.
- Setelah **Tinjau data**, pengguna masuk ke halaman tinjauan; kesalahan terlihat di bagian atas dan isi input tetap tersedia.
- Tinjauan memuat 20 baris per halaman tanpa menghilangkan baris dari total dataset.
- Duplikasi di dalam satu batch maupun lintas batch, barang yang telah terdaftar, konflik master, dan referensi tidak valid diberi hasil yang konsisten.
- Membatalkan atau gagal meninjau tidak mengubah katalog. Konfirmasi hanya menyimpan baris yang memenuhi aturan, dengan pemeriksaan ulang server-side.
- Tujuan impor otomatis mengikuti assignment Admin Gudang Umum/Admin Gudang Store; Super Admin dapat memilih Gudang A, Gudang B, atau keduanya, dan item tercatat pada semua lokasi yang dipilih.
- Pemrosesan dataset besar dibatasi per payload/batch, memiliki progres yang terlihat, dan tidak membuat satu request memuat seluruh data.
- Tes membuktikan akses sesuai role/gudang, hasil kedua sumber input konsisten, perilaku penting tidak memotong data, serta tabel katalog dan alur stok yang sudah ada tidak rusak.
- Tidak ada tabel/model/migration baru untuk pencatatan impor dan tidak ada perubahan pada alur Import Stok Awal.

## Area kode yang kemungkinan disentuh

- `routes/web.php`
- `app/Http/Controllers/MasterData/`
- `app/Http/Requests/MasterData/`
- `app/Services/MasterData/`
- `app/Imports/` dan `app/Exports/`
- `database/seeders/RolePermissionSeeder.php`
- `app/Services/SuperAdmin/RoleService.php`
- `resources/views/master-data/`
- `resources/js/`
- `tests/Feature/` dan tes frontend yang sudah digunakan proyek

## Implementasi Import Excel

Gunakan Maatwebsite/Laravel Excel yang kompatibel dengan versi Laravel dan PHP project.

dengan ketentuan : 

- Struktur dan mapping kolom.
- Validasi header serta isi data.
- Penanganan duplikasi.
- Aturan insert atau update.
- Relasi dengan master data.
- Aturan penyimpanan dan pelaporan error.
- Output atau notifikasi kepada pengguna.

Untuk optimasi performa:
- Gunakan WithChunkReading jika sesuai dengan jenis import.
- Gunakan WithBatchInserts atau operasi database batch yang sesuai.
- Evaluasi penggunaan WithValidation dan validasi manual agar tidak terjadi pemrosesan berulang yang tidak perlu.
- Hindari query individual untuk setiap baris jika dapat digantikan dengan lookup terkelompok atau operasi batch.
- Hindari memuat seluruh file ke dalam array atau collection sekaligus.
- Hindari query N+1.
- Optimalkan pencarian data existing, validasi, insert, dan update.
- Sesuaikan ukuran chunk dan batch berdasarkan pengujian, bukan asumsi.

## Implementasi
Jika mengatur banyak sheet, nama sheet, isi laporan, urutan kolom, format angka, rumus, ringkasan, atau perhitungan stok, seluruh ketentuan tersebut harus dipertahankan.

Untuk optimasi:
- Gunakan FromQuery untuk pengambilan dataset besar jika sesuai.
- Gunakan WithMultipleSheets apabila workbook memang membutuhkan banyak sheet.
- Hindari mengambil seluruh data ke memori menggunakan Model::all() atau get() tanpa pertimbangan volume.
- Optimalkan agregasi menggunakan query database.
- Hindari query berulang untuk setiap tanggal atau barang.
- Gunakan eager loading hanya jika diperlukan dan tidak menyebabkan pembengkakan memori.
  - Evaluasi index database yang relevan dengan filter tanggal, relasi barang, dan jenis transaksi.
- Optimalkan pembuatan file sementara dan proses unduhan.
- Pertahankan otorisasi akses dan keamanan file hasil export.
