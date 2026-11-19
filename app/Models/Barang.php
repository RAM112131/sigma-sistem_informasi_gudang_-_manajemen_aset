<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSemanticSearch;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
    use HasFactory, HasSemanticSearch, Loggable, SoftDeletes;

    protected $fillable = [
        'gudang_id',
        'master_barang_id',
        'kategori_id',
        'satuan_id',
        'kode',
        'nama',
        'minimum_stok',
        'harga_satuan',
        'deskripsi',
        'foto_path',
        'is_aktif',
    ];

    protected $casts = [
        'minimum_stok' => 'integer',
        'harga_satuan' => 'decimal:2',
        'is_aktif' => 'boolean',
    ];

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function masterBarang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class, 'master_barang_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
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

    /**
     * Scope to filter barang by warehouse context or global master.
     */
    public function scopeForGudang(Builder $query, ?int $gudangId): Builder
    {
        if ($gudangId === null) {
            return $query; // Super Admin / Global Holding sees all items
        }

        // Show items assigned to specific warehouse OR global items (gudang_id IS NULL)
        return $query->where(function (Builder $q) use ($gudangId) {
            $q->where('gudang_id', $gudangId)
                ->orWhereNull('gudang_id');
        });
    }
}
