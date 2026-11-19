<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\DivisiPeminta;
use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RecordingGUPengeluaranPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_gu_uses_out_numbers_matching_the_rcv_format_for_each_day(): void
    {
        [$recording, $gudang, $barang] = $this->makeRecordingContext();
        $payload = [
            'waktu_keluar' => '07:36',
            'entry_mode' => 'live',
            'bagian_peminta' => 'Produksi',
            'items' => [
                ['barang_id' => $barang->id, 'qty' => 2],
            ],
        ];

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
                ...$payload,
                'tanggal' => '2026-11-03',
            ])
            ->assertCreated()
            ->assertJsonPath('data.bpb', 'OUT-UNI-20261103001-0001');

        $this->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
            ...$payload,
            'tanggal' => '2026-11-04',
        ])
            ->assertCreated()
            ->assertJsonPath('data.bpb', 'OUT-UNI-20261104001-0001');

        $this->assertDatabaseCount('pengeluaran_units', 2);
        $this->assertDatabaseHas('pengeluaran_units', [
            'nomor_bpb' => 'OUT-UNI-20261104001-0001',
            'gudang_id' => $gudang->id,
            'tanggal' => '2026-11-04',
        ]);
    }

    public function test_recording_gu_saves_and_accumulates_entries_for_the_same_day_and_requester(): void
    {
        [$recording, $gudang, $barang] = $this->makeRecordingContext();
        $payload = [
            'tanggal' => '2026-10-29',
            'waktu_keluar' => '10:30',
            'entry_mode' => 'live',
            'bagian_peminta' => 'Produksi',
            'catatan' => 'Catatan awal',
            'items' => [
                ['barang_id' => $barang->id, 'qty' => 1.25, 'catatan' => 'Baris pertama'],
            ],
        ];

        $firstResponse = $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), $payload)
            ->assertCreated()
            ->assertJsonPath('data.requester', 'Produksi')
            ->assertJsonPath('data.verificationStatus', 'menunggu_verifikasi');

        $transactionId = $firstResponse->json('data.id');

        $this->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
            ...$payload,
            'waktu_keluar' => '11:45',
            'catatan' => 'Catatan tambahan',
            'items' => [
                ['barang_id' => $barang->id, 'qty' => 3, 'catatan' => 'Baris kedua'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.id', $transactionId)
            ->assertJsonPath('data.items.0.qty', 4.25);

        $this->assertDatabaseCount('pengeluaran_units', 1);
        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $transactionId,
            'gudang_id' => $gudang->id,
            'tanggal' => '2026-10-29',
            'bagian_peminta' => 'Produksi',
            'waktu_keluar' => '11:45:00',
        ]);
        $area = DivisiPeminta::query()
            ->where('gudang_id', $gudang->id)
            ->where('nama', 'Produksi')
            ->firstOrFail();
        $this->assertDatabaseHas('pengeluaran_units', [
            'id' => $transactionId,
            'divisi_peminta_id' => $area->id,
        ]);
        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'pengeluaran_unit_id' => $transactionId,
            'barang_id' => $barang->id,
            'qty' => 4.25,
            'catatan' => 'Baris pertama; Baris kedua',
        ]);

        $this->withSession(['active_gudang_id' => $gudang->id])
            ->get(route('unit-bisnis.recording-gu.pengeluaran.index'))
            ->assertOk()
            ->assertViewHas('transactions', static fn (array $transactions): bool => $transactions[0]['id'] === $transactionId)
            ->assertSee(route('unit-bisnis.recording-gu.pengeluaran.store'), false)
            ->assertSee('Tanggal Pengembalian')
            ->assertDontSee('PILIH TANGGAL PENGELUARAN')
            ->assertDontSee('Semua Tanggal (Gabungan)')
            ->assertSee('BPB');
    }

    public function test_recording_gu_snapshots_hpp_and_initial_usage_value_on_the_bpb_detail(): void
    {
        [$recording, $gudang, $barang] = $this->makeRecordingContext();
        $barang->update(['harga_satuan' => 3250]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
                'tanggal' => '2026-10-29',
                'waktu_keluar' => '10:30',
                'entry_mode' => 'live',
                'bagian_peminta' => 'Produksi',
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 4],
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'barang_id' => $barang->id,
            'qty' => 4,
            'harga_hpp' => 3250,
            'subtotal_nilai' => 13000,
        ]);
    }

    public function test_recording_gu_returns_requester_items_across_issue_dates_on_selected_return_date(): void
    {
        [$recording, $gudang, $barang] = $this->makeRecordingContext();
        $this->actingAs($recording)->withSession(['active_gudang_id' => $gudang->id]);

        foreach (['2026-09-29' => 2, '2026-10-30' => 3] as $date => $quantity) {
            $this->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
                'tanggal' => $date,
                'waktu_keluar' => '10:30',
                'entry_mode' => 'live',
                'bagian_peminta' => 'Produksi',
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => $quantity],
                ],
            ])->assertCreated();
        }

        $this->getJson(route('unit-bisnis.recording-gu.pengeluaran.borrowed-items', [
            'department' => 'Produksi',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.totalTaken', 5)
            ->assertJsonPath('data.0.availableToReturn', 5);

        $this->postJson(route('unit-bisnis.recording-gu.pengeluaran.pengembalian.store'), [
            'bagian_peminta' => 'Produksi',
            'tanggal_kembali' => '2026-11-04',
            'items' => [
                ['name' => $barang->nama, 'qty_kembali' => 1.5],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.borrowedItems.0.totalTaken', 5)
            ->assertJsonPath('data.borrowedItems.0.totalReturned', 1.5)
            ->assertJsonPath('data.borrowedItems.0.availableToReturn', 3.5);

        $latestIssue = PengeluaranUnit::query()
            ->where('gudang_id', $gudang->id)
            ->whereDate('tanggal', '2026-10-30')
            ->firstOrFail();
        $this->assertDatabaseHas('pengembalian_units', [
            'gudang_id' => $gudang->id,
            'pengeluaran_unit_id' => $latestIssue->id,
            'barang_id' => $barang->id,
            'bagian_peminta' => 'Produksi',
            'qty_kembali' => 1.5,
            'tanggal_kembali' => '2026-11-04',
        ]);
    }

    public function test_recording_gu_cannot_return_more_than_requester_outstanding_quantity(): void
    {
        [$recording, $gudang, $barang] = $this->makeRecordingContext();
        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
                'tanggal' => '2026-10-29',
                'waktu_keluar' => '10:30',
                'entry_mode' => 'live',
                'bagian_peminta' => 'Produksi',
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 2],
                ],
            ])
            ->assertCreated();

        $response = $this->postJson(route('unit-bisnis.recording-gu.pengeluaran.pengembalian.store'), [
            'bagian_peminta' => 'Produksi',
            'tanggal_kembali' => '2026-11-04',
            'items' => [
                ['name' => $barang->nama, 'qty_kembali' => 3],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('pengembalian_units', 0);
        $this->assertDatabaseHas('pengeluaran_unit_details', [
            'barang_id' => $barang->id,
            'qty' => 2,
            'qty_kembali' => 0,
        ]);
    }

    public function test_recording_gu_must_provide_the_return_date(): void
    {
        [$recording, $gudang, $barang] = $this->makeRecordingContext();
        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
                'tanggal' => '2026-10-29',
                'waktu_keluar' => '10:30',
                'entry_mode' => 'live',
                'bagian_peminta' => 'Produksi',
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 2],
                ],
            ])
            ->assertCreated();

        $this->postJson(route('unit-bisnis.recording-gu.pengeluaran.pengembalian.store'), [
            'bagian_peminta' => 'Produksi',
            'items' => [
                ['name' => $barang->nama, 'qty_kembali' => 1],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_kembali');

        $this->assertDatabaseCount('pengembalian_units', 0);
    }

    public function test_recording_gu_cannot_save_an_item_from_an_unassigned_warehouse(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $otherGudang = $this->makeGudang('UNIT-LAIN', 'Gudang Lain');
        $barangLain = Barang::create([
            'gudang_id' => $otherGudang->id,
            'kode' => 'ITEM-LAIN',
            'nama' => 'Barang Gudang Lain',
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [
                'tanggal' => '2026-10-29',
                'waktu_keluar' => '10:30',
                'entry_mode' => 'live',
                'bagian_peminta' => 'Produksi',
                'items' => [
                    ['barang_id' => $barangLain->id, 'qty' => 2],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('pengeluaran_units', 0);
    }

    public function test_recording_gu_can_save_a_new_requester_immediately_from_quick_add(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.requester.store'), [
                'bagian_peminta' => 'Area Baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Area Baru');

        $this->assertDatabaseHas('divisi_pemintas', [
            'gudang_id' => $gudang->id,
            'nama' => 'Area Baru',
            'is_aktif' => true,
        ]);
    }

    public function test_user_without_recording_role_cannot_save_an_issue(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Pengguna Tidak Berwenang',
            'username' => 'bukan-recording',
            'password' => 'password',
            'is_aktif' => true,
        ]));

        $this->actingAs($user)
            ->postJson(route('unit-bisnis.recording-gu.pengeluaran.store'), [])
            ->assertForbidden();
    }

    /**
     * @return array{User, Gudang, Barang}
     */
    private function makeRecordingContext(): array
    {
        $recording = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording',
            'username' => 'recording-unit',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $recording->assignRole(Role::create([
            'name' => 'Recording Gudang Umum',
            'guard_name' => 'web',
        ]));

        $gudang = $this->makeGudang('UNIT-RECORD', 'Gudang Unit Recording');
        $recording->gudang()->attach($gudang);
        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'ITEM-001',
            'nama' => 'Barang Uji',
            'is_aktif' => true,
        ]);

        return [$recording, $gudang, $barang];
    }

    private function makeGudang(string $code, string $name): Gudang
    {
        return Gudang::create([
            'kode' => $code,
            'nama' => $name,
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
    }
}
