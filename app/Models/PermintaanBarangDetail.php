<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanBarangDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'permintaan_barang_id',
        'barang_id',
        'qty_diminta',
        'qty_diproses',
        'satuan_id',
        'catatan',
    ];

    public function permintaanBarang(): BelongsTo
    {
        return $this->belongsTo(PermintaanBarang::class, 'permintaan_barang_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
