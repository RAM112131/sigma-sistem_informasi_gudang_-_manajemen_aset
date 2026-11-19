<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'pengeluaran_unit_number_migration_backups';

    public function up(): void
    {
        DB::transaction(function (): void {
            Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
                $table->unsignedBigInteger('pengeluaran_unit_id')->primary();
                $table->string('nomor_bpb_lama')->unique();
            });

            $legacyIssues = DB::table('pengeluaran_units as pengeluaran')
                ->join('gudangs', 'gudangs.id', '=', 'pengeluaran.gudang_id')
                ->where('pengeluaran.nomor_bpb', 'like', 'BPB/%')
                ->select([
                    'pengeluaran.id',
                    'pengeluaran.nomor_bpb',
                    'pengeluaran.gudang_id',
                    'pengeluaran.tanggal',
                    'gudangs.kode',
                    'gudangs.nama',
                ])
                ->orderBy('pengeluaran.gudang_id')
                ->orderBy('pengeluaran.tanggal')
                ->orderBy('pengeluaran.nomor_bpb')
                ->get();

            foreach ($legacyIssues->groupBy(
                static fn (object $issue): string => $issue->gudang_id.'|'.$issue->tanggal,
            ) as $dailyIssues) {
                $firstIssue = $dailyIssues->first();
                $identifier = strtoupper(preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    (string) ($firstIssue->kode ?: $firstIssue->nama),
                ) ?? '');
                $unitPrefix = match (true) {
                    str_contains($identifier, 'SUKAHATI') => 'SHT',
                    str_contains($identifier, 'TAMANSARI') => 'TMS',
                    default => substr($identifier, 0, 3) ?: 'UNT',
                };
                $dateNumber = str_replace('-', '', (string) $firstIssue->tanggal);

                foreach ($dailyIssues->values() as $index => $issue) {
                    $newNumber = sprintf('OUT-%s-%s001-%04d', $unitPrefix, $dateNumber, $index + 1);

                    if (DB::table('pengeluaran_units')->where('nomor_bpb', $newNumber)->exists()) {
                        throw new RuntimeException("Cannot convert legacy pengeluaran number because {$newNumber} already exists.");
                    }

                    DB::table(self::BACKUP_TABLE)->insert([
                        'pengeluaran_unit_id' => $issue->id,
                        'nomor_bpb_lama' => $issue->nomor_bpb,
                    ]);

                    DB::table('pengeluaran_units')
                        ->where('id', $issue->id)
                        ->update(['nomor_bpb' => $newNumber]);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            throw new RuntimeException('Cannot restore legacy pengeluaran numbers because the backup table is missing.');
        }

        DB::transaction(function (): void {
            $numberBackups = DB::table(self::BACKUP_TABLE)->get();

            foreach ($numberBackups as $numberBackup) {
                $restored = DB::table('pengeluaran_units')
                    ->where('id', $numberBackup->pengeluaran_unit_id)
                    ->update(['nomor_bpb' => $numberBackup->nomor_bpb_lama]);

                if ($restored !== 1) {
                    throw new RuntimeException("Cannot restore pengeluaran number for transaction {$numberBackup->pengeluaran_unit_id}.");
                }
            }

            Schema::drop(self::BACKUP_TABLE);
        });
    }
};
