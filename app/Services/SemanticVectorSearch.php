<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SemanticVectorSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SemanticVectorSearch
{
    /**
     * @var array<string, array<int, string>>
     */
    private const FALLBACK_COLUMNS = [
        'users' => ['name', 'username'],
        'barangs' => ['kode', 'nama', 'deskripsi'],
        'master_barangs' => ['kode', 'nama', 'deskripsi'],
        'penerimaan_barang_stores' => ['nomor_penerimaan', 'sumber_supplier', 'catatan', 'catatan_rekap'],
        'audit_logs' => ['action', 'description', 'ip_address'],
    ];

    public function __construct(private readonly EmbeddingGenerator $embeddingGenerator) {}

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function apply(Builder $query, string $table, string $search, string $inputKey = 'q'): Builder
    {
        if (! isset(self::FALLBACK_COLUMNS[$table])) {
            throw new \InvalidArgumentException('Tabel tidak diizinkan untuk pencarian vector.');
        }

        $search = trim($search);
        if ($search === '') {
            return $query;
        }

        $provider = (string) config('semantic_search.provider', 'openai');
        if ($provider === 'openai' && empty(config('ai.providers.openai.key')) && empty(env('OPENAI_API_KEY'))) {
            return $this->applyTextSearch($query, $table, $search);
        }

        if (! SemanticVectorSchema::hasVectorColumn($table)) {
            return $this->applyTextSearch($query, $table, $search);
        }

        try {
            $embedding = $this->embeddingGenerator->generateVector($search);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->applyTextSearch($query, $table, $search);
        }

        $column = $table.'.embedding_search';

        if ($table === 'audit_logs') {
            $pattern = '%'.$search.'%';
            $query->where(function (Builder $matching) use ($column, $pattern): void {
                $matching->whereNotNull($column)
                    ->orWhere('action', 'like', $pattern)
                    ->orWhere('description', 'like', $pattern)
                    ->orWhere('ip_address', 'like', $pattern)
                    ->orWhereRaw('CAST(changes AS TEXT) LIKE ?', [$pattern]);
            })->orderByRaw(
                'CASE WHEN action LIKE ? OR description LIKE ? OR ip_address LIKE ? OR CAST(changes AS TEXT) LIKE ? THEN 0 ELSE 1 END',
                [$pattern, $pattern, $pattern, $pattern],
            );
        } elseif ($table === 'users') {
            $threshold = (float) config('semantic_search.distance_threshold', 0.65);
            $query->where(function (Builder $matching) use ($column, $embedding, $threshold, $table, $search): void {
                $matching->where(function (Builder $vectorQuery) use ($column, $embedding, $threshold): void {
                    $vectorQuery->whereNotNull($column)
                        ->whereRaw("({$column} <=> ?::vector) <= ?", [$embedding, $threshold]);
                })->orWhere(fn (Builder $unindexed) => $this->applyTextSearch($unindexed, $table, $search));
            });
        } else {
            $query->where(function (Builder $matching) use ($column, $search, $table): void {
                $matching->whereNotNull($column)
                    ->orWhere(fn (Builder $unindexed) => $this->applyTextSearch($unindexed, $table, $search));
            });
        }

        return $query->whereVectorSimilarTo($column, $embedding);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function applyTextSearch(Builder $query, string $table, string $search): Builder
    {
        $pattern = '%'.$search.'%';
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return $query->where(function (Builder $matching) use ($table, $pattern, $operator): void {
            foreach (self::FALLBACK_COLUMNS[$table] as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $matching->{$method}($column, $operator, $pattern);
            }

            if ($table === 'users') {
                $matching->orWhereHas('roles', static fn (Builder $roles) => $roles->where('name', $operator, $pattern))
                    ->orWhereHas('gudang', static fn (Builder $gudangs) => $gudangs->where('nama', $operator, $pattern));
            }

            if (in_array($table, ['barangs', 'master_barangs'], true)) {
                $matching->orWhereHas('kategori', fn (Builder $kategori) => $kategori->where('nama', $operator, $pattern))
                    ->orWhereHas('satuan', fn (Builder $satuan) => $satuan->where('nama', $operator, $pattern));
            }

            if ($table === 'penerimaan_barang_stores') {
                $matching->orWhereHas('details.barang', function (Builder $barang) use ($pattern, $operator): void {
                    $barang->where('nama', $operator, $pattern)
                        ->orWhere('kode', $operator, $pattern);
                });
            }

            if ($table === 'audit_logs') {
                $matching->orWhereHas('causer', function (Builder $causer) use ($pattern, $operator): void {
                    $causer->where('name', $operator, $pattern)
                        ->orWhere('username', $operator, $pattern);
                })->orWhereRaw('CAST(changes AS TEXT) LIKE ?', [$pattern]);
            }
        });
    }
}
