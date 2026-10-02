<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\User;
use Database\Seeders\PangkatSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RankReferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function rankPermissions(): array
    {
        return [
            'active programmer' => [UserRole::SystemAdmin, true, true],
            'inactive programmer' => [UserRole::SystemAdmin, false, false],
            'active SSDM' => [UserRole::AdminSsdm, true, false],
            'inactive SSDM' => [UserRole::AdminSsdm, false, false],
            'active operator' => [UserRole::Operator, true, false],
            'inactive operator' => [UserRole::Operator, false, false],
        ];
    }

    #[DataProvider('rankPermissions')]
    public function test_rank_maintenance_permission_matrix(UserRole $role, bool $active, bool $allowed): void
    {
        $user = User::factory()->make(['role' => $role, 'is_active' => $active]);

        $this->assertSame($allowed, Gate::forUser($user)->allows('manage-reference', 'pangkat'));
    }

    public function test_ssdm_can_read_and_select_rank_but_mutations_return_403_without_changes(): void
    {
        $rank = Pangkat::factory()->create(['kode' => 'BHARADA', 'nama' => 'Bhayangkara Dua']);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $payload = ['kode' => 'NEW', 'nama' => 'Tidak boleh', 'jenis_personel' => 'polri', 'urutan' => 99, 'is_active' => false];

        $this->getJson('/api/v1/references/pangkat')->assertOk()->assertJsonPath('data.0.id', $rank->id);
        $this->getJson("/api/v1/references/pangkat/{$rank->id}")->assertOk()->assertJsonPath('data.nama', 'Bhayangkara Dua');
        $this->getJson('/api/v1/reference-options?only=non_unit')->assertOk()->assertJsonPath('data.pangkat.0.id', $rank->id);
        $this->postJson('/api/v1/references/pangkat', $payload)->assertForbidden();
        $this->putJson("/api/v1/references/pangkat/{$rank->id}", $payload)->assertForbidden();
        $this->deleteJson("/api/v1/references/pangkat/{$rank->id}")->assertForbidden();

        $this->assertDatabaseMissing('pangkat', ['kode' => 'NEW']);
        $this->assertDatabaseHas('pangkat', ['id' => $rank->id, 'nama' => 'Bhayangkara Dua', 'is_active' => true, 'deleted_at' => null]);
    }

    public function test_complete_rank_seed_is_idempotent_and_available_in_hierarchy_order(): void
    {
        $this->seed(PangkatSeeder::class);
        $ids = Pangkat::query()->orderBy('kode')->pluck('id', 'kode')->all();

        $this->seed(PangkatSeeder::class);

        $this->assertSame($ids, Pangkat::query()->orderBy('kode')->pluck('id', 'kode')->all());
        $this->assertSame(22, Pangkat::query()->where('jenis_personel', 'polri')->count());
        $this->assertSame(17, Pangkat::query()->where('jenis_personel', 'pns')->count());
        $this->assertSame(['BHARADA', 'BHARATU', 'BHARAKA', 'ABRIPDA', 'ABRIPTU', 'ABRIP', 'BRIPDA'],
            Pangkat::query()->where('jenis_personel', 'polri')->orderBy('urutan')->limit(7)->pluck('kode')->all());
        $this->assertDatabaseHas('pangkat', ['kode' => 'BHARADA', 'nama' => 'Bhayangkara Dua', 'is_active' => true]);
        $this->assertDatabaseHas('pangkat', ['kode' => 'ABRIP', 'nama' => 'Ajun Brigadir Polisi', 'urutan' => 6]);
        $this->assertDatabaseHas('pangkat', ['kode' => 'JENDERAL', 'urutan' => 22]);
        $this->assertDatabaseHas('pangkat', ['kode' => 'PNS-IA', 'nama' => 'Juru Muda', 'urutan' => 1]);
        $this->assertDatabaseHas('pangkat', ['kode' => 'PNS-IVE', 'nama' => 'Pembina Utama', 'urutan' => 17]);

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $this->getJson('/api/v1/references/pangkat?per_page=100')->assertOk()->assertJsonCount(39, 'data')
            ->assertJsonPath('data.0.kode', 'PNS-IA')->assertJsonPath('data.16.kode', 'PNS-IVE')
            ->assertJsonPath('data.17.kode', 'BHARADA')->assertJsonPath('data.22.kode', 'ABRIP')
            ->assertJsonPath('data.23.kode', 'BRIPDA')->assertJsonPath('data.38.kode', 'JENDERAL');
        $this->getJson('/api/v1/reference-options?only=non_unit')->assertOk()->assertJsonCount(39, 'data.pangkat')
            ->assertJsonPath('data.pangkat.17.kode', 'BHARADA');
    }

    public function test_rank_seed_preserves_existing_personnel_links_and_maintenance_status(): void
    {
        $rank = Pangkat::factory()->create(['kode' => 'BRIPDA', 'is_active' => false]);
        $person = Personel::factory()->for($rank)->create();
        $deleted = Pangkat::factory()->create(['kode' => 'PNS-IIIA']);
        $deleted->delete();

        $this->seed(PangkatSeeder::class);

        $this->assertDatabaseHas('pangkat', ['id' => $rank->id, 'kode' => 'BRIPDA', 'urutan' => 7, 'is_active' => false]);
        $this->assertSame($rank->id, $person->refresh()->pangkat_id);
        $this->assertSoftDeleted($deleted);
        $this->assertSame(39, Pangkat::withTrashed()->count());
    }
}
