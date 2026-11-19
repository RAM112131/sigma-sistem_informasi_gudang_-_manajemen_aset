<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Gudang;
use App\Services\Laporan\LaporanQueryService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class LaporanTransaksiHarianSheet implements FromQuery, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private int $baris = 0;

    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
        private readonly int $tanggal,
        private readonly string $jenis,
    ) {}

    public function title(): string
    {
        return (string) $this->tanggal;
    }

    public function query(): Builder
    {
        $service = app(LaporanQueryService::class);

        return match ($this->jenis) {
            'barang_masuk' => $service->barangMasuk($this->gudang, $this->periode, $this->tanggal),
            'barang_keluar' => $service->barangKeluar($this->gudang, $this->periode, $this->tanggal),
            'pembelian_tunai_transfer' => $service->pembelianTunaiTransfer($this->gudang, $this->periode, $this->tanggal),
        };
    }

    public function headings(): array
    {
        return [
            [$this->reportTitle()],
            ['Bulan: '.Carbon::parse($this->periode.'-01')->locale((string) config('app.locale'))->translatedFormat('F Y')],
            match ($this->jenis) {
                'barang_masuk' => ['No', 'Nomor Penerimaan', 'Kode Barang', 'Nama Barang', 'Qty', 'Satuan', 'Supplier', 'Harga Satuan', 'Diterima Oleh'],
                'barang_keluar' => ['No', 'No. Surat Jalan', 'Tanggal Kirim', 'Kode Barang', 'Nama Barang', 'Qty Dikirim', 'Satuan', 'Tujuan', 'Status', 'Dikirim Oleh'],
                'pembelian_tunai_transfer' => ['No', 'Nomor Penerimaan', 'Kode Barang', 'Nama Barang', 'Qty', 'Satuan', 'Supplier', 'Metode Pembayaran', 'Harga Satuan', 'Total', 'Diterima Oleh'],
            },
        ];
    }

    private function reportTitle(): string
    {
        return match ($this->jenis) {
            'barang_masuk' => 'LAPORAN BARANG MASUK',
            'barang_keluar' => 'LAPORAN BARANG KELUAR',
            'pembelian_tunai_transfer' => 'LAPORAN PEMBELIAN TUNAI/TRANSFER',
        };
    }

    public function map(mixed $row): array
    {
        $nomor = ++$this->baris;

        return match ($this->jenis) {
            'barang_masuk' => [
                $nomor,
                $row->nomor_dokumen,
                $row->kode_barang,
                $row->nama_barang,
                $row->qty,
                $row->satuan,
                $row->sumber,
                $row->harga_satuan,
                $row->diterima_oleh ?? '-',
            ],
            'barang_keluar' => [
                $nomor,
                $row->nomor_sj,
                $row->dikirim_at,
                $row->kode_barang,
                $row->nama_barang,
                $row->qty_dikirim,
                $row->satuan,
                $row->gudang_tujuan ?? '-',
                $row->status,
                $row->dikirim_oleh ?? '-',
            ],
            'pembelian_tunai_transfer' => [
                $nomor,
                $row->nomor_dokumen,
                $row->kode_barang,
                $row->nama_barang,
                $row->qty,
                $row->satuan,
                $row->sumber,
                $row->metode_pembayaran,
                $row->harga_satuan,
                $row->total_harga,
                $row->diterima_oleh ?? '-',
            ],
        };
    }

    public function columnWidths(): array
    {
        return match ($this->jenis) {
            'barang_masuk' => ['A' => 6, 'B' => 24, 'C' => 16, 'D' => 32, 'E' => 12, 'F' => 14, 'G' => 28, 'H' => 18, 'I' => 24],
            'barang_keluar' => ['A' => 6, 'B' => 24, 'C' => 22, 'D' => 16, 'E' => 32, 'F' => 14, 'G' => 14, 'H' => 28, 'I' => 16, 'J' => 24],
            'pembelian_tunai_transfer' => ['A' => 6, 'B' => 24, 'C' => 16, 'D' => 32, 'E' => 12, 'F' => 14, 'G' => 28, 'H' => 22, 'I' => 18, 'J' => 18, 'K' => 24],
        };
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = match ($this->jenis) {
            'barang_masuk' => 'I',
            'barang_keluar' => 'J',
            'pembelian_tunai_transfer' => 'K',
        };
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '145C54']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '145C54']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A3:{$lastColumn}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '145C54']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('145C54');
        $sheet->freezePane('A4');

        if ($this->jenis === 'barang_masuk') {
            $sheet->getStyle("H4:H{$lastRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        if ($this->jenis === 'pembelian_tunai_transfer') {
            $sheet->getStyle("I4:J{$lastRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        if ($this->jenis === 'barang_keluar') {
            $sheet->getStyle("C4:C{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        }

        return [];
    }

    public function registerEvents(): array
    {
        if ($this->jenis !== 'pembelian_tunai_transfer') {
            return [];
        }

        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = $sheet->getHighestRow();
                $totalRow = $lastDataRow + 1;

                $sheet->setCellValue("A{$totalRow}", 'TOTAL PEMBELIAN');
                $sheet->setCellValue(
                    "J{$totalRow}",
                    $lastDataRow > 3 ? "=SUM(J4:J{$lastDataRow})" : 0,
                );
                $sheet->getStyle("A{$totalRow}:K{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3F7A52']],
                ]);
                $sheet->getStyle("J{$totalRow}")->getNumberFormat()->setFormatCode('"Rp" #,##0');
            },
        ];
    }
}
