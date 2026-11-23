<?php

declare(strict_types=1);

namespace App\Services\Portal;

use Illuminate\Support\Facades\Route;

final class LandingModuleCatalog
{
    /**
     * @return array<int, array{nama: string, deskripsi: string, ikon: string, route: string}>
     */
    public function activeModules(): array
    {
        return array_values(array_filter(
            $this->modules(),
            static fn (array $module): bool => Route::has($module['route']),
        ));
    }

    /**
     * @return array<int, array{nama: string, deskripsi: string, ikon: string, route: string}>
     */
    private function modules(): array
    {
        return [
            [
                'nama' => 'Master Barang',
                'deskripsi' => 'Kelola katalog barang beserta kategori, satuan, dan pendaftaran gudang.',
                'ikon' => 'database',
                'route' => 'master.barang.index',
            ],
            [
                'nama' => 'Stok Gudang Store',
                'deskripsi' => 'Lihat saldo, alokasi, kartu stok, dan status barang pada Gudang Store.',
                'ikon' => 'database',
                'route' => 'store.stok.index',
            ],
            [
                'nama' => 'Penerimaan Barang',
                'deskripsi' => 'Catat penerimaan barang pada Gudang Store beserta riwayat dan dokumennya.',
                'ikon' => 'truck-incoming',
                'route' => 'store.penerimaan.index',
            ],
            [
                'nama' => 'Permintaan Barang',
                'deskripsi' => 'Kelola permintaan barang dari unit bisnis, status proses, dan pemenuhan permintaan.',
                'ikon' => 'shopping-cart',
                'route' => 'store.permintaan.index',
            ],
            [
                'nama' => 'Pengeluaran Barang',
                'deskripsi' => 'Kelola pemindahan dan pengiriman barang keluar dari Gudang Store.',
                'ikon' => 'truck-outgoing',
                'route' => 'store.pengeluaran.index',
            ],
            [
                'nama' => 'Stok Barang',
                'deskripsi' => 'Pantau persediaan, kartu stok, import data awal, dan status stok unit bisnis.',
                'ikon' => 'database',
                'route' => 'unit-bisnis.stok.index',
            ],
            [
                'nama' => 'Stok Opname Bulanan',
                'deskripsi' => 'Catat stok opname, selisih, draf, reset, penerapan, dan berita acara.',
                'ikon' => 'clipboard-check',
                'route' => 'unit-bisnis.admin-gu.stok-opname.index',
            ],
            [
                'nama' => 'Rekapitulasi Laporan Matriks',
                'deskripsi' => 'Lihat rekap laporan pemasukan, pengeluaran, stok, dan hasil matriks gudang.',
                'ikon' => 'chart-bar',
                'route' => 'unit-bisnis.admin-gu.laporan.index',
            ],
        ];
    }
}
