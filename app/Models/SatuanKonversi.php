<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatuanKonversi extends Model
{
    use HasFactory;

    protected $fillable = [
        'barang_id',
        'satuan_turunan_id',
        'rasio_konversi',
        'label_konversi',
    ];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function satuanTurunan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_turunan_id');
    }
}
