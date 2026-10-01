<?php

namespace App\Models;

use Database\Factories\JenisPenugasanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('jenis_penugasan')]
#[Fillable(['kode', 'nama', 'is_active'])]
class JenisPenugasan extends Model
{
    /** @use HasFactory<JenisPenugasanFactory> */
    use HasFactory, SoftDeletes;

    public function riwayatJabatan(): HasMany
    {
        return $this->hasMany(RiwayatJabatan::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
