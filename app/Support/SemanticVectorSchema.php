<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SemanticVectorSchema
{
    private const TABLES = [
        'audit_logs',
        'barangs',
        'gudangs',
        'master_barangs',
        'penerimaan_barang_stores',
        'users',
    ];

    public static function hasVectorColumn(string $table): bool
    {
        if (! in_array($table, self::TABLES, true) || DB::connection()->getDriverName() !== 'pgsql') {
            return false;
        }

        static $columnAvailability = [];
        $cacheKey = DB::getDefaultConnection().':'.$table;

        if (array_key_exists($cacheKey, $columnAvailability)) {
            return $columnAvailability[$cacheKey];
        }

        $column = DB::selectOne(
            'SELECT format_type(attribute.atttypid, attribute.atttypmod) AS type
             FROM pg_attribute AS attribute
             WHERE attribute.attrelid = to_regclass(?)
               AND attribute.attname = ?
               AND NOT attribute.attisdropped',
            [$table, 'embedding_search'],
        );

        return $columnAvailability[$cacheKey] = ($column->type ?? null) === 'vector(1536)';
    }
}
