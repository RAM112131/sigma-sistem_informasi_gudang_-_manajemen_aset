<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateVectorEmbedding;
use App\Models\AuditLog;
use App\Models\Barang;
use App\Models\MasterBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\User;
use App\Support\SemanticVectorSchema;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ReindexStoreSemanticSearch extends Command
{
    protected $signature = 'store:semantic-search-reindex {--chunk=100 : Jumlah record per batch}';

    protected $description = 'Buat embedding untuk log audit, user, master barang, barang, dan penerimaan yang belum terindeks.';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->info('Database bukan PostgreSQL; pencarian teks fallback tetap aktif dan tidak memerlukan reindex vector.');

            return self::SUCCESS;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $queued = 0;

        $targets = [
            AuditLog::class => ['audit_logs', ['causer']],
            User::class => ['users', ['roles', 'gudang']],
            MasterBarang::class => ['master_barangs', ['kategori', 'satuan']],
            Barang::class => ['barangs', ['kategori', 'satuan']],
            PenerimaanBarangStore::class => ['penerimaan_barang_stores', ['details.barang.kategori', 'details.satuan']],
        ];

        foreach ($targets as $modelClass => [$table, $relations]) {
            if (! SemanticVectorSchema::hasVectorColumn($table)) {
                $this->warn("Melewati {$table}: kolom vector tidak tersedia; pencarian teks fallback tetap aktif.");

                continue;
            }

            $this->queueMissingEmbeddings(
                $modelClass,
                $relations,
                $chunkSize,
                $queued,
            );
        }

        $this->info("Berhasil memasukkan {$queued} pekerjaan embedding ke antrean.");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, string>  $relations
     */
    private function queueMissingEmbeddings(
        string $modelClass,
        array $relations,
        int $chunkSize,
        int &$queued,
    ): void {
        $modelClass::query()
            ->with($relations)
            ->whereNull('embedding_search')
            ->chunkById($chunkSize, function ($models) use (&$queued): void {
                foreach ($models as $model) {
                    if (! method_exists($model, 'getSemanticText')) {
                        continue;
                    }

                    GenerateVectorEmbedding::dispatch($model, $model->getSemanticText());
                    $queued++;
                }
            });
    }
}
