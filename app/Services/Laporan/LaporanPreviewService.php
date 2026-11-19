<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Exports\Sheets\LaporanTransaksiHarianSheet;
use App\Exports\Sheets\StokHarianSheet;
use App\Models\Gudang;

final class LaporanPreviewService
{
    private const BATAS_PREVIEW = 50;

    /**
     * @return array{
     *     judul: string,
     *     subjudul: string,
     *     kolom: array<int, string>,
     *     baris: array<int, array<int, mixed>>,
     *     jumlah_data: int,
     *     batas_preview: int
     * }
     */
    public function preview(
        Gudang $gudang,
        string $jenis,
        string $periode,
        int $tanggal,
        bool $hanyaPerluRestock,
        LaporanQueryService $queryService,
    ): array {
        $sheet = match ($jenis) {
            'stok' => new StokHarianSheet($gudang, $periode, $tanggal, $hanyaPerluRestock),
            'barang_masuk' => new LaporanTransaksiHarianSheet($gudang, $periode, $tanggal, 'barang_masuk'),
            'barang_keluar' => new LaporanTransaksiHarianSheet($gudang, $periode, $tanggal, 'barang_keluar'),
            'pembelian_tunai_transfer' => new LaporanTransaksiHarianSheet($gudang, $periode, $tanggal, 'pembelian_tunai_transfer'),
        };

        if ($sheet instanceof StokHarianSheet) {
            $stokPreview = $queryService->stokPerTanggalPreview(
                $gudang,
                $periode,
                $tanggal,
                $hanyaPerluRestock,
                self::BATAS_PREVIEW,
            );
            $jumlahData = $stokPreview['jumlah_data'];
            $baris = collect($stokPreview['baris'])
                ->map(fn (mixed $row): array => $sheet->map($row))
                ->all();
            $judul = $sheet->headings()[0][0];
            $subjudul = $sheet->headings()[1][0];
            $kolom = $sheet->headings()[2];
        } else {
            $query = $sheet->query();
            $jumlahData = (clone $query)->count();
            $baris = $query
                ->limit(self::BATAS_PREVIEW)
                ->get()
                ->map(fn (mixed $row): array => $sheet->map($row))
                ->all();
            $judul = $sheet->headings()[0][0];
            $subjudul = $sheet->headings()[1][0];
            $kolom = $sheet->headings()[2];
        }

        return [
            'judul' => $judul,
            'subjudul' => $subjudul,
            'kolom' => $kolom,
            'baris' => $baris,
            'jumlah_data' => $jumlahData,
            'batas_preview' => self::BATAS_PREVIEW,
        ];
    }
}
