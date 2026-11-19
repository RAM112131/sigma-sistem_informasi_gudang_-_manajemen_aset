<?php

declare(strict_types=1);

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class MasterBarangRowsImport implements Import, WithMultipleSheets
{
    public const string SHEET_NAME = 'Master Barang';

    private readonly MasterBarangRowsSheetImport $dataSheet;

    /** @param resource $stream */
    public function __construct(mixed $stream, private readonly string $sheetName)
    {
        $this->dataSheet = new MasterBarangRowsSheetImport($stream);
    }

    /** @return array<string, Import> */
    public function sheets(): array
    {
        return [$this->sheetName => $this->dataSheet];
    }

    public function rowCount(): int
    {
        return $this->dataSheet->rowCount();
    }
}
