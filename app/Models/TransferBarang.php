<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransferBarang extends Model
{
    use HasFactory, Loggable;

    protected $fillable = [
        'nomor_sj',
        'permintaan_barang_id',
        'gudang_asal_id',
        'gudang_tujuan_id',
        'barang_id',
        'qty_diminta',
        'qty_dikirim',
        'qty_diterima',
        'status',
        'foto_bukti_path',
        'supir_pengantar',
        'dikirim_oleh',
        'diterima_oleh',
        'dikirim_at',
        'diterima_at',
    ];

    protected $casts = [
        'dikirim_at' => 'datetime',
        'diterima_at' => 'datetime',
    ];

    public function permintaanBarang(): BelongsTo
    {
        return $this->belongsTo(PermintaanBarang::class, 'permintaan_barang_id');
    }

    public function gudangAsal(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }

    public function gudangTujuan(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikirim_oleh');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function penerimaanUnit(): HasOne
    {
        return $this->hasOne(PenerimaanPemindahanUnit::class, 'transfer_barang_id');
    }

    /**
     * Generate Nomor Pengeluaran / Surat Jalan otomatis.
     * Format: OUT-{YYYYMMDD}-{SEQ_3} atau OUT-{KODE}-{DDMMYYYY}{RAND_3}-{SEQ_4}
     * Contoh: OUT-20260930-001
     */
    public static function generateNomorPengeluaran(string $unitKode = ''): string
    {
        $dateStr = now()->format('Ymd');
        $countToday = self::whereDate('created_at', now()->toDateString())->count() + 1;
        $sequence = str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);

        if ($unitKode !== '') {
            $dmY = now()->format('dmY');
            $randomDigits = mt_rand(100, 999);

            return "OUT-{$unitKode}-{$dmY}{$randomDigits}-".str_pad((string) $countToday, 4, '0', STR_PAD_LEFT);
        }

        return "OUT-{$dateStr}-{$sequence}";
    }
}
