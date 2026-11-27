<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\DivisiPeminta;
use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\PengembalianUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordingPengembalianService
{
    /**
     * @return array<int, array{name: string, label: string, sortOrder: int}>
     */
    public function returnDepartmentOptionsForGudang(Gudang $activeGudang): array
    {
        $transactions = PengeluaranUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->with(['details'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $allDepts = DivisiPeminta::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->where('is_aktif', true)
            ->pluck('nama')
            ->merge($transactions->pluck('bagian_peminta'))
            ->filter(static fn (?string $name): bool => trim((string) $name) !== '')
            ->unique(static fn (string $name): string => mb_strtolower(trim($name)))
            ->sort()
            ->values();

        $options = [];

        foreach ($allDepts as $deptName) {
            $deptKey = mb_strtolower(trim($deptName));
            $matchingTxs = $transactions->filter(
                static fn (PengeluaranUnit $tx): bool => mb_strtolower(trim((string) $tx->bagian_peminta)) === $deptKey,
            );
            $openItemsCount = $matchingTxs
                ->flatMap(static fn (PengeluaranUnit $tx) => $tx->details)
                ->filter(static fn (PengeluaranUnitDetail $detail): bool => (float) $detail->qty > (float) $detail->qty_kembali)
                ->count();

            $options[] = [
                'name' => $deptName,
                'label' => $openItemsCount > 0
                    ? "{$deptName} — {$openItemsCount} baris belum dikembalikan"
                    : "{$deptName} — tidak ada sisa barang",
                'sortOrder' => $openItemsCount > 0 ? 1 : 2,
            ];
        }

        usort($options, static function (array $a, array $b): int {
            if ($a['sortOrder'] !== $b['sortOrder']) {
                return $a['sortOrder'] <=> $b['sortOrder'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $options;
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     category: string,
     *     unit: string,
     *     totalTaken: float,
     *     totalReturned: float,
     *     availableToReturn: float,
     *     finalUsage: float,
     *     hpp: float
     * }>
     */
    public function borrowedItemsForGudang(Gudang $activeGudang, string $department): array
    {
        $deptKey = mb_strtolower(trim($department));
        if ($deptKey === '') {
            return [];
        }

        $transactions = PengeluaranUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->whereRaw('LOWER(bagian_peminta) = ?', [$deptKey])
            ->with(['details.barang'])
            ->orderByDesc('tanggal')
            ->get();

        $itemsMap = [];

        foreach ($transactions as $tx) {
            foreach ($tx->details as $detail) {
                $itemName = $detail->nama_barang;
                if (! isset($itemsMap[$itemName])) {
                    $itemsMap[$itemName] = [
                        'name' => $itemName,
                        'category' => $detail->kategori ?: 'Umum',
                        'unit' => $detail->satuan,
                        'totalTaken' => 0.0,
                        'totalReturned' => 0.0,
                        'hpp' => (float) ($detail->harga_hpp > 0 ? $detail->harga_hpp : ($detail->barang?->harga_satuan ?? 0)),
                        'detailIds' => [],
                    ];
                }

                $itemsMap[$itemName]['totalTaken'] = round($itemsMap[$itemName]['totalTaken'] + (float) $detail->qty, 3);
                $itemsMap[$itemName]['totalReturned'] = round($itemsMap[$itemName]['totalReturned'] + (float) $detail->qty_kembali, 3);
                $itemsMap[$itemName]['detailIds'][] = (int) $detail->id;
            }
        }

        $result = [];
        foreach ($itemsMap as $entry) {
            $available = max(0.0, round($entry['totalTaken'] - $entry['totalReturned'], 3));
            if ($available === 0.0) {
                continue;
            }

            $result[] = [
                'name' => $entry['name'],
                'category' => $entry['category'],
                'unit' => $entry['unit'],
                'totalTaken' => $entry['totalTaken'],
                'totalReturned' => $entry['totalReturned'],
                'availableToReturn' => $available,
                'finalUsage' => $available,
                'hpp' => $entry['hpp'],
            ];
        }

        return $result;
    }

    /**
     * @param array{
     *     bagian_peminta: string,
     *     tanggal_kembali: string,
     *     items: array<int, array{name: string, qty_kembali: float|int|string, catatan?: ?string}>
     * } $validated
     * @param  callable(PengeluaranUnit): array<string, mixed>  $mapIssueRecord
     * @return array<string, mixed>
     */
    public function storeForGudang(
        User $user,
        Gudang $activeGudang,
        array $validated,
        callable $mapIssueRecord,
    ): array {
        $department = trim($validated['bagian_peminta']);
        $deptKey = mb_strtolower($department);
        $tanggalKembali = $validated['tanggal_kembali'];

        return DB::transaction(function () use ($activeGudang, $deptKey, $department, $tanggalKembali, $validated, $user, $mapIssueRecord): array {
            $transactions = PengeluaranUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->whereRaw('LOWER(bagian_peminta) = ?', [$deptKey])
                ->with(['details.barang'])
                ->lockForUpdate()
                ->orderByDesc('tanggal')
                ->get();

            if ($transactions->isEmpty()) {
                throw ValidationException::withMessages([
                    'bagian_peminta' => 'Tidak ditemukan riwayat pengeluaran untuk bagian peminta tersebut.',
                ]);
            }

            $summary = [];

            foreach ($validated['items'] as $item) {
                $itemName = trim((string) $item['name']);
                $qtyToReturn = round((float) $item['qty_kembali'], 3);
                if ($qtyToReturn <= 0) {
                    continue;
                }

                $remainingToReturn = $qtyToReturn;

                foreach ($transactions as $tx) {
                    if ($remainingToReturn <= 0) {
                        break;
                    }

                    $details = $tx->details->filter(
                        static fn (PengeluaranUnitDetail $d): bool => strcasecmp(trim($d->nama_barang), $itemName) === 0,
                    );

                    foreach ($details as $detail) {
                        if ($remainingToReturn <= 0) {
                            break;
                        }

                        $availableOnDetail = max(0.0, round((float) $detail->qty - (float) $detail->qty_kembali, 3));
                        if ($availableOnDetail <= 0) {
                            continue;
                        }

                        $allocated = min($availableOnDetail, $remainingToReturn);

                        if (in_array($tx->status_verifikasi, ['terverifikasi', 'sesuai'], true)) {
                            $tx->update([
                                'status_verifikasi' => 'menunggu_verifikasi',
                                'diverifikasi_oleh' => null,
                                'diverifikasi_at' => null,
                                'catatan_verifikasi' => null,
                            ]);

                            foreach ($tx->details as $transactionDetail) {
                                $transactionDetail->update([
                                    'qty_keluar_rekonsiliasi' => null,
                                    'qty_kembali_rekonsiliasi' => null,
                                ]);
                            }

                            $tx->setAttribute('status_verifikasi', 'menunggu_verifikasi');
                        }

                        $newQtyKembali = round((float) $detail->qty_kembali + $allocated, 3);
                        $pemakaianRiil = max(0.0, round((float) $detail->qty - $newQtyKembali, 3));
                        $hpp = (float) $detail->harga_hpp;
                        if ($hpp <= 0) {
                            $hpp = (float) ($detail->barang?->harga_satuan ?? 0);
                        }
                        $newSubtotal = round($pemakaianRiil * $hpp, 2);

                        $detail->update([
                            'qty_kembali' => $newQtyKembali,
                            'qty_keluar_rekonsiliasi' => null,
                            'qty_kembali_rekonsiliasi' => null,
                            'harga_hpp' => $hpp,
                            'subtotal_nilai' => $newSubtotal,
                        ]);

                        PengembalianUnit::create([
                            'gudang_id' => $activeGudang->getKey(),
                            'pengeluaran_unit_id' => $tx->getKey(),
                            'pengeluaran_unit_detail_id' => $detail->getKey(),
                            'barang_id' => $detail->barang_id,
                            'bagian_peminta' => $department,
                            'nama_barang' => $itemName,
                            'satuan' => $detail->satuan,
                            'qty_kembali' => $allocated,
                            'tanggal_kembali' => $tanggalKembali,
                            'catatan' => $item['catatan'] ?? "Sisa pengembalian {$department} (BPB: {$tx->nomor_bpb})",
                            'dicatat_oleh' => $user->getKey(),
                        ]);

                        $remainingToReturn = round($remainingToReturn - $allocated, 3);
                    }
                }

                if ($remainingToReturn > 0) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah pengembalian {$itemName} melebihi sisa barang yang masih tercatat.",
                    ]);
                }

                $summary[] = "{$itemName} (+{$qtyToReturn})";
            }

            if ($summary === []) {
                throw ValidationException::withMessages([
                    'items' => 'Masukkan angka pengembalian lebih dari 0.',
                ]);
            }

            $updatedTransactions = PengeluaranUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->with(['details.barang', 'pencatat', 'verifier'])
                ->orderByDesc('tanggal')
                ->orderByDesc('waktu_keluar')
                ->limit(100)
                ->get()
                ->map($mapIssueRecord)
                ->all();

            return [
                'message' => 'Pengembalian sisa barang berhasil dicatat dan stok gudang telah diperbarui: '.implode(', ', $summary),
                'summary' => $summary,
                'transactions' => $updatedTransactions,
                'borrowedItems' => $this->borrowedItemsForGudang($activeGudang, $department),
                'departmentOptions' => $this->returnDepartmentOptionsForGudang($activeGudang),
                'returnRecords' => $this->getRecentReturns($activeGudang),
            ];
        }, attempts: 3);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentReturns(?Gudang $activeGudang): array
    {
        if ($activeGudang === null) {
            return [];
        }

        return PengembalianUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->with(['pencatat', 'pengeluaranUnit'])
            ->orderByDesc('tanggal_kembali')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(static fn (PengembalianUnit $r): array => [
                'id' => (string) $r->id,
                'transactionId' => (int) $r->pengeluaran_unit_id,
                'department' => $r->bagian_peminta,
                'itemName' => $r->nama_barang,
                'qty' => (float) $r->qty_kembali,
                'unit' => $r->satuan,
                'condition' => 'Layak pakai (kembali)',
                'returnedAt' => $r->tanggal_kembali->format('Y-m-d').'T12:00:00.000Z',
                'recordedAt' => $r->created_at?->toIso8601String(),
                'note' => $r->catatan,
                'clerk' => $r->pencatat?->name ?? 'Recording GU',
            ])
            ->all();
    }
}
