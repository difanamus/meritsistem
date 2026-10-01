<?php

namespace App\Models;

use App\Enums\JenisUnit;
use Database\Factories\UnitOrganisasiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table('unit_organisasi')]
#[Fillable(['parent_id', 'kode', 'nama', 'jenis_unit', 'is_active'])]
class UnitOrganisasi extends Model
{
    /** @use HasFactory<UnitOrganisasiFactory> */
    use HasFactory, SoftDeletes;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function userScopes(): HasMany
    {
        return $this->hasMany(UserScope::class);
    }

    public function personel(): HasMany
    {
        return $this->hasMany(Personel::class);
    }

    protected function casts(): array
    {
        return [
            'jenis_unit' => JenisUnit::class,
            'is_active' => 'boolean',
        ];
    }
}
