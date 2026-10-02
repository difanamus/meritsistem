<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_endpoints_return_401_without_authentication(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
        $this->postJson('/api/v1/users', [])->assertUnauthorized();
    }

    public function test_operator_is_forbidden_from_user_management(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [])->assertForbidden();
    }

    public function test_system_admin_creates_operator_with_scopes_and_password_is_hidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $unit = UnitOrganisasi::factory()->create();

        $response = $this->postJson('/api/v1/users', $this->operatorPayload($unit));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'operator.baru@example.test')
            ->assertJsonPath('data.scopes.0.unit_organisasi.id', $unit->id)
            ->assertJsonMissingPath('data.password');
        $this->assertDatabaseHas('users', [
            'email' => 'operator.baru@example.test',
            'role' => UserRole::Operator->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('user_scopes', [
            'unit_organisasi_id' => $unit->id,
            'scope_type' => ScopeType::UnitAndDescendants->value,
            'is_active' => true,
        ]);
    }

    public function test_admin_ssdm_only_lists_and_manages_operator_accounts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        User::factory()->create(['role' => UserRole::SystemAdmin]);
        User::factory()->create(['role' => UserRole::AdminSsdm]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $operator->id);

        $this->getJson("/api/v1/users/{$operator->id}")->assertOk();
        $this->getJson("/api/v1/users/{$admin->id}")->assertForbidden();
    }

    public function test_admin_ssdm_cannot_create_a_global_account(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->postJson('/api/v1/users', [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.test',
            'password' => 'Rahasia12345',
            'password_confirmation' => 'Rahasia12345',
            'role' => UserRole::AdminSsdm->value,
            'is_active' => true,
            'scopes' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'admin.baru@example.test']);
    }

    public function test_operator_requires_an_active_scope_and_global_role_rejects_scopes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $unit = UnitOrganisasi::factory()->create();
        $operatorPayload = $this->operatorPayload($unit);
        $operatorPayload['scopes'][0]['is_active'] = false;

        $this->postJson('/api/v1/users', $operatorPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scopes');

        $globalPayload = $this->operatorPayload($unit);
        $globalPayload['email'] = 'system.baru@example.test';
        $globalPayload['role'] = UserRole::SystemAdmin->value;
        $this->postJson('/api/v1/users', $globalPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scopes');
    }

    public function test_system_admin_updates_role_and_replaces_scopes(): void
    {
        $systemAdmin = User::factory()->create(['role' => UserRole::SystemAdmin]);
        $oldUnit = UnitOrganisasi::factory()->create();
        $newUnit = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($oldUnit)->create();
        Sanctum::actingAs($systemAdmin);

        $response = $this->putJson("/api/v1/users/{$operator->id}", [
            'personel_id' => Personel::factory()->create(['nama_lengkap' => 'Operator Diperbarui'])->id,
            'name' => 'Operator Diperbarui',
            'email' => $operator->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::Operator->value,
            'is_active' => true,
            'scopes' => [[
                'unit_organisasi_id' => $newUnit->id,
                'scope_type' => ScopeType::OwnUnit->value,
                'is_active' => true,
                'berlaku_mulai' => '2026-01-01',
                'berlaku_sampai' => null,
            ]],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Operator Diperbarui')
            ->assertJsonPath('data.scopes.0.unit_organisasi.id', $newUnit->id);
        $this->assertDatabaseMissing('user_scopes', [
            'user_id' => $operator->id,
            'unit_organisasi_id' => $oldUnit->id,
        ]);
        $this->assertDatabaseHas('user_scopes', [
            'user_id' => $operator->id,
            'unit_organisasi_id' => $newUnit->id,
        ]);
    }

    public function test_user_cannot_update_or_deactivate_own_account(): void
    {
        $systemAdmin = User::factory()->create(['role' => UserRole::SystemAdmin]);
        Sanctum::actingAs($systemAdmin);

        $this->putJson("/api/v1/users/{$systemAdmin->id}", [
            'name' => 'Nama Baru',
            'email' => $systemAdmin->email,
            'role' => UserRole::Operator->value,
            'is_active' => true,
            'scopes' => [],
        ])->assertForbidden();
        $this->deleteJson("/api/v1/users/{$systemAdmin->id}")->assertForbidden();
    }

    public function test_deactivation_revokes_tokens_and_disables_scopes(): void
    {
        $systemAdmin = User::factory()->create(['role' => UserRole::SystemAdmin]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $scope = UserScope::factory()->for($operator)->create();
        $token = $operator->createToken('browser');
        Sanctum::actingAs($systemAdmin);

        $this->deleteJson("/api/v1/users/{$operator->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse($operator->refresh()->is_active);
        $this->assertFalse($scope->refresh()->is_active);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_list_supports_search_role_status_and_stable_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        User::factory()->create([
            'name' => 'Operator Aktif Khusus',
            'role' => UserRole::Operator,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Operator Nonaktif Khusus',
            'role' => UserRole::Operator,
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/users?search=aktif%20khusus&role=operator&status=active&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Operator Aktif Khusus')
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_scope_payload_rejects_unexpected_owner_key(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SystemAdmin]);
        Sanctum::actingAs($admin);
        $payload = $this->operatorPayload(UnitOrganisasi::factory()->create());
        $payload['scopes'][0]['user_id'] = $admin->id;

        $this->postJson('/api/v1/users', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scopes.0');

        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        $this->assertDatabaseCount('user_scopes', 0);
    }

    public function test_update_deactivates_scopes_and_revokes_tokens(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $unit = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($unit)->create();
        $token = $operator->createToken('browser');
        $payload = $this->operatorPayload($unit);
        $payload['email'] = $operator->email;
        $payload['is_active'] = false;

        $this->putJson("/api/v1/users/{$operator->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.scopes.0.is_active', false);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('user_scopes', ['user_id' => $operator->id, 'is_active' => false]);
    }

    public function test_admin_ssdm_reactivates_operator_with_explicit_active_scope(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => false]);
        $unit = UnitOrganisasi::factory()->create();
        UserScope::factory()->for($operator)->for($unit)->create(['is_active' => false]);
        $payload = $this->operatorPayload($unit);
        $payload['email'] = $operator->email;
        $payload['password'] = '';
        $payload['password_confirmation'] = '';

        $this->putJson("/api/v1/users/{$operator->id}", $payload)
            ->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.scopes.0.is_active', true);

        $this->assertDatabaseHas('users', ['id' => $operator->id, 'is_active' => true]);
        $this->assertDatabaseHas('user_scopes', ['user_id' => $operator->id, 'is_active' => true]);
    }

    /** @return array<string, mixed> */
    private function operatorPayload(UnitOrganisasi $unit): array
    {
        return [
            'personel_id' => Personel::factory()->create(['nama_lengkap' => 'Operator Baru'])->id,
            'name' => 'Operator Baru',
            'email' => 'operator.baru@example.test',
            'password' => 'Rahasia12345',
            'password_confirmation' => 'Rahasia12345',
            'role' => UserRole::Operator->value,
            'is_active' => true,
            'scopes' => [[
                'unit_organisasi_id' => $unit->id,
                'scope_type' => ScopeType::UnitAndDescendants->value,
                'is_active' => true,
                'berlaku_mulai' => '2026-01-01',
                'berlaku_sampai' => null,
            ]],
        ];
    }

    public function test_staff_account_requires_existing_personnel_and_name_is_derived_from_database(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $payload = $this->operatorPayload(UnitOrganisasi::factory()->create());
        $person = Personel::query()->findOrFail($payload['personel_id']);
        $payload['name'] = 'Nama bebas tidak boleh dipakai';
        $response = $this->postJson('/api/v1/users', $payload)->assertCreated()
            ->assertJsonPath('data.name', $person->nama_lengkap)->assertJsonPath('data.personel.id', $person->id);
        $this->assertDatabaseHas('users', ['id' => $response->json('data.id'), 'personel_id' => $person->id, 'name' => $person->nama_lengkap]);
    }

    public function test_personnel_cannot_have_two_accounts(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $payload = $this->operatorPayload(UnitOrganisasi::factory()->create());
        User::factory()->create(['personel_id' => $payload['personel_id']]);
        $this->postJson('/api/v1/users', $payload)->assertUnprocessable()->assertJsonValidationErrors('personel_id');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_operator_creation_without_personnel_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $payload = $this->operatorPayload(UnitOrganisasi::factory()->create());
        unset($payload['personel_id']);
        $this->postJson('/api/v1/users', $payload)->assertUnprocessable()->assertJsonValidationErrors('personel_id');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_external_programmer_can_create_account_without_personnel(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $this->postJson('/api/v1/users', ['name' => 'Programmer Eksternal', 'email' => 'programmer@example.test', 'password' => 'Rahasia12345', 'password_confirmation' => 'Rahasia12345', 'role' => 'system_admin', 'is_active' => true, 'scopes' => []])
            ->assertCreated()->assertJsonPath('data.personel_id', null);
        $this->assertDatabaseHas('users', ['email' => 'programmer@example.test', 'name' => 'Programmer Eksternal', 'personel_id' => null]);
    }

    public function test_linked_account_cannot_be_reassigned_to_another_personnel(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $owner = Personel::factory()->create();
        $account = User::factory()->create(['role' => UserRole::Operator, 'personel_id' => $owner->id]);
        $payload = $this->operatorPayload(UnitOrganisasi::factory()->create());
        $payload['email'] = $account->email;
        $this->putJson("/api/v1/users/{$account->id}", $payload)->assertUnprocessable()->assertJsonValidationErrors('personel_id');
        $this->assertSame($owner->id, $account->refresh()->personel_id);
    }

    public function test_archived_personnel_account_can_remain_inactive_but_cannot_be_reactivated(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $person = Personel::factory()->create();
        $account = User::factory()->create(['role' => UserRole::Operator, 'personel_id' => $person->id, 'is_active' => false]);
        $person->delete();
        $payload = $this->operatorPayload(UnitOrganisasi::factory()->create());
        $payload['personel_id'] = $person->id;
        $payload['email'] = $account->email;
        $payload['is_active'] = false;
        $this->putJson("/api/v1/users/{$account->id}", $payload)->assertOk()->assertJsonPath('data.is_active', false);
        $payload['is_active'] = true;
        $this->putJson("/api/v1/users/{$account->id}", $payload)->assertUnprocessable()->assertJsonValidationErrors('personel_id');
        $this->assertFalse($account->refresh()->is_active);
    }

    public function test_personnel_picker_is_admin_only_bounded_and_excludes_archived_or_inactive_personnel(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        Personel::factory()->count(28)->create(['nama_lengkap' => 'Personel Pilihan']);
        Personel::factory()->create(['nama_lengkap' => 'Personel Nonaktif', 'status' => 'pensiun']);
        $archived = Personel::factory()->create(['nama_lengkap' => 'Personel Arsip']);
        $archived->delete();
        $this->getJson('/api/v1/personel-options?search=Personel')->assertOk()->assertJsonCount(25, 'data')->assertJsonMissing(['nama_lengkap' => 'Personel Arsip'])->assertJsonMissing(['nama_lengkap' => 'Personel Nonaktif']);
        $this->getJson('/api/v1/personel-options?search=Pe')->assertUnprocessable()->assertJsonValidationErrors('search');
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));
        $this->getJson('/api/v1/personel-options?search=Personel')->assertForbidden();
    }
}
