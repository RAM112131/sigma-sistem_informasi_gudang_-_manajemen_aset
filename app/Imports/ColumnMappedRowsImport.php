<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ColumnMappedRowsImport implements Import, WithChunkReading, WithMultipleSheets
{
    private readonly ColumnMappedRowsSheetImport $sheetImport;

    /**
     * @param  array<string, int>  $mapping
     */
    public function __construct(
        int $sheetIndex,
        int $headingRow,
        array $mapping,
        private readonly int $maximumRows,
        bool $validateHeader = true,
        private readonly int $chunkSize = 1000,
    ) {
        $this->sheetImport = new ColumnMappedRowsSheetImport(
            $headingRow,
            $mapping,
            $maximumRows,
            $validateHeader,
        );
        $this->sheetIndex = $sheetIndex;
    }

    private readonly int $sheetIndex;

    public function sheets(): array
    {
        return [$this->sheetIndex => $this->sheetImport];
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->sheetImport->rows();
    }
}
