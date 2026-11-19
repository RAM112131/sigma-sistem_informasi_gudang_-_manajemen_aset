<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriBarang extends Model
{
    use HasFactory, Loggable, SoftDeletes;

    protected $table = 'kategori_barangs';

    protected $fillable = [
        'gudang_id',
        'nama',
        'deskripsi',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class, 'kategori_id');
    }

    public function masterBarangs(): HasMany
    {
        return $this->hasMany(MasterBarang::class, 'kategori_id');
    }

    public function scopeForGudang(Builder $query, ?int $gudangId): Builder
    {
        if ($gudangId === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($gudangId) {
            $q->where('gudang_id', $gudangId)
                ->orWhereNull('gudang_id');
        });
    }
}
