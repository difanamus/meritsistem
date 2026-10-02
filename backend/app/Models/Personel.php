<?php

namespace App\Models;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use Database\Factories\PersonelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('personel')]
#[Fillable([
    'jenis_personel',
    'nomor_identitas',
    'nama_lengkap',
    'pangkat_id',
    'tempat_lahir',
    'tanggal_lahir',
    'unit_organisasi_id',
    'status',
    'created_by',
    'updated_by',
    'alasan_arsip',
    'archived_by',
])]
class Personel extends Model
{
    /** @use HasFactory<PersonelFactory> */
    use HasFactory, SoftDeletes;

    public function pangkat(): BelongsTo
    {
        return $this->belongsTo(Pangkat::class);
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class);
    }

    public function kualifikasi(): HasMany
    {
        return $this->hasMany(KualifikasiPersonel::class);
    }

    public function riwayatJabatan(): HasMany
    {
        return $this->hasMany(RiwayatJabatan::class);
    }

    public function penugasanOperasi(): HasMany
    {
        return $this->hasMany(PenugasanOperasi::class);
    }

    public function prestasi(): HasMany
    {
        return $this->hasMany(PrestasiPersonel::class);
    }

    public function penghargaan(): HasMany
    {
        return $this->hasMany(PenghargaanPersonel::class);
    }

    public function jabatanUtamaAktif(): HasOne
    {
        return $this->hasOne(RiwayatJabatan::class)
            ->where('is_jabatan_utama', true)
            ->whereNull('tanggal_selesai');
    }

    public function penugasanTambahanAktif(): HasMany
    {
        return $this->hasMany(RiwayatJabatan::class)
            ->where('is_jabatan_utama', false)
            ->whereNull('tanggal_selesai');
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
            'jenis_personel' => JenisPersonel::class,
            'tanggal_lahir' => 'date',
            'status' => StatusPersonel::class,
        ];
    }
}
