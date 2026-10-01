<?php

namespace App\Models;

use App\Enums\ScopeType;
use Database\Factories\UserScopeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'unit_organisasi_id', 'scope_type', 'is_active', 'berlaku_mulai', 'berlaku_sampai'])]
class UserScope extends Model
{
    /** @use HasFactory<UserScopeFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitOrganisasi(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class);
    }

    protected function casts(): array
    {
        return [
            'scope_type' => ScopeType::class,
            'is_active' => 'boolean',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
        ];
    }
}
