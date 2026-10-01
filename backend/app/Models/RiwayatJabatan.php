<?php

namespace App\Models;

use Database\Factories\RiwayatJabatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('riwayat_jabatan')]
#[Fillable([
    'personel_id',
    'nama_jabatan',
    'unit_organisasi_id',
    'bidang_fungsi_id',
    'jenis_penugasan_id',
    'tanggal_mulai',
    'tanggal_selesai',
    'nivelering',
    'is_jabatan_utama',
    'keterangan',
    'dokumen_sk_path',
    'dokumen_sk_nama_asli',
    'dokumen_sk_mime',
    'dokumen_sk_ukuran',
    'created_by',
    'updated_by',
])]
class RiwayatJabatan extends Model
{
    /** @use HasFactory<RiwayatJabatanFactory> */
    use HasFactory, SoftDeletes;

    public function personel(): BelongsTo
    {
        return $this->belongsTo(Personel::class);
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class);
    }

    public function bidangFungsi(): BelongsTo
    {
        return $this->belongsTo(BidangFungsi::class);
    }

    public function jenisPenugasan(): BelongsTo
    {
        return $this->belongsTo(JenisPenugasan::class);
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
            'is_jabatan_utama' => 'boolean',
            'dokumen_sk_ukuran' => 'integer',
        ];
    }
}
