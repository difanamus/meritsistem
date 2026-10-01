<?php

namespace App\Services;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Builder;

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

        $scopes = $this->activeScopes($user)->get();
        $ownUnitIds = $scopes
            ->where('scope_type', ScopeType::OwnUnit)
            ->pluck('unit_organisasi_id');
        $rootIds = $scopes
            ->where('scope_type', ScopeType::UnitAndDescendants)
            ->pluck('unit_organisasi_id');

        if ($rootIds->isEmpty()) {
            return $ownUnitIds->map(fn ($id): int => (int) $id)->unique()->values()->all();
        }

        $childrenByParent = UnitOrganisasi::query()
            ->where('is_active', true)
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');
        $accessibleIds = $ownUnitIds->merge($rootIds)->map(fn ($id): int => (int) $id)->all();
        $queue = $rootIds->map(fn ($id): int => (int) $id)->values()->all();

        while ($queue !== []) {
            $parentId = array_shift($queue);

            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                $childId = (int) $child->id;

                if (! in_array($childId, $accessibleIds, true)) {
                    $accessibleIds[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return array_values(array_unique($accessibleIds));
    }

    public function canAccessUnit(User $user, int $unitId): bool
    {
        $accessibleIds = $this->accessibleUnitIds($user);

        return $accessibleIds === null || in_array($unitId, $accessibleIds, true);
    }

    /** @param Builder<Personel> $query */
    public function scopePersonelQuery(Builder $query, User $user): Builder
    {
        $accessibleIds = $this->accessibleUnitIds($user);

        if ($accessibleIds === null) {
            return $query;
        }

        return $query->whereIn('unit_organisasi_id', $accessibleIds);
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
}
