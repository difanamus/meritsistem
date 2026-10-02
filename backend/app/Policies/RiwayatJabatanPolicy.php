<?php

namespace App\Policies;

use App\Models\RiwayatJabatan;
use App\Models\User;

class RiwayatJabatanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RiwayatJabatan $riwayatJabatan): bool
    {
        return $user->can('view', $riwayatJabatan->personel);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RiwayatJabatan $riwayatJabatan): bool
    {
        return $user->can('update', $riwayatJabatan->personel);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RiwayatJabatan $riwayatJabatan): bool
    {
        return $riwayatJabatan->personel !== null && $user->can('update', $riwayatJabatan->personel);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RiwayatJabatan $riwayatJabatan): bool
    {
        return $user->can('restore', $riwayatJabatan->personel);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RiwayatJabatan $riwayatJabatan): bool
    {
        return false;
    }
}
