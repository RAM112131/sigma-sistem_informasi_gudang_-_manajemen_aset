<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StokOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'gudang_id',
        'periode',
        'tanggal_efektif',
        'status',
        'dibuat_oleh',
        'diterapkan_oleh',
        'diterapkan_at',
    ];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'tanggal_efektif' => 'date',
            'diterapkan_at' => 'datetime',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(StokOpnameDetail::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function diterapkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterapkan_oleh');
    }
}
