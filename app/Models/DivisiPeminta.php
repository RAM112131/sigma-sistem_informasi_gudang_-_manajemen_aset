<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DivisiPeminta extends Model
{
    use HasFactory, Loggable;

    protected $table = 'divisi_pemintas';

    protected $fillable = [
        'gudang_id',
        'kode',
        'nama',
        'penanggung_jawab',
        'is_aktif',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function pengeluarans(): HasMany
    {
        return $this->hasMany(PengeluaranUnit::class, 'divisi_peminta_id');
    }
}
