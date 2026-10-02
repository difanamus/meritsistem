<?php

namespace App\Policies;

use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\User;

class KualifikasiPersonelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Personel::class);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KualifikasiPersonel $kualifikasiPersonel): bool
    {
        return $user->can('view', $kualifikasiPersonel->personel);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create', Personel::class);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KualifikasiPersonel $kualifikasiPersonel): bool
    {
        return $user->can('update', $kualifikasiPersonel->personel);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KualifikasiPersonel $kualifikasiPersonel): bool
    {
        return $kualifikasiPersonel->personel !== null && $user->can('update', $kualifikasiPersonel->personel);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KualifikasiPersonel $kualifikasiPersonel): bool
    {
        return $user->can('restore', $kualifikasiPersonel->personel);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KualifikasiPersonel $kualifikasiPersonel): bool
    {
        return false;
    }
}
