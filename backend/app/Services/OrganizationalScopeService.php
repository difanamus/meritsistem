<?php

namespace App\Services;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrganizationalScopeService
{
    public function isGlobal(User $user): bool
    {
        return in_array($user->role, [UserRole::SystemAdmin, UserRole::AdminSsdm], true);
    }

    /**
     * A null result means that the user has global access.
     *
     * @return array<int, int>|null
     */
    public function accessibleUnitIds(User $user): ?array
    {
        if ($this->isGlobal($user)) {
            return null;
        }

        return array_map(
            fn (object $row): int => (int) $row->id,
            DB::select($this->accessibleUnitsSql(), $this->accessibleUnitsBindings($user)),
        );
    }

    public function hasAccessibleUnits(User $user): bool
    {
        return $this->isGlobal($user) || $this->activeScopes($user)->exists();
    }

    public function canAccessUnit(User $user, int $unitId): bool
    {
        if ($this->isGlobal($user)) {
            return true;
        }

        return DB::selectOne(
            'SELECT 1 AS allowed FROM ('.$this->accessibleUnitsSql().') accessible_units WHERE id = ? LIMIT 1',
            [...$this->accessibleUnitsBindings($user), $unitId],
        ) !== null;
    }

    /** @param Builder<Personel> $query */
    public function scopePersonelQuery(Builder $query, User $user): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        return $query->whereRaw(
            'personel.unit_organisasi_id IN ('.$this->accessibleUnitsSql().')',
            $this->accessibleUnitsBindings($user),
        );
    }

    /** @param Builder<UnitOrganisasi> $query */
    public function scopeUnitQuery(Builder $query, User $user): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        return $query->whereRaw(
            'unit_organisasi.id IN ('.$this->accessibleUnitsSql().')',
            $this->accessibleUnitsBindings($user),
        );
    }

    /** @return Builder<UserScope> */
    private function activeScopes(User $user): Builder
    {
        return UserScope::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('berlaku_mulai')->orWhereDate('berlaku_mulai', '<=', today());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('berlaku_sampai')->orWhereDate('berlaku_sampai', '>=', today());
            });
    }

    private function accessibleUnitsSql(): string
    {
        return <<<'SQL'
            WITH RECURSIVE valid_scopes AS (
                SELECT unit_organisasi_id, scope_type
                FROM user_scopes
                WHERE user_id = ? AND is_active = TRUE
                    AND (berlaku_mulai IS NULL OR DATE(berlaku_mulai) <= ?)
                    AND (berlaku_sampai IS NULL OR DATE(berlaku_sampai) >= ?)
            ), descendants(id) AS (
                SELECT unit_organisasi_id FROM valid_scopes WHERE scope_type = ?
                UNION
                SELECT child.id
                FROM unit_organisasi child
                JOIN descendants parent ON child.parent_id = parent.id
                WHERE child.is_active = TRUE AND child.deleted_at IS NULL
            )
            SELECT id FROM descendants
            UNION
            SELECT unit_organisasi_id AS id FROM valid_scopes WHERE scope_type = ?
            SQL;
    }

    /** @return array<int, int|string> */
    private function accessibleUnitsBindings(User $user): array
    {
        $today = today()->toDateString();

        return [$user->id, $today, $today, ScopeType::UnitAndDescendants->value, ScopeType::OwnUnit->value];
    }
}
