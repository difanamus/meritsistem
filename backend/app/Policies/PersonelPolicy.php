<?php

namespace App\Policies;

use App\Models\Personel;
use App\Models\User;
use App\Services\OrganizationalScopeService;

class PersonelPolicy
{
    public function __construct(private readonly OrganizationalScopeService $scopeService) {}

    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($ability === 'forceDelete') {
            return false;
        }

        return $this->scopeService->isGlobal($user) ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->scopeService->hasAccessibleUnits($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Personel $personel): bool
    {
        return $this->scopeService->canAccessUnit($user, $personel->unit_organisasi_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->scopeService->hasAccessibleUnits($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Personel $personel): bool
    {
        return $this->view($user, $personel);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Personel $personel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Personel $personel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Personel $personel): bool
    {
        return false;
    }
}
