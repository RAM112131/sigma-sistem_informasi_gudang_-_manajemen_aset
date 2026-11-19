<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PenerimaanUnitDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'penerimaan_unit_id',
        'barang_id',
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'qty_diterima',
        'harga_beli',
        'subtotal_nilai',
        'kondisi',
        'qty_rusak',
        'qty_diperiksa',
        'qty_rusak_diperiksa',
        'qty_kurang',
        'qty_lebih',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'qty_diterima' => 'decimal:3',
            'harga_beli' => 'decimal:2',
            'subtotal_nilai' => 'decimal:2',
            'qty_rusak' => 'decimal:3',
            'qty_diperiksa' => 'decimal:3',
            'qty_rusak_diperiksa' => 'decimal:3',
            'qty_kurang' => 'decimal:3',
            'qty_lebih' => 'decimal:3',
        ];
    }

    public function penerimaanUnit(): BelongsTo
    {
        return $this->belongsTo(PenerimaanUnit::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
