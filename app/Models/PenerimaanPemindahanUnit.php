<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenerimaanPemindahanUnit extends Model
{
    use Loggable;

    protected $fillable = [
        'transfer_barang_id',
        'gudang_id',
        'barang_id',
        'qty_dikirim',
        'qty_diterima',
        'qty_rusak',
        'catatan_discrepancy',
        'diterima_oleh',
        'diterima_at',
        'qty_diperiksa',
        'qty_rusak_diperiksa',
        'status_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'qty_dikirim' => 'integer',
        'qty_diterima' => 'integer',
        'qty_rusak' => 'integer',
        'qty_diperiksa' => 'decimal:3',
        'qty_rusak_diperiksa' => 'decimal:3',
        'diterima_at' => 'datetime',
        'diverifikasi_at' => 'datetime',
    ];

    protected $attributes = [
        'status_verifikasi' => 'menunggu_verifikasi',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(TransferBarang::class, 'transfer_barang_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
