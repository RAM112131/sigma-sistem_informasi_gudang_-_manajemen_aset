<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gudang extends Model
{
    use HasFactory, Loggable;

    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_gudang');
    }

    public function barangs(): HasMany
    {
        return $this->hasMany(Barang::class, 'gudang_id');
    }

    public function kategoriBarangs(): HasMany
    {
        return $this->hasMany(KategoriBarang::class, 'gudang_id');
    }

    public function satuans(): HasMany
    {
        return $this->hasMany(Satuan::class, 'gudang_id');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'holding', 'global_master', 'gudang_store' => 'Gudang Holding (Master Global)',
            'unit_bisnis', 'gudang_sukahati', 'gudang_umum' => 'Gudang Unit Bisnis',
            default => 'Unit Bisnis ('.title_case($this->jenis).')'
        };
    }
}
