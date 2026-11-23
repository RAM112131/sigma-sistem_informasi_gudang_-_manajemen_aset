<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VECTOR_TABLES = [
        'users',
        'gudangs',
        'audit_logs',
        'master_barangs',
        'barangs',
        'penerimaan_barang_stores',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $available = DB::selectOne(
            "SELECT CASE WHEN EXISTS (
                SELECT 1 FROM pg_available_extensions WHERE name = 'vector'
            ) THEN 1 ELSE 0 END AS available"
        );

        if ((int) ($available->available ?? 0) !== 1) {
            Log::warning('pgvector is not installed on this PostgreSQL server; semantic search will use text fallback.');

            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (QueryException $exception) {
            Log::warning('Unable to enable pgvector; semantic search will use text fallback.', [
                'exception' => $exception,
            ]);

            return;
        }

        foreach (self::VECTOR_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $column = DB::selectOne(
                'SELECT format_type(attribute.atttypid, attribute.atttypmod) AS type
                 FROM pg_attribute AS attribute
                 WHERE attribute.attrelid = to_regclass(?)
                   AND attribute.attname = ?
                   AND NOT attribute.attisdropped',
                [$table, 'embedding_search'],
            );

            if (($column->type ?? null) === 'vector(1536)') {
                continue;
            }

            if ($column) {
                DB::statement("ALTER TABLE {$table} DROP COLUMN embedding_search");
            }

            DB::statement("ALTER TABLE {$table} ADD COLUMN embedding_search vector(1536) NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            foreach (self::VECTOR_TABLES as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'embedding_search')) {
                    DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS embedding_search");
                }
            }
        }
    }
};
