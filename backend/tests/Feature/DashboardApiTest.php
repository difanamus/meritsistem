<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_empty_personnel_database_returns_zero_summary_and_empty_list(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->getJson('/api/v1/dashboard')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.personnel.total', 0)
            ->assertJsonPath('data.qualifications', 0)
            ->assertJsonPath('data.active_positions', 0)
            ->assertJsonCount(0, 'data.recent_personnel');
        $this->getJson('/api/v1/personel')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_dashboard_requires_authentication_and_active_account(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/system-status')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin, 'is_active' => false]));
        $this->getJson('/api/v1/dashboard')->assertForbidden();
        $this->getJson('/api/v1/system-status')->assertForbidden();
    }

    public function test_global_dashboard_excludes_deleted_data_and_limits_account_counts_by_role(): void
    {
        $system = User::factory()->create(['role' => UserRole::SystemAdmin]);
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        User::factory()->create(['role' => UserRole::Operator]);
        User::factory()->create(['role' => UserRole::Operator, 'is_active' => false]);
        $person = Personel::factory()->create();
        Personel::factory()->create(['status' => 'pensiun']);
        $deleted = Personel::factory()->create();
        KualifikasiPersonel::factory()->create(['personel_id' => $person->id]);
        KualifikasiPersonel::factory()->create(['personel_id' => $deleted->id]);
        $deleted->delete();
        KualifikasiPersonel::factory()->create(['personel_id' => $person->id])->delete();
        RiwayatJabatan::factory()->create(['personel_id' => $person->id]);
        Sanctum::actingAs($system);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.personnel.total', 2)
            ->assertJsonPath('data.personnel.aktif', 1)->assertJsonPath('data.personnel.pensiun', 1)
            ->assertJsonPath('data.qualifications', 1)->assertJsonPath('data.active_positions', 1)
            ->assertJsonPath('data.accounts.total', 4)->assertJsonPath('data.scope.global', true)->assertJsonCount(2, 'data.recent_personnel');
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.accounts.total', 2)
            ->assertJsonPath('data.accounts.active', 1)->assertJsonPath('data.accounts.label', 'Akun Operator');
    }

    public function test_operator_counts_follow_current_personnel_scope_not_historical_unit(): void
    {
        $root = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->create(['parent_id' => $root->id]);
        $outside = Personel::factory()->create();
        $inside = Personel::factory()->create(['unit_organisasi_id' => $child->id]);
        KualifikasiPersonel::factory()->create(['personel_id' => $inside->id]);
        KualifikasiPersonel::factory()->create(['personel_id' => $outside->id]);
        RiwayatJabatan::factory()->create(['personel_id' => $outside->id, 'unit_organisasi_id' => $root->id]);
        RiwayatJabatan::factory()->create(['personel_id' => $inside->id, 'unit_organisasi_id' => $outside->unit_organisasi_id]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $scope = UserScope::factory()->create(['user_id' => $operator->id, 'unit_organisasi_id' => $root->id, 'scope_type' => ScopeType::OwnUnit]);
        Sanctum::actingAs($operator);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.personnel.total', 0);
        $scope->update(['scope_type' => ScopeType::UnitAndDescendants]);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.scope.unit_count', 2)
            ->assertJsonPath('data.personnel.total', 1)->assertJsonPath('data.qualifications', 1)
            ->assertJsonPath('data.active_positions', 1)->assertJsonPath('data.accounts', null)
            ->assertJsonPath('data.recent_personnel.0.id', $inside->id)->assertJsonCount(1, 'data.recent_personnel');
        $scope->update(['berlaku_sampai' => today()->subDay()]);
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.personnel.total', 0)
            ->assertJsonPath('data.scope.unit_count', 0)->assertJsonPath('data.active_positions', 0)->assertJsonCount(0, 'data.recent_personnel');
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.permissions.create_personnel', false);
    }

    public function test_recent_records_are_limited_and_future_or_deleted_positions_are_not_active(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $people = Personel::factory()->count(7)->create();
        RiwayatJabatan::factory()->create(['personel_id' => $people[0]->id, 'tanggal_mulai' => today()->addDay()]);
        RiwayatJabatan::factory()->create(['personel_id' => $people[1]->id])->delete();
        $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.active_positions', 0)
            ->assertJsonCount(5, 'data.recent_personnel')->assertJsonPath('data.recent_personnel.0.id', $people[6]->id);
    }

    public function test_technical_status_is_system_admin_only_and_contains_no_secrets(): void
    {
        foreach ([UserRole::Operator, UserRole::AdminSsdm] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/v1/system-status')->assertForbidden();
        }
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $response = $this->getJson('/api/v1/system-status')->assertOk()->assertJsonPath('data.database', 'connected');
        $this->assertSame(['database', 'database_driver', 'php_version', 'laravel_version', 'checked_at'], array_keys($response->json('data')));
    }

    public function test_failed_database_probe_returns_generic_error_without_exception_details(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new \RuntimeException('secret-connection-string'));
        $this->getJson('/api/v1/system-status')->assertStatus(503)
            ->assertExactJson(['success' => false, 'message' => 'Koneksi database tidak tersedia.']);
    }
}
