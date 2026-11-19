<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DivisiPeminta;
use App\Models\Gudang;
use Illuminate\Database\Seeder;

class DivisiPemintaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gudangs = Gudang::query()
            ->whereIn('jenis', ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'])
            ->get();

        $divisiList = [
            ['kode' => 'JER', 'nama' => 'Area Jeroan', 'pj' => 'Supervisor Jeroan'],
            ['kode' => 'BON', 'nama' => 'Boneless', 'pj' => 'Supervisor Boneless'],
            ['kode' => 'PNG', 'nama' => 'Pengarungan', 'pj' => 'Koordinator Pengarungan'],
            ['kode' => 'PMA', 'nama' => 'Parman A', 'pj' => 'Mandor Parman A'],
            ['kode' => 'PMB', 'nama' => 'Parman B', 'pj' => 'Mandor Parman B'],
            ['kode' => 'PMC', 'nama' => 'Parman C', 'pj' => 'Mandor Parman C'],
            ['kode' => 'PMD', 'nama' => 'Parman D', 'pj' => 'Mandor Parman D'],
            ['kode' => 'PME', 'nama' => 'Parman E', 'pj' => 'Mandor Parman E'],
            ['kode' => 'PMF', 'nama' => 'Parman F', 'pj' => 'Mandor Parman F'],
            ['kode' => 'GDF', 'nama' => 'Gudang Fresh', 'pj' => 'Admin Gudang Fresh'],
            ['kode' => 'GDZ', 'nama' => 'Gudang Frozen', 'pj' => 'Admin Gudang Frozen'],
            ['kode' => 'TMS', 'nama' => 'TMS', 'pj' => 'Koordinator TMS'],
            ['kode' => 'WHL', 'nama' => 'Whole', 'pj' => 'Koordinator Whole'],
            ['kode' => 'KEU', 'nama' => 'Keuangan', 'pj' => 'Staff Keuangan'],
            ['kode' => 'FAK', 'nama' => 'Fakturis', 'pj' => 'Staff Fakturis'],
            ['kode' => 'KLA', 'nama' => 'Klasifikasi', 'pj' => 'Staff Klasifikasi'],
            ['kode' => 'KTR', 'nama' => 'Area Kotor', 'pj' => 'Mandor Area Kotor'],
            ['kode' => 'PRD', 'nama' => 'Produksi', 'pj' => 'Supervisor Produksi'],
            ['kode' => 'SAN', 'nama' => 'Sanitasi/Kebersihan', 'pj' => 'Koordinator Sanitasi'],
            ['kode' => 'PGR', 'nama' => 'Pengairan', 'pj' => 'Teknisi Pengairan/WWTP'],
            ['kode' => 'KGA', 'nama' => 'Kordinator GA', 'pj' => 'Koordinator GA'],
            ['kode' => 'TEK', 'nama' => 'Teknisi', 'pj' => 'Head Maintenance'],
            ['kode' => 'SEC', 'nama' => 'Satpam', 'pj' => 'Danru Security'],
            ['kode' => 'LOD', 'nama' => 'Loading', 'pj' => 'Mandor Loading'],
            ['kode' => 'USS', 'nama' => 'Usus', 'pj' => 'Mandor Usus'],
            ['kode' => 'KRK', 'nama' => 'Karkas', 'pj' => 'Mandor Karkas'],
            ['kode' => 'RPA', 'nama' => 'RPA', 'pj' => 'Kepala Unit RPA'],
        ];

        foreach ($gudangs as $gudang) {
            foreach ($divisiList as $item) {
                DivisiPeminta::query()->updateOrCreate(
                    [
                        'gudang_id' => $gudang->id,
                        'nama' => $item['nama'],
                    ],
                    [
                        'kode' => $item['kode'],
                        'penanggung_jawab' => $item['pj'],
                        'is_aktif' => true,
                    ]
                );
            }
        }
    }
}
