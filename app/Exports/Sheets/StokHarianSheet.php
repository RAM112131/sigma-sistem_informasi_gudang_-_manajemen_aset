<?php

declare(strict_types=1);

namespace App\Exports\Sheets;

use App\Models\Gudang;
use App\Services\Laporan\LaporanQueryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

final class StokHarianSheet implements FromQuery, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly Gudang $gudang,
        private readonly string $periode,
        private readonly int $tanggal,
        private readonly bool $hanyaPerluRestock,
    ) {}

    public function title(): string
    {
        return (string) $this->tanggal;
    }

    public function query(): Builder
    {
        return app(LaporanQueryService::class)->stokPerTanggalQuery(
            $this->gudang,
            $this->periode,
            $this->tanggal,
            $this->hanyaPerluRestock,
        );
    }

    public function headings(): array
    {
        return [
            ['LAPORAN STOK'],
            ['Bulan: '.Carbon::parse($this->periode.'-01')->locale((string) config('app.locale'))->translatedFormat('F Y')],
            ['Kode Barang', 'Nama Barang', 'Kategori', 'Saldo', 'Satuan', 'Ambang Minimum', 'Status'],
        ];
    }

    public function map(mixed $row): array
    {
        return [
            $row->barang->kode,
            $row->barang->nama,
            $row->barang->kategori?->nama ?? 'Umum',
            $row->saldo,
            $row->barang->satuan?->nama ?? 'unit',
            $row->barang->minimum_stok,
            $row->di_bawah_minimum ? 'Perlu Restock' : 'Aman',
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 34, 'C' => 24, 'D' => 14, 'E' => 16, 'F' => 20, 'G' => 22];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '145C54']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '145C54']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3:G3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '145C54']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle("A3:G{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('145C54');
        $sheet->getStyle("D4:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->freezePane('A4');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                for ($row = 4; $row <= $sheet->getHighestRow(); $row++) {
                    if ($sheet->getCell("G{$row}")->getValue() === 'Perlu Restock') {
                        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B8791A']],
                        ]);
                    }
                }
            },
        ];
    }
}
