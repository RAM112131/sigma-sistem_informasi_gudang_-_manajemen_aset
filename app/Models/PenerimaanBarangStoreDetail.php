<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerimaanBarangStoreDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'penerimaan_barang_store_id',
        'barang_id',
        'qty_diterima',
        'qty_rusak',
        'qty_kurang',
        'qty_lebih',
        'satuan_id',
        'kondisi',
        'pemakai',
        'harga_satuan',
        'total_harga',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'qty_diterima' => 'integer',
        'qty_rusak' => 'integer',
        'qty_kurang' => 'integer',
        'qty_lebih' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarangStore::class, 'penerimaan_barang_store_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
