<?php

namespace Database\Seeders;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PolsekDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $unit = UnitOrganisasi::query()->where('kode', 'POLSEK-TALANG-EMPAT')->where('is_active', true)->firstOrFail();
            $user = User::withTrashed()->firstOrCreate(
                ['email' => 'operator.polsek@example.test'],
                [
                    'name' => 'Operator Polsek Talang Empat',
                    'password' => Hash::make('Password123!'),
                    'role' => UserRole::Operator,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            if (! $user->wasRecentlyCreated) {
                return;
            }

            UserScope::query()->create([
                'user_id' => $user->id,
                'unit_organisasi_id' => $unit->id,
                'scope_type' => ScopeType::OwnUnit,
                'is_active' => true,
                'berlaku_mulai' => now()->toDateString(),
                'berlaku_sampai' => null,
            ]);
        });
    }
}
