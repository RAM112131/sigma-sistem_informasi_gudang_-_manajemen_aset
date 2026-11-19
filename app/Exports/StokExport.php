<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\StokHarianSheet;
use App\Models\Gudang;
use App\Services\Laporan\LaporanQueryService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class StokExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
        private readonly bool $hanyaPerluRestock = false,
    ) {}

    public function sheets(): array
    {
        $jumlahHari = app(LaporanQueryService::class)
            ->jumlahHariTersediaDalamPeriode($this->periode);

        return collect(range(1, $jumlahHari))
            ->map(fn (int $tanggal): StokHarianSheet => new StokHarianSheet(
                $this->gudang,
                $this->periode,
                $tanggal,
                $this->hanyaPerluRestock,
            ))
            ->all();
    }
}
