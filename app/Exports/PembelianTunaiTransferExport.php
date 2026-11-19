<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\LaporanTransaksiHarianSheet;
use App\Models\Gudang;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class PembelianTunaiTransferExport implements Export, WithMultipleSheets
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
    ) {}

    public function sheets(): array
    {
        $jumlahHari = Carbon::parse($this->periode.'-01')->daysInMonth;

        return collect(range(1, $jumlahHari))
            ->map(fn (int $tanggal): LaporanTransaksiHarianSheet => new LaporanTransaksiHarianSheet(
                $this->gudang,
                $this->periode,
                $tanggal,
                'pembelian_tunai_transfer',
            ))
            ->all();
    }
}
