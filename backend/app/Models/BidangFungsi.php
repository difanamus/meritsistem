<?php

namespace App\Models;

use Database\Factories\BidangFungsiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('bidang_fungsi')]
#[Fillable(['kode', 'nama', 'deskripsi', 'is_active'])]
class BidangFungsi extends Model
{
    /** @use HasFactory<BidangFungsiFactory> */
    use HasFactory, SoftDeletes;

    public function kualifikasiPersonel(): HasMany
    {
        return $this->hasMany(KualifikasiPersonel::class);
    }

    public function riwayatJabatan(): HasMany
    {
        return $this->hasMany(RiwayatJabatan::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
