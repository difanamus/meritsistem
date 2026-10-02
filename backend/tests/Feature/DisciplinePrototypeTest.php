<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DisciplineSnapshot;
use App\Models\Personel;
use App\Models\User;
use App\Models\UserScope;
use Database\Seeders\DisciplinePrototypeSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DisciplinePrototypeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function readerRoles(): array
    {
        return ['system admin' => [UserRole::SystemAdmin], 'admin ssdm' => [UserRole::AdminSsdm]];
    }

    #[DataProvider('readerRoles')]
    public function test_admin_reads_only_own_personnel_demo_snapshots_with_pagination(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->assertTrue(Gate::forUser($user)->allows('view-discipline-prototype'));
        Sanctum::actingAs($user);
        $person = Personel::factory()->create();
        DisciplineSnapshot::factory()->for($person)->count(16)->create();
        $cancelled = DisciplineSnapshot::factory()->for($person)->create([
            'tanggal_keputusan' => '2025-01-01', 'status_keputusan' => 'dibatalkan', 'keterangan_pembatalan' => 'DEMO/BATAL/1',
        ]);
        DisciplineSnapshot::factory()->create();
        DisciplineSnapshot::factory()->for($person)->create(['source_system' => 'future_source']);

        $this->getJson("/api/v1/personel/{$person->id}/disiplin-prototype")->assertOk()
            ->assertJsonPath('integration.connected', false)->assertJsonPath('integration.status', 'prototype')
            ->assertJsonPath('integration.last_synced_at', null)->assertJsonPath('meta.total', 17)
            ->assertJsonCount(15, 'data')->assertJsonPath('data.0.id', $cancelled->id)
            ->assertJsonPath('data.0.status_keputusan', 'dibatalkan')->assertJsonPath('data.0.keterangan_pembatalan', 'DEMO/BATAL/1');
        $this->getJson("/api/v1/personel/{$person->id}/disiplin-prototype?page=2")->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_operator_is_forbidden_even_for_personnel_in_scope_and_public_profile_does_not_leak_snapshots(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $this->assertFalse(Gate::forUser($operator)->allows('view-discipline-prototype'));
        Sanctum::actingAs($operator);
        $person = Personel::factory()->create();
        UserScope::factory()->for($operator)->create(['unit_organisasi_id' => $person->unit_organisasi_id]);
        DisciplineSnapshot::factory()->for($person)->create(['ringkasan' => 'PRIVATE DEMO RECORD']);

        $this->getJson("/api/v1/personel/{$person->id}/disiplin-prototype")->assertForbidden();
        $this->getJson("/api/v1/personel/{$person->id}")->assertOk()->assertJsonMissing(['ringkasan' => 'PRIVATE DEMO RECORD']);
    }

    public function test_anonymous_request_is_unauthorized_and_write_methods_are_not_available(): void
    {
        $person = Personel::factory()->create();
        $path = "/api/v1/personel/{$person->id}/disiplin-prototype";
        $this->getJson($path)->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $this->postJson($path, [])->assertMethodNotAllowed();
        $this->putJson($path, [])->assertMethodNotAllowed();
        $this->deleteJson($path)->assertMethodNotAllowed();
        $this->assertDatabaseCount('discipline_snapshots', 0);
    }

    public function test_empty_missing_and_archived_personnel_do_not_return_demo_allegations(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $person = Personel::factory()->create();
        $this->getJson("/api/v1/personel/{$person->id}/disiplin-prototype")->assertOk()->assertJsonCount(0, 'data');
        $person->delete();
        $this->getJson("/api/v1/personel/{$person->id}/disiplin-prototype")->assertNotFound();
        $this->getJson('/api/v1/personel/999999/disiplin-prototype')->assertNotFound();
    }

    public function test_seeder_only_uses_exact_demo_identity_and_is_idempotent_without_overwriting(): void
    {
        $person = Personel::factory()->create(['nomor_identitas' => '99990001', 'nama_lengkap' => 'Aditya Pratama (DEMO 001)']);
        $other = Personel::factory()->create();
        $this->seed(DisciplinePrototypeSeeder::class);
        $this->assertDatabaseCount('discipline_snapshots', 2);
        $this->assertSame(0, DisciplineSnapshot::query()->where('personel_id', $other->id)->count());
        $record = DisciplineSnapshot::query()->where('personel_id', $person->id)->firstOrFail();
        $record->update(['ringkasan' => 'Preserve this change']);
        $this->seed(DisciplinePrototypeSeeder::class);
        $this->assertDatabaseCount('discipline_snapshots', 2);
        $this->assertSame('Preserve this change', $record->refresh()->ringkasan);
    }

    public function test_seeder_refuses_a_manual_person_with_colliding_demo_number(): void
    {
        Personel::factory()->create(['nomor_identitas' => '99990001', 'nama_lengkap' => 'Manual person']);
        try {
            $this->seed(DisciplinePrototypeSeeder::class);
            $this->fail('Seeder must refuse manual personnel.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('DEMO 001 belum tersedia', $exception->getMessage());
        }
        $this->assertDatabaseCount('discipline_snapshots', 0);
    }

    public function test_seeder_cannot_run_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prototype disiplin DEMO hanya untuk local/testing.');
        (new DisciplinePrototypeSeeder)->run();
    }
}
