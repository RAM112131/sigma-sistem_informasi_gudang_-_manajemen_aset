<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PengeluaranUnitDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pengeluaran_unit_id',
        'barang_id',
        'kode_barang',
        'nama_barang',
        'kategori',
        'satuan',
        'qty',
        'qty_keluar_dilaporkan',
        'qty_keluar_rekonsiliasi',
        'qty_kembali',
        'qty_kembali_rekonsiliasi',
        'harga_hpp',
        'subtotal_nilai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'qty_keluar_dilaporkan' => 'decimal:3',
            'qty_keluar_rekonsiliasi' => 'decimal:3',
            'qty_kembali' => 'decimal:3',
            'qty_kembali_rekonsiliasi' => 'decimal:3',
            'harga_hpp' => 'decimal:2',
            'subtotal_nilai' => 'decimal:2',
        ];
    }

    public function pengeluaranUnit(): BelongsTo
    {
        return $this->belongsTo(PengeluaranUnit::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function pengembalians(): HasMany
    {
        return $this->hasMany(PengembalianUnit::class);
    }
}
