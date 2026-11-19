# Spesifikasi Fitur dan Alur WMS — Admin Gudang Store

> Dokumen ini merinci fitur Gudang Store dan hubungan operasionalnya dengan unit peminta/penerima. Katalog barang adalah katalog global yang dibrowse secara read-only oleh Admin Store; saldo stok, penerimaan, alokasi, pengiriman, dan laporan operasional tetap dibatasi pada Gudang Store aktif. Siklus pemenuhan permintaan berakhir setelah unit tujuan mengonfirmasi penerimaan.

## 1. Tujuan dan prinsip WMS

1. Setiap kuantitas stok harus berasal dari transaksi yang dapat diaudit; saldo tidak boleh diedit langsung pada master barang.
2. Katalog global menjadi referensi barang bersama. Halaman Katalog untuk Admin Store adalah browse/search/detail; Admin Store tidak mengedit atau menonaktifkan master yang sudah ada.
3. Hak Quick-Add, bila diberikan melalui permission, hanya membuat barang baru di katalog global melalui form transaksi yang diizinkan; bukan membuka CRUD master pada halaman Katalog dan tidak otomatis membuat saldo. Barang existing tidak boleh diubah melalui Quick-Add.
4. Stok Store tidak pernah digabungkan dengan stok unit. Semua transaksi harus memakai gudang aktif yang sudah diotorisasi.
5. Alokasi permintaan menahan stok tersedia, tetapi tidak mengurangi stok fisik. Saldo fisik Store berkurang pada konfirmasi pengiriman.
6. Pengiriman dan penerimaan unit adalah peristiwa berbeda: `dikirim` berarti dalam perjalanan, `diterima` hanya setelah penerima pada unit mengonfirmasi barang.
7. Operasi perubahan stok dan status harus tercatat dengan aktor, waktu, dokumen sumber, kuantitas, dan alasan/keterangan bila relevan.
8. Form utama tetap divalidasi di server; UI hanya membantu memasukkan data dan menampilkan batas.

### Definisi saldo

- **Saldo fisik Store** = Stok Awal + barang masuk yang dapat digunakan − pemindahan berstatus `dikirim` atau `diterima`.
- Barang rusak/tidak dapat digunakan tidak menambah saldo tersedia; aturan detailnya mengikuti pencatatan kondisi penerimaan.
- **Stok dialokasikan** = jumlah pemindahan Store berstatus `diminta`.
- **Stok tersedia untuk alokasi** = max(0, saldo fisik Store − stok dialokasikan).
- Alokasi tidak membuat barang keluar. Barang keluar hanya saat konfirmasi pengiriman mengubah pemindahan menjadi `dikirim`.
- Stok milik gudang tujuan dicatat oleh proses penerimaan unit; jangan menambahkan kuantitas yang sama ke saldo Store maupun menganggap pengiriman Store sebagai penerimaan unit.

## 2. Peran dan batas akses

| Aktor | Tanggung jawab dalam alur Store |
|---|---|
| Admin Gudang Store | Memantau stok, mencatat penerimaan supplier, meninjau permintaan, mengalokasikan stok, mengonfirmasi pengiriman, dan melihat laporan Store |
| Admin/Recording Gudang Unit | Membuat/mengajukan permintaan ke Store sesuai proses unit, menerima kiriman, mencatat kuantitas aktual/kondisi, serta mengonfirmasi atau melaporkan selisih |
| Super Admin | Mengelola master dan konfigurasi akses sesuai permission; tidak otomatis melakukan transaksi Store |
| Sistem | Menghitung saldo dan reservasi, memvalidasi gudang/status/kuantitas di server, membentuk nomor dokumen, serta merekam audit |

### Prinsip permission

- `store.view`: melihat stok dan halaman Store yang diizinkan.
- `store.penerimaan`: mencatat penerimaan supplier.
- `store.distribusi`: melihat/mengelola Pengeluaran Store dan mengonfirmasi pengiriman.
- `stok_awal.import`: menggunakan Import Data Awal; tampilkan hanya kepada pemegang permission.
- `barang.view`, `barang.create`, `kategori.create`, `satuan.create`: mengatur akses browse atau Quick-Add sesuai permission yang diberikan. Tidak berarti Admin Store dapat mengedit/nonaktifkan barang master.
- Konfirmasi penerimaan unit harus dilindungi permission operasional unit yang sesuai. Permission tepatnya ditetapkan pada desain RBAC lintas modul; jangan memakai permission Admin Store untuk mengotorisasi penerima unit.
- Route dan controller wajib memvalidasi permission dan gudang aktif; menyembunyikan tombol saja bukan otorisasi.

## 3. Ringkasan menu

| Menu | Fungsi |
|---|---|
| Dashboard Gudang Store | Ikhtisar stok, permintaan menunggu, alokasi/pengiriman, penerimaan hari ini, dan alert |
| Katalog Barang | Browse katalog global, cari/filter, lihat detail; read-only untuk Admin Store |
| Stok Gudang Store | Saldo fisik per barang, stok dialokasikan/tersedia, status minimum, kartu stok, dan Import Data Awal |
| Penerimaan Barang | Catat barang datang dari supplier dan buat mutasi masuk Store |
| Pengeluaran Barang | Satu menu dengan tab **Permintaan** dan **Pemindahan** untuk memenuhi permintaan unit sampai penerimaan dikonfirmasi |
| Laporan Stok Store | Saldo dan pergerakan Store serta audit siklus permintaan/pemindahan |

## 4. Dashboard Gudang Store

### Informasi yang ditampilkan

- Stok kritis dan mendekati minimum; setiap baris membuka detail/kartu stok barang.
- Permintaan `baru` dan `diproses`, termasuk jumlah, unit peminta, umur permintaan, serta sisa kuantitas.
- Pemindahan `diminta` (menunggu dikirim), `dikirim` (dalam perjalanan), dan `diterima`.
- Ringkasan jumlah penerimaan supplier dan pengiriman Store hari ini; bedakan jumlah dokumen dengan jumlah unit barang.
- Alert transaksi yang memerlukan tindak lanjut: stok tersedia tidak cukup, kiriman terlalu lama dalam perjalanan, atau penerimaan unit melaporkan selisih.
- Shortcut ke Penerimaan Barang, tab Permintaan, tab Pemindahan, Stok, dan Laporan.

### Alur

1. Sistem menentukan Gudang Store aktif dari penugasan dan sesi user.
2. Semua widget mengambil data hanya dari gudang tersebut.
3. Klik widget/alert membuka menu dengan tab dan filter yang relevan.
4. Jika data kosong, tampilkan empty state dan langkah selanjutnya; jangan menampilkan angka pengganti atau data dari gudang lain.

## 5. Katalog Barang global

### Fitur

- Daftar barang global: kode, nama, kategori, satuan dasar, stok minimum default (jika ditetapkan pada master), dan status aktif. Lokasi rak serta ambang minimum khusus gudang ditampilkan dari konfigurasi Store, bukan dianggap atribut katalog global.
- Cari berdasarkan kode/nama/kategori/deskripsi; hasil katalog tidak berarti barang tersebut memiliki stok di Store.
- Filter kategori dan status aktif/nonaktif.
- Detail barang dan metadata pembaruan terakhir, jika tersedia.
- Admin Store tidak mendapatkan aksi edit, nonaktifkan, hapus, atau tambah langsung pada halaman Katalog.
- Bila Quick-Add diizinkan, prosesnya hanya dari konteks transaksi yang mendukung; cek kemiripan dan duplikasi sebelum simpan, lalu pilih kembali barang baru ke transaksi. Quick-Add tidak boleh menjadi jalan untuk mengubah master existing.

### Alur browse

1. Buka Katalog → sistem menampilkan katalog global sesuai hak lihat.
2. Masukkan kata pencarian/filter → hasil diperbarui; pencarian minimal mengikuti aturan UI yang ditetapkan aplikasi.
3. Buka baris → lihat detail master.
4. Untuk mengetahui kuantitas Store, gunakan halaman Stok, bukan angka katalog.

## 6. Stok Gudang Store dan Kartu Stok

### Tampilan stok

- Tabel menampilkan kode/nama, kategori, lokasi rak, satuan, saldo fisik, dialokasikan, tersedia, minimum, dan status aman/peringatan/kritis.
- Pencarian/filter kategori dan status stok; pagination dan parameter filter dipertahankan saat navigasi.
- Tidak ada input saldo manual.
- Kartu Stok menampilkan mutasi kronologis: Stok Awal, penerimaan supplier, dan pemindahan keluar saat dikirim; sertakan waktu, dokumen, kuantitas, petugas, dan saldo sesudah mutasi.
- Mutasi `diminta` ditampilkan sebagai reservasi/alokasi, bukan pengeluaran fisik. Jika ditampilkan di kartu, bedakan secara visual dari mutasi yang mengubah saldo.

### Import Data Awal

Fitur hanya tersedia kepada pemegang `stok_awal.import`, umumnya selama setup awal.

1. Unduh template.
2. Unggah file dan pilih sheet/range/kolom yang tepat jika file berisi beberapa tabel.
3. Isi tanggal acuan dan catatan batch; gudang tujuan mengikuti gudang aktif terotorisasi.
4. Preview setiap baris: barang existing atau baru, kecocokan satuan, kuantitas, kondisi, hasil validasi, dan peringatan kemiripan.
5. Konfirmasi proses; pekerjaan massal dapat berjalan melalui queue.
6. Tampilkan ringkasan sukses/gagal dan unduhan baris gagal; simpan file sumber sebagai bukti.

Aturan data: `kode_barang` menjadi kunci pencocokan; barang existing tidak boleh diam-diam ditimpa; satuan harus cocok; qty bilangan nonnegatif; barang baru harus memiliki data minimum yang valid. Cegah pencatatan Stok Awal ganda untuk barang dan gudang yang sama kecuali ada proses koreksi/audit khusus yang disetujui.

## 7. Penerimaan Barang Supplier

Penerimaan supplier adalah alur masuk ke Store, terpisah dari penerimaan kiriman oleh unit.

### Data dan validasi

- Tanggal penerimaan dan nomor dokumen yang unik.
- Supplier, pemesan, pengambil/penerima barang, dan referensi PO bila ada.
- Satu dokumen dapat memuat beberapa barang; cegah barang duplikat dalam satu kelompok supplier/dokumen sesuai aturan form.
- Barang, satuan, qty fisik diterima, qty rusak/kurang, harga/nilai bila digunakan, kondisi, catatan.
- Status surat jalan dinyatakan eksplisit `ada` atau `belum_ada`; bila belum ada, alasan wajib.
- Bukti dokumen/foto mengikuti kebijakan penerimaan yang dikonfirmasi; jangan menyamakan nilai kosong dengan “tidak ada”.
- Qty rusak tidak boleh melebihi qty diterima; semua total header dihitung dari detail dan bukan sumber saldo terpisah.

### Alur penerimaan

1. Admin Store membuka tab Form Penerimaan Baru.
2. Memilih tanggal, supplier, dokumen/surat jalan, serta data pemesan/penerima.
3. Menambahkan satu atau lebih barang dan mengisi jumlah/kondisi tiap detail.
4. Sistem memvalidasi referensi barang, satuan, duplikasi, surat jalan/alasan, dan aritmetika jumlah.
5. Admin meninjau ringkasan jumlah/nilai dan menyimpan.
6. Sistem membuat dokumen penerimaan dan detail dalam satu transaksi; saldo Store bertambah hanya sebesar kuantitas layak pakai.
7. Riwayat menyimpan dokumen, petugas, waktu, dan detail untuk audit.

### Riwayat

- Cari nomor penerimaan, supplier, barang; filter supplier dan rentang tanggal.
- Buka detail penerimaan; tampilkan jumlah fisik, baik/rusak/kurang, referensi dan bukti.
- Error validasi harus mempertahankan input dan menjelaskan baris/field yang bermasalah.

## 8. Pengeluaran Barang — alur lintas Store dan unit

Pengeluaran Barang adalah **satu menu** dengan tab Permintaan dan Pemindahan. Pemindahan dapat dibuat untuk memenuhi permintaan unit atau dibuat langsung tanpa permintaan; keduanya memakai reservasi, surat jalan, dan alur pengiriman/penerimaan yang sama. Ini bukan transaksi pengeluaran stok tanpa tujuan atau transfer bebas lintas gudang.

### 8.1 Pengajuan Permintaan oleh unit

1. Admin/Recording unit memilih barang dari katalog, jumlah/satuan, kebutuhan/tanggal, catatan, dan tujuan unit.
2. Sistem memvalidasi hak user terhadap gudang peminta, tujuan Store yang tersedia, barang aktif, satuan, qty positif, dan dokumen/nomor permintaan unik.
3. Sistem menyimpan permintaan berstatus `baru` dan menampilkan nomor/tanggal serta ringkasan.
4. Permintaan muncul pada tab Permintaan Store hanya jika `gudang_tujuan_id` sama dengan Store aktif.

### 8.2 Tab Permintaan — review dan alokasi oleh Admin Store

Daftar memuat nomor, waktu, unit/peminta, catatan, ringkasan maksimal tiga barang (dengan jumlah barang sisanya), status, saldo fisik, reservasi lain, dan stok tersedia. Aksi **Buat Pemindahan** membuka tab Pemindahan dengan formulir alokasi di dalam halaman; hanya rincian dari permintaan yang dipilih dimuat di sana.

1. Admin Store membuka detail dan membandingkan sisa permintaan dengan stok tersedia.
2. Admin mengalokasikan qty per detail pada halaman Pemindahan. Server memvalidasi ulang bahwa qty tidak melebihi sisa yang diminta maupun stok tersedia setelah reservasi lain.
3. Sistem membuat pemindahan terpisah per barang/kuantitas dengan status `diminta`, nomor surat jalan, asal Store dan tujuan unit.
4. Alokasi menurunkan stok tersedia untuk alokasi lain tetapi **tidak mengurangi saldo fisik**.
5. Permintaan berubah `baru` → `diproses`. Sisa kuantitas yang belum dialokasikan tetap bisa diproses berikutnya.
6. Penolakan wajib alasan dan hanya tersedia sebelum alokasi. Kebijakan pembatalan/revisi setelah alokasi harus diaudit dan tidak boleh menghilangkan reservasi tanpa membatalkan pemindahan terkait.

### 8.3 Pemindahan langsung tanpa permintaan

Admin Store dapat membuat pemindahan langsung dari tab Pemindahan bila tidak ada permintaan unit yang mendasarinya.

1. Admin memilih gudang unit tujuan yang aktif, mencari barang aktif dari katalog yang tersedia untuk Store, dan memasukkan qty.
2. Sistem membatasi hasil pencarian dan menampilkan saldo tersedia setelah reservasi lain; pencarian tidak memuat seluruh katalog ke halaman.
3. Server memvalidasi ulang gudang Store aktif, gudang unit tujuan, barang, dan saldo tersedia di dalam transaksi.
4. Sistem membuat surat jalan dengan `permintaan_barang_id` kosong dan status `diminta`. Qty menjadi reservasi; saldo fisik belum berkurang.
5. Pemindahan tanpa permintaan ditandai jelas pada daftar dan diteruskan melalui konfirmasi kirim serta penerimaan unit yang sama dengan pemindahan dari permintaan.

### 8.4 Tab Pemindahan — persiapan dan konfirmasi kirim Store

Daftar memuat nomor surat jalan, sumber (nomor permintaan atau **Tanpa Permintaan**), unit tujuan, barang, qty alokasi/aktual dikirim, supir, bukti, waktu, status, pengirim, dan aksi sesuai status.

Daftar memakai paginasi server-side; query daftar membatasi kolom/relasi yang dimuat dan rincian permintaan tambahan diambil sesuai kebutuhan. Filter/tab tetap memakai query server dan tidak mengirim seluruh objek transaksi ke state browser.

1. Admin Store memilih pemindahan `diminta`, memeriksa barang, tujuan, dan qty.
2. Admin memasukkan qty aktual yang dikirim (positif dan tidak melebihi alokasi), supir, serta bukti kirim opsional.
3. Server mengunci transaksi yang relevan dan menghitung ulang saldo/ reservasi sebelum mengubah status untuk mencegah over-allocation/over-dispatch.
4. Jika valid, status berubah menjadi `dikirim`; catat petugas dan waktu. Pada saat inilah kuantitas aktual keluar dari saldo fisik Store.
5. Selisih antara alokasi dan qty aktual yang tidak dikirim dilepas kembali menjadi sisa permintaan yang dapat dialokasikan, kecuali kebijakan koreksi yang disetujui menetapkan cara lain.
6. Status `dikirim` berarti dalam perjalanan, bukan selesai diterima.

### 8.5 Konfirmasi penerimaan unit

1. Penerima yang berwenang pada gudang tujuan membuka detail kiriman dan membandingkan barang, qty, kondisi, serta bukti.
2. Penerima mencatat qty aktual diterima, waktu, penerima, dan catatan/bukti bila diperlukan.
3. Bila sesuai, pemindahan berubah `dikirim` → `diterima`.
4. Unit mencatat penerimaan pada saldo gudangnya melalui transaksi yang terhubung ke surat jalan; jangan menambah stok unit tanpa dokumen penerimaan.
5. Bila ada selisih/rusak, sistem mencatat discrepancy dan tidak menutupinya dengan nilai qty yang direncanakan. Kuantitas kurang tetap menjadi sisa/isu yang perlu ditindaklanjuti berdasarkan kebijakan yang disetujui.
6. Permintaan berubah `selesai` hanya setelah seluruh qty permintaan telah diterima atau diselesaikan melalui keputusan discrepancy yang tercatat. Selama masih ada qty belum terpenuhi atau kiriman belum dikonfirmasi, status tetap `diproses`.

### Status dan transisi utama

| Entitas | Status/transisi | Arti |
|---|---|---|
| Permintaan | `baru` → `diproses` | Store mulai mengalokasikan; dapat terjadi bertahap |
| Permintaan | `baru` → `ditolak` | Ditolak dengan alasan sebelum alokasi |
| Permintaan | `diproses` → `selesai` | Semua kuantitas yang diminta telah diterima/diselesaikan dan tidak ada kiriman/sisa terbuka |
| Pemindahan | `diminta` → `dikirim` | Store telah mengirim barang; saldo Store dikurangi |
| Pemindahan | `dikirim` → `diterima` | Unit mengonfirmasi barang diterima; siklus kiriman selesai |

Transisi balik, pembatalan, dan koreksi tidak boleh mengedit sejarah secara diam-diam; gunakan pembatalan/koreksi terotorisasi yang menyimpan alasan, aktor, waktu, dan dampak saldo.

### Diagram ringkas

```text
Unit buat permintaan (baru)
        ↓ terlihat pada Pengeluaran > Permintaan Store
Store review → alokasi sebagian/penuh → pemindahan dibuat (diminta)
        ↓ stok fisik tetap, kuantitas menjadi reservasi
Store konfirmasi qty aktual dikirim → pemindahan (dikirim)
        ↓ stok fisik Store berkurang
Unit cocokkan barang/qty/kondisi → konfirmasi penerimaan
        ├─ sesuai → pemindahan (diterima), penerimaan unit tercatat
        └─ selisih → discrepancy tercatat dan perlu tindak lanjut
        ↓
Permintaan selesai hanya setelah seluruh qty terselesaikan
```

## 9. Laporan dan ekspor

- **Stok akhir:** per barang pada Store aktif; saldo fisik, alokasi, tersedia, minimum, kategori; jelaskan tanggal/waktu acuannya.
- **Mutasi:** penerimaan supplier dan pemindahan keluar berdasarkan waktu transaksi; filter rentang tanggal, barang, kategori, jenis, dokumen.
- **Permintaan/pemindahan:** nomor permintaan dan surat jalan, unit, item/qty diminta-dialokasikan-dikirim-diterima, status, petugas, waktu, selisih/discrepancy.
- Ekspor Excel/PDF mengikuti filter yang tampil dan membatasi hasil ke gudang aktif; sertakan waktu cetak dan parameter filter.
- Laporan tidak boleh menghitung alokasi `diminta` sebagai pengeluaran fisik.

## 10. Audit, notifikasi, dan keadaan UI

- Catat create/update status untuk penerimaan, import, permintaan/alokasi, penolakan, pengiriman, penerimaan unit, discrepancy, pembatalan, dan koreksi.
- Audit minimum: jenis/ID dokumen, gudang asal/tujuan, user, waktu, nilai sebelum/sesudah, qty, alasan, serta tautan bukti bila ada.
- Tampilkan indikator untuk permintaan baru, alokasi menunggu dikirim, pemindahan dalam perjalanan, dan discrepancy; notifikasi otomatis (email/push) belum menjadi cakupan kecuali diputuskan.
- Definisikan empty, loading, sukses, validasi gagal, akses ditolak, item tidak ditemukan, stok berubah saat submit, upload gagal, dan queue gagal.
- Jangan memberi respons sukses bila transaksi gagal; input harus dapat dipulihkan untuk kesalahan validasi yang dapat diperbaiki.

## 11. Data dan permission yang terkait

Entitas utama yang digunakan oleh alur ini:

- Katalog Barang, Kategori, dan Satuan adalah master global bersama.
- Stok Awal per gudang/barang.
- Penerimaan Store dan detail sebagai sumber mutasi masuk Store.
- Permintaan barang dan detail yang memuat gudang peminta, gudang tujuan Store, peminta, qty diminta, qty yang sudah dialokasikan/diterima, status, dan catatan.
- Pemindahan barang yang menghubungkan permintaan (bila ada), gudang asal/tujuan, barang, `qty_diminta` (kuantitas dialokasikan), `qty_dikirim`, `qty_diterima`, status, aktor, waktu, supir, dan bukti.
- Dokumen penerimaan unit yang mereferensikan pemindahan/surat jalan agar stok tujuan hanya bertambah melalui penerimaan yang tercatat.

Nomor dokumen harus unik; relasi gudang dan barang harus tervalidasi pada server; mutasi/status diproses secara transaksional agar saldo konsisten.

## 12. Aturan penerimaan fitur dan pertanyaan validasi

- Akun Store tanpa gudang Store aktif/penugasan valid tidak dapat membaca atau mengubah transaksi Store.
- Unit hanya melihat permintaan dan pemindahan untuk gudang yang ditugaskan kepadanya.
- Stok cukup diperiksa ulang saat alokasi dan saat kirim; angka UI bukan sumber kebenaran.
- Partial fulfillment tidak menghapus sisa permintaan; partial dispatch melepas kembali qty yang tidak jadi dikirim.
- Tidak ada barang keluar saat alokasi, dan tidak ada barang masuk ke unit hanya karena Store mengirim.
- Supplier receipt menambah Store; shipment dispatch mengurangi Store; unit receipt menambah gudang tujuan.
- Catat tanpa surat jalan sebagai pilihan eksplisit dengan alasan wajib; validasi detail SOP penerimaan sebelum field final dianggap definitif.

Hal yang masih perlu disepakati sebelum implementasi tambahan:

1. Cara menyelesaikan discrepancy jumlah/kondisi saat penerimaan unit: kekurangan tetap sebagai sisa permintaan, klaim pengiriman, atau penyesuaian setelah verifikasi.
2. Apakah permintaan atau alokasi boleh dibatalkan setelah pemindahan berstatus `diminta`, siapa yang boleh, dan bagaimana pelepasan reservasi dicatat.
3. Kebijakan menerima sebagian/rusak: apakah unit dapat mengonfirmasi sebagian lalu membuat tindak lanjut untuk sisanya.
4. Bukti wajib/opsional pada penerimaan supplier dan penerimaan unit, serta masa simpan file.
5. Kebijakan tanggal acuan dan koreksi Import Data Awal setelah transaksi berjalan.
6. Kebijakan Quick-Add: role yang boleh membuat barang baru di tengah transaksi dan bagaimana barang tersebut dibagikan di katalog global.

## 13. Batas scope

Termasuk: browse katalog, stok Store, import awal terotorisasi, penerimaan supplier, permintaan unit, pemindahan dari permintaan maupun langsung tanpa permintaan ke gudang unit, konfirmasi pengiriman, konfirmasi penerimaan unit, laporan, dan jejak audit.

Belum termasuk tanpa keputusan tersendiri: opname Store, retur, pengeluaran stok tanpa tujuan/penerima yang tercatat, transfer bebas lintas gudang di luar alur Store-ke-unit, label, integrasi Mac.id, notifikasi eksternal, dan approval digital.
