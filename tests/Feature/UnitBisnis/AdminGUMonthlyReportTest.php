<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Exports\AdminGu\WarehouseMonthlyReportExport;
use App\Models\Barang;
use App\Models\DivisiPeminta;
use App\Models\Gudang;
use App\Models\PenerimaanUnit;
use App\Models\PenerimaanUnitDetail;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\StokAwal;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGUMonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_calculates_only_verified_stock_movements_and_applied_opname(): void
    {
        $this->travelTo('2026-11-17 10:00:00');
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $warehouse = Gudang::query()->create([
            'kode' => 'GU-MATRIX',
            'nama' => 'Gudang Matriks',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($warehouse);
        $item = Barang::query()->create([
            'gudang_id' => $warehouse->getKey(),
            'kode' => 'SKU-REPORT-01',
            'nama' => 'Tepung Terigu',
            'minimum_stok' => 0,
            'harga_satuan' => 2500,
            'is_aktif' => true,
        ]);
        StokAwal::query()->create([
            'gudang_id' => $warehouse->getKey(),
            'barang_id' => $item->getKey(),
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'import',
        ]);
        $receipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-REPORT-01',
            'gudang_id' => $warehouse->getKey(),
            'nama_supplier' => 'PT Contoh',
            'tanggal_terima' => '2026-10-10',
            'waktu_terima' => '09:00',
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_at' => '2026-10-10 10:00:00',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $receipt->getKey(),
            'barang_id' => $item->getKey(),
            'kode_barang' => $item->kode,
            'nama_barang' => $item->nama,
            'satuan' => 'Kg',
            'qty_diterima' => 10,
            'qty_diperiksa' => 8,
            'qty_rusak' => 0,
            'qty_rusak_diperiksa' => 1,
            'harga_beli' => 2000,
            'subtotal_nilai' => 14000,
        ]);
        $pendingReceipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-REPORT-PENDING',
            'gudang_id' => $warehouse->getKey(),
            'nama_supplier' => 'PT Belum Verifikasi',
            'tanggal_terima' => '2026-11-11',
            'waktu_terima' => '09:00',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $pendingReceipt->getKey(),
            'barang_id' => $item->getKey(),
            'kode_barang' => $item->kode,
            'nama_barang' => $item->nama,
            'satuan' => 'Kg',
            'qty_diterima' => 100,
            'harga_beli' => 2000,
        ]);
        $issue = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-REPORT-01',
            'gudang_id' => $warehouse->getKey(),
            'tanggal' => '2026-11-12',
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'terverifikasi',
        ]);
        PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $issue->getKey(),
            'barang_id' => $item->getKey(),
            'kode_barang' => $item->kode,
            'nama_barang' => $item->nama,
            'satuan' => 'Kg',
            'qty' => 3,
            'qty_kembali' => 1,
            'harga_hpp' => 2000,
            'subtotal_nilai' => 4000,
        ]);
        $opname = StokOpname::query()->create([
            'gudang_id' => $warehouse->getKey(),
            'periode' => '2026-11-01',
            'tanggal_efektif' => '2026-11-30',
            'status' => 'diterapkan',
        ]);
        StokOpnameDetail::query()->create([
            'stok_opname_id' => $opname->getKey(),
            'barang_id' => $item->getKey(),
            'kode_barang' => $item->kode,
            'nama_barang' => $item->nama,
            'satuan' => 'Kg',
            'qty_sistem' => 15,
            'qty_sistem_at' => '2026-11-17 10:00:00',
            'qty_fisik' => 14,
            'harga_satuan' => 2500,
            'catatan' => 'Satu unit tidak ditemukan',
        ]);

        $response = $this->actingAs($admin)->get(route('unit-bisnis.admin-gu.laporan.index', [
            'period' => '2026-11',
        ]));

        $response->assertOk()
            ->assertSee('Rekapitulasi Laporan Matriks')
            ->assertSee('Laporan Berjalan (Belum Tutup Buku)')
            ->assertViewHas('report', static function (array $report): bool {
                self::assertCount(1, $report['items']);
                self::assertSame('SKU-REPORT-01', $report['items'][0]['kode']);
                self::assertSame(17.0, $report['items'][0]['saldo_awal']);
                self::assertSame(2.0, $report['items'][0]['keluar_bersih']);
                self::assertSame(-1.0, $report['items'][0]['selisih_opname']);
                self::assertSame(14.0, $report['items'][0]['saldo_akhir']);
                self::assertSame(35000.0, $report['items'][0]['nilai_stok']);

                return true;
            });

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', [
                'period' => '2026-10',
            ]))
            ->assertOk()
            ->assertViewHas('report', static function (array $report): bool {
                self::assertSame(0.0, $report['items'][0]['selisih_opname']);
                self::assertSame(7.0, $report['items'][0]['saldo_akhir']);

                return true;
            });
    }

    public function test_guest_and_recording_role_cannot_open_admin_gu_reports(): void
    {
        $this->get(route('unit-bisnis.admin-gu.laporan.index'))
            ->assertRedirect(route('login'));

        $recording = User::factory()->create();
        $recording->assignRole(Role::findOrCreate('Recording Gudang Umum', 'web'));

        $this->actingAs($recording)
            ->get(route('unit-bisnis.admin-gu.laporan.index'))
            ->assertForbidden();
    }

    public function test_report_rejects_an_invalid_period(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', ['period' => '2026-13']))
            ->assertSessionHasErrors('period');
    }

    public function test_report_cache_rebuilds_payloads_with_invalid_row_shapes(): void
    {
        [$admin, $warehouse, $item] = $this->makeReportContext();
        $filters = [
            'category_id' => null,
            'matrix_mode' => 'qty',
            'page' => 1,
            'paginated' => true,
            'period' => '2026-11',
            'search' => '',
            'source_type' => null,
            'tab' => 'mutasi',
        ];
        ksort($filters);
        $hash = hash('sha256', serialize([$warehouse->getKey(), $filters]));
        Cache::put("report:v2:2026-11:mutasi:{$hash}", ['items' => ['stale']], 600);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', ['period' => '2026-11']))
            ->assertOk()
            ->assertViewHas('report', static function (array $report) use ($item): bool {
                self::assertSame($item->kode, $report['items'][0]['kode']);

                return true;
            });
    }

    public function test_division_matrix_uses_verified_net_quantity_and_captured_hpp(): void
    {
        $this->travelTo('2026-11-17 10:00:00');
        [$admin, $warehouse, $item] = $this->makeReportContext();
        $division = DivisiPeminta::query()->create([
            'gudang_id' => $warehouse->getKey(),
            'kode' => 'PROD',
            'nama' => 'Produksi',
            'is_aktif' => true,
        ]);
        $issue = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-MATRIX-01',
            'gudang_id' => $warehouse->getKey(),
            'tanggal' => '2026-11-12',
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Produksi',
            'divisi_peminta_id' => $division->getKey(),
            'status_verifikasi' => 'terverifikasi',
        ]);
        PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $issue->getKey(),
            'barang_id' => $item->getKey(),
            'kode_barang' => $item->kode,
            'nama_barang' => $item->nama,
            'satuan' => 'Kg',
            'qty' => 6,
            'qty_kembali' => 2,
            'harga_hpp' => 1250,
            'subtotal_nilai' => 5000,
        ]);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', [
                'period' => '2026-11',
                'tab' => 'matriks',
            ]))
            ->assertOk()
            ->assertSee('Produksi')
            ->assertViewHas('report', static function (array $report) use ($division): bool {
                self::assertSame(4.0, $report['items'][0]['divisions'][$division->getKey()]['qty']);
                self::assertSame(5000.0, $report['items'][0]['divisions'][$division->getKey()]['value']);
                self::assertSame(4.0, $report['totals']['qty']);

                return true;
            });
    }

    public function test_excel_and_pdf_downloads_render_the_selected_monthly_report(): void
    {
        [$admin] = $this->makeReportContext();

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.export-excel', ['period' => '2026-11']))
            ->assertDownload('laporan-gudang-umum-2026-11-mutasi.xlsx');

        $this->get(route('unit-bisnis.admin-gu.laporan.export-pdf', [
            'period' => '2026-11',
            'tab' => 'selisih',
        ]))->assertDownload('laporan-gudang-umum-2026-11-selisih.pdf');
    }

    public function test_inbound_tab_lists_verified_supplier_receipts_and_value(): void
    {
        [$admin, $warehouse, $item] = $this->makeReportContext();
        $receipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-INBOUND-01',
            'gudang_id' => $warehouse->getKey(),
            'nama_supplier' => 'PT Pemasok',
            'tanggal_terima' => '2026-11-10',
            'waktu_terima' => '09:00',
            'nomor_surat_jalan' => 'SJ-77',
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_at' => '2026-11-10 10:00:00',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $receipt->getKey(),
            'barang_id' => $item->getKey(),
            'kode_barang' => $item->kode,
            'nama_barang' => $item->nama,
            'satuan' => 'Kg',
            'qty_diterima' => 4,
            'qty_diperiksa' => 4,
            'qty_rusak' => 0,
            'qty_rusak_diperiksa' => 0,
            'harga_beli' => 500,
            'subtotal_nilai' => 2000,
        ]);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', [
                'period' => '2026-11',
                'tab' => 'pemasukan',
                'source_type' => 'direct_purchase',
            ]))
            ->assertOk()
            ->assertSee('RCV-INBOUND-01')
            ->assertSee('PT Pemasok')
            ->assertViewHas('report', static function (array $report): bool {
                self::assertSame(2000.0, $report['kpi']['purchase_value']);
                self::assertSame(1, $report['kpi']['purchase_documents']);
                self::assertCount(1, $report['documents']);

                return true;
            });
    }

    public function test_inbound_documents_are_paginated_without_truncating_kpis(): void
    {
        [$admin, $warehouse, $item] = $this->makeReportContext();
        for ($number = 1; $number <= 51; $number++) {
            $receipt = PenerimaanUnit::query()->create([
                'nomor_rcv' => sprintf('RCV-PAGE-%03d', $number),
                'gudang_id' => $warehouse->getKey(),
                'nama_supplier' => 'PT Pemasok',
                'tanggal_terima' => '2026-11-10',
                'waktu_terima' => '09:00',
                'status_verifikasi' => 'terverifikasi',
                'diverifikasi_at' => '2026-11-10 10:00:00',
            ]);
            PenerimaanUnitDetail::query()->create([
                'penerimaan_unit_id' => $receipt->getKey(),
                'barang_id' => $item->getKey(),
                'kode_barang' => $item->kode,
                'nama_barang' => $item->nama,
                'satuan' => 'Kg',
                'qty_diterima' => 1,
                'qty_diperiksa' => 1,
                'harga_beli' => 100,
                'subtotal_nilai' => 100,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', [
                'period' => '2026-11',
                'tab' => 'pemasukan',
            ]))
            ->assertOk()
            ->assertViewHas('report', static function (array $report): bool {
                self::assertSame(50, $report['documents']->perPage());
                self::assertSame(51, $report['documents']->total());
                self::assertCount(50, $report['documents']);
                self::assertSame(51, $report['kpi']['purchase_documents']);
                self::assertSame(5100.0, $report['kpi']['purchase_value']);

                return true;
            });

        $this->get(route('unit-bisnis.admin-gu.laporan.index', [
            'period' => '2026-11',
            'tab' => 'pemasukan',
            'page' => 2,
        ]))->assertOk()
            ->assertViewHas('report', static function (array $report): bool {
                self::assertSame(2, $report['documents']->currentPage());
                self::assertCount(1, $report['documents']);
                self::assertSame(51, $report['kpi']['purchase_documents']);

                return true;
            });
    }

    public function test_all_report_tabs_render_successfully(): void
    {
        [$admin] = $this->makeReportContext();
        $this->actingAs($admin);

        foreach (['mutasi', 'matriks', 'pemasukan', 'selisih'] as $tab) {
            $this->get(route('unit-bisnis.admin-gu.laporan.index', [
                'period' => '2026-11',
                'tab' => $tab,
            ]))->assertOk();
        }
    }

    public function test_excel_mutation_export_uses_excel_formulas_for_grand_totals(): void
    {
        $export = new WarehouseMonthlyReportExport([
            'items' => [[
                'kode' => 'SKU-1',
                'nama' => 'Barang',
                'satuan' => 'Pcs',
                'kategori' => 'Umum',
                'saldo_awal' => 3,
                'masuk_store' => 1,
                'masuk_beli_umum' => 0,
                'keluar_bersih' => 1,
                'selisih_opname' => 0,
                'saldo_akhir' => 3,
                'hpp' => 10,
                'nilai_stok' => 30,
            ]],
        ], ['tab' => 'mutasi']);

        $rows = $export->array();

        self::assertSame('=SUM(E2:E2)', $rows[1][4]);
        self::assertSame('=SUM(L2:L2)', $rows[1][11]);
    }

    public function test_sku_report_pages_are_capped_and_keep_full_totals(): void
    {
        [$admin, $warehouse, $existingItem] = $this->makeReportContext();
        StokAwal::query()->create([
            'gudang_id' => $warehouse->getKey(),
            'barang_id' => $existingItem->getKey(),
            'qty' => 1,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'import',
        ]);
        for ($number = 1; $number <= 50; $number++) {
            $item = Barang::query()->create([
                'gudang_id' => $warehouse->getKey(),
                'kode' => sprintf('SKU-PAGE-%03d', $number),
                'nama' => 'Barang Pagination '.$number,
                'minimum_stok' => 0,
                'harga_satuan' => 100,
                'is_aktif' => true,
            ]);
            StokAwal::query()->create([
                'gudang_id' => $warehouse->getKey(),
                'barang_id' => $item->getKey(),
                'qty' => 1,
                'tanggal_acuan' => '2026-10-01',
                'sumber' => 'import',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.laporan.index', ['period' => '2026-11']))
            ->assertOk()
            ->assertViewHas('report', static function (array $report): bool {
                self::assertSame(50, $report['items']->perPage());
                self::assertSame(51, $report['items']->total());
                self::assertCount(50, $report['items']);
                self::assertSame(51.0, $report['totals']['saldo_akhir']);

                return true;
            });

        $this->get(route('unit-bisnis.admin-gu.laporan.index', [
            'period' => '2026-11',
            'page' => 2,
        ]))->assertOk()
            ->assertViewHas('report', static function (array $report): bool {
                self::assertSame(2, $report['items']->currentPage());
                self::assertCount(1, $report['items']);
                self::assertSame(51.0, $report['totals']['saldo_akhir']);

                return true;
            });
    }

    /**
     * @return array{User, Gudang, Barang}
     */
    private function makeReportContext(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $warehouse = Gudang::query()->create([
            'kode' => 'GU-MATRIX',
            'nama' => 'Gudang Matriks',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($warehouse);
        $item = Barang::query()->create([
            'gudang_id' => $warehouse->getKey(),
            'kode' => 'SKU-REPORT-01',
            'nama' => 'Tepung Terigu',
            'minimum_stok' => 0,
            'harga_satuan' => 2500,
            'is_aktif' => true,
        ]);

        return [$admin, $warehouse, $item];
    }
}
