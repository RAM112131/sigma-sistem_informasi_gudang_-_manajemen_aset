<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->ensureVectorExtension()) {
            return;
        }

        $column = DB::selectOne(
            'SELECT format_type(attribute.atttypid, attribute.atttypmod) AS type
             FROM pg_attribute AS attribute
             WHERE attribute.attrelid = to_regclass(?)
               AND attribute.attname = ?
               AND NOT attribute.attisdropped',
            ['users', 'embedding_search'],
        );

        if (($column->type ?? null) === 'vector(1536)') {
            return;
        }

        if ($column) {
            DB::statement('ALTER TABLE users DROP COLUMN embedding_search');
        }

        DB::statement('ALTER TABLE users ADD COLUMN embedding_search vector(1536) NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS embedding_search');
        }
    }

    private function ensureVectorExtension(): bool
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return false;
        }

        $available = DB::selectOne(
            "SELECT CASE WHEN EXISTS (
                SELECT 1 FROM pg_available_extensions WHERE name = 'vector'
            ) THEN 1 ELSE 0 END AS available"
        );

        if ((int) ($available->available ?? 0) !== 1) {
            Log::warning('pgvector is not installed on this PostgreSQL server; semantic search will use text fallback.');

            return false;
        }

        $installed = DB::selectOne(
            "SELECT CASE WHEN EXISTS (
                SELECT 1 FROM pg_extension WHERE extname = 'vector'
            ) THEN 1 ELSE 0 END AS installed"
        );

        if ((int) ($installed->installed ?? 0) === 1) {
            return true;
        }

        try {
            DB::statement('CREATE EXTENSION vector');
        } catch (QueryException $exception) {
            Log::warning('Unable to enable pgvector; semantic search will use text fallback.', [
                'exception' => $exception,
            ]);

            return false;
        }

        return true;
    }
};
