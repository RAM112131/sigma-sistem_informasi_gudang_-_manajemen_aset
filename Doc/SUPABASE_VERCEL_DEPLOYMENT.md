# Panduan Integrasi SIGMA: Laravel + Supabase + Vercel

Panduan ini memakai struktur proyek saat ini: Laravel 13.25, PHP `^8.3`, aplikasi Blade, PostgreSQL, dan entry point Vercel `api/index.php`. Tujuannya bukan mengaktifkan semua fitur setiap platform, tetapi memilih fitur yang tepat dan menghindari dua sistem yang mengerjakan hal yang sama.

## 1. Arsitektur dan pembagian tanggung jawab

### Peran yang disarankan untuk SIGMA

| Bagian | Platform yang menjadi sumber utama | Alasannya |
|---|---|---|
| Halaman Blade, route, validasi, workflow gudang | Laravel | Aplikasi ini sudah berupa monolith Laravel server-rendered; aturan bisnis dan otorisasi ada di Laravel. |
| Login, session, role, permission | Laravel Auth + Spatie Permission | SIGMA sudah memiliki alur login Laravel dan role/permission. Hindari membuat login kedua tanpa rencana migrasi identitas. |
| Data persisten | Supabase PostgreSQL | Laravel memakai driver `pgsql` PDO; Supabase menyediakan database PostgreSQL terkelola. |
| Asset hasil Vite | Vercel CDN atau hosting Laravel | Vercel dapat menyajikan asset build, tetapi Blade tetap membutuhkan server Laravel. |
| Pencarian semantik | Laravel AI SDK + provider embedding + pgvector di Supabase (opsional) | Laravel mengirim teks ke provider embedding yang dikonfigurasi, lalu pgvector menyimpan embedding 1536 dimensi. Supabase bukan pengganti AI provider; API key provider tetap rahasia di server. |
| Upload file permanen (jika fitur ini dipakai) | Supabase Storage atau object storage lain | Filesystem Vercel tidak persisten antar-invocation. |
| Queue worker yang selalu berjalan | Host Laravel/worker terpisah | HTTP Function Vercel bukan proses worker Laravel yang hidup terus-menerus. |

### Pilih bentuk deployment

**Pilihan A — lebih cocok untuk produksi SIGMA saat ini:** host Laravel pada platform yang memang mendukung proses Laravel/PHP (misalnya Laravel Cloud atau host PHP terkelola), lalu hubungkan Laravel ke Supabase PostgreSQL. Aplikasi ini memakai Blade dan route Laravel, jadi tidak perlu memisahkan frontend hanya demi memakai Vercel. Vercel dapat ditambahkan kemudian jika ada frontend terpisah yang memang membutuhkan CDN/deployment Vercel.

```text
Browser -> Laravel host (Blade + PHP) -> Supabase PostgreSQL
                         └-----------> Supabase Storage (opsional)
```

**Pilihan B — mempertahankan deployment repo yang sekarang:** request masuk ke Vercel, lalu rewrite menuju `api/index.php`, yang menjalankan Laravel melalui runtime PHP pihak ketiga.

```text
Browser -> Vercel CDN / PHP Function -> Laravel -> Supabase PostgreSQL
```

Pilihan B cocok hanya jika runtime PHP tersebut berhasil dibangun dan diuji di akun/proyek Vercel Anda. Runtime di `vercel.json` adalah runtime komunitas, bukan runtime PHP resmi Vercel, dan upstream-nya telah diarsipkan. Karena itu pilihan ini memiliki risiko pemeliharaan yang tidak dimiliki pilihan A.

### Fitur Supabase: gunakan sesuai kebutuhan

| Fitur Supabase | Pakai untuk SIGMA? | Catatan implementasi |
|---|---|---|
| **Database (PostgreSQL)** | Ya | Laravel terhubung ke database lewat PDO; ini integrasi utama. Password DB hanya berada di backend. |
| **Supabase Auth** | Tidak untuk konfigurasi awal | Login dan session SIGMA tetap dikelola Laravel. Supabase Auth memakai JWT; menggantinya perlu menghubungkan identitas/JWT ke middleware dan role Laravel. Jangan mengaktifkan dua alur login sekaligus tanpa desain migrasi. |
| **Data API / Supabase client di browser** | Tidak untuk workflow stok | Penulisan data lewat Laravel mempertahankan validasi, otorisasi, transaksi, dan audit yang sudah ada. Jika kelak browser mengakses Data API, aktifkan RLS dan buat policy sebelum mengekspos tabel; jangan pernah menaruh `service_role` atau kredensial database di frontend. |
| **Storage** | Opsional | Gunakan untuk berkas yang perlu bertahan setelah request selesai. Buat bucket dan policy yang sesuai. Supabase S3 access key memberi akses luas dan melewati RLS, jadi simpan hanya di backend. |
| **Realtime** | Opsional | Gunakan hanya jika ada kebutuhan live update stok/status. Tidak dibutuhkan agar CRUD Laravel bekerja. |
| **Edge Functions** | Tidak untuk menjalankan Laravel | Edge Functions cocok untuk fungsi ringan berbasis HTTP/TypeScript di sisi Supabase; bukan pengganti controller atau queue worker Laravel. |
| **pgvector** | Opsional, sudah didukung alur pencarian aplikasi | Aktifkan sebelum migration jika ingin pencarian semantik; dimensinya harus cocok dengan model embedding 1536. |

Laravel memakai akun database server-side yang dipercaya. Hak akses pengguna aplikasi tetap harus dicek oleh middleware/policy/permission Laravel; RLS Supabase tidak otomatis menggantikan pemeriksaan otorisasi Laravel, terutama ketika koneksi menggunakan role database pemilik.

#### Kapan mengaktifkan fitur Supabase lainnya?

- **Auth:** tetap gunakan Laravel Auth untuk SIGMA saat ini. Supabase Auth menggunakan JWT dan mengelola identitas tersendiri. Jika suatu hari dipilih, rencanakan perpindahan login, verifikasi token pada Laravel, penyelarasan user ID dan role, reset password, serta redirect URL. Jangan hanya menambahkan `SUPABASE_URL` dan key lalu menganggap login Laravel sudah terhubung.
- **Storage:** aktifkan hanya jika ada file bisnis yang perlu disimpan permanen. Buat bucket private untuk dokumen gudang; S3 access keys Supabase melewati RLS dan hanya boleh digunakan dari server.
- **Realtime:** aktifkan jika pengguna memang perlu melihat perubahan stok/status tanpa refresh. Tetap periksa izin pengguna di Laravel; jangan menyiarkan data bisnis sensitif ke channel publik.
- **Edge Functions:** gunakan untuk tugas kecil yang secara sengaja dijalankan di sisi Supabase. Kode aplikasi, otorisasi SIGMA, dan queue job tetap di Laravel/worker.
- **Data API dari browser:** untuk alur stok SIGMA, hindari akses langsung agar validasi dan pencatatan audit tidak dapat dilewati. Jika ada kebutuhan baca publik, expose hanya tabel/kolom yang aman, aktifkan RLS, dan tulis policy sebelum memasang client di browser.

### Fitur Vercel: apa yang cocok

- **Git deployment dan Preview** cocok untuk menguji perubahan sebelum Production. Pisahkan environment variables dan database Preview dari Production.
- **CDN/static assets** cocok untuk file hasil `npm run build`.
- **Functions** menjalankan request HTTP; jangan mengasumsikan filesystem-nya permanen atau prosesnya hidup terus.
- **Edge Functions** bukan tempat menjalankan PHP/Laravel.
- **Queue/background worker** harus dirancang terpisah. Untuk job Laravel yang sudah ada, siapkan worker di host persisten atau gunakan pendekatan antrean yang benar-benar memiliki consumer.

Laravel terhubung langsung ke PostgreSQL Supabase menggunakan PDO. Pada arsitektur ini, Laravel tidak memerlukan Supabase `anon` key atau `service_role` key untuk query database.

## 2. Peringatan sebelum mulai

1. **Rotasi kredensial yang pernah masuk ke repository.** Konfigurasi Vercel proyek sebelumnya menyimpan kredensial database dan `APP_KEY` langsung di `vercel.json`. Anggap nilai lama telah terekspos: ganti password database lama di Filess.io, buat password database baru di Supabase, dan ganti `APP_KEY`. Mengganti `APP_KEY` akan membuat cookie/sesi lama tidak berlaku; pengguna perlu login kembali.
2. **Hapus konfigurasi rahasia dari `vercel.json`.** Simpan rahasia di Vercel Project Settings, bukan di file, commit, log, atau screenshot. Nilai yang sudah pernah masuk Git dapat tetap ada di riwayat commit; rotasi rahasia tetap wajib meskipun file kemudian dihapus.
3. **Runtime PHP Vercel perlu perhatian khusus.** Proyek menggunakan runtime pihak ketiga `vercel-php@0.8.0`. Repository upstream runtime tersebut telah diarsipkan pada 29 Oktober 2026 dan PHP tidak tercantum sebagai runtime resmi Vercel. Pertahankan konfigurasi yang ada hanya jika deployment aktual masih berhasil; untuk layanan produksi jangka panjang, pertimbangkan menjalankan Laravel pada host yang mendukung PHP secara resmi dan memakai Vercel untuk frontend.
4. **Jangan arahkan test ke database produksi.** `phpunit.xml` proyek memakai database test PostgreSQL tersendiri, yaitu `db_wms_test`.

## 3. Buat proyek Supabase

1. Buat proyek PostgreSQL baru di [Supabase Dashboard](https://supabase.com/dashboard).
2. Pilih region yang sedekat mungkin dengan region Function Vercel.
3. Simpan password database di password manager.
4. Buka **Connect** di dashboard. Salin host, port, nama database, dan username dari connection method yang dipilih. Jangan menebak host atau username pooler; username pooler biasanya berbeda dari direct connection.
5. Untuk koneksi dari Vercel, pilih salah satu:
   - **Shared Pooler — Session mode**: kompatibel dengan konfigurasi PostgreSQL proyek yang menjalankan `SET search_path` saat membuka koneksi; memakai port `5432`.
   - **Shared Pooler — Transaction mode**: direkomendasikan Supabase untuk function/serverless yang membuat banyak koneksi singkat; umumnya port `6543`. Namun mode ini tidak mempertahankan session state antar-transaksi. Proyek ini mengatur `search_path`, sehingga gunakan mode ini hanya setelah menguji konfigurasi dan kebutuhan pgvector dengan seksama.

   Selalu gunakan nilai yang diberikan tombol **Connect**. Host pooler tidak boleh disusun manual dari region.
   Session mode lebih mudah untuk kompatibilitas awal, tetapi koneksi serverless yang banyak dapat mencapai limit pool. Pantau koneksi Supabase dan pertimbangkan Transaction mode setelah penyesuaian serta pengujian.
6. Sebelum production, periksa batas koneksi, backup/PITR, dan biaya sesuai plan Supabase yang sedang berlaku. Jangan mengandalkan nilai limit dari tutorial lama karena bergantung pada plan/compute.
7. Untuk Preview, gunakan proyek database staging terpisah (atau Supabase Branching jika tersedia di plan dan alur kerja Anda). Jangan mengarahkan Preview ke database Production.

## 4. Siapkan PostgreSQL dan pgvector

Seluruh tabel aplikasi menggunakan schema `public`. Aplikasi memakai pgvector secara opsional untuk pencarian semantik; pencarian teks biasa tetap tersedia tanpa ekstensi tersebut.

Jika ingin memakai pencarian semantik, aktifkan ekstensi **vector** melalui **Database → Extensions** sebelum migration pertama. Periksa schema ekstensi dengan query ini di Supabase SQL Editor:

```sql
select extname, extnamespace::regnamespace as installed_schema
from pg_extension
where extname = 'vector';
```

Migration proyek membuat kolom bertipe `vector(1536)`. Jika ekstensi belum tersedia saat migration berjalan, migration dapat selesai dengan fallback teks. Karena migration tersebut kemudian tercatat selesai, mengaktifkan ekstensi belakangan tidak otomatis menjalankan ulang pembuatan kolom. Buat migration baru untuk menambah kolom yang belum ada dan lakukan reindex; **jangan rollback atau reset migration produksi** hanya untuk mengulangnya.

## 5. Pindahkan data dari Filess.io

`php artisan migrate` hanya membuat/memperbarui struktur tabel; perintah itu **tidak memindahkan isi database lama**. Jika data lama harus dipertahankan, gunakan backup PostgreSQL.

### 5.1 Buat backup sebelum cutover

Pasang PostgreSQL command-line tools di komputer Windows, lalu buka PowerShell di luar folder repository (misalnya `%TEMP%`). Minta izin maintenance/read-only untuk aplikasi lama agar tidak ada transaksi baru selama ekspor dan perpindahan data.

Contoh ekspor schema aplikasi `public` ke format backup PostgreSQL. Ganti placeholder host, username, port, dan nama database dengan nilai dari Filess.io. Opsi `--password` meminta password secara interaktif; jangan menaruh password pada command line.

```powershell
$backup = Join-Path $env:TEMP 'sigma-public.dump'

pg_dump `
  --host=SOURCE_HOST `
  --port=SOURCE_PORT `
  --username=SOURCE_USER `
  --dbname=SOURCE_DATABASE `
  --format=custom `
  --no-owner `
  --no-acl `
  --schema=public `
  --password `
  --file="$backup"
```

Pastikan command selesai dengan kode sukses dan file backup berukuran masuk akal. Simpan file tersebut dengan aman; backup berisi data aplikasi.

### 5.2 Pulihkan backup ke Supabase

Pulihkan ke proyek Supabase baru yang masih kosong. Ambil host, port, dan username dari **Connect**; untuk restore dari jaringan IPv4, shared session pooler biasanya dapat dijangkau. Masukkan nama database sesuai dashboard (umumnya `postgres`).

```powershell
$backup = Join-Path $env:TEMP 'sigma-public.dump'

pg_restore `
  --host=SUPABASE_HOST `
  --port=SUPABASE_PORT `
  --username=SUPABASE_USER `
  --dbname=SUPABASE_DATABASE `
  --no-owner `
  --no-acl `
  --password `
  --verbose `
  "$backup"
```

Restore ke database kosong agar tidak menimpa tabel yang sudah berisi data. Periksa output sampai selesai; jangan menganggap restore berhasil jika ada error. Jika ekstensi `vector` menimbulkan konflik, aktifkan/periksa ekstensi di Supabase sebelum restore dan selesaikan error ekstensi secara spesifik sebelum melanjutkan.

Jika tidak perlu mempertahankan data, lewati backup/restore dan buat tabel baru dengan menjalankan migration proyek pada database Supabase.

### 5.3 Verifikasi dan cutover

Sebelum mengarahkan pengguna ke Supabase:

1. Bandingkan jumlah baris tabel penting di sumber dan tujuan.
2. Uji login, gudang, stok, penerimaan/pengeluaran, dan laporan.
3. Setelah verifikasi, hentikan write ke database lama dan lakukan ekspor akhir jika ada perubahan sejak backup awal.
4. Alihkan konfigurasi aplikasi ke Supabase.
5. Pertahankan database lama dalam keadaan read-only sampai aplikasi produksi terverifikasi. Jangan menghapus database lama atau backup sebelum periode validasi selesai.

## 6. Uji koneksi Laravel dari komputer lokal

Perbarui `.env` lokal dengan kredensial Supabase. Contoh berikut hanya template; jangan commit nilai password:

```dotenv
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=HOST_DARI_SUPABASE_CONNECT
DB_PORT=PORT_DARI_SUPABASE_CONNECT
DB_DATABASE=DATABASE_DARI_SUPABASE_CONNECT
DB_USERNAME=USERNAME_DARI_SUPABASE_CONNECT
DB_PASSWORD=PASSWORD_DATABASE_SUPABASE
DB_SSLMODE=require
DB_SCHEMA=public
DB_PERSISTENT=false
DB_TIMEOUT=10

SESSION_DRIVER=file
CACHE_STORE=array
QUEUE_CONNECTION=sync
```

Nilai session/cache/queue di atas untuk smoke test lokal sederhana. Konfigurasi lokal proyek memakai session file pada `APP_ENV=local`; gunakan `sync` agar queued job tidak menunggu worker selama tes koneksi. Jangan menyalin nilai ini begitu saja ke production.

Jika memakai ekstensi `vector` yang terpasang dalam schema `extensions` dan koneksi Session mode, gunakan `DB_SCHEMA=public,extensions`. Untuk Transaction mode, jangan mengandalkan perubahan `search_path` berbasis session; schema ekstensi harus diuji secara khusus.

Connection PostgreSQL di `config/database.php` sudah membaca variabel koneksi di atas, termasuk `DB_SCHEMA`, `DB_SSLMODE`, `DB_PERSISTENT`, dan `DB_TIMEOUT`. Untuk Supabase Transaction mode, PDO harus menggunakan emulated prepares karena pooler bersama mode tersebut tidak mendukung prepared statements. Tambahkan opsi berikut ke array `options` koneksi `pgsql` yang sudah ada:

```php
PDO::ATTR_EMULATE_PREPARES => filter_var(
    env('DB_EMULATE_PREPARES', false),
    FILTER_VALIDATE_BOOLEAN,
),
```

Lalu atur `DB_EMULATE_PREPARES=true` hanya pada environment yang memakai Transaction mode. Session mode mendukung prepared statements dan tidak memerlukan perubahan perilaku tersebut.

Tes koneksi dan migration dari komputer lokal:

```powershell
php artisan optimize:clear
php artisan migrate:status
php artisan migrate
```

Periksa output command dan pastikan semua migration sukses. Jangan menjalankan suite PHPUnit terhadap Supabase produksi; gunakan database test `db_wms_test` sesuai `phpunit.xml`.

## 7. Konfigurasi Vercel

### 7.1 Hubungkan repository

1. Import repository ke Vercel dan pilih root repository SIGMA sebagai **Root Directory**.
2. Jika Vercel meminta Framework Preset, gunakan preset yang tidak mengubah Laravel menjadi aplikasi frontend lain (umumnya **Other** untuk konfigurasi custom ini).
3. Pertahankan konfigurasi build yang sudah digunakan proyek: `npm run build && node vercel-build.mjs`. Build membuat asset Vite di `public/build`, lalu menyalinnya ke `vercel-static`.
4. Fungsi `api/index.php` meneruskan request ke bootstrap Laravel. Pastikan Vercel Build Logs menunjukkan dependency Composer terpasang, fungsi PHP terdeteksi, dan build asset selesai.
5. Atur region Function sedekat mungkin dengan region Supabase untuk mengurangi latency database; pilihan region bisa bergantung pada plan Vercel.
6. Atur production branch (biasanya `main`). Push branch lain untuk membuat Preview; uji Preview sebelum promosi ke Production.

Vercel Git integration yang membuat Preview/Production deployment; folder `.github` atau workflow GitHub Actions tidak dibutuhkan untuk alur ini. Alternatif deployment manual adalah Vercel CLI (`npx vercel` untuk Preview dan `npx vercel --prod` untuk Production) setelah variable dan target DB benar.

Pengaturan ini hanya menjelaskan konfigurasi saat ini. Karena runtime PHP yang dipakai bukan runtime resmi dan upstream-nya diarsipkan, gagalnya deteksi/build PHP adalah alasan untuk memilih Pilihan A pada bagian 1, bukan menghapus pengecekan atau menyimpan rahasia di file.

### 7.2 Hapus rahasia dari file konfigurasi

Di Vercel, buka **Project → Settings → Environment Variables**. Tambahkan variabel untuk environment yang dibutuhkan (**Production**, dan **Preview** hanya bila diperlukan). Perubahan variabel baru berlaku pada deployment baru.

Setelah memindahkan semua variabel, hapus blok `env` dari `vercel.json`. Pertahankan konfigurasi build/function/rewrite yang sudah dipakai proyek, misalnya:

```json
{
  "$schema": "https://openapi.vercel.sh/vercel.json",
  "buildCommand": "npm run build && node vercel-build.mjs",
  "outputDirectory": "vercel-static",
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.8.0"
    }
  },
  "rewrites": [
    {
      "source": "/(.*)",
      "destination": "/api/index.php"
    }
  ]
}
```

Contoh ini mempertahankan runtime yang sekarang dipakai; lihat peringatan runtime di bagian 2 sebelum mengandalkannya untuk produksi.

### 7.3 Tambahkan environment variables

Daftarkan variabel server berikut di Vercel. Untuk DB, gunakan **Shared Pooler — Session mode** terlebih dahulu agar konfigurasi `search_path` proyek berfungsi. Gunakan host, port, database, dan username persis seperti yang tampil di Supabase **Connect**.

| Variable | Nilai |
|---|---|
| `APP_NAME` | `SIGMA` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Key baru yang dibuat satu kali dengan `php artisan key:generate --show`; simpan sebagai Secret |
| `APP_URL` | URL domain produksi dengan HTTPS |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Host pooler Supabase dari **Connect** |
| `DB_PORT` | Port dari **Connect**; `5432` untuk shared Session mode |
| `DB_DATABASE` | Nama database dari **Connect** |
| `DB_USERNAME` | Username dari **Connect** |
| `DB_PASSWORD` | Password database Supabase; simpan sebagai Secret |
| `DB_SSLMODE` | `require` |
| `DB_SCHEMA` | `public` |
| `DB_PERSISTENT` | `false` |
| `DB_TIMEOUT` | `10` |
| `SESSION_DRIVER` | `cookie` untuk deployment stateless sederhana |
| `SESSION_SECURE_COOKIE` | `true` untuk cookie HTTPS di Production |
| `CACHE_STORE` | `array` untuk cache sementara per proses; gunakan Redis eksternal bila cache harus dibagi antar-instance |
| `QUEUE_CONNECTION` | Set eksplisit: `sync` hanya untuk beban ringan tanpa worker; selain itu gunakan queue service dengan worker yang berjalan |
| `OPENAI_API_KEY` | Secret server-side bila pencarian semantik/provider OpenAI dipakai |
| `AI_EMBEDDINGS_PROVIDER` | Provider yang sudah didukung konfigurasi aplikasi, bila perlu override |
| `AI_EMBEDDINGS_MODEL` | Model embedding dengan dimensi 1536, bila perlu override |
| `AI_EMBEDDINGS_TIMEOUT` | Opsional; timeout provider embedding |
| `AI_EMBEDDINGS_CACHE_TTL` | Opsional; masa cache embedding |

`APP_KEY` bukan password database. Buat key baru karena key sebelumnya pernah tertanam di konfigurasi. Jangan jalankan `key:generate` setiap deployment; semua instance harus menggunakan key produksi yang sama.

Jangan masukkan kredensial database produksi ke environment **Preview**. Gunakan proyek Supabase staging. Setelah mengganti variable di Vercel, buat deployment baru—deployment lama tidak otomatis mendapat nilai variable terbaru.

Variabel yang hanya dipakai saat build, seperti `VITE_APP_NAME`, bukan tempat menyimpan secret: nilai `VITE_*` dapat masuk ke bundle browser. Jangan pernah memberi prefix `VITE_` pada password, private key, atau credential provider.

Setelah Preview berfungsi, hubungkan domain produksi melalui **Project → Settings → Domains**, ikuti instruksi DNS Vercel, lalu set `APP_URL` ke domain HTTPS tersebut. Jangan mengubah DNS sebelum deployment Preview dan domain target siap.

### 7.4 Pooler Transaction mode (opsional, setelah pengujian)

Untuk traffic yang lebih tinggi, Vercel serverless biasanya lebih cocok dengan Transaction mode. Supabase menyatakan mode ini tidak mendukung prepared statements dan tidak mempertahankan session-level state seperti `SET search_path`. Sebelum berpindah:

1. Tambahkan opsi `PDO::ATTR_EMULATE_PREPARES` di `config/database.php` seperti pada bagian 6.
2. Atur `DB_EMULATE_PREPARES=true`.
3. Ubah DB host/port/username ke nilai **Transaction pooler** dari dashboard.
4. Uji login, semua query, migration, dan pencarian semantik di Preview.
5. Periksa bahwa aplikasi tidak bergantung pada schema ekstensi yang hanya tersedia lewat `SET search_path`. Proyek ini memakai `vector` secara opsional dan referensi tipe vector tidak diberi nama schema eksplisit; jangan aktifkan mode ini untuk produksi dengan pgvector sampai jalur pencariannya berhasil diuji.

## 8. Queue, cache, session, dan file di Vercel

- **Queue:** default `config/queue.php` proyek adalah `redis`, sedangkan konfigurasi Vercel harus diatur eksplisit. `GenerateVectorEmbedding` adalah queued job. Vercel Function bukan queue worker Laravel yang terus hidup. Jangan set `QUEUE_CONNECTION=database` atau `redis` tanpa worker terpisah—job dapat menumpuk dan tidak diproses. Untuk volume kecil, `QUEUE_CONNECTION=sync` menjalankan job dalam request dan bisa menambah waktu respons/menyebabkan timeout. Untuk produksi, gunakan queue service dan worker Laravel yang berjalan di host jangka panjang.
- **Cache:** `CACHE_STORE=array` aman sebagai cache sementara, tetapi tidak dibagi antar-instance atau bertahan setelah function selesai. Gunakan Redis terkelola bila aplikasi memerlukan cache bersama/persisten.
- **Session:** driver `cookie` tidak membutuhkan penyimpanan file lokal, tetapi ukuran cookie terbatas dan cookie harus memakai HTTPS di produksi. Untuk session database, jalankan migration tabel sessions dan evaluasi beban koneksi.
- **File:** filesystem function Vercel tidak boleh dianggap penyimpanan permanen. `api/index.php` proyek menyiapkan direktori runtime di `/tmp`; isinya sementara. Simpan upload permanen di object storage, bukan disk lokal Vercel.

Vercel memiliki produk background/queue tersendiri, tetapi itu tidak otomatis menjalankan `php artisan queue:work` untuk aplikasi ini. Evaluasi dukungan runtime, consumer, retry, dan observability bila memilih layanan tersebut; worker Laravel pada host persisten adalah jalur yang lebih langsung.

### Memakai Supabase Storage (jika aplikasi perlu upload permanen)

Alur Storage ini opsional dan terpisah dari migrasi database:

1. Buat bucket di **Supabase → Storage**. Pilih private untuk dokumen bisnis; bucket public hanya untuk file yang memang boleh diakses siapa saja.
2. Jika Laravel yang mengelola upload, buat S3 access key dari **Storage → S3 Configuration**. Salin endpoint, region, Access Key ID, dan Secret Access Key dari dashboard.
3. Adapter S3 belum terpasang di dependency proyek. Jika memilih integrasi ini, tambahkan adapter resmi Flysystem yang kompatibel dengan versi Laravel/Flysystem proyek, misalnya `composer require league/flysystem-aws-s3-v3`, lalu commit perubahan `composer.json` dan `composer.lock`.
4. Konfigurasi disk `s3` yang sudah ada di `config/filesystems.php` membaca environment variables berikut. Ambil endpoint dan region persis dari S3 Configuration dashboard; URL endpoint umumnya berbentuk `https://PROJECT_REF.storage.supabase.co/storage/v1/s3`.

   ```dotenv
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=ACCESS_KEY_DARI_SUPABASE
   AWS_SECRET_ACCESS_KEY=SECRET_KEY_DARI_SUPABASE
   AWS_DEFAULT_REGION=REGION_DARI_SUPABASE
   AWS_BUCKET=NAMA_BUCKET
   AWS_ENDPOINT=ENDPOINT_DARI_SUPABASE_S3_CONFIGURATION
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```

   Tambahkan values sebagai server-side Environment Variables, bukan `VITE_*`. Uji upload/download di bucket staging sebelum memakai Production.
5. Akses private file melalui Laravel dengan authorization atau signed URL. Jangan kirim S3 secret ke Blade JavaScript/browser.

Supabase S3 access keys memberi akses luas ke bucket dan melewati RLS. Jika membutuhkan akses langsung per pengguna, gunakan mekanisme token/policy Supabase yang memang dirancang untuk user-scoped access, bukan membocorkan access key server.

## 9. Deploy dan pemeriksaan akhir

1. Pastikan variable Production tersedia di Vercel.
2. Pastikan perubahan kode/config yang diperlukan sudah di-commit tanpa `.env`, dump database, password, atau key.
3. Deploy Preview terlebih dahulu, lalu cek log Function di Vercel.
4. Jika migration belum dijalankan, jalankan dari komputer/runner tepercaya yang memakai koneksi Supabase sesuai panduan:

   ```powershell
   php artisan migrate --force
   ```

   Jangan jalankan migration otomatis pada setiap request Function.
5. Uji login, data gudang, transaksi masuk/keluar, export, dan pencarian.
6. Setelah Preview lulus, deploy Production.
7. Pantau Vercel Function logs dan Supabase **Database → Observability** untuk error koneksi dan penggunaan koneksi.

### Troubleshooting singkat

| Gejala | Yang diperiksa |
|---|---|
| `password authentication failed` | Password Supabase terbaru, username pooler yang benar, dan tidak ada spasi ekstra pada variable. |
| `tenant or user not found` | Gunakan host dan username pooler persis dari dashboard; jangan menyusun sendiri. |
| Timeout / gagal resolve host | Mode koneksi dan jaringan IPv4/IPv6; shared pooler Supabase tersedia via IPv4. |
| `prepared statement ... not supported` | Jika memakai Transaction mode, pastikan `PDO::ATTR_EMULATE_PREPARES=true`. |
| `vector type does not exist` | Aktifkan ekstensi sebelum migration, periksa schema ekstensi dan `DB_SCHEMA`; jangan rollback migration produksi. |
| Halaman error detail di produksi | Pastikan `APP_DEBUG=false` dan buat deployment baru setelah mengubah variables. |
| Job tetap pending | Pastikan ada queue worker terpisah atau gunakan `sync` hanya untuk beban ringan. |

## 10. Checklist sebelum Production

- [ ] Database Supabase baru berisi schema dan data yang sudah diverifikasi, atau migration yang dibutuhkan telah dijalankan.
- [ ] Password database lama yang pernah tersimpan di repo sudah dirotasi; `APP_KEY` lama sudah diganti.
- [ ] Tidak ada `.env`, dump SQL, password, `APP_KEY`, Supabase S3 secret, atau API key provider di Git maupun bundle `VITE_*`.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, dan `APP_URL` sesuai domain.
- [ ] Environment Preview memakai database Supabase staging, bukan Production.
- [ ] Login, permission, transaksi stok, laporan, dan export sudah diuji pada Preview.
- [ ] Queue worker tersedia untuk job asynchronous, atau konsekuensi `sync` sudah diterima.
- [ ] Upload permanen tidak disimpan di filesystem function Vercel.
- [ ] Runtime PHP Vercel aktual sudah terbukti build dan berjalan; bila tidak, pindahkan Laravel ke host PHP yang didukung.

## Referensi resmi

- [Supabase: Connect to your database](https://supabase.com/docs/guides/database/connecting-to-postgres)
- [Supabase: Connection pooling and limits](https://supabase.com/docs/guides/database/connecting-to-postgres/pooling-and-limits)
- [Supabase: pgvector](https://supabase.com/docs/guides/database/extensions/pgvector)
- [Supabase: Auth](https://supabase.com/docs/guides/auth)
- [Supabase: Row Level Security](https://supabase.com/docs/guides/database/postgres/row-level-security)
- [Supabase: Storage S3 authentication](https://supabase.com/docs/guides/storage/s3)
- [Supabase: S3 compatibility](https://supabase.com/docs/guides/storage/s3/compatibility)
- [Supabase: Realtime](https://supabase.com/docs/guides/realtime)
- [Laravel 13: Database](https://laravel.com/docs/13.x/database)
- [Vercel: Environment variables](https://vercel.com/docs/environment-variables)
- [Vercel: Deployment environments](https://vercel.com/docs/deployments/environments)
- [Vercel: Function runtimes](https://vercel.com/docs/functions/runtimes)
- [Vercel: Background workers](https://vercel.com/docs/background-workers)
- [Vercel PHP runtime upstream](https://github.com/vercel-community/php) — repository diarsipkan.
