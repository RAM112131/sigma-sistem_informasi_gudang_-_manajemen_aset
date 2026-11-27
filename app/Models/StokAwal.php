<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokAwal extends Model
{
    use HasFactory, Loggable, SoftDeletes;

    protected $table = 'stok_awals';

    protected $fillable = [
        'gudang_id',
        'barang_id',
        'qty',
        'harga_awal',
        'tanggal_acuan',
        'catatan',
        'sumber',
        'file_asal_path',
        'diinput_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'harga_awal' => 'decimal:2',
            'tanggal_acuan' => 'date',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function diinputOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diinput_oleh');
    }
}
