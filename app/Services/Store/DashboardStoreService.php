<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanBarangStore;
use App\Models\PermintaanBarang;
use App\Models\TransferBarang;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class DashboardStoreService
{
    public function __construct(
        private readonly StokService $stokService,
    ) {}

    /**
     * @return array{
     *     stokKritis: Collection,
     *     jumlahStokKritis: int,
     *     permintaanBelumDiproses: Collection,
     *     jumlahPermintaanBelumDiproses: int,
     *     pemindahanBerjalan: Collection,
     *     jumlahPemindahanBerjalan: int,
     *     totalPenerimaanHariIni: int,
     *     totalPemindahanHariIni: int
     * }
     */
    public function getDashboardData(Gudang $gudangStore): array
    {
        $todayStart = today();
        $tomorrowStart = $todayStart->copy()->addDay();

        $summary = Cache::remember("dashboard_store_summary:{$gudangStore->id}", 30, function () use ($gudangStore, $todayStart, $tomorrowStart): array {
            $barangStore = Barang::query()
                ->where('gudang_id', $gudangStore->id)
                ->where('is_aktif', true)
                ->where('minimum_stok', '>', 0)
                ->get(['id', 'minimum_stok']);

            $totals = $this->stokService->totals($barangStore->modelKeys(), (int) $gudangStore->id);
            $levelStok = $barangStore->map(fn (Barang $barang): array => [
                'id' => (int) $barang->id,
                'minimum_stok' => (int) $barang->minimum_stok,
                'stok_aktual' => max(
                    0,
                    (int) ($totals['awal'][$barang->id] ?? 0)
                        + (int) ($totals['masuk'][$barang->id] ?? 0)
                        - (int) ($totals['keluar'][$barang->id] ?? 0),
                ),
            ]);

            $barangKritis = $levelStok
                ->filter(fn (array $level): bool => $level['stok_aktual'] <= $level['minimum_stok'])
                ->sort(fn (array $first, array $second): int => ($first['stok_aktual'] <=> $second['stok_aktual'])
                    ?: ($second['minimum_stok'] <=> $first['minimum_stok']))
                ->values();

            return [
                'jumlahStokKritis' => $barangKritis->count(),
                'kritisLevels' => $barangKritis->take(5)->all(),
                'jumlahPermintaanBelumDiproses' => PermintaanBarang::query()
                    ->where('status', 'baru')
                    ->where('gudang_tujuan_id', $gudangStore->id)
                    ->count(),
                'jumlahPemindahanBerjalan' => TransferBarang::query()
                    ->where('status', 'dikirim')
                    ->where('gudang_asal_id', $gudangStore->id)
                    ->count(),
                'totalPenerimaanHariIni' => PenerimaanBarangStore::query()
                    ->where('gudang_id', $gudangStore->id)
                    ->where('tanggal_diterima', '>=', $todayStart)
                    ->where('tanggal_diterima', '<', $tomorrowStart)
                    ->count(),
                'totalPemindahanHariIni' => TransferBarang::query()
                    ->where('gudang_asal_id', $gudangStore->id)
                    ->where('dikirim_at', '>=', $todayStart)
                    ->where('dikirim_at', '<', $tomorrowStart)
                    ->count(),
            ];
        });

        $barangKritisTerpilih = collect($summary['kritisLevels']);
        $stockLevelsById = $barangKritisTerpilih->keyBy('id');
        $stokKritis = $barangKritisTerpilih->isEmpty()
            ? collect()
            : Barang::query()
                ->with(['kategori:id,nama', 'satuan:id,nama'])
                ->whereKey($barangKritisTerpilih->pluck('id'))
                ->get(['id', 'kode', 'nama', 'minimum_stok', 'kategori_id', 'satuan_id'])
                ->map(function (Barang $barang) use ($stockLevelsById): Barang {
                    $level = $stockLevelsById->get($barang->id);
                    $barang->setAttribute('stok_aktual', $level['stok_aktual']);

                    return $barang;
                })
                ->values();

        return [
            'stokKritis' => $stokKritis,
            'jumlahStokKritis' => $summary['jumlahStokKritis'],
            'permintaanBelumDiproses' => $summary['jumlahPermintaanBelumDiproses'] === 0
                ? collect()
                : PermintaanBarang::query()
                    ->where('status', 'baru')
                    ->where('gudang_tujuan_id', $gudangStore->id)
                    ->select(['id', 'gudang_peminta_id', 'nomor_pr', 'created_at'])
                    ->with('gudangPeminta:id,nama')
                    ->withCount('details')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(),
            'jumlahPermintaanBelumDiproses' => $summary['jumlahPermintaanBelumDiproses'],
            'pemindahanBerjalan' => $summary['jumlahPemindahanBerjalan'] === 0
                ? collect()
                : TransferBarang::query()
                    ->where('status', 'dikirim')
                    ->where('gudang_asal_id', $gudangStore->id)
                    ->with(['gudangTujuan', 'barang'])
                    ->orderByDesc('dikirim_at')
                    ->limit(5)
                    ->get(),
            'jumlahPemindahanBerjalan' => $summary['jumlahPemindahanBerjalan'],
            'totalPenerimaanHariIni' => $summary['totalPenerimaanHariIni'],
            'totalPemindahanHariIni' => $summary['totalPemindahanHariIni'],
        ];
    }
}
