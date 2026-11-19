<?php

declare(strict_types=1);

namespace App\Exports\AdminGu;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class WarehouseMonthlyReportExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    /** @var array<int, array<int, mixed>> */
    private array $rows;

    /** @var array<int, string> */
    private array $headings;

    /** @var array<int, int> */
    private array $numericColumns;

    /** @var array<int, int> */
    private array $moneyColumns;

    /** @var array<int, int> */
    private array $formulaColumns;

    /**
     * @param  array<string, mixed>  $report
     * @param  array<string, mixed>  $filters
     */
    public function __construct(array $report, array $filters)
    {
        [$this->headings, $rows, $this->numericColumns, $this->moneyColumns, $this->formulaColumns] = match ($filters['tab']) {
            'matriks' => $this->matrixRows($report, $filters['matrix_mode']),
            'pemasukan' => $this->inboundRows($report),
            'selisih' => $this->discrepancyRows($report),
            default => $this->mutationRows($report),
        };
        $this->rows = $this->withFormulaTotals($rows);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formats = [];
        foreach ($this->numericColumns as $column) {
            $formats[$this->columnLetter($column)] = in_array($column, $this->moneyColumns, true)
                ? '#,##0;[Red]-#,##0'
                : '#,##0.###;[Red]-#,##0.###';
        }

        return $formats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $totalRow = count($this->rows) + 1;

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '145C54'],
                ],
            ],
            $totalRow => [
                'font' => ['bold' => true],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{array<int, string>, array<int, array<int, mixed>>, array<int, int>, array<int, int>, array<int, int>}
     */
    private function mutationRows(array $report): array
    {
        $headings = ['Kode SKU', 'Nama Barang', 'Satuan', 'Kategori', 'Saldo Awal', 'Masuk Store', 'Masuk Beli Umum', 'Keluar Bersih', 'Selisih Opname', 'Saldo Akhir', 'HPP', 'Nilai Stok'];
        $rows = array_map(static fn (array $item): array => [
            $item['kode'], $item['nama'], $item['satuan'], $item['kategori'], $item['saldo_awal'],
            $item['masuk_store'], $item['masuk_beli_umum'], $item['keluar_bersih'], $item['selisih_opname'],
            $item['saldo_akhir'], $item['hpp'], $item['nilai_stok'],
        ], $report['items']);

        return [$headings, $rows, [5, 6, 7, 8, 9, 10, 11, 12], [11, 12], [5, 6, 7, 8, 9, 10, 12]];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{array<int, string>, array<int, array<int, mixed>>, array<int, int>, array<int, int>, array<int, int>}
     */
    private function matrixRows(array $report, string $mode): array
    {
        $headings = ['Kode SKU', 'Nama Barang', 'Satuan'];
        $numericColumns = [];
        $moneyColumns = [];
        foreach ($report['divisions'] as $division) {
            $headings[] = $division['nama'].' ('.($mode === 'qty' ? 'Qty' : 'Rp').')';
            $numericColumns[] = count($headings);
            if ($mode === 'rupiah') {
                $moneyColumns[] = count($headings);
            }
        }
        $headings[] = 'Total Pemakaian';
        $numericColumns[] = count($headings);
        $formulaColumns = $numericColumns;
        $headings[] = 'Total Biaya (Rp)';
        $numericColumns[] = count($headings);
        $moneyColumns[] = count($headings);
        $formulaColumns[] = count($headings);

        $rows = [];
        foreach ($report['items'] as $item) {
            $row = [$item['kode'], $item['nama'], $item['satuan']];
            foreach ($report['divisions'] as $division) {
                $row[] = $item['divisions'][$division['id']][$mode === 'qty' ? 'qty' : 'value'];
            }
            $row[] = $item['total_qty'];
            $row[] = $item['total_value'];
            $rows[] = $row;
        }

        return [$headings, $rows, $numericColumns, $moneyColumns, $formulaColumns];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{array<int, string>, array<int, array<int, mixed>>, array<int, int>, array<int, int>, array<int, int>}
     */
    private function inboundRows(array $report): array
    {
        $headings = ['No. Dokumen', 'Tanggal Verifikasi', 'Sumber / Nama Supplier', 'No. Nota / Surat Jalan', 'Jumlah SKU', 'Kondisi Fisik', 'Total Nilai Masuk (Rp)', 'Catatan Tindak Lanjut / Klaim'];
        $rows = array_map(static fn (array $document): array => [
            $document['document_number'],
            $document['verified_at'],
            $document['source_name'],
            $document['reference_number'],
            $document['sku_count'],
            $document['physical_condition'],
            $document['total_value'],
            $document['follow_up'],
        ], $report['documents']);

        return [$headings, $rows, [5, 7], [7], [5, 7]];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{array<int, string>, array<int, array<int, mixed>>, array<int, int>, array<int, int>, array<int, int>}
     */
    private function discrepancyRows(array $report): array
    {
        $headings = ['Jenis Data', 'Kode SKU', 'Nama Barang', 'Satuan', 'Saldo Sistem', 'Stok Fisik / Qty Rusak', 'Selisih Qty', 'HPP Satuan (Rp)', 'Nilai Dampak (Rp)', 'Catatan'];
        $rows = [];
        foreach ($report['opname_items'] as $item) {
            $rows[] = [
                'Selisih Opname', $item['kode'], $item['nama'], $item['satuan'], $item['saldo_sistem'],
                $item['stok_fisik'], $item['selisih_qty'], $item['hpp'], $item['selisih_nilai'], $item['catatan'],
            ];
        }
        foreach ($report['damaged_items'] as $item) {
            $rows[] = [
                $item['source_type'], $item['kode'], $item['nama'], $item['satuan'], null,
                $item['damaged_qty'], null, $item['hpp'], $item['impact_value'], $item['note'],
            ];
        }

        return [$headings, $rows, [5, 6, 7, 8, 9], [8, 9], [5, 6, 7, 9]];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, array<int, mixed>>
     */
    private function withFormulaTotals(array $rows): array
    {
        $totalRow = array_fill(0, count($this->headings), null);
        $totalRow[0] = 'GRAND TOTAL';
        $firstDataRow = 2;
        $lastDataRow = count($rows) + 1;

        foreach ($this->formulaColumns as $column) {
            $letter = $this->columnLetter($column);
            $totalRow[$column - 1] = $rows === []
                ? '=0'
                : "=SUM({$letter}{$firstDataRow}:{$letter}{$lastDataRow})";
        }

        $rows[] = $totalRow;

        return $rows;
    }

    private function columnLetter(int $column): string
    {
        $letter = '';
        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letter = chr(65 + $remainder).$letter;
            $column = intdiv($column - 1, 26);
        }

        return $letter;
    }
}
