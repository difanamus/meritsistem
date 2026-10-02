<?php

namespace Tests\Feature;

use App\Enums\JenisUnit;
use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\JenisKualifikasi;
use App\Models\KualifikasiPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReferenceAdministrationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function referenceTypes(): array
    {
        return [
            'unit' => ['unit-organisasi', 'unit_organisasi', ['jenis_unit' => 'root', 'parent_id' => null]],
            'rank' => ['pangkat', 'pangkat', ['jenis_personel' => 'polri', 'urutan' => 4]],
            'function' => ['bidang-fungsi', 'bidang_fungsi', ['deskripsi' => 'Fungsi demo']],
            'qualification' => ['jenis-kualifikasi', 'jenis_kualifikasi', []],
            'assignment' => ['jenis-penugasan', 'jenis_penugasan', []],
        ];
    }

    #[DataProvider('referenceTypes')]
    public function test_global_admin_can_create_read_update_and_delete_unused_reference(string $type, string $table, array $extra): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => $type === 'pangkat' ? UserRole::SystemAdmin : UserRole::AdminSsdm]));
        $payload = ['kode' => 'DEMO', 'nama' => 'Referensi Demo', 'is_active' => true, ...$extra];

        $created = $this->postJson("/api/v1/references/{$type}", $payload)
            ->assertCreated()->assertJsonPath('data.kode', 'DEMO');
        $id = $created->json('data.id');

        $this->assertDatabaseHas($table, ['id' => $id, 'nama' => 'Referensi Demo']);
        $this->getJson("/api/v1/references/{$type}/{$id}")->assertOk()->assertJsonPath('data.id', $id);
        $this->putJson("/api/v1/references/{$type}/{$id}", [...$payload, 'nama' => 'Diperbarui'])
            ->assertOk()->assertJsonPath('data.nama', 'Diperbarui');
        $this->assertDatabaseHas($table, ['id' => $id, 'nama' => 'Diperbarui']);
        $this->deleteJson("/api/v1/references/{$type}/{$id}")->assertOk();
        $this->assertSoftDeleted($table, ['id' => $id]);
        $this->getJson("/api/v1/references/{$type}/{$id}")->assertNotFound();
    }

    #[DataProvider('referenceTypes')]
    public function test_operator_can_read_but_cannot_mutate_references(string $type, string $table, array $extra): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $payload = ['kode' => 'DEMO', 'nama' => 'Demo', 'is_active' => true, ...$extra];
        $id = $this->postJson("/api/v1/references/{$type}", $payload)->assertCreated()->json('data.id');
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));

        $this->getJson("/api/v1/references/{$type}")->assertOk();
        $this->postJson("/api/v1/references/{$type}", $payload)->assertForbidden();
        $this->putJson("/api/v1/references/{$type}/{$id}", $payload)->assertForbidden();
        $this->deleteJson("/api/v1/references/{$type}/{$id}")->assertForbidden();

        $this->assertDatabaseHas($table, ['id' => $id, 'deleted_at' => null]);
    }

    public function test_reference_routes_require_authentication_and_reject_unknown_type(): void
    {
        $this->getJson('/api/v1/references/pangkat')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));

        $this->getJson('/api/v1/references/users')->assertNotFound();
        $this->postJson('/api/v1/references/pangkat', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['kode', 'nama', 'jenis_personel', 'urutan', 'is_active']);
    }

    public function test_unit_hierarchy_rejects_self_parent_and_descendant_cycles(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $root = UnitOrganisasi::factory()->create(['jenis_unit' => JenisUnit::Root]);
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create(['jenis_unit' => JenisUnit::Polda]);
        $grandchild = UnitOrganisasi::factory()->for($child, 'parent')->create(['jenis_unit' => JenisUnit::Polres]);
        $payload = ['kode' => $child->kode, 'nama' => $child->nama, 'jenis_unit' => 'polda', 'is_active' => true];

        $this->putJson("/api/v1/references/unit-organisasi/{$child->id}", [...$payload, 'parent_id' => $child->id])
            ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $this->putJson("/api/v1/references/unit-organisasi/{$child->id}", [...$payload, 'parent_id' => $grandchild->id])
            ->assertUnprocessable()->assertJsonValidationErrors('parent_id');

        $this->assertSame($root->id, $child->refresh()->parent_id);
    }

    public function test_unit_requires_available_parent_and_parent_cannot_be_deactivated_before_children(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $root = UnitOrganisasi::factory()->create(['jenis_unit' => JenisUnit::Root]);
        UnitOrganisasi::factory()->for($root, 'parent')->create();
        $payload = ['kode' => 'NEW', 'nama' => 'Unit baru', 'jenis_unit' => 'polda', 'is_active' => true];

        $this->postJson('/api/v1/references/unit-organisasi', [...$payload, 'parent_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $this->putJson("/api/v1/references/unit-organisasi/{$root->id}", [
            'kode' => $root->kode, 'nama' => $root->nama, 'jenis_unit' => 'root', 'parent_id' => null, 'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_operator_reads_only_units_in_scope(): void
    {
        $unit = UnitOrganisasi::factory()->create();
        $outside = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($unit)->create(['scope_type' => ScopeType::OwnUnit]);
        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/references/unit-organisasi')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $unit->id);
        $this->getJson("/api/v1/references/unit-organisasi/{$outside->id}")->assertForbidden();
    }

    public function test_used_references_cannot_be_deleted_even_when_dependent_record_is_soft_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $person = Personel::factory()->create();
        $qualification = KualifikasiPersonel::factory()->for($person)->create();
        $position = RiwayatJabatan::factory()->for($person)->create();
        $qualification->delete();
        $position->delete();
        $person->delete();

        foreach ([
            ['unit-organisasi', $person->unit_organisasi_id],
            ['pangkat', $person->pangkat_id],
            ['bidang-fungsi', $qualification->bidang_fungsi_id],
            ['jenis-kualifikasi', $qualification->jenis_kualifikasi_id],
            ['jenis-penugasan', $position->jenis_penugasan_id],
        ] as [$type, $id]) {
            $this->deleteJson("/api/v1/references/{$type}/{$id}")->assertConflict();
        }
    }

    public function test_used_rank_cannot_change_personnel_type_but_can_be_deactivated(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $rank = Pangkat::factory()->create(['jenis_personel' => 'polri']);
        Personel::factory()->for($rank)->create();
        $payload = ['kode' => $rank->kode, 'nama' => $rank->nama, 'urutan' => 1, 'is_active' => false];

        $this->putJson("/api/v1/references/pangkat/{$rank->id}", [...$payload, 'jenis_personel' => 'pns'])
            ->assertUnprocessable()->assertJsonValidationErrors('jenis_personel');
        $this->putJson("/api/v1/references/pangkat/{$rank->id}", [...$payload, 'jenis_personel' => 'polri'])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->getJson('/api/v1/reference-options')->assertOk()->assertJsonCount(0, 'data.pangkat');
        $this->assertDatabaseHas('pangkat', ['id' => $rank->id, 'is_active' => false, 'deleted_at' => null]);
    }

    public function test_code_stays_unique_after_soft_delete_and_filters_support_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        $type = JenisKualifikasi::factory()->create(['kode' => 'DEMO']);
        $type->delete();
        JenisKualifikasi::factory()->create(['nama' => 'Pelatihan Aktif', 'is_active' => true]);
        JenisKualifikasi::factory()->create(['nama' => 'Pelatihan Nonaktif', 'is_active' => false]);

        $this->postJson('/api/v1/references/jenis-kualifikasi', ['kode' => 'DEMO', 'nama' => 'Demo', 'is_active' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('kode');
        $this->getJson('/api/v1/references/jenis-kualifikasi?search=pelatihan&status=active&per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama', 'Pelatihan Aktif')
            ->assertJsonPath('meta.total', 1);
    }
}
