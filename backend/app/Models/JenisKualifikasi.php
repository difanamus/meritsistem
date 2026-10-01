<?php

namespace App\Models;

use Database\Factories\JenisKualifikasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('jenis_kualifikasi')]
#[Fillable(['kode', 'nama', 'is_active'])]
class JenisKualifikasi extends Model
{
    /** @use HasFactory<JenisKualifikasiFactory> */
    use HasFactory, SoftDeletes;

    public function kualifikasiPersonel(): HasMany
    {
        return $this->hasMany(KualifikasiPersonel::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
