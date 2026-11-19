<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StokOpnameDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stok_opname_id',
        'barang_id',
        'kode_barang',
        'nama_barang',
        'satuan',
        'qty_sistem',
        'qty_sistem_at',
        'qty_fisik',
        'harga_satuan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'qty_sistem' => 'decimal:3',
            'qty_sistem_at' => 'datetime',
            'qty_fisik' => 'decimal:3',
            'harga_satuan' => 'decimal:2',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StokOpname::class, 'stok_opname_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
