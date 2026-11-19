<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSemanticSearch;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MasterBarang extends Model
{
    use HasFactory, HasSemanticSearch, Loggable, SoftDeletes;

    protected $fillable = [
        'kode',
        'nama',
        'kategori_id',
        'satuan_id',
        'harga_satuan',
        'deskripsi',
        'foto_path',
        'is_aktif',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'is_aktif' => 'boolean',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class, 'master_barang_id');
    }

    public function getSemanticText(): string
    {
        $this->loadMissing(['kategori', 'satuan']);

        return implode(' ', array_filter([
            $this->kode,
            $this->nama,
            $this->kategori?->nama,
            $this->satuan?->nama,
            $this->deskripsi,
        ]));
    }
}
