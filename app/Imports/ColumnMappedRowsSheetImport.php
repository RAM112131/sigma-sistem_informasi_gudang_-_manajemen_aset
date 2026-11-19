<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ColumnMappedRowsSheetImport implements ToCollection, WithChunkReading, WithColumnLimit, WithStartRow
{
    use RemembersChunkOffset;

    /**
     * @var list<array<string, mixed>>
     */
    private array $rows = [];

    private bool $headerValidated = false;

    /**
     * @param  array<string, int>  $mapping
     */
    public function __construct(
        private readonly int $headingRow,
        private readonly array $mapping,
        private readonly int $maximumRows,
        private readonly bool $validateHeader = true,
    ) {}

    public function collection(Collection $rows): void
    {
        $chunkOffset = $this->getChunkOffset() ?? $this->headingRow;

        foreach ($rows as $offset => $row) {
            $cells = array_values($row->all());
            $sourceRow = $chunkOffset + $offset;

            if ($sourceRow === $this->headingRow) {
                if ($this->validateHeader) {
                    $this->validateHeader($cells);
                }

                continue;
            }

            $mapped = [];

            foreach ($this->mapping as $field => $column) {
                $mapped[$field] = trim((string) ($cells[$column] ?? ''));
            }

            if (array_filter($mapped, static fn (string $value): bool => $value !== '') === []) {
                continue;
            }

            if (count($this->rows) >= $this->maximumRows) {
                throw ValidationException::withMessages([
                    'file' => "File maksimal berisi {$this->maximumRows} baris data.",
                ]);
            }

            $this->rows[] = [
                'line' => $sourceRow,
                ...$mapped,
            ];
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function startRow(): int
    {
        return $this->headingRow;
    }

    public function endColumn(): string
    {
        return Coordinate::stringFromColumnIndex(max($this->mapping ?: [0]) + 1);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function validateHeader(array $cells): void
    {
        if ($this->headerValidated) {
            return;
        }

        foreach ($this->mapping as $field => $column) {
            $header = $cells[$column] ?? null;
            if (! is_scalar($header) || trim((string) $header) === '') {
                throw ValidationException::withMessages([
                    'mapping.'.$field => 'Header untuk kolom terpilih wajib berisi nama kolom.',
                ]);
            }
        }

        $this->headerValidated = true;
    }
}
