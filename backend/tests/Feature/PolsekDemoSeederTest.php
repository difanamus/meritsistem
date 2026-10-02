<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PolsekDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PolsekDemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_standard_installation_includes_polsek_account_with_own_unit_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'operator.polsek@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $scope = UserScope::query()->where('user_id', $user->id)->sole();
        $this->assertSame(ScopeType::OwnUnit, $scope->scope_type);
        $unit = UnitOrganisasi::query()->where('kode', 'POLSEK-TALANG-EMPAT')->firstOrFail();
        $this->assertSame($unit->id, $scope->unit_organisasi_id);
        $inside = Personel::factory()->for($unit, 'unitOrganisasi')->create();
        $outside = Personel::factory()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/personel')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $inside->id);
        $this->getJson('/api/v1/personel/'.$outside->id)->assertForbidden();
        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_rerun_preserves_existing_password_account_state_and_scope(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'operator.polsek@example.test')->firstOrFail();
        $user->update(['name' => 'Nama diganti', 'password' => Hash::make('ChangedPassword123!'), 'is_active' => false]);
        UserScope::query()->where('user_id', $user->id)->update(['is_active' => false]);
        $this->seed(PolsekDemoSeeder::class);
        $user->refresh();
        $this->assertSame('Nama diganti', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertTrue(Hash::check('ChangedPassword123!', $user->password));
        $this->assertFalse(UserScope::query()->where('user_id', $user->id)->sole()->is_active);
        $user->delete();
        $this->seed(PolsekDemoSeeder::class);
        $this->assertSoftDeleted($user);
        $this->assertSame(1, User::withTrashed()->where('email', 'operator.polsek@example.test')->count());
    }
}
