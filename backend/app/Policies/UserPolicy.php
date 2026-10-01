<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_active ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SystemAdmin, UserRole::AdminSsdm], true);
    }

    public function view(User $user, User $managedUser): bool
    {
        if ($user->role === UserRole::SystemAdmin) {
            return true;
        }

        return $user->role === UserRole::AdminSsdm
            && $managedUser->role === UserRole::Operator;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SystemAdmin, UserRole::AdminSsdm], true);
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->isNot($managedUser) && $this->view($user, $managedUser);
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $user->isNot($managedUser) && $managedUser->is_active && $this->view($user, $managedUser);
    }

    public function restore(User $user, User $managedUser): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $managedUser): bool
    {
        return false;
    }
}
