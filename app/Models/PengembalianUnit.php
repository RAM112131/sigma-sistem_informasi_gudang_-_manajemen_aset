<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PengembalianUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'gudang_id',
        'pengeluaran_unit_id',
        'pengeluaran_unit_detail_id',
        'barang_id',
        'bagian_peminta',
        'nama_barang',
        'satuan',
        'qty_kembali',
        'tanggal_kembali',
        'catatan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'qty_kembali' => 'decimal:3',
            'tanggal_kembali' => 'date',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function pengeluaranUnit(): BelongsTo
    {
        return $this->belongsTo(PengeluaranUnit::class);
    }

    public function pengeluaranUnitDetail(): BelongsTo
    {
        return $this->belongsTo(PengeluaranUnitDetail::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
