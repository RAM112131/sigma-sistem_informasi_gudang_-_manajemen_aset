<?php

declare(strict_types=1);

namespace App\Exports\UnitBisnis;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class UnitStockImportTemplateExport implements FromArray, WithColumnWidths, WithEvents, WithFreezePane, WithStyles, WithTitle
{
    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'qty', 'harga_satuan', 'minimum_stok', 'catatan'],
        ];
    }

    public function title(): string
    {
        return 'Import Stok Awal';
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    /**
     * @return array<string, float>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 34,
            'C' => 24,
            'D' => 14,
            'E' => 14,
            'F' => 16,
            'G' => 16,
            'H' => 30,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '145C54']],
                'alignment' => ['vertical' => 'center'],
            ],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => static function (AfterSheet $event): void {
                $event->sheet->setAutoFilter('A1:H1');
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}

