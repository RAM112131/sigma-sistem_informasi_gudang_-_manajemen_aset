<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanBarang extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_pr',
        'gudang_peminta_id',
        'gudang_tujuan_id',
        'status',
        'catatan',
        'alasan_penolakan',
        'dibuat_oleh',
    ];

    public function gudangPeminta(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_peminta_id');
    }

    public function gudangTujuan(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PermintaanBarangDetail::class, 'permintaan_barang_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(TransferBarang::class, 'permintaan_barang_id');
    }

    public function peminta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Generate Nomor PR otomatis sesuai format unit.
     * Format: REQ-{KODE}-{DDMMYYYY}{RAND_3}-{SEQ_4}
     * Contoh: REQ-SHT-30092026822-0001
     */
    public static function generateNomorPr(string $unitKode = 'SHT'): string
    {
        $code = self::resolveLokasiKode($unitKode);
        $dateStr = now()->format('dmY');
        $randomDigits = str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $searchPrefix = "REQ-{$code}-{$dateStr}";

        $todayCount = self::query()
            ->where('nomor_pr', 'like', $searchPrefix.'%')
            ->count();

        $sequence = str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);

        return "{$searchPrefix}{$randomDigits}-{$sequence}";
    }

    /**
     * Resolve singkatan 3 huruf kode unit gudang.
     */
    public static function resolveLokasiKode(string $kode): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $kode));
        if (str_contains($normalized, 'SUKAHATI')) {
            return 'SHT';
        }
        if (str_contains($normalized, 'TAMANSARI')) {
            return 'TMS';
        }
        if (str_contains($normalized, 'STORE') || str_contains($normalized, 'ARMADA')) {
            return 'STR';
        }
        if (strlen($normalized) <= 4 && strlen($normalized) >= 2) {
            return $normalized;
        }
        $cleaned = preg_replace('/^(?:RPA|UNIT|GUDANG)/', '', $normalized) ?: $normalized;

        return substr($cleaned, 0, 3) ?: 'UNT';
    }
}
