<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class WelcomeController extends Controller
{
    /**
     * Tampilkan halaman welcome/landing publik SIGMA (Sistem Informasi Gudang & Manajemen Aset).
     */
    public function __invoke(): View
    {
        $appName = (string) config('app.name', 'SIGMA');
        $version = (string) config('app.version', app()->version());

        $modules = [
            [
                'nama' => 'Master Data & Katalog',
                'deskripsi' => 'Pengelolaan data barang, kategori, satuan global, dan lokasi gudang terisolasi per unit.',
                'ikon' => 'database',
            ],
            [
                'nama' => 'Stok Opname & Selisih',
                'deskripsi' => 'Pencatatan opname fisik harian/bulanan dengan kalkulasi selisih otomatis dan Berita Acara Kehilangan.',
                'ikon' => 'clipboard-check',
            ],
            [
                'nama' => 'Penerimaan Barang',
                'deskripsi' => 'Pencocokan PO, unggah foto bukti surat jalan, serta input penerimaan tanpa surat jalan.',
                'ikon' => 'truck-incoming',
            ],
            [
                'nama' => 'Pengeluaran & BPP',
                'deskripsi' => 'Pengeluaran barang produksi atau penggunaan internal dengan lebih terpantau.',
                'ikon' => 'truck-outgoing',
            ],
            [
                'nama' => 'Pencetakan Label Produk',
                'deskripsi' => 'Cetak label otomatis dengan nomor seri, kode area, dan masa kedaluwarsa.',
                'ikon' => 'tag',
            ],
            [
                'nama' => 'Retur Barang',
                'deskripsi' => 'Catat retur bahan produksi agar stok kembali akurat.',
                'ikon' => 'return',
            ],
            [
                'nama' => 'Pengadaan & Permintaan (PR)',
                'deskripsi' => 'Buat draft permintaan bahan dan catat referensi PO terintegrasi.',
                'ikon' => 'shopping-cart',
            ],
            [
                'nama' => 'Laporan & Audit Trail',
                'deskripsi' => 'Pantau stok, pengingat minimum dan kedaluwarsa, serta audit aktivitas.',
                'ikon' => 'chart-bar',
            ],
        ];

        return view('welcome', compact('appName', 'version', 'modules'));
    }
}
