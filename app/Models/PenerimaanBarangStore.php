<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenerimaanBarangStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_penerimaan',
        'gudang_id',
        'barang_id',
        'qty_diterima',
        'satuan_id',
        'po_referensi',
        'sumber_supplier',
        'pemesan',
        'pengambil_barang',
        'pemakai',
        'metode_pembayaran',
        'foto_dokumen_path',
        'tanpa_surat_jalan',
        'alasan_tanpa_sj',
        'catatan',
        'catatan_rekap',
        'source_groups',
        'diterima_oleh',
        'tanggal_diterima',
    ];

    protected $casts = [
        'tanpa_surat_jalan' => 'boolean',
        'tanggal_diterima' => 'datetime',
        'source_groups' => 'array',
    ];

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PenerimaanBarangStoreDetail::class, 'penerimaan_barang_store_id');
    }

    public function getSemanticText(): string
    {
        $this->loadMissing(['details.barang.kategori', 'details.satuan']);

        $items = $this->details->map(function (PenerimaanBarangStoreDetail $detail): string {
            return implode(' ', array_filter([
                $detail->barang?->kode,
                $detail->barang?->nama,
                $detail->barang?->kategori?->nama,
                $detail->satuan?->nama,
                'qty diterima '.$detail->qty_diterima,
                'qty rusak '.$detail->qty_rusak,
                'qty kurang '.$detail->qty_kurang,
                'qty lebih '.$detail->qty_lebih,
                $detail->pemakai,
                $detail->kondisi,
                $detail->catatan,
            ]));
        })->implode(' ');

        return implode(' ', array_filter([
            $this->nomor_penerimaan,
            $this->sumber_supplier,
            $this->pemesan,
            $this->pengambil_barang,
            $this->metode_pembayaran,
            $this->catatan_rekap,
            $this->alasan_tanpa_sj,
            $items,
        ]));
    }
}
