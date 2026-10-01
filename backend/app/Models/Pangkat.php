<?php

namespace App\Models;

use App\Enums\JenisPersonel;
use Database\Factories\PangkatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('pangkat')]
#[Fillable(['kode', 'nama', 'jenis_personel', 'urutan', 'is_active'])]
class Pangkat extends Model
{
    /** @use HasFactory<PangkatFactory> */
    use HasFactory, SoftDeletes;

    public function personel(): HasMany
    {
        return $this->hasMany(Personel::class);
    }

    protected function casts(): array
    {
        return [
            'jenis_personel' => JenisPersonel::class,
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
