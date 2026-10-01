<?php

namespace App\Models;

use Database\Factories\KualifikasiPersonelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('kualifikasi_personel')]
#[Fillable([
    'personel_id',
    'jenis_kualifikasi_id',
    'bidang_fungsi_id',
    'nama_kualifikasi',
    'jenjang',
    'bidang_studi',
    'institusi_penyelenggara',
    'tanggal_mulai',
    'tanggal_selesai',
    'tahun',
    'nomor_dokumen',
    'keterangan',
    'dokumen_pendukung_path',
    'dokumen_pendukung_nama_asli',
    'dokumen_pendukung_mime',
    'dokumen_pendukung_ukuran',
    'created_by',
    'updated_by',
])]
class KualifikasiPersonel extends Model
{
    /** @use HasFactory<KualifikasiPersonelFactory> */
    use HasFactory, SoftDeletes;

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class);
    }

    public function jenisKualifikasi(): BelongsTo
    {
        return $this->belongsTo(JenisKualifikasi::class);
    }

    public function bidangFungsi(): BelongsTo
    {
        return $this->belongsTo(BidangFungsi::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tahun' => 'integer',
            'dokumen_pendukung_ukuran' => 'integer',
        ];
    }
}
