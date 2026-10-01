<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserAdministrationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $scopes = Arr::pull($data, 'scopes', []);
            Arr::forget($data, 'password_confirmation');

            $user = User::query()->create($data);
            $user->forceFill(['email_verified_at' => now()])->save();
            $this->syncScopes($user, $scopes);

            return $user;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $scopes = Arr::pull($data, 'scopes', []);
            Arr::forget($data, 'password_confirmation');
            if (blank($data['password'] ?? null)) {
                Arr::forget($data, 'password');
            }

            $user->update($data);
            $this->syncScopes($user, $scopes);

            if (! $user->is_active) {
                $user->scopes()->update(['is_active' => false]);
                $user->tokens()->delete();
            }

            return $user;
        });
    }

    public function deactivate(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->update(['is_active' => false]);
            $user->scopes()->update(['is_active' => false]);
            $user->tokens()->delete();
        });
    }

    /** @param array<int, array<string, mixed>> $scopes */
    private function syncScopes(User $user, array $scopes): void
    {
        $user->scopes()->delete();

        if ($user->role === UserRole::Operator && $scopes !== []) {
            $user->scopes()->createMany($scopes);
        }
    }
}
