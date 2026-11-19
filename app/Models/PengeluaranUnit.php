<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PengeluaranUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_bpb',
        'gudang_id',
        'tanggal',
        'waktu_keluar',
        'bagian_peminta',
        'divisi_peminta_id',
        'catatan',
        'entry_mode',
        'status_verifikasi',
        'dicatat_oleh',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function divisiPeminta(): BelongsTo
    {
        return $this->belongsTo(DivisiPeminta::class, 'divisi_peminta_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PengeluaranUnitDetail::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function pengembalians(): HasMany
    {
        return $this->hasMany(PengembalianUnit::class);
    }
}
