<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\PengembalianUnit;
use App\Models\User;
use App\Services\Store\StokService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGURekonsiliasiPengeluaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_assigned_warehouse_bpbs_and_reconciliation_workspace(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REKON-LIST');
        $barang = $this->makeBarang($gudang, 'REKON-001');
        $nomorPengeluaran = 'OUT-SHT-'.today()->format('Ymd').'001-0001';
        [$issue] = $this->makeIssue($gudang, $barang, $nomorPengeluaran);
        $this->makeIssue($gudang, $barang, 'BPB-REKON-NEXT');

        $otherWarehouse = $this->makeGudang('GU-REKON-OTHER');
        $otherBarang = $this->makeBarang($otherWarehouse, 'REKON-OTHER-001');
        $this->makeIssue($otherWarehouse, $otherBarang, 'BPB-REKON-OTHER');

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index', [
                'pengeluaran_id' => $issue->getKey(),
            ]))
            ->assertOk()
            ->assertSee('Rekonsiliasi Pengeluaran')
            ->assertSee('Nomor Pengeluaran')
            ->assertSee($nomorPengeluaran)
            ->assertSeeText('Qty Pengembalian')
            ->assertSee('name="items[0][qty_keluar]"', false)
            ->assertSee('#verification-workspace', false)
            ->assertSee('id="verification-workspace"', false)
            ->assertSee(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index'), false)
            ->assertDontSee('BPB-REKON-OTHER');
    }

    public function test_requester_filter_only_shows_matching_requester_and_options_from_the_active_warehouse(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REKON-REQUESTERS');
        $barang = $this->makeBarang($gudang, 'REKON-REQUESTER-001');
        [$firstIssue] = $this->makeIssue($gudang, $barang, 'OUT-REQUESTER-001', issueAttributes: [
            'bagian_peminta' => 'Alpha Peminta',
        ]);
        $this->makeIssue($gudang, $barang, 'OUT-REQUESTER-002', issueAttributes: [
            'bagian_peminta' => 'Beta Peminta',
        ]);

        $otherWarehouse = $this->makeGudang('GU-REKON-REQUESTERS-OTHER');
        $otherBarang = $this->makeBarang($otherWarehouse, 'REKON-REQUESTER-OTHER-001');
        $this->makeIssue($otherWarehouse, $otherBarang, 'OUT-REQUESTER-OTHER', issueAttributes: [
            'bagian_peminta' => 'Peminta Gudang Lain',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $gudang->getKey()])
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index', [
                'bagian_peminta' => 'Alpha Peminta',
            ]));

        $response->assertOk()
            ->assertSee('Semua Peminta')
            ->assertSee('name="bagian_peminta"', false)
            ->assertSee('Alpha Peminta')
            ->assertSee('Beta Peminta')
            ->assertDontSee('Peminta Gudang Lain')
            ->assertDontSee('Semua Divisi Peminta')
            ->assertDontSee('OUT-REQUESTER-002');

        $this->assertSame(
            ['Alpha Peminta', 'Beta Peminta'],
            $response->viewData('pemintas'),
        );
        $this->assertSame(
            [$firstIssue->getKey()],
            $response->viewData('issues')->getCollection()->modelKeys(),
        );
    }

    public function test_guest_is_redirected_to_sign_in_before_opening_reconciliation(): void
    {
        $this->get(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index'))
            ->assertRedirect(route('login'));
    }

    public function test_recording_role_cannot_open_admin_reconciliation(): void
    {
        $recording = User::factory()->create();
        $recording->assignRole(Role::findOrCreate('Recording Gudang Umum', 'web'));

        $this->actingAs($recording)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index'))
            ->assertForbidden();
    }

    public function test_saving_draft_does_not_change_return_stock_or_approve_the_bpb(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REKON-DRAFT');
        $barang = $this->makeBarang($gudang, 'REKON-DRAFT-001');
        [$issue, $detail] = $this->makeIssue($gudang, $barang, 'BPB-REKON-DRAFT', [
            'qty' => 10,
            'qty_kembali' => 2,
            'harga_hpp' => 1200,
            'subtotal_nilai' => 9600,
        ]);

        $response = $this->actingAs($admin)->post(
            route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.draft', $issue->getKey()),
            [
                'gudang_id' => $gudang->getKey(),
                'items' => [
                    ['detail_id' => $detail->getKey(), 'qty_keluar' => 12, 'qty_kembali' => 4],
                ],
            ],
        );

        $response->assertRedirect()
            ->assertSessionHas('success', 'Draf rekonsiliasi berhasil disimpan. Stok belum berubah.');
        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'id' => $detail->getKey(),
            'qty' => 10,
            'qty_keluar_rekonsiliasi' => 12,
            'qty_kembali' => 2,
            'qty_kembali_rekonsiliasi' => 4,
        ]);
        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $issue->getKey(),
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);

        $totals = app(StokService::class)->totals([$barang->getKey()], (int) $gudang->getKey());
        self::assertSame(2.0, (float) $totals['masuk'][$barang->getKey()]);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index', [
                'pengeluaran_id' => $issue->getKey(),
            ]))
            ->assertOk()
            ->assertViewHas('metrics', static function (array $metrics): bool {
                self::assertSame(9600.0, $metrics['pending_value']);

                return true;
            })
            ->assertViewHas('issues', static function (LengthAwarePaginator $issues): bool {
                self::assertSame(9600.0, (float) $issues->first()->total_nilai);

                return true;
            });
    }

    public function test_verifying_bpb_persists_final_return_cogs_and_stock(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REKON-VERIFY');
        $barang = $this->makeBarang($gudang, 'REKON-VERIFY-001');
        [$issue, $detail] = $this->makeIssue($gudang, $barang, 'BPB-REKON-VERIFY', [
            'qty' => 10,
            'qty_kembali' => 2,
            'harga_hpp' => 1200,
            'subtotal_nilai' => 9600,
        ]);

        $response = $this->actingAs($admin)->post(
            route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.verify', $issue->getKey()),
            [
                'gudang_id' => $gudang->getKey(),
                'items' => [
                    ['detail_id' => $detail->getKey(), 'qty_keluar' => 12, 'qty_kembali' => 3],
                ],
                'catatan_verifikasi' => 'Retur fisik sudah dicocokkan.',
            ],
        );

        $response->assertRedirect()
            ->assertSessionHas('success', 'BPB berhasil diverifikasi. Qty keluar, retur, dan pemakaian bersih telah diperbarui.');
        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $issue->getKey(),
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_oleh' => $admin->getKey(),
            'catatan_verifikasi' => 'Retur fisik sudah dicocokkan.',
        ]);
        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'id' => $detail->getKey(),
            'qty' => 12,
            'qty_keluar_dilaporkan' => 10,
            'qty_keluar_rekonsiliasi' => 12,
            'qty_kembali' => 3,
            'qty_kembali_rekonsiliasi' => 3,
            'subtotal_nilai' => 10800,
        ]);

        $totals = app(StokService::class)->totals([$barang->getKey()], (int) $gudang->getKey());
        self::assertSame(3.0, (float) $totals['masuk'][$barang->getKey()]);
        self::assertSame(12.0, (float) $totals['keluar'][$barang->getKey()]);
    }

    public function test_return_quantity_above_issue_is_rejected_without_writing(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REKON-INVALID');
        $barang = $this->makeBarang($gudang, 'REKON-INVALID-001');
        [$issue, $detail] = $this->makeIssue($gudang, $barang, 'BPB-REKON-INVALID', [
            'qty' => 10,
            'qty_kembali' => 2,
            'harga_hpp' => 1200,
            'subtotal_nilai' => 9600,
        ]);

        $response = $this->actingAs($admin)->from(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index'))->post(
            route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.verify', $issue->getKey()),
            [
                'gudang_id' => $gudang->getKey(),
                'items' => [
                    ['detail_id' => $detail->getKey(), 'qty_keluar' => 10, 'qty_kembali' => 11],
                ],
            ],
        );

        $response->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index'))
            ->assertSessionHasErrors('items');
        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $issue->getKey(),
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'id' => $detail->getKey(),
            'qty_kembali' => 2,
            'qty_keluar_rekonsiliasi' => null,
            'qty_kembali_rekonsiliasi' => null,
            'subtotal_nilai' => 9600,
        ]);
    }

    public function test_admin_cannot_reconcile_an_issue_from_an_unassigned_warehouse(): void
    {
        [$admin, $assignedGudang] = $this->makeAdminContext('GU-REKON-ASSIGNED');
        $otherWarehouse = $this->makeGudang('GU-REKON-UNASSIGNED');
        $barang = $this->makeBarang($otherWarehouse, 'REKON-UNASSIGNED-001');
        [$issue, $detail] = $this->makeIssue($otherWarehouse, $barang, 'BPB-REKON-UNASSIGNED');

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.verify', $issue->getKey()), [
                'gudang_id' => $assignedGudang->getKey(),
                'items' => [
                    ['detail_id' => $detail->getKey(), 'qty_keluar' => 10, 'qty_kembali' => 0],
                ],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $issue->getKey(),
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
    }

    public function test_new_recorded_return_reopens_a_verified_bpb_using_its_final_return_balance(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REKON-LATE-RETURN');
        $barang = $this->makeBarang($gudang, 'REKON-LATE-RETURN-001');
        [$issue, $detail] = $this->makeIssue($gudang, $barang, 'BPB-REKON-LATE-RETURN', [
            'qty' => 10,
            'qty_keluar_dilaporkan' => 10,
            'qty_keluar_rekonsiliasi' => 10,
            'qty_kembali' => 1,
            'qty_kembali_rekonsiliasi' => 1,
            'harga_hpp' => 1200,
            'subtotal_nilai' => 10800,
        ]);
        $issue->update([
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_oleh' => $admin->getKey(),
            'diverifikasi_at' => now(),
        ]);
        PengembalianUnit::query()->create([
            'gudang_id' => $gudang->getKey(),
            'pengeluaran_unit_id' => $issue->getKey(),
            'pengeluaran_unit_detail_id' => $detail->getKey(),
            'barang_id' => $barang->getKey(),
            'bagian_peminta' => 'Produksi',
            'nama_barang' => $barang->nama,
            'satuan' => 'Pack',
            'qty_kembali' => 1,
            'tanggal_kembali' => today(),
        ]);

        $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $gudang->getKey()])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.pengembalian.store'), [
                'bagian_peminta' => 'Produksi',
                'tanggal_kembali' => today()->toDateString(),
                'items' => [
                    ['name' => $barang->nama, 'qty_kembali' => 1],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $issue->getKey(),
            'status_verifikasi' => 'menunggu_verifikasi',
            'diverifikasi_oleh' => null,
            'diverifikasi_at' => null,
        ]);
        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'id' => $detail->getKey(),
            'qty_kembali' => 2,
            'qty_keluar_rekonsiliasi' => null,
            'qty_kembali_rekonsiliasi' => null,
            'subtotal_nilai' => 9600,
        ]);
        $this->assertDatabaseHas('pengembalian_units', [
            'pengeluaran_unit_id' => $issue->getKey(),
            'qty_kembali' => 1,
        ]);
    }

    /**
     * @return array{0: User, 1: Gudang}
     */
    private function makeAdminContext(string $kode): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = $this->makeGudang($kode);
        $admin->gudang()->attach($gudang);

        return [$admin, $gudang];
    }

    private function makeGudang(string $kode): Gudang
    {
        return Gudang::query()->create([
            'kode' => $kode,
            'nama' => "Gudang {$kode}",
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
    }

    private function makeBarang(Gudang $gudang, string $kode): Barang
    {
        return Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => $kode,
            'nama' => "Barang {$kode}",
            'harga_satuan' => 1200,
            'is_aktif' => true,
        ]);
    }

    /**
     * @param  array<string, int|float|string>  $detailAttributes
     * @param  array<string, int|float|string>  $issueAttributes
     * @return array{0: PengeluaranUnit, 1: PengeluaranUnitDetail}
     */
    private function makeIssue(
        Gudang $gudang,
        Barang $barang,
        string $nomorBpb,
        array $detailAttributes = [],
        array $issueAttributes = [],
    ): array {
        $issue = PengeluaranUnit::query()->create(array_merge([
            'nomor_bpb' => $nomorBpb,
            'gudang_id' => $gudang->getKey(),
            'tanggal' => today(),
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'menunggu_verifikasi',
        ], $issueAttributes));
        $detail = $issue->details()->create(array_merge([
            'barang_id' => $barang->getKey(),
            'kode_barang' => $barang->kode,
            'nama_barang' => $barang->nama,
            'kategori' => 'Umum',
            'satuan' => 'Pack',
            'qty' => 10,
            'qty_kembali' => 0,
            'harga_hpp' => 1200,
            'subtotal_nilai' => 12000,
        ], $detailAttributes));

        return [$issue, $detail];
    }
}
