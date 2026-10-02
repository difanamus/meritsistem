<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonnelSortingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function textSorts(): array
    {
        return [
            'job ascending' => ['jabatan', 'asc', 'Zulu Personel'],
            'job descending' => ['jabatan', 'desc', 'Alpha Personel'],
            'unit ascending' => ['satker', 'asc', 'Zulu Personel'],
            'unit descending' => ['satker', 'desc', 'Alpha Personel'],
        ];
    }

    #[DataProvider('textSorts')]
    public function test_text_sort_is_applied_before_pagination_and_ignores_non_current_positions(string $sort, string $direction, string $expected): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $alphaUnit = UnitOrganisasi::factory()->create(['nama' => 'Alpha Satker']);
        $zuluUnit = UnitOrganisasi::factory()->create(['nama' => 'Zulu Satker']);
        $alpha = Personel::factory()->for($zuluUnit, 'unitOrganisasi')->create(['nama_lengkap' => 'Alpha Personel']);
        $zulu = Personel::factory()->for($alphaUnit, 'unitOrganisasi')->create(['nama_lengkap' => 'Zulu Personel']);
        RiwayatJabatan::factory()->for($alpha)->for($zuluUnit, 'unitOrganisasi')->create(['nama_jabatan' => 'Zulu Jabatan']);
        RiwayatJabatan::factory()->for($zulu)->for($alphaUnit, 'unitOrganisasi')->create(['nama_jabatan' => 'Alpha Jabatan']);
        RiwayatJabatan::factory()->for($zulu)->create(['nama_jabatan' => 'Zzz tambahan', 'is_jabatan_utama' => false]);
        RiwayatJabatan::factory()->for($zulu)->create(['nama_jabatan' => 'Zzz riwayat lama', 'tanggal_selesai' => '2025-01-01']);
        $deleted = RiwayatJabatan::factory()->for($zulu)->create(['nama_jabatan' => 'Zzz dihapus', 'is_jabatan_utama' => false]);
        $deleted->delete();

        $this->getJson("/api/v1/personel?sort={$sort}&direction={$direction}&per_page=1")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.nama_lengkap', $expected);
    }

    public function test_job_sort_keeps_empty_values_deterministic_and_filters_operator_scope(): void
    {
        $unit = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($unit)->create(['scope_type' => ScopeType::OwnUnit]);
        Sanctum::actingAs($operator);
        $first = Personel::factory()->for($unit, 'unitOrganisasi')->create(['nama_lengkap' => 'Zulu', 'status' => 'nonaktif']);
        $second = Personel::factory()->for($unit, 'unitOrganisasi')->create(['nama_lengkap' => 'Alpha', 'status' => 'nonaktif']);
        $outside = Personel::factory()->create();
        RiwayatJabatan::factory()->for($outside)->create(['nama_jabatan' => 'Outside']);

        $this->getJson('/api/v1/personel?sort=jabatan&direction=asc&status=nonaktif')->assertOk()
            ->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $first->id)->assertJsonPath('data.1.id', $second->id);
    }

    public function test_sort_and_direction_reject_untrusted_sql_identifiers(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->getJson('/api/v1/personel?sort=nama%3BDROP&direction=desc%3BDROP')
            ->assertUnprocessable()->assertJsonValidationErrors(['sort', 'direction']);
    }

    public static function mixedCaseSorts(): array
    {
        return [
            ['nama', 'asc'], ['nama', 'desc'],
            ['jabatan', 'asc'], ['jabatan', 'desc'],
            ['satker', 'asc'], ['satker', 'desc'],
        ];
    }

    #[DataProvider('mixedCaseSorts')]
    public function test_text_sort_ignores_case_without_changing_saved_names(string $sort, string $direction): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        foreach (['Eka Wijaya', 'diva namus akbar', 'Dimas Pratama'] as $name) {
            $unit = UnitOrganisasi::factory()->create(['nama' => $name]);
            $personel = Personel::factory()->for($unit, 'unitOrganisasi')->create(['nama_lengkap' => $name]);
            RiwayatJabatan::factory()->for($personel)->for($unit, 'unitOrganisasi')->create(['nama_jabatan' => $name]);
        }
        $expected = $direction === 'asc'
            ? ['Dimas Pratama', 'diva namus akbar', 'Eka Wijaya']
            : ['Eka Wijaya', 'diva namus akbar', 'Dimas Pratama'];

        $response = $this->getJson("/api/v1/personel?sort={$sort}&direction={$direction}&per_page=2")
            ->assertOk()->assertJsonPath('meta.total', 3);
        $this->assertSame(array_slice($expected, 0, 2), array_column($response->json('data'), 'nama_lengkap'));
        $this->getJson("/api/v1/personel?sort={$sort}&direction={$direction}&per_page=2&page=2")
            ->assertOk()->assertJsonPath('data.0.nama_lengkap', $expected[2]);
        $this->assertDatabaseHas('personel', ['nama_lengkap' => 'diva namus akbar']);
    }
}
