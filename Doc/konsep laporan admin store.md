# PRD — Modul Laporan (Admin Gudang Store)

**Sub-dokumen dari:** Sistem Informasi Gudang & Manajemen Aset — PT Taman Hati Group
**Scope:** Spesifikasi teknis end-to-end untuk 4 jenis laporan Excel di role Admin Gudang Store — ditulis setingkat siap-implementasi untuk tim fullstack (bukan sekadar deskripsi fitur).

---

## 1. Overview

**Tujuan:** Mengganti rekap manual Excel (`RINCIAN PEMBELIAN BELANJA TUNAI TRANSFER TAMANHATI STORE`, dicatat manual per-sheet-harian) dengan laporan yang digenerate otomatis dari data transaksi sistem, tanpa kehilangan format yang sudah familiar bagi pengguna (sheet per tanggal, 1 file per bulan).

**Keputusan arsitektur:** laporan tidak disimpan sebagai arsip server dan tidak
digenerate saat transaksi. Pengguna memilih menu laporan, mereview satu sheet
tanggal melalui preview, lalu meminta download workbook bulanan. Preview dibatasi
hingga 50 baris; file export tetap memuat semua data dan semua sheet tanggal.

**4 jenis laporan dalam scope ini:**

| Kode | Nama | Struktur File |
|---|---|---|
| `stok` | Laporan Stok (Sisa + Reminder) | 1 file/bulan, 1 sheet per tanggal — saldo stok *per akhir hari itu*, bukan cuma kondisi saat ini |
| `barang_masuk` | Laporan Barang Masuk | 1 file/bulan, 1 sheet per tanggal |
| `barang_keluar` | Laporan Barang Keluar | 1 file/bulan, 1 sheet per tanggal |
| `pembelian_tunai_transfer` | Laporan Pembelian Tunai/Transfer | 1 file/bulan, 1 sheet per tanggal |

**Catatan khusus laporan Stok:** sheet hanya dibuat untuk tanggal yang **sudah lewat** (termasuk hari ini) — tidak ada sheet untuk tanggal yang belum terjadi dalam bulan berjalan, karena memang belum ada data pergerakan stok untuk tanggal tersebut.

--- 

## 2. Dependency & Stack

| Kebutuhan | Pilihan | Alasan |
|---|---|---|
| Generate Excel | `maatwebsite/excel` (wrapper resmi PhpSpreadsheet untuk Laravel) | Mendukung multi-sheet, styling, dan column formatting |
| Preview | Halaman review terpisah untuk satu tanggal/sheet yang dipilih, maksimum 50 baris | Pengguna memeriksa format dan data representatif sebelum mengunduh workbook penuh |
| Proses export | Sinkron setelah pengguna menyetujui preview | Workbook bulanan dibuat on-demand dan dikirim langsung sebagai download |
| Lokasi penyimpanan | Dialog simpan browser per file melalui File System Access API bila tersedia | Pengguna menentukan nama/lokasi lokal; browser lain memakai perilaku download default |
| Penyimpanan server | Tidak ada arsip laporan server atau tabel log | File XLSX hanya disiapkan sementara oleh library untuk respons download |

Install: `composer require maatwebsite/excel`

---

## 3. Alur Sistem End-to-End

```
1. User memilih periode, menu, tanggal sheet yang ingin direview, dan opsi restock (untuk stok).
2. Frontend meminta GET /laporan/{jenis}/preview dengan parameter periode/tanggal/filter.
3. Controller memvalidasi permission, periode, tanggal, filter, dan scope gudang; service menyajikan maksimum 50 baris.
4. User meninjau judul, bulan, header, dan contoh isi sheet yang dimuat inline di bawah kartu laporan; tanggal lain dapat dipilih melalui tab sheet dalam panel accordion.
5. User menekan Export Excel; browser meminta lokasi/nama file melalui Save File Picker saat didukung.
6. Controller memvalidasi ulang input dan Laravel Excel mengirim workbook lengkap sebagai respons.
7. Tanpa dukungan Save File Picker, browser menjalankan unduhan biasa dan menentukan lokasi sesuai pengaturannya.
8. Kesalahan validasi atau pembuatan ditampilkan pada tahapnya; tidak ada status export persisten atau polling.
```

```
┌────────┐ GET review fragment ┌────────────┐ preview rows ┌──────────────┐
│ Browser│ ──────────────────▶ │ Controller │ ───────────▶ │ Query Service│
└────────┘                      └────────────┘               └──────────────┘
    │ Review satu sheet/tanggal di accordion inline      ▲
    │ Klik Export                                        │
    └── GET export ─▶ Controller ─▶ Excel ─▶ Save File Picker / browser download
```

---

## 4. Data Model

Tidak ada tabel atau migration baru untuk laporan maupun status export. Seluruh isi
workbook diambil langsung dari tabel transaksi existing melalui `LaporanQueryService`.

---

## 5. Query Layer — Service per Jenis Laporan

Satu service, satu method per jenis laporan. Controller/Job tidak pernah query langsung — delegasi penuh ke sini (konsisten dengan aturan "jangan query berat di komponen UI/Controller").

```php
declare(strict_types=1);

namespace App\Services\Laporan;

final class LaporanQueryService
{
    /**
     * Saldo stok per barang, dihitung kumulatif SAMPAI tanggal tertentu (inklusif).
     * Hari yang sudah lewat = immutable, boleh di-cache permanen.
     * Hari ini = live, cache pendek (mis. 5 menit) saja.
     */
    public function stokPerTanggal(Gudang $gudang, string $periode, int $tanggal): Collection
    {
        $tanggalCutoff = Carbon::parse("$periode-" . str_pad((string) $tanggal, 2, '0', STR_PAD_LEFT))->endOfDay();
        $isHariIni = $tanggalCutoff->isToday();

        $cacheKey = "laporan:stok:{$gudang->id}:{$periode}:{$tanggal}";
        $ttl = $isHariIni ? now()->addMinutes(5) : null; // null = forever (hari yang sudah lewat)

        $hitung = function () use ($gudang, $tanggalCutoff) {
            $saldo = DB::table(DB::raw('(
                SELECT barang_id, tanggal_acuan AS tanggal, qty AS delta FROM stok_awal WHERE gudang_id = ?
                UNION ALL
                SELECT pbd.barang_id, pb.tanggal_penerimaan, pbd.qty
                    FROM penerimaan_barang_detail pbd
                    JOIN penerimaan_barang pb ON pb.id = pbd.penerimaan_barang_id
                    WHERE pb.gudang_id = ?
                UNION ALL
                SELECT barang_id, DATE(updated_at), qty
                    FROM transfer_barang WHERE gudang_tujuan_id = ? AND status = \'diterima\'
                UNION ALL
                SELECT barang_id, DATE(updated_at), -qty
                    FROM transfer_barang WHERE gudang_asal_id = ? AND status IN (\'dikirim\',\'diterima\')
            ) AS pergerakan'))
                ->setBindings([$gudang->id, $gudang->id, $gudang->id, $gudang->id])
                ->where('tanggal', '<=', $tanggalCutoff)
                ->groupBy('barang_id')
                ->selectRaw('barang_id, SUM(delta) AS saldo')
                ->get()
                ->keyBy('barang_id');

            return Barang::query()
                ->with(['kategori:id,nama', 'satuan:id,nama'])
                ->select(['id', 'kode_barang', 'nama_barang', 'kategori_id', 'satuan_id', 'stok_minimum'])
                ->get()
                ->map(fn (Barang $b) => (object) [
                    'barang' => $b,
                    'saldo' => (int) ($saldo[$b->id]->saldo ?? 0),
                    'di_bawah_minimum' => (int) ($saldo[$b->id]->saldo ?? 0) <= $b->stok_minimum,
                ]);
        };

        return $ttl
            ? Cache::remember($cacheKey, $ttl, $hitung)
            : Cache::rememberForever($cacheKey, $hitung);
    }

    public function jumlahHariTersediaDalamPeriode(string $periode): int
    {
        $awalBulan = Carbon::parse("$periode-01");
        $akhirBulan = $awalBulan->copy()->endOfMonth();
        // hanya sampai hari ini jika bulan berjalan masih aktif
        $batasAkhir = now()->lt($akhirBulan) ? now() : $akhirBulan;

        return $awalBulan->isSameMonth(now()) ? now()->day : $akhirBulan->day;
    }

    public function barangMasuk(Gudang $gudang, string $periode, int $tanggal): Builder
    {
        [$tahun, $bulan] = explode('-', $periode);

        return PenerimaanBarangDetail::query()
            ->join('penerimaan_barang', 'penerimaan_barang.id', '=', 'penerimaan_barang_detail.penerimaan_barang_id')
            ->where('penerimaan_barang.gudang_id', $gudang->id)
            ->whereDate('penerimaan_barang.tanggal_penerimaan', "$tahun-$bulan-" . str_pad((string)$tanggal, 2, '0', STR_PAD_LEFT))
            ->with(['barang:id,kode_barang,nama_barang', 'barang.satuan:id,nama'])
            ->select([
                'penerimaan_barang_detail.id',
                'penerimaan_barang_detail.barang_id',
                'penerimaan_barang_detail.qty',
                'penerimaan_barang_detail.harga_satuan',
                'penerimaan_barang.jenis_penerimaan',
                'penerimaan_barang.tanggal_penerimaan',
                'penerimaan_barang.diterima_oleh',
            ]);
    }

    public function barangKeluar(Gudang $gudang, string $periode, int $tanggal): Builder
    {
        [$tahun, $bulan] = explode('-', $periode);

        return TransferBarang::query()
            ->where('gudang_asal_id', $gudang->id)
            ->whereDate('updated_at', "$tahun-$bulan-" . str_pad((string)$tanggal, 2, '0', STR_PAD_LEFT)) // tanggal status 'dikirim'
            ->with(['barang:id,kode_barang,nama_barang', 'gudangTujuan:id,nama', 'dikirimOleh:id,nama'])
            ->select(['id', 'barang_id', 'qty', 'gudang_tujuan_id', 'status', 'dikirim_oleh', 'updated_at']);
    }

    public function pembelianTunaiTransfer(Gudang $gudang, string $periode, int $tanggal): Builder
    {
        return $this->barangMasuk($gudang, $periode, $tanggal)
            ->where('penerimaan_barang.jenis_penerimaan', 'pembelian_mendadak');
    }
}
```

**Catatan performa:** setiap query pakai `select()` eksplisit (bukan `SELECT *`) dan `with()` eager load — sesuai guardrail Section 16 yang sudah dikunci. Untuk laporan bulanan (30+ query per sheet), query dijalankan **per tanggal saat sheet itu dirender** oleh Laravel Excel (lihat Section 6), bukan menarik semua data sebulan ke memori sekaligus.

---

## 6. Excel Generation Layer

### 6.1 Export Class Multi-Sheet (Barang Masuk — pola yang sama untuk Keluar & Pembelian)

```php
declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class BarangMasukExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode, // "2026-09"
    ) {}

    public function sheets(): array
    {
        $jumlahHari = Carbon::parse($this->periode . '-01')->daysInMonth;

        return collect(range(1, $jumlahHari))
            ->map(fn (int $tanggal) => new BarangMasukHarianSheet($this->gudang, $this->periode, $tanggal))
            ->all();
    }
}
```

### 6.2 Sheet per Tanggal

Setiap sheet memakai nama tab berupa tanggal (`1`, `2`, dan seterusnya). Isi sheet
diawali judul laporan pada baris pertama, nama bulan dan tahun pada baris kedua,
kemudian header kolom tabel pada baris ketiga dan data mulai baris keempat.

```php
declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{
    FromQuery, WithTitle, WithHeadings, WithMapping,
    WithStyles, WithColumnWidths, ShouldAutoSize
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment};

final class BarangMasukHarianSheet implements FromQuery, WithTitle, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
        private readonly int $tanggal,
    ) {}

    public function title(): string
    {
        return (string) $this->tanggal; // nama tab = "1", "2", ... — persis pola Excel asli
    }

    public function query()
    {
        return app(LaporanQueryService::class)->barangMasuk($this->gudang, $this->periode, $this->tanggal);
    }

    public function headings(): array
    {
        return ['No', 'Kode Barang', 'Nama Barang', 'Qty', 'Satuan', 'Sumber', 'Harga', 'Diterima Oleh'];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->barang->kode_barang,
            $row->barang->nama_barang,
            $row->qty,
            $row->barang->satuan->nama,
            match ($row->jenis_penerimaan) {
                'supplier' => 'Supplier',
                'pembelian_mendadak' => 'Pembelian Mendadak',
            },
            $row->harga_satuan,
            $row->diterimaOleh->nama ?? '-',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 14, 'C' => 32, 'D' => 10, 'E' => 10, 'F' => 18, 'G' => 14, 'H' => 18];
    }

    public function styles(Worksheet $sheet): array
    {
        // Header: bold putih, background brand-primary, border, freeze row 1
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '145C54']], // token brand-primary
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->freezePane('A2');

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle("G2:G{$lastRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $sheet->getStyle("D2:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [];
    }
}
```

**Pola yang sama (ganti query + heading) dipakai untuk:** `BarangKeluarExport`/`...HarianSheet` dan `PembelianTunaiTransferExport`/`...HarianSheet`. Untuk `pembelian_tunai_transfer`, tambahkan baris Total di akhir sheet via event `AfterSheet` (jumlah kolom Total).

### 6.3 Laporan Stok (multi-sheet per tanggal — saldo kumulatif per akhir hari)

```php
final class StokExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
    ) {}

    public function sheets(): array
    {
        $jumlahHari = app(LaporanQueryService::class)->jumlahHariTersediaDalamPeriode($this->periode);
        // hanya sampai hari ini jika bulan berjalan masih aktif — tidak ada sheet untuk tanggal
        // yang belum terjadi

        return collect(range(1, $jumlahHari))
            ->map(fn (int $tanggal) => new StokHarianSheet($this->gudang, $this->periode, $tanggal))
            ->all();
    }
}

final class StokHarianSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
        private readonly int $tanggal,
    ) {}

    public function title(): string
    {
        return (string) $this->tanggal; // tab "1", "2", ... — konsisten dengan 3 laporan lain
    }

    public function collection(): Collection
    {
        return app(LaporanQueryService::class)->stokPerTanggal($this->gudang, $this->periode, $this->tanggal);
    }

    public function headings(): array
    {
        return ['Kode Barang', 'Nama Barang', 'Kategori', 'Saldo', 'Satuan', 'Ambang Minimum', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->barang->kode_barang,
            $row->barang->nama_barang,
            $row->barang->kategori->nama,
            $row->saldo,
            $row->barang->satuan->nama,
            $row->barang->stok_minimum,
            $row->di_bawah_minimum ? 'Perlu Restock' : 'Aman',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // header styling sama seperti sheet laporan lain (lihat 6.2)
        // tambahan: baris dengan status "Perlu Restock" diberi fill status-warning/status-critical
        // via event AfterSheet, dibandingkan terhadap kolom G hasil map()
        return [];
    }
}
```

**Toggle "Hanya yang Perlu Restock"** (dari rincian fitur sebelumnya) tetap berlaku — diterapkan sebagai filter tambahan di `collection()` sebelum sheet dirender, bukan menghilangkan baris setelah di-generate.

---

## 7. Synchronous Export

Queue/job tidak digunakan pada implementasi ini. Export dieksekusi sinkron sebagai
respons download setelah tombol Export ditekan. Konsekuensinya, request menunggu
sampai workbook selesai digenerate; query dan export tetap harus menggunakan pola
chunk/eager-load/cache yang sesuai untuk mengendalikan penggunaan memori dan waktu.
Sebelum request ini, pengguna dapat mereview satu sheet per tanggal melalui endpoint
preview yang hanya mengambil maksimal 50 baris.

---

## 8. Controller & Routes

```php
Route::get('/laporan/{jenis}/export', [LaporanExportController::class, 'export'])
    ->whereIn('jenis', ['stok', 'barang_masuk', 'barang_keluar', 'pembelian_tunai_transfer'])
    ->middleware(['auth', 'permission:laporan.export']);
Route::get('/laporan/{jenis}/preview', [LaporanExportController::class, 'preview'])
    ->whereIn('jenis', ['stok', 'barang_masuk', 'barang_keluar', 'pembelian_tunai_transfer'])
    ->middleware(['auth', 'permission:laporan.export']);
```

```php
final class LaporanExportController extends Controller
{
    public function export(LaporanExportRequest $request, string $jenis): BinaryFileResponse
    {
        $data = $request->validated();
        $gudang = $this->resolveStoreGudang($request);
        $export = $this->makeExport($jenis, $gudang, $data);

        return Excel::download($export, "laporan-{$jenis}-{$data['periode']}.xlsx");
    }
}
```

---

## 9. Frontend (Blade + Alpine.js)

Halaman menu menampilkan satu pemilih bulan dan empat kartu laporan yang seluruh
areanya dapat diklik. Klik kartu membuka panel accordion tepat di bawah kartu itu
tanpa navigasi halaman atau modal. Panel menampilkan judul dan bulan sesuai
workbook, tab tanggal/sheet, header, serta sampai 50 baris data aktual untuk sheet
terpilih. Periode maupun tanggal dapat diubah sebelum export. Jika endpoint preview
dibuka langsung sebagai navigasi halaman, pengguna diarahkan kembali ke menu laporan
dengan accordion, periode, dan tanggal yang diminta tetap terbuka.

Tombol Export pada halaman review mengunduh workbook bulanan penuh. Untuk File
System Access API, handler membuka `showSaveFilePicker()` langsung dari aksi
menggunakan nama seperti `laporan-barang_masuk-2026-10-{gudang_id}.xlsx`, lalu
mengambil respons XLSX dan menulisnya ke file yang dipilih pengguna. Jika API tidak
tersedia, browser mengunduh ke lokasi yang diatur pengguna di browser.

---

## 10. Keamanan & Scoping

- `permission:laporan.export` pada route export; validasi Form Request tetap berjalan sebelum query.
- Endpoint preview juga memerlukan permission yang sama dan validasi parameter periode/tanggal/filter.
- Gudang laporan diambil melalui resolver gudang aktif yang hanya mengizinkan gudang aktif yang ditugaskan ke user (kecuali Super Admin).
- File XLSX dihasilkan dalam respons request terotorisasi, tidak disimpan ke disk publik atau diarsipkan oleh aplikasi.

---

## 11. Performance Checklist (Definition of Done khusus modul ini)

- [ ] Tidak ada query di dalam loop `map()` Export class — semua data ditarik via `FromQuery`/`FromCollection` dengan eager load sebelumnya
- [ ] `stokSaatIni()` pada laporan Stok diambil dari cache Redis, bukan hitung ulang agregat per baris
- [ ] Waktu respons dan penggunaan memori export sinkron diuji dengan data volume tinggi dan workbook hingga 30 sheet
- [ ] Preview hanya mengambil satu tanggal/sheet dan maksimum 50 baris; file final mencakup data penuh
- [ ] Export class pakai `FromQuery` (bukan `FromCollection` untuk dataset besar) agar Laravel Excel bisa chunk-read dari database, tidak menarik semua baris ke memori sekaligus
- [ ] Saldo stok per tanggal untuk **hari yang sudah lewat** di-cache permanen (`Cache::rememberForever`) — tidak dihitung ulang setiap kali laporan bulan yang sama di-export lagi; hanya saldo "hari ini" yang cache-nya pendek (±5 menit)
- [ ] Query gabungan pergerakan stok (`UNION ALL` 4 sumber) diuji dengan `EXPLAIN ANALYZE` — pastikan kolom `gudang_id`, `tanggal_penerimaan`, dan `status`+`updated_at` pada `transfer_barang` sudah berindex sebelum go-live

---

## 12. Open Questions

- Apakah laporan `barang_keluar` perlu menyertakan status "diterima" (dari sisi gudang tujuan), atau cukup mencatat tanggal "dikirim" dari sisi Gudang Store saja?
- Apakah export perlu kembali menggunakan queue/arsip bila volume data membuat respons sinkron terlalu lama?
- Apakah dibutuhkan laporan gabungan lintas bulan (mis. "Q3 2026"), atau selalu per-bulan seperti pola Excel asli?
- **Baru:** untuk laporan Stok per tanggal, sheet yang belum terjadi (sisa hari di bulan berjalan) **tidak dibuat sama sekali** — apakah ini sesuai ekspektasi, atau lebih disukai tetap ada 30 tab kosong supaya strukturnya selalu konsisten?
- **Baru:** `stok_minimum` yang dipakai untuk menandai "Perlu Restock" per tanggal memakai **nilai ambang batas saat ini** (bukan nilai yang berlaku pada tanggal tersebut secara historis) — apakah ini batasan yang bisa diterima, atau butuh tracking histori perubahan `stok_minimum` dari waktu ke waktu?