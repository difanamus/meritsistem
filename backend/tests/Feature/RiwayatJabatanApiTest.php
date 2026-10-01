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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiwayatJabatanApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_additional_assignment_can_overlap_primary_position_and_store_private_pdf(): void
    {
        Storage::fake('local');
        [$operator, $personel, $unit] = $this->operatorAndPersonnel();
        Sanctum::actingAs($operator);
        RiwayatJabatan::factory()->for($personel)->for($unit)->create([
            'tanggal_mulai' => '2024-01-01',
        ]);

        $response = $this->postJson("/api/v1/personel/{$personel->id}/riwayat-jabatan", [
            ...$this->positionPayload($unit),
            'nama_jabatan' => 'Plt. Kanit Intelkam',
            'tanggal_mulai' => '2025-01-01',
            'is_jabatan_utama' => false,
            'dokumen_sk' => UploadedFile::fake()->createWithContent('sk-plt.pdf', "%PDF-1.4\nSK PLT"),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.is_jabatan_utama', false)
            ->assertJsonPath('data.nama_jabatan', 'Plt. Kanit Intelkam');

        $path = RiwayatJabatan::query()->find($response->json('data.id'))->dokumen_sk_path;
        Storage::disk('local')->assertExists($path);
    }

    public function test_overlapping_primary_position_is_rejected(): void
    {
        [$operator, $personel, $unit] = $this->operatorAndPersonnel();
        Sanctum::actingAs($operator);
        RiwayatJabatan::factory()->for($personel)->for($unit)->create([
            'tanggal_mulai' => '2024-01-01',
        ]);

        $this->postJson("/api/v1/personel/{$personel->id}/riwayat-jabatan", [
            ...$this->positionPayload($unit),
            'tanggal_mulai' => '2025-01-01',
            'is_jabatan_utama' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_mulai');
    }

    public function test_active_primary_position_cannot_be_ended_or_deleted_directly(): void
    {
        [$operator, $personel, $unit] = $this->operatorAndPersonnel();
        Sanctum::actingAs($operator);
        $position = RiwayatJabatan::factory()->for($personel)->for($unit)->create([
            'tanggal_mulai' => '2024-01-01',
        ]);

        $this->putJson("/api/v1/riwayat-jabatan/{$position->id}", [
            'tanggal_selesai' => '2025-01-01',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_selesai');

        $this->deleteJson("/api/v1/riwayat-jabatan/{$position->id}")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_completed_position_can_be_updated_and_soft_deleted(): void
    {
        [$operator, $personel, $unit] = $this->operatorAndPersonnel();
        Sanctum::actingAs($operator);
        $position = RiwayatJabatan::factory()->for($personel)->for($unit)->create([
            'tanggal_mulai' => '2020-01-01',
            'tanggal_selesai' => '2022-01-01',
        ]);

        $this->putJson("/api/v1/riwayat-jabatan/{$position->id}", [
            'nama_jabatan' => 'Jabatan Terkoreksi',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_jabatan', 'Jabatan Terkoreksi');

        $this->deleteJson("/api/v1/riwayat-jabatan/{$position->id}")->assertOk();
        $this->assertSoftDeleted($position);
    }

    public function test_operator_cannot_assign_position_in_unit_outside_scope(): void
    {
        [$operator, $personel] = $this->operatorAndPersonnel();
        Sanctum::actingAs($operator);

        $this->postJson("/api/v1/personel/{$personel->id}/riwayat-jabatan", [
            ...$this->positionPayload(UnitOrganisasi::factory()->create()),
            'is_jabatan_utama' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unit_organisasi_id');
    }

    /** @return array{User, Personel, UnitOrganisasi} */
    private function operatorAndPersonnel(): array
    {
        $unit = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($unit)->create([
            'scope_type' => ScopeType::OwnUnit,
        ]);

        return [$operator, Personel::factory()->for($unit)->create(), $unit];
    }

    /** @return array<string, mixed> */
    private function positionPayload(UnitOrganisasi $unit): array
    {
        return [
            'nama_jabatan' => 'Banit Sat Intelkam',
            'unit_organisasi_id' => $unit->id,
            'bidang_fungsi_id' => BidangFungsi::factory()->create()->id,
            'jenis_penugasan_id' => JenisPenugasan::factory()->create()->id,
            'tanggal_mulai' => '2023-01-01',
        ];
    }
}
