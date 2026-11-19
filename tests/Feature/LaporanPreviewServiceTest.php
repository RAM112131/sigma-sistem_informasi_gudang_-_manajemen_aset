<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\StokAwal;
use App\Services\Laporan\LaporanPreviewService;
use App\Services\Laporan\LaporanQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LaporanPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_preview_returns_at_most_fifty_rows_and_preserves_total_counts(): void
    {
        $gudang = Gudang::create([
            'kode' => 'STORE-PREVIEW',
            'nama' => 'Gudang Preview',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);

        foreach (range(1, 60) as $number) {
            $barang = Barang::create([
                'gudang_id' => $gudang->id,
                'kode' => sprintf('ITEM-%03d', $number),
                'nama' => sprintf('Barang %03d', $number),
                'minimum_stok' => 5,
                'is_aktif' => true,
            ]);

            if ($number === 1) {
                StokAwal::create([
                    'gudang_id' => $gudang->id,
                    'barang_id' => $barang->id,
                    'qty' => 10,
                    'tanggal_acuan' => '2026-01-01',
                    'sumber' => 'manual',
                ]);
            }
        }

        $previewService = app(LaporanPreviewService::class);
        $queryService = app(LaporanQueryService::class);

        $semuaStok = $previewService->preview($gudang, 'stok', '2026-10', 1, false, $queryService);
        $perluRestock = $previewService->preview($gudang, 'stok', '2026-10', 1, true, $queryService);

        $this->assertSame(60, $semuaStok['jumlah_data']);
        $this->assertCount(50, $semuaStok['baris']);
        $this->assertSame(59, $perluRestock['jumlah_data']);
        $this->assertCount(50, $perluRestock['baris']);
        $this->assertSame('ITEM-002', $perluRestock['baris'][0][0]);
    }
}
