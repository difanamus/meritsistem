<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Personel;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAdministrationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $scopes = Arr::pull($data, 'scopes', []);
            Arr::forget($data, 'password_confirmation');
            $data = $this->linkPersonnel($data);

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
            $data = $this->linkPersonnel($data, $user);
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

    /** @return array<string, mixed> */
    private function linkPersonnel(array $data, ?User $account = null): array
    {
        if (! empty($data['personel_id'])) {
            $person = Personel::withTrashed()->lockForUpdate()->find($data['personel_id']);
            $preservingInactive = $account?->personel_id === (int) $data['personel_id'] && ! $data['is_active'];
            if (! $person || (! $preservingInactive && ($person->trashed() || $person->status->value !== 'aktif'))) {
                throw ValidationException::withMessages(['personel_id' => 'Personel harus aktif dan tidak diarsipkan.']);
            }
            if (User::withTrashed()->where('personel_id', $person->id)->when($account, fn ($query) => $query->whereKeyNot($account->id))->exists()) {
                throw ValidationException::withMessages(['personel_id' => 'Personel sudah memiliki akun.']);
            }
            $data['name'] = $person->nama_lengkap;
        }

        return $data;
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
