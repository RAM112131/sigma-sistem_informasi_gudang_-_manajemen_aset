<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PenerimaanUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_rcv',
        'gudang_id',
        'nama_supplier',
        'tanggal_terima',
        'waktu_terima',
        'po_referensi',
        'nomor_surat_jalan',
        'has_surat_jalan',
        'alasan_tanpa_sj',
        'foto_surat_jalan_path',
        'catatan',
        'diterima_oleh',
        'status_verifikasi',
        'diverifikasi_oleh',
        'diverifikasi_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terima' => 'date',
            'has_surat_jalan' => 'boolean',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PenerimaanUnitDetail::class);
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function diterimaOleh(): BelongsTo
    {
        return $this->penerima();
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->verifier();
    }
}
