<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits(): array
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::setDefaultConnection('sqlite');
        $connection = DB::connection('sqlite');
        $pdo = $connection->getPdo();
        static $connectionWithSqliteCompatibility = null;
        if ($connectionWithSqliteCompatibility !== $connection) {
            $connectionWithSqliteCompatibility = $connection;
            $connection->beforeExecuting(static function (string &$query): void {
                $query = preg_replace(
                    '/\b([A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)?)::date\b/i',
                    'date($1)',
                    $query,
                ) ?? $query;
                $query = preg_replace(
                    '/CAST\(\s*receipts\.tanggal_terima\s+AS\s+TEXT\s*\)/i',
                    'date(receipts.tanggal_terima)',
                    $query,
                ) ?? $query;
                $query = preg_replace(
                    '/((?:"[^"]+"\.)?"periode")\s*=\s*\?/i',
                    'date($1) = ?',
                    $query,
                ) ?? $query;
                if (str_contains($query, 'from "pengeluaran_units"')) {
                    $query = preg_replace(
                        '/"tanggal"\s*=\s*\?/i',
                        'date("tanggal") = ?',
                        $query,
                    ) ?? $query;
                }
            });
        }
        $pdo->sqliteCreateFunction(
            'GREATEST',
            static function (mixed ...$values): mixed {
                $values = array_values(array_filter(
                    $values,
                    static fn (mixed $value): bool => $value !== null,
                ));

                return $values === [] ? null : max($values);
            },
            -1,
        );
        $pdo->sqliteCreateFunction(
            'REGEXP_REPLACE',
            static function (?string $value, ?string $pattern, ?string $replacement): ?string {
                if ($value === null || $pattern === null || $replacement === null) {
                    return null;
                }

                return preg_replace('~'.str_replace('~', '\\~', $pattern).'~', $replacement, $value);
            },
            3,
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return parent::setUpTraits();
    }
}
