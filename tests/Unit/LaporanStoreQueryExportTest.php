<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Gudang;
use App\Services\Laporan\LaporanQueryService;
use App\Services\Store\LaporanStoreService;
use Illuminate\Database\Query\Builder;
use Tests\TestCase;

final class LaporanStoreQueryExportTest extends TestCase
{
    public function test_stock_export_is_query_backed_and_preserves_report_columns(): void
    {
        $store = $this->store(17);

        [$query, $headings] = app(LaporanStoreService::class)
            ->exportQueryData($store, 'stok_akhir', 9, '2026-09-01', '2026-09-30');

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertSame([
            'Kode',
            'Nama Barang',
            'Kategori',
            'Total Masuk',
            'Total Keluar',
            'Stok Akhir',
            'Minimum',
            'Satuan',
        ], $headings);
        $this->assertContains(17, $query->getBindings());
        $this->assertContains(9, $query->getBindings());
        $this->assertStringContainsString('GREATEST(0, COALESCE(balance.awal', $query->toSql());
    }

    public function test_mutation_export_is_query_backed_and_scoped_by_store_and_dates(): void
    {
        [$query, $headings] = app(LaporanStoreService::class)
            ->exportQueryData($this->store(17), 'mutasi', null, '2026-09-01', '2026-09-30');

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertSame('No. Dokumen', $headings[1]);
        $this->assertContains(17, $query->getBindings());
        $this->assertContains('2026-09-01 00:00:00', $query->getBindings());
        $this->assertContains('2026-09-30 23:59:59', $query->getBindings());
        $this->assertStringContainsString('UNION ALL', strtoupper($query->toSql()));
    }

    public function test_distribution_export_is_query_backed_and_scoped_by_store_and_dates(): void
    {
        [$query, $headings] = app(LaporanStoreService::class)
            ->exportQueryData($this->store(17), 'distribusi', null, '2026-09-01', '2026-09-30');

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertSame('No. Surat Jalan', $headings[0]);
        $this->assertContains(17, $query->getBindings());
        $this->assertContains('2026-09-01 00:00:00', $query->getBindings());
        $this->assertContains('2026-09-30 23:59:59', $query->getBindings());
    }

    public function test_daily_stock_export_query_includes_restocks_status(): void
    {
        $query = app(LaporanQueryService::class)
            ->stokPerTanggalQuery($this->store(17), '2026-09', 30);

        $this->assertStringContainsString('di_bawah_minimum', $query->toSql());
        $this->assertContains(17, $query->getBindings());
    }

    private function store(int $id): Gudang
    {
        $store = new Gudang;
        $store->setAttribute('id', $id);

        return $store;
    }
}
