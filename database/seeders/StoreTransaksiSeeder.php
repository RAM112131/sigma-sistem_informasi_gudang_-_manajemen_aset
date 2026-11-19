<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanBarangStore;
use App\Models\PermintaanBarang;
use App\Models\PermintaanBarangDetail;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreTransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds for Store Transactions.
     * Uses users matched to their respective unit & role.
     */
    public function run(): void
    {
        $gudangStore = Gudang::where('kode', 'STORE')->first() ?? Gudang::where('kode', 'ARMADA')->first();
        $gudangSukahati = Gudang::where('kode', 'SUKAHATI')->first();
        $gudangTamansari = Gudang::where('kode', 'TAMANSARI')->first();

        // ── 4 core users untuk testing ──
        $adminStore = User::where('username', 'adminstore')->first()
                        ?? User::where('username', 'superadmin')->first();
        $adminGU = User::where('username', 'admingu')->first()
                        ?? User::where('username', 'superadmin')->first();
        $recordingGU = User::where('username', 'recordinggu')->first()
                        ?? $adminGU;

        // Alias untuk Tamansari — fallback ke user Sukahati yang ada
        $adminSukahati1 = $adminGU;
        $adminSukahati2 = $recordingGU;
        $adminTamansari1 = $adminGU;
        $adminTamansari2 = $recordingGU;
        $recSukahati1 = $recordingGU;
        $recSukahati2 = $adminGU;
        $recTamansari1 = $recordingGU;
        $recTamansari2 = $adminGU;
        $adminStore2 = $adminStore;

        if (! $gudangStore || ! $adminStore) {
            return;
        }

        // ── Barang ──
        $barangBeras = Barang::where('kode', 'STR-004')->first() ?? Barang::first();
        $barangMinyak = Barang::where('kode', 'STR-005')->first() ?? Barang::first();
        $barangBumbu = Barang::where('kode', 'STR-006')->first() ?? Barang::first();
        $barangPlastik = Barang::where('kode', 'PL-012')->first() ?? Barang::first();
        $barangDeterjen = Barang::where('kode', 'STR-007')->first() ?? Barang::first();
        $barangSosis = Barang::where('kode', 'STR-003')->first() ?? Barang::first();
        $barangAir = Barang::where('kode', 'STR-008')->first() ?? Barang::first();
        $barangEtiket = Barang::where('kode', 'STR-009')->first() ?? Barang::first();
        $barangNugget = Barang::where('kode', 'STR-002')->first() ?? Barang::first();

        // ── Satuan ──
        $satuanPcs = Satuan::where('kode', 'PCS')->first() ?? Satuan::first();
        $satuanKg = Satuan::where('kode', 'KG')->first() ?? Satuan::first();
        $satuanPack = Satuan::where('kode', 'PACK')->first() ?? Satuan::first();
        $satuanRoll = Satuan::where('kode', 'ROLL')->first() ?? Satuan::first();

        // ══════════════════════════════════════════════════════════════
        // 1. STOK AWAL GUDANG STORE
        // ══════════════════════════════════════════════════════════════
        $stokItems = [
            ['barang' => $barangBeras,    'qty' => 500],
            ['barang' => $barangMinyak,   'qty' => 300],
            ['barang' => $barangBumbu,    'qty' => 450],
            ['barang' => $barangPlastik,  'qty' => 250],
            ['barang' => $barangDeterjen, 'qty' => 150],
            ['barang' => $barangSosis,    'qty' => 200],
            ['barang' => $barangAir,      'qty' => 600],
            ['barang' => $barangEtiket,   'qty' => 120],
            ['barang' => $barangNugget,   'qty' => 180],
        ];

        foreach ($stokItems as $sItem) {
            if ($sItem['barang']) {
                StokAwal::firstOrCreate(
                    ['gudang_id' => $gudangStore->id, 'barang_id' => $sItem['barang']->id],
                    [
                        'qty' => $sItem['qty'],
                        'tanggal_acuan' => now()->startOfMonth(),
                        'diinput_oleh' => $adminStore->id,
                        'sumber' => 'manual',
                    ]
                );
            }
        }

        // ══════════════════════════════════════════════════════════════
        // 2. PENERIMAAN SUPPLIER KE STORE
        // ══════════════════════════════════════════════════════════════
        PenerimaanBarangStore::firstOrCreate(
            ['nomor_penerimaan' => 'RCV-STR-202609-001'],
            [
                'gudang_id' => $gudangStore->id,
                'barang_id' => $barangPlastik?->id ?? $barangBeras->id,
                'qty_diterima' => 200,
                'satuan_id' => $barangPlastik?->satuan_id ?? $satuanRoll->id,
                'po_referensi' => 'PO-STORE-9921',
                'sumber_supplier' => 'PT Surya Cipta Packaging',
                'tanpa_surat_jalan' => false,
                'diterima_oleh' => $adminStore->id,
                'tanggal_diterima' => now()->subDays(3),
            ]
        );

        PenerimaanBarangStore::firstOrCreate(
            ['nomor_penerimaan' => 'RCV-STR-202609-002'],
            [
                'gudang_id' => $gudangStore->id,
                'barang_id' => $barangBumbu?->id ?? $barangBeras->id,
                'qty_diterima' => 300,
                'satuan_id' => $barangBumbu?->satuan_id ?? $satuanKg->id,
                'po_referensi' => 'PO-STORE-9925',
                'sumber_supplier' => 'CV Bumbu Nusantara',
                'tanpa_surat_jalan' => false,
                'diterima_oleh' => $adminStore2->id,
                'tanggal_diterima' => now()->subDays(1),
            ]
        );

        PenerimaanBarangStore::firstOrCreate(
            ['nomor_penerimaan' => 'RCV-STR-202609-003'],
            [
                'gudang_id' => $gudangStore->id,
                'barang_id' => $barangNugget?->id ?? $barangBeras->id,
                'qty_diterima' => 150,
                'satuan_id' => $barangNugget?->satuan_id ?? $satuanPack->id,
                'po_referensi' => 'PO-STORE-9930',
                'sumber_supplier' => 'PT Boga Prima Ritel',
                'tanpa_surat_jalan' => false,
                'diterima_oleh' => $adminStore->id,
                'tanggal_diterima' => now()->subHours(6),
            ]
        );

        // ══════════════════════════════════════════════════════════════
        // 3. PERMINTAAN DARI UNIT — beragam status & user sesuai unit
        // ══════════════════════════════════════════════════════════════

        // PR-1: REQ-SHT — Dibuat oleh Admin GU Sukahati (Agus)
        // Status: baru — belum diproses
        if ($gudangSukahati && $barangBeras && $barangMinyak) {
            $pr1 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-SHT-30092026822-0001'],
                [
                    'gudang_peminta_id' => $gudangSukahati->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'baru',
                    'catatan' => 'Kebutuhan beras & minyak goreng dapur utama operasional.',
                    'dibuat_oleh' => $adminSukahati1->id,
                    'created_at' => now()->subHours(4),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr1->id, 'barang_id' => $barangBeras->id],
                ['qty_diminta' => 20, 'qty_diproses' => 0, 'satuan_id' => $barangBeras->satuan_id]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr1->id, 'barang_id' => $barangMinyak->id],
                ['qty_diminta' => 15, 'qty_diproses' => 0, 'satuan_id' => $barangMinyak->satuan_id]
            );
        }

        // PR-2: REQ-SHT — Dibuat oleh Recording GU Sukahati (Budi)
        // Status: baru — belum diproses
        if ($gudangSukahati && $barangBumbu && $barangEtiket) {
            $pr2 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-SHT-30092026615-0002'],
                [
                    'gudang_peminta_id' => $gudangSukahati->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'baru',
                    'catatan' => 'Bumbu marinasi & etiket label untuk batch produksi pagi.',
                    'dibuat_oleh' => $recSukahati1->id,
                    'created_at' => now()->subHours(2),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr2->id, 'barang_id' => $barangBumbu->id],
                ['qty_diminta' => 25, 'qty_diproses' => 0, 'satuan_id' => $barangBumbu->satuan_id]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr2->id, 'barang_id' => $barangEtiket->id],
                ['qty_diminta' => 10, 'qty_diproses' => 0, 'satuan_id' => $barangEtiket->satuan_id]
            );
        }

        // PR-3: REQ-SHT — Dibuat oleh Recording GU Sukahati (Fitri)
        // Status: diproses — sebagian terpenuhi
        if ($gudangSukahati && $barangPlastik && $barangBumbu) {
            $pr3 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-SHT-30092026338-0003'],
                [
                    'gudang_peminta_id' => $gudangSukahati->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'diproses',
                    'catatan' => 'Stok kemasan vacuum & bumbu marinasi produksi.',
                    'dibuat_oleh' => $recSukahati2->id,
                    'created_at' => now()->subHours(12),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr3->id, 'barang_id' => $barangPlastik->id],
                ['qty_diminta' => 50, 'qty_diproses' => 25, 'satuan_id' => $barangPlastik->satuan_id]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr3->id, 'barang_id' => $barangBumbu->id],
                ['qty_diminta' => 30, 'qty_diproses' => 30, 'satuan_id' => $barangBumbu->satuan_id]
            );

            TransferBarang::firstOrCreate(
                ['nomor_sj' => 'OUT-20260930-001'],
                [
                    'permintaan_barang_id' => $pr3->id,
                    'gudang_asal_id' => $gudangStore->id,
                    'gudang_tujuan_id' => $gudangSukahati->id,
                    'barang_id' => $barangBumbu->id,
                    'qty_diminta' => 30,
                    'qty_dikirim' => 30,
                    'qty_diterima' => 0,
                    'status' => 'dikirim',
                    'supir_pengantar' => 'Rahmat (Driver Armada #02)',
                    'dikirim_oleh' => $adminStore->id,
                    'dikirim_at' => now()->subHours(2),
                ]
            );
        }

        // PR-4: REQ-TMS — Dibuat oleh Admin GU Tamansari (Dedi)
        // Status: baru — belum diproses
        if ($gudangTamansari && $barangDeterjen && $barangAir) {
            $pr4 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-TMS-30092026911-0001'],
                [
                    'gudang_peminta_id' => $gudangTamansari->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'baru',
                    'catatan' => 'Permintaan kebutuhan kebersihan & konsumsi area potong Tamansari.',
                    'dibuat_oleh' => $adminTamansari1->id,
                    'created_at' => now()->subHours(2),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr4->id, 'barang_id' => $barangDeterjen->id],
                ['qty_diminta' => 10, 'qty_diproses' => 0, 'satuan_id' => $barangDeterjen->satuan_id]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr4->id, 'barang_id' => $barangAir->id],
                ['qty_diminta' => 24, 'qty_diproses' => 0, 'satuan_id' => $barangAir->satuan_id]
            );
        }

        // PR-5: REQ-TMS — Dibuat oleh Recording GU Tamansari (Joko)
        // Status: baru
        if ($gudangTamansari && $barangNugget) {
            $pr5 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-TMS-30092026503-0002'],
                [
                    'gudang_peminta_id' => $gudangTamansari->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'baru',
                    'catatan' => 'Kebutuhan nugget untuk display outlet Tamansari.',
                    'dibuat_oleh' => $recTamansari1->id,
                    'created_at' => now()->subHour(),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr5->id, 'barang_id' => $barangNugget->id],
                ['qty_diminta' => 30, 'qty_diproses' => 0, 'satuan_id' => $barangNugget->satuan_id]
            );
        }

        // PR-6: REQ-SHT — Dibuat oleh Admin GU Sukahati (Wati)
        // Status: selesai — terpenuhi penuh
        if ($gudangSukahati && $barangAir) {
            $pr6 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-SHT-29092026744-0001'],
                [
                    'gudang_peminta_id' => $gudangSukahati->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'selesai',
                    'catatan' => 'Pasokan air minum konsumsi staf shift malam.',
                    'dibuat_oleh' => $adminSukahati2->id,
                    'created_at' => now()->subDays(1),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr6->id, 'barang_id' => $barangAir->id],
                ['qty_diminta' => 50, 'qty_diproses' => 50, 'satuan_id' => $barangAir->satuan_id]
            );
            TransferBarang::firstOrCreate(
                ['nomor_sj' => 'OUT-20260929-001'],
                [
                    'permintaan_barang_id' => $pr6->id,
                    'gudang_asal_id' => $gudangStore->id,
                    'gudang_tujuan_id' => $gudangSukahati->id,
                    'barang_id' => $barangAir->id,
                    'qty_diminta' => 50,
                    'qty_dikirim' => 50,
                    'qty_diterima' => 50,
                    'status' => 'diterima',
                    'supir_pengantar' => 'Budi (Kurir Internal)',
                    'dikirim_oleh' => $adminStore->id,
                    'diterima_oleh' => $adminSukahati1->id,
                    'dikirim_at' => now()->subDays(1),
                    'diterima_at' => now()->subHours(20),
                ]
            );
        }

        // PR-7: REQ-TMS — Dibuat oleh Recording GU Tamansari (Nurul)
        // Status: selesai
        if ($gudangTamansari && $barangMinyak) {
            $pr7 = PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-TMS-29092026281-0001'],
                [
                    'gudang_peminta_id' => $gudangTamansari->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'selesai',
                    'catatan' => 'Minyak goreng untuk kebutuhan pengolahan harian.',
                    'dibuat_oleh' => $recTamansari2->id,
                    'created_at' => now()->subDays(1)->subHours(3),
                ]
            );
            PermintaanBarangDetail::firstOrCreate(
                ['permintaan_barang_id' => $pr7->id, 'barang_id' => $barangMinyak->id],
                ['qty_diminta' => 20, 'qty_diproses' => 20, 'satuan_id' => $barangMinyak->satuan_id]
            );
            TransferBarang::firstOrCreate(
                ['nomor_sj' => 'OUT-20260929-002'],
                [
                    'permintaan_barang_id' => $pr7->id,
                    'gudang_asal_id' => $gudangStore->id,
                    'gudang_tujuan_id' => $gudangTamansari->id,
                    'barang_id' => $barangMinyak->id,
                    'qty_diminta' => 20,
                    'qty_dikirim' => 20,
                    'qty_diterima' => 20,
                    'status' => 'diterima',
                    'supir_pengantar' => 'Hartono (Driver Armada #03)',
                    'dikirim_oleh' => $adminStore2->id,
                    'diterima_oleh' => $adminTamansari1->id,
                    'dikirim_at' => now()->subDays(1),
                    'diterima_at' => now()->subDays(1)->addHours(3),
                ]
            );
        }

        // PR-8: REQ-SHT — Dibuat oleh Admin GU Sukahati (Agus)
        // Status: ditolak
        if ($gudangSukahati && $barangSosis) {
            PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-SHT-28092026112-0001'],
                [
                    'gudang_peminta_id' => $gudangSukahati->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'ditolak',
                    'catatan' => 'Stok sosis crispy untuk promo akhir bulan.',
                    'alasan_penolakan' => 'Stok sosis sedang di-quarantine oleh tim QC pabrik. Harap ajukan ulang pekan depan.',
                    'dibuat_oleh' => $adminSukahati1->id,
                    'created_at' => now()->subDays(2),
                ]
            );
        }

        // PR-9: REQ-TMS — Dibuat oleh Admin GU Tamansari (Sri)
        // Status: ditolak
        if ($gudangTamansari && $barangBeras) {
            PermintaanBarang::firstOrCreate(
                ['nomor_pr' => 'REQ-TMS-28092026445-0001'],
                [
                    'gudang_peminta_id' => $gudangTamansari->id,
                    'gudang_tujuan_id' => $gudangStore->id,
                    'status' => 'ditolak',
                    'catatan' => 'Beras untuk konsumsi karyawan shift malam.',
                    'alasan_penolakan' => 'Kuota beras bulan ini sudah terpenuhi. Ajukan di awal bulan berikutnya.',
                    'dibuat_oleh' => $adminTamansari2->id,
                    'created_at' => now()->subDays(2)->subHours(5),
                ]
            );
        }
    }
}
