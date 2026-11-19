<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;
use RuntimeException;

final class MasterBarangRowsSheetImport implements OnEachRow, WithChunkReading
{
    private const array HEADINGS = ['kode_barang', 'nama_barang', 'kategori', 'satuan'];

    private const array REQUIRED_HEADINGS = ['kode_barang', 'nama_barang', 'kategori', 'satuan'];

    /** @var array<string, int> */
    private array $columnIndexes = [];

    private int $rowCount = 0;

    private ?int $headerRowIndex = null;

    /** @param resource $stream */
    public function __construct(private readonly mixed $stream)
    {
        if (! is_resource($stream)) {
            throw new RuntimeException('Penyimpanan sementara untuk membaca file Excel tidak tersedia.');
        }
    }

    public function onRow(Row $row): void
    {
        $cells = $row->toArray();

        if ($this->headerRowIndex === null) {
            $headerIndexes = $this->headerIndexes($cells);
            $missingHeadings = array_diff(self::REQUIRED_HEADINGS, array_keys($headerIndexes));
            $rowIndex = $row->getIndex();

            if ($rowIndex === 1 && $headerIndexes === []) {
                return;
            }

            if ($missingHeadings !== []) {
                throw ValidationException::withMessages([
                    'file' => 'Header sheet Master Barang harus memiliki kolom wajib: '.implode(', ', $missingHeadings).'.',
                ]);
            }

            $this->columnIndexes = $headerIndexes;
            $this->headerRowIndex = $rowIndex;

            return;
        }

        $values = [];

        foreach (self::HEADINGS as $heading) {
            $value = $cells[$this->columnIndexes[$heading] ?? -1] ?? '';
            $values[$heading] = is_scalar($value) ? trim((string) $value) : '';
        }

        if (count(array_filter($values, static fn (string $value): bool => $value !== '')) === 0) {
            return;
        }

        $record = [
            'line' => $row->getIndex(),
            'kode_barang' => $values['kode_barang'] ?? '',
            'nama_barang' => $values['nama_barang'] ?? '',
            'kategori' => $values['kategori'] ?? '',
            'satuan' => $values['satuan'] ?? '',
        ];

        $line = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE).PHP_EOL;
        $written = fwrite($this->stream, $line);

        if ($written !== strlen($line)) {
            throw new RuntimeException('Data dari file Excel tidak dapat disiapkan untuk ditinjau.');
        }

        $this->rowCount++;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * @param  array<int|string, mixed>  $cells
     * @return array<string, int>
     */
    private function headerIndexes(array $cells): array
    {
        $indexes = [];

        foreach ($cells as $index => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $heading = Str::slug(trim((string) $value), '_');

            if (! in_array($heading, self::HEADINGS, true)) {
                continue;
            }

            if (isset($indexes[$heading])) {
                throw ValidationException::withMessages([
                    'file' => "Header sheet Master Barang memiliki kolom “{$heading}” lebih dari satu kali.",
                ]);
            }

            $indexes[$heading] = (int) $index;
        }

        return $indexes;
    }

    public function rowCount(): int
    {
        return $this->rowCount;
    }
}
