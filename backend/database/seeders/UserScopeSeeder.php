<?php

namespace Database\Seeders;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['System Admin', 'system.admin@example.test', UserRole::SystemAdmin],
            ['Admin SSDM', 'admin.ssdm@example.test', UserRole::AdminSsdm],
            ['Operator SDM Polda Bengkulu', 'operator.polda@example.test', UserRole::Operator],
            ['Operator Polres Bengkulu Tengah', 'operator.polres@example.test', UserRole::Operator],
            ['Operator Sat Intelkam', 'operator.intelkam@example.test', UserRole::Operator],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('Password123!'),
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->assignScope('operator.polda@example.test', 'POLDA-BENGKULU', ScopeType::UnitAndDescendants);
        $this->assignScope('operator.polres@example.test', 'POLRES-BENTENG', ScopeType::UnitAndDescendants);
        $this->assignScope('operator.intelkam@example.test', 'SATINTEL-BENTENG', ScopeType::OwnUnit);
    }

    private function assignScope(string $email, string $kodeUnit, ScopeType $scopeType): void
    {
        $user = User::query()->where('email', $email)->firstOrFail();
        $unit = UnitOrganisasi::query()->where('kode', $kodeUnit)->firstOrFail();

        UserScope::query()->updateOrCreate(
            ['user_id' => $user->id, 'unit_organisasi_id' => $unit->id],
            [
                'scope_type' => $scopeType,
                'is_active' => true,
                'berlaku_mulai' => now()->toDateString(),
                'berlaku_sampai' => null,
            ],
        );
    }
}
