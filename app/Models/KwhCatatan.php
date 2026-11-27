<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KwhCatatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'gudang_id',
        'tanggal',
        'lwbp_meter',
        'wbp_meter',
        'selisih_lwbp',
        'selisih_wbp',
        'ct_factor',
        'tarif_lwbp',
        'tarif_wbp',
        'nominal_lwbp',
        'nominal_wbp',
        'total_biaya',
        'keterangan',
        'pencatat_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'lwbp_meter' => 'decimal:2',
            'wbp_meter' => 'decimal:2',
            'selisih_lwbp' => 'decimal:2',
            'selisih_wbp' => 'decimal:2',
            'ct_factor' => 'decimal:2',
            'tarif_lwbp' => 'decimal:2',
            'tarif_wbp' => 'decimal:2',
            'nominal_lwbp' => 'decimal:2',
            'nominal_wbp' => 'decimal:2',
            'total_biaya' => 'decimal:2',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pencatat_id');
    }
}
