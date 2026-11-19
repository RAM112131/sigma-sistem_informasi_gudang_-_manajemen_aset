<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PenerimaanPemindahanUnit;
use App\Models\PenerimaanUnit;
use App\Models\PenerimaanUnitDetail;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\PengembalianUnit;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGUDashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_active_workflows_without_removed_module_links(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = Gudang::query()->create([
            'kode' => 'GU-WORKFLOW',
            'nama' => 'Gudang Umum Workflow',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($gudang);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.dashboard'))
            ->assertOk()
            ->assertSee('Ajukan permintaan')
            ->assertSee('Kiriman menunggu penerimaan')
            ->assertSee('Belum ada transaksi yang menunggu verifikasi.')
            ->assertSee('Belum ada data transaksi')
            ->assertDontSee('Kustom')
            ->assertSee('data-admin-gu-chart-form', false)
            ->assertViewHas('chartData', static function (array $chartData): bool {
                self::assertFalse($chartData['movements']['has_data']);
                self::assertSame(0.0, $chartData['movements']['average_outgoing_transactions_per_day']);
                self::assertNull($chartData['movements']['incoming_ratio']);
                self::assertFalse($chartData['divisions']['has_data']);

                return true;
            })
            ->assertSee('Belum ada transaksi pengeluaran')
            ->assertSee('Belum ada data master barang untuk gudang ini.')
            ->assertDontSee('Pemeriksaan Penerimaan')
            ->assertDontSee('Verifikasi Pengeluaran (BPB)')
            ->assertDontSee('Mutasi Stok/HPP')
            ->assertDontSee('Stok Opname (Simulasi)')
            ->assertDontSee('Rekapitulasi Bulanan')
            ->assertDontSee('Pencatatan KWH Listrik')
            ->assertSee(route('unit-bisnis.stok.index'), false)
            ->assertDontSee('Saldo Awal Gudang')
            ->assertDontSee(route('unit-bisnis.recording-gu.penerimaan.index'), false)
            ->assertDontSee(route('unit-bisnis.recording-gu.pengeluaran.index'), false);
    }

    public function test_dashboard_lists_pending_receipt_verifications_and_scrollable_low_stock_items_for_active_warehouse(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = Gudang::query()->create([
            'kode' => 'GU-QUEUE',
            'nama' => 'Gudang Antrean',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($gudang);
        $barang = Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => 'QUEUE-001',
            'nama' => 'Barang Antrean',
            'minimum_stok' => 10,
            'harga_satuan' => 0,
            'is_aktif' => true,
        ]);
        $receipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-GU-QUEUE-001',
            'gudang_id' => $gudang->getKey(),
            'nama_supplier' => 'PT Jaya',
            'tanggal_terima' => today(),
            'waktu_terima' => '09:00',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $receipt->getKey(),
            'barang_id' => $barang->getKey(),
            'nama_barang' => $barang->nama,
            'satuan' => 'Pack',
            'qty_diterima' => 5,
            'harga_beli' => 0,
        ]);
        $pricedReceipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-GU-QUEUE-002',
            'gudang_id' => $gudang->getKey(),
            'nama_supplier' => 'PT Makmur',
            'tanggal_terima' => today(),
            'waktu_terima' => '09:30',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $pricedReceipt->getKey(),
            'barang_id' => $barang->getKey(),
            'nama_barang' => $barang->nama,
            'satuan' => 'Pack',
            'qty_diterima' => 3,
            'harga_beli' => 2500,
        ]);
        $issue = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-QUEUE-001',
            'gudang_id' => $gudang->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        $issueDetail = PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $issue->getKey(),
            'barang_id' => $barang->getKey(),
            'nama_barang' => $barang->nama,
            'satuan' => 'Pack',
            'qty' => 4,
        ]);
        PengembalianUnit::query()->create([
            'gudang_id' => $gudang->getKey(),
            'pengeluaran_unit_id' => $issue->getKey(),
            'pengeluaran_unit_detail_id' => $issueDetail->getKey(),
            'barang_id' => $barang->getKey(),
            'bagian_peminta' => 'Gudang Fresh',
            'nama_barang' => $barang->nama,
            'satuan' => 'Pack',
            'qty_kembali' => 1,
            'tanggal_kembali' => today(),
        ]);

        foreach (range(1, 6) as $index) {
            Barang::query()->create([
                'gudang_id' => $gudang->getKey(),
                'kode' => "LOW-{$index}",
                'nama' => "Stok Kritis {$index}",
                'minimum_stok' => 10,
                'harga_satuan' => 100,
                'is_aktif' => true,
            ]);
        }

        $otherWarehouse = Gudang::query()->create([
            'kode' => 'GU-QUEUE-OTHER',
            'nama' => 'Gudang Antrean Lain',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-OTHER-QUEUE',
            'gudang_id' => $otherWarehouse->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '11:00',
            'bagian_peminta' => 'Gudang Lain',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.dashboard'));

        $response->assertOk()
            ->assertViewHas('verificationQueue', static function (array $verificationQueue): bool {
                self::assertCount(2, $verificationQueue);
                self::assertSame(
                    ['Cocokkan Bon', 'Input Harga Beli'],
                    collect($verificationQueue)->pluck('task')->sort()->values()->all(),
                );
                self::assertSame(['Penerimaan', 'Penerimaan'], collect($verificationQueue)->pluck('type')->all());

                return true;
            })
            ->assertViewHas('lowStockItems', static function (array $lowStockItems): bool {
                self::assertCount(7, $lowStockItems);

                return true;
            })
            ->assertSee('Antrean Verifikasi Penerimaan')
            ->assertSee('Kontrol Stok di Bawah Minimum')
            ->assertSee('Penerimaan - Supplier PT Jaya')
            ->assertSee('Penerimaan - Supplier PT Makmur')
            ->assertSee('Periksa & Setujui')
            ->assertSee('data-verification-unavailable', false)
            ->assertSee('data-low-stock-scroll', false)
            ->assertSee('Stok Kritis 6')
            ->assertDontSee('BPB-GU-OTHER-QUEUE');
    }

    public function test_dashboard_history_shows_only_today_approved_issues_and_their_returns(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = Gudang::query()->create([
            'kode' => 'GU-HISTORY',
            'nama' => 'Gudang Riwayat',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($gudang);
        $barang = Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => 'HISTORY-001',
            'nama' => 'Barang Riwayat',
            'harga_satuan' => 32500,
            'is_aktif' => true,
        ]);
        $approvedIssue = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-HISTORY-001',
            'gudang_id' => $gudang->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_at' => now()->subMinutes(5),
        ]);
        $approvedDetail = PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $approvedIssue->getKey(),
            'barang_id' => $barang->getKey(),
            'nama_barang' => $barang->nama,
            'satuan' => 'Kilogram',
            'qty' => 50,
            'qty_kembali' => 2,
            'harga_hpp' => 32500,
            'subtotal_nilai' => 1560000,
        ]);
        PengembalianUnit::query()->create([
            'gudang_id' => $gudang->getKey(),
            'pengeluaran_unit_id' => $approvedIssue->getKey(),
            'pengeluaran_unit_detail_id' => $approvedDetail->getKey(),
            'barang_id' => $barang->getKey(),
            'bagian_peminta' => 'Produksi',
            'nama_barang' => $barang->nama,
            'satuan' => 'Kilogram',
            'qty_kembali' => 2,
            'tanggal_kembali' => today(),
        ]);
        PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-HISTORY-PENDING',
            'gudang_id' => $gudang->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '11:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-HISTORY-OLD',
            'gudang_id' => $gudang->getKey(),
            'tanggal' => today()->subDay(),
            'waktu_keluar' => '09:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_at' => now()->subDay(),
        ]);
        $otherWarehouse = Gudang::query()->create([
            'kode' => 'GU-HISTORY-OTHER',
            'nama' => 'Gudang Riwayat Lain',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-HISTORY-OTHER',
            'gudang_id' => $otherWarehouse->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '12:00',
            'bagian_peminta' => 'Gudang Lain',
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.dashboard'));

        $response->assertOk()
            ->assertViewHas('verificationHistory', static function (array $verificationHistory): bool {
                self::assertCount(2, $verificationHistory);
                self::assertSame(['Pengeluaran', 'Pengembalian'], collect($verificationHistory)->pluck('type')->sort()->values()->all());
                self::assertSame(
                    ['BPB-GU-HISTORY-001', 'BPB-GU-HISTORY-001'],
                    collect($verificationHistory)->pluck('reference')->all(),
                );
                self::assertSame(
                    ['+2 Kilogram | HPP Rp 32.500', '-50 Kilogram | HPP Rp 32.500'],
                    collect($verificationHistory)->pluck('impact')->sort()->values()->all(),
                );
                self::assertSame(['Disetujui Admin GU', 'Disetujui Admin GU'], collect($verificationHistory)->pluck('status')->all());

                return true;
            })
            ->assertSee('Riwayat Verifikasi Selesai Hari Ini')
            ->assertSee('Dampak Stok/HPP')
            ->assertSee('BPB-GU-HISTORY-001')
            ->assertDontSee('BPB-GU-HISTORY-PENDING')
            ->assertDontSee('BPB-GU-HISTORY-OLD')
            ->assertDontSee('BPB-GU-HISTORY-OTHER');
    }

    public function test_dashboard_charts_and_activity_use_actual_data_from_the_active_warehouse(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = Gudang::query()->create([
            'kode' => 'GU-DASHBOARD',
            'nama' => 'Gudang Dashboard',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($gudang);
        $gudangLain = Gudang::query()->create([
            'kode' => 'GU-LAIN',
            'nama' => 'Gudang Lain',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $category = KategoriBarang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'nama' => 'Bahan Baku',
            'is_aktif' => true,
        ]);
        $barang = Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kategori_id' => $category->getKey(),
            'kode' => 'BB-001',
            'nama' => 'Bahan Uji',
            'minimum_stok' => 200,
            'harga_satuan' => 1200,
            'is_aktif' => true,
        ]);
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 4,
            'tanggal_acuan' => today(),
        ]);
        $supplierReceipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-GU-DASH-001',
            'gudang_id' => $gudang->getKey(),
            'nama_supplier' => 'Supplier Aktif',
            'tanggal_terima' => today(),
            'waktu_terima' => '09:00',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $supplierReceipt->getKey(),
            'barang_id' => $barang->getKey(),
            'kode_barang' => $barang->kode,
            'nama_barang' => $barang->nama,
            'satuan' => 'Kilogram',
            'qty_diterima' => 120.5,
            'qty_rusak' => 0.5,
        ]);
        $outgoing = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-GU-DASH-001',
            'gudang_id' => $gudang->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $outgoing->getKey(),
            'barang_id' => $barang->getKey(),
            'kode_barang' => $barang->kode,
            'nama_barang' => $barang->nama,
            'satuan' => 'Kilogram',
            'qty' => 40,
        ]);
        $barangPcs = Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => 'BB-002',
            'nama' => 'Barang Satuan Pcs',
            'is_aktif' => false,
        ]);
        PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $outgoing->getKey(),
            'barang_id' => $barangPcs->getKey(),
            'kode_barang' => $barangPcs->kode,
            'nama_barang' => $barangPcs->nama,
            'satuan' => 'Pcs',
            'qty' => 20,
        ]);
        $gudangStore = Gudang::query()->create([
            'kode' => 'STORE-DASH',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $transfer = TransferBarang::query()->create([
            'nomor_sj' => 'SJ-GU-DASH-001',
            'gudang_asal_id' => $gudangStore->getKey(),
            'gudang_tujuan_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty_diminta' => 30,
            'qty_dikirim' => 30,
            'qty_diterima' => 30,
            'status' => 'diterima',
            'dikirim_at' => now()->subHour(),
            'diterima_at' => now(),
        ]);
        PenerimaanPemindahanUnit::query()->create([
            'transfer_barang_id' => $transfer->getKey(),
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty_dikirim' => 30,
            'qty_diterima' => 30,
            'qty_rusak' => 5,
            'diterima_at' => now(),
        ]);
        $otherWarehouseReceipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => 'RCV-GU-OTHER-001',
            'gudang_id' => $gudangLain->getKey(),
            'nama_supplier' => 'Supplier Gudang Lain',
            'tanggal_terima' => today(),
            'waktu_terima' => '09:00',
        ]);
        PenerimaanUnitDetail::query()->create([
            'penerimaan_unit_id' => $otherWarehouseReceipt->getKey(),
            'barang_id' => $barang->getKey(),
            'nama_barang' => $barang->nama,
            'satuan' => 'Kilogram',
            'qty_diterima' => 700,
            'qty_rusak' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.dashboard'));

        $response->assertOk()
            ->assertViewHas('chartData', static function (array $chartData): bool {
                self::assertSame(2, $chartData['movements']['incoming'][6]);
                self::assertSame(1, $chartData['movements']['outgoing'][6]);
                self::assertSame(2, $chartData['movements']['incoming_total']);
                self::assertSame(1, $chartData['movements']['outgoing_total']);
                self::assertSame(0.1, $chartData['movements']['average_outgoing_transactions_per_day']);
                self::assertSame(100.0, $chartData['movements']['incoming_ratio']);
                self::assertSame(['Produksi'], $chartData['divisions']['labels']);
                self::assertSame([1], $chartData['divisions']['values']);

                return true;
            })
            ->assertViewHas('chartRange', static function (array $chartRange): bool {
                self::assertSame('week', $chartRange['period']);
                self::assertSame(today()->subDays(6)->toDateString(), $chartRange['start_date']);
                self::assertSame(today()->toDateString(), $chartRange['end_date']);

                return true;
            })
            ->assertViewHas('stats', static function (array $stats): bool {
                self::assertSame(1, $stats['active_sku_count']);
                self::assertSame(1, $stats['low_stock_count']);
                self::assertSame(130800.0, $stats['estimated_inventory_value']);
                self::assertSame(1, $stats['received_today_count']);
                self::assertSame(0, $stats['unpriced_sku_count']);

                return true;
            })
            ->assertSee('Divisi dengan transaksi terbanyak')
            ->assertSee('Produksi')
            ->assertSee('Grafik Jumlah Transaksi Mingguan')
            ->assertSee('Transaksi Masuk')
            ->assertSee('Transaksi Keluar')
            ->assertSee('Rata-rata transaksi keluar per hari')
            ->assertSee('0,1 transaksi')
            ->assertSee('Belum ada verifikasi pengeluaran atau pengembalian yang selesai hari ini.')
            ->assertDontSee('RCV-GU-OTHER-001');

        $customStartDate = today()->subDay()->toDateString();

        $this->get(route('unit-bisnis.admin-gu.dashboard', [
            'period' => 'custom',
            'start_date' => $customStartDate,
            'end_date' => today()->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('chartRange', static function (array $chartRange) use ($customStartDate): bool {
                self::assertSame('custom', $chartRange['period']);
                self::assertSame($customStartDate, $chartRange['start_date']);
                self::assertSame(today()->toDateString(), $chartRange['end_date']);

                return true;
            })
            ->assertViewHas('chartData', static function (array $chartData): bool {
                self::assertSame([today()->subDay()->format('d/m'), today()->format('d/m')], $chartData['movements']['labels']);
                self::assertSame([0, 2], $chartData['movements']['incoming']);
                self::assertSame([0, 1], $chartData['movements']['outgoing']);
                self::assertSame(0.5, $chartData['movements']['average_outgoing_transactions_per_day']);

                return true;
            });

        $this->getJson(route('unit-bisnis.admin-gu.dashboard', [
            'period' => 'custom',
            'start_date' => $customStartDate,
            'end_date' => today()->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonStructure([
                'chartRange' => ['period', 'start_date', 'end_date', 'errors'],
                'movements' => ['labels', 'incoming', 'outgoing', 'average_outgoing_transactions_per_day', 'incoming_ratio', 'has_data'],
            ])
            ->assertJsonPath('chartRange.start_date', $customStartDate)
            ->assertJsonPath('chartRange.end_date', today()->toDateString())
            ->assertJsonPath('movements.incoming', [0, 2])
            ->assertJsonPath('movements.outgoing', [0, 1])
            ->assertJsonPath('movements.average_outgoing_transactions_per_day', 0.5);
    }

    public function test_dashboard_rejects_an_invalid_custom_date_range_and_shows_the_fallback(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = Gudang::query()->create([
            'kode' => 'GU-INVALID-DATE',
            'nama' => 'Gudang Invalid Date',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($gudang);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.dashboard', [
                'period' => 'custom',
                'start_date' => today()->toDateString(),
                'end_date' => today()->subDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Rentang tanggal tidak valid. Filter direset ke 7 hari terakhir.')
            ->assertViewHas('chartRange', static function (array $chartRange): bool {
                self::assertSame('week', $chartRange['period']);
                self::assertNotEmpty($chartRange['errors']);
                self::assertSame(today()->subDays(6)->toDateString(), $chartRange['start_date']);
                self::assertSame(today()->toDateString(), $chartRange['end_date']);

                return true;
            });

        $this->getJson(route('unit-bisnis.admin-gu.dashboard', [
            'period' => 'custom',
            'start_date' => today()->toDateString(),
            'end_date' => today()->subDay()->toDateString(),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_dashboard_hides_inventory_value_when_stocked_items_have_no_master_price(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = Gudang::query()->create([
            'kode' => 'GU-UNPRICED',
            'nama' => 'Gudang Harga Belum Ada',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($gudang);
        $barang = Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => 'BB-UNPRICED',
            'nama' => 'Barang Tanpa Harga',
            'is_aktif' => true,
        ]);
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 2,
            'tanggal_acuan' => today(),
        ]);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', static function (array $stats): bool {
                self::assertNull($stats['estimated_inventory_value']);
                self::assertSame(1, $stats['unpriced_sku_count']);

                return true;
            })
            ->assertSeeText('Harga belum tersedia untuk 1 SKU yang memiliki stok')
            ->assertSee('Belum tersedia');
    }

    public function test_standalone_opening_balance_route_is_removed(): void
    {
        $this->get('/unit-bisnis/admin-gu/saldo-awal')->assertNotFound();
    }
}
