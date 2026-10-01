<?php

namespace Database\Factories;

use App\Enums\ScopeType;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserScope>
 */
class UserScopeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'unit_organisasi_id' => UnitOrganisasi::factory(),
            'scope_type' => ScopeType::OwnUnit,
            'is_active' => true,
            'berlaku_mulai' => now()->toDateString(),
            'berlaku_sampai' => null,
        ];
    }
}
