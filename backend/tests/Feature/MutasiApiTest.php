<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\BidangFungsi;
use App\Models\JenisPenugasan;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use App\Services\PositionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MutasiApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_operator_with_parent_scope_can_mutate_personnel_atomically(): void
    {
        [$operator, $personel, $source, $destination, $currentPosition] = $this->mutationFixture();
        Sanctum::actingAs($operator);

        $response = $this->postJson("/api/v1/personel/{$personel->id}/mutasi", [
            ...$this->newPositionPayload(),
            'unit_organisasi_id' => $destination->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unit_organisasi.id', $destination->id);

        $this->assertSame($destination->id, $personel->refresh()->unit_organisasi_id);
        $this->assertSame('2024-12-31', $currentPosition->refresh()->tanggal_selesai->toDateString());
        $this->assertDatabaseHas('riwayat_jabatan', [
            'personel_id' => $personel->id,
            'unit_organisasi_id' => $destination->id,
            'tanggal_selesai' => null,
            'created_by' => $operator->id,
        ]);
        $this->assertSame(
            '2025-01-01',
            $personel->jabatanUtamaAktif()->first()->tanggal_mulai->toDateString(),
        );
        $this->assertNotSame($source->id, $personel->unit_organisasi_id);
    }

    public function test_mutation_rejects_destination_outside_operator_scope_without_changes(): void
    {
        [$operator, $personel, $source, , $currentPosition] = $this->mutationFixture();
        $outside = UnitOrganisasi::factory()->create();
        Sanctum::actingAs($operator);

        $this->postJson("/api/v1/personel/{$personel->id}/mutasi", [
            ...$this->newPositionPayload(),
            'unit_organisasi_id' => $outside->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unit_organisasi_id');

        $this->assertSame($source->id, $personel->refresh()->unit_organisasi_id);
        $this->assertNull($currentPosition->refresh()->tanggal_selesai);
    }

    public function test_change_position_keeps_personnel_in_same_unit(): void
    {
        [$operator, $personel, $source, , $currentPosition] = $this->mutationFixture();
        Sanctum::actingAs($operator);

        $this->postJson("/api/v1/personel/{$personel->id}/ganti-jabatan", $this->newPositionPayload())
            ->assertCreated()
            ->assertJsonPath('data.unit_organisasi.id', $source->id);

        $this->assertSame($source->id, $personel->refresh()->unit_organisasi_id);
        $this->assertSame('2024-12-31', $currentPosition->refresh()->tanggal_selesai->toDateString());
    }

    public function test_old_unit_loses_access_and_new_unit_gains_access_after_mutation(): void
    {
        [$parentOperator, $personel, $source, $destination] = $this->mutationFixture();
        $sourceOperator = User::factory()->create(['role' => UserRole::Operator]);
        $destinationOperator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($sourceOperator)->for($source)->create();
        UserScope::factory()->for($destinationOperator)->for($destination)->create();
        Sanctum::actingAs($parentOperator);

        $this->postJson("/api/v1/personel/{$personel->id}/mutasi", [
            ...$this->newPositionPayload(),
            'unit_organisasi_id' => $destination->id,
        ])->assertCreated();

        $personel->refresh();
        $this->assertFalse(Gate::forUser($sourceOperator)->allows('view', $personel));
        $this->assertTrue(Gate::forUser($destinationOperator)->allows('view', $personel));
    }

    public function test_mutation_rolls_back_if_new_position_cannot_be_created(): void
    {
        [$operator, $personel, $source, $destination, $currentPosition] = $this->mutationFixture();

        try {
            app(PositionService::class)->replacePrimary($personel, [
                ...$this->newPositionPayload(),
                'unit_organisasi_id' => $destination->id,
                'bidang_fungsi_id' => 999999,
            ], $operator, true);
            $this->fail('Mutasi seharusnya gagal karena foreign key tidak valid.');
        } catch (QueryException) {
            $this->assertSame($source->id, $personel->refresh()->unit_organisasi_id);
            $this->assertNull($currentPosition->refresh()->tanggal_selesai);
            $this->assertSame(1, $personel->riwayatJabatan()->count());
        }
    }

    /** @return array{User, Personel, UnitOrganisasi, UnitOrganisasi, RiwayatJabatan} */
    private function mutationFixture(): array
    {
        $parent = UnitOrganisasi::factory()->create();
        $source = UnitOrganisasi::factory()->for($parent, 'parent')->create();
        $destination = UnitOrganisasi::factory()->for($parent, 'parent')->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($parent)->create([
            'scope_type' => ScopeType::UnitAndDescendants,
        ]);
        $personel = Personel::factory()->for($source)->create();
        $position = RiwayatJabatan::factory()->for($personel)->for($source)->create([
            'tanggal_mulai' => '2024-01-01',
        ]);

        return [$operator, $personel, $source, $destination, $position];
    }

    /** @return array<string, mixed> */
    private function newPositionPayload(): array
    {
        return [
            'nama_jabatan' => 'Kanit Intelkam',
            'bidang_fungsi_id' => BidangFungsi::factory()->create()->id,
            'jenis_penugasan_id' => JenisPenugasan::factory()->create()->id,
            'tanggal_mulai' => '2025-01-01',
        ];
    }
}
