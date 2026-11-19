<?php

declare(strict_types=1);

namespace App\Exports\MasterData;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class MasterBarangTemplateDataSheet implements FromArray, WithColumnWidths, WithEvents, WithFreezePane, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['Panduan: isi data mulai baris 3. Header wajib: kode_barang, nama_barang, kategori, satuan. Urutan bebas.'],
            ['kode_barang', 'nama_barang', 'kategori', 'satuan'],
        ];
    }

    public function title(): string
    {
        return 'Master Barang';
    }

    public function freezePane(): string
    {
        return 'A3';
    }

    /** @return array<string, float> */
    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 36,
            'C' => 25,
            'D' => 16,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '344054']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E8F1EF']],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '145C54']],
                'alignment' => ['vertical' => 'center'],
            ],
        ];
    }

    /** @return array<string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => static function (AfterSheet $event): void {
                $event->sheet->getDelegate()->mergeCells('A1:D1');
                $event->sheet->setAutoFilter('A2:D2');
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(36);
                $event->sheet->getDelegate()->getRowDimension(2)->setRowHeight(28);
            },
        ];
    }
}
