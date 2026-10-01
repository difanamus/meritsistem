<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\StatusPersonel;
use App\Enums\UserRole;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\JenisPenugasan;
use App\Models\KualifikasiPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonelApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_personnel_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/personel')->assertUnauthorized();
        $this->postJson('/api/v1/personel', [])->assertUnauthorized();
    }

    public function test_admin_can_create_active_personnel_with_initial_primary_position_atomically(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);

        $unit = UnitOrganisasi::factory()->create();
        $pangkat = Pangkat::factory()->create();
        $bidang = BidangFungsi::factory()->create();
        $jenisPenugasan = JenisPenugasan::factory()->create();

        $response = $this->postJson('/api/v1/personel', $this->validPayload(
            $unit,
            $pangkat,
            $bidang,
            $jenisPenugasan,
        ));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nama_lengkap', 'Agus Setiawan')
            ->assertJsonPath('data.jabatan_utama_aktif.nama_jabatan', 'Banit Sat Intelkam');

        $personelId = $response->json('data.id');
        $this->assertDatabaseHas('personel', [
            'id' => $personelId,
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('riwayat_jabatan', [
            'personel_id' => $personelId,
            'is_jabatan_utama' => true,
            'tanggal_selesai' => null,
        ]);
    }

    public function test_active_personnel_requires_initial_primary_position(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);

        $payload = $this->validPayload(
            UnitOrganisasi::factory()->create(),
            Pangkat::factory()->create(),
            BidangFungsi::factory()->create(),
            JenisPenugasan::factory()->create(),
        );
        unset($payload['jabatan_utama']);

        $this->postJson('/api/v1/personel', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jabatan_utama');

        $this->assertDatabaseCount('personel', 0);
    }

    public function test_operator_cannot_create_personnel_outside_scope(): void
    {
        $allowedUnit = UnitOrganisasi::factory()->create();
        $outsideUnit = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($allowedUnit)->create([
            'scope_type' => ScopeType::OwnUnit,
        ]);
        Sanctum::actingAs($operator);

        $this->postJson('/api/v1/personel', $this->validPayload(
            $outsideUnit,
            Pangkat::factory()->create(),
            BidangFungsi::factory()->create(),
            JenisPenugasan::factory()->create(),
        ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unit_organisasi_id');
    }

    public function test_operator_list_and_detail_are_limited_to_organizational_scope(): void
    {
        $root = UnitOrganisasi::factory()->create();
        $child = UnitOrganisasi::factory()->for($root, 'parent')->create();
        $outside = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($root)->create([
            'scope_type' => ScopeType::UnitAndDescendants,
        ]);
        Sanctum::actingAs($operator);

        $visible = Personel::factory()->for($child)->create(['nama_lengkap' => 'Personel Terlihat']);
        $hidden = Personel::factory()->for($outside)->create(['nama_lengkap' => 'Personel Rahasia']);

        $this->getJson('/api/v1/personel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);

        $this->getJson("/api/v1/personel/{$visible->id}")->assertOk();
        $this->getJson("/api/v1/personel/{$hidden->id}")->assertForbidden();
    }

    public function test_personnel_identity_must_be_unique_and_rank_must_match_personnel_type(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);
        Personel::factory()->create(['nomor_identitas' => '12345678']);
        $unit = UnitOrganisasi::factory()->create();
        $pnsRank = Pangkat::factory()->create(['jenis_personel' => 'pns']);
        $payload = $this->validPayload(
            $unit,
            $pnsRank,
            BidangFungsi::factory()->create(),
            JenisPenugasan::factory()->create(),
        );

        $this->postJson('/api/v1/personel', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nomor_identitas', 'pangkat_id']);
    }

    public function test_unit_cannot_be_changed_through_regular_update_endpoint(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);
        $personel = Personel::factory()->create();

        $this->putJson("/api/v1/personel/{$personel->id}", [
            'unit_organisasi_id' => UnitOrganisasi::factory()->create()->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unit_organisasi_id');
    }

    public function test_changing_status_from_active_closes_primary_position(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);
        $personel = Personel::factory()->create(['status' => StatusPersonel::Aktif]);
        $position = $personel->riwayatJabatan()->create([
            'nama_jabatan' => 'Banit',
            'unit_organisasi_id' => $personel->unit_organisasi_id,
            'bidang_fungsi_id' => BidangFungsi::factory()->create()->id,
            'jenis_penugasan_id' => JenisPenugasan::factory()->create()->id,
            'tanggal_mulai' => today()->subYear(),
            'is_jabatan_utama' => true,
        ]);

        $this->putJson("/api/v1/personel/{$personel->id}", [
            'status' => StatusPersonel::Pensiun->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', StatusPersonel::Pensiun->value)
            ->assertJsonPath('data.jabatan_utama_aktif', null);

        $this->assertNotNull($position->refresh()->tanggal_selesai);
    }

    public function test_delete_soft_deletes_personnel_and_closes_active_primary_position(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);
        $personel = Personel::factory()->create();
        $position = $personel->riwayatJabatan()->create([
            'nama_jabatan' => 'Banit',
            'unit_organisasi_id' => $personel->unit_organisasi_id,
            'bidang_fungsi_id' => BidangFungsi::factory()->create()->id,
            'jenis_penugasan_id' => JenisPenugasan::factory()->create()->id,
            'tanggal_mulai' => today()->subYear(),
            'is_jabatan_utama' => true,
        ]);

        $this->deleteJson("/api/v1/personel/{$personel->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted($personel);
        $this->assertNotNull($position->refresh()->tanggal_selesai);
    }

    public function test_function_filter_returns_factual_qualification_and_experience_summary_without_score(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($admin);
        $intelkam = BidangFungsi::factory()->create(['nama' => 'Intelkam']);
        $reskrim = BidangFungsi::factory()->create(['nama' => 'Reskrim']);
        $qualificationType = JenisKualifikasi::factory()->create();
        $assignmentType = JenisPenugasan::factory()->create();
        $personA = Personel::factory()->create(['nama_lengkap' => 'Andi Intel']);
        $personB = Personel::factory()->create(['nama_lengkap' => 'Budi Intel']);
        $personC = Personel::factory()->create(['nama_lengkap' => 'Citra Reskrim']);

        KualifikasiPersonel::factory()->count(2)->for($personA)->create([
            'bidang_fungsi_id' => $intelkam->id,
            'jenis_kualifikasi_id' => $qualificationType->id,
            'tahun' => 2025,
        ]);
        KualifikasiPersonel::factory()->for($personB)->create([
            'bidang_fungsi_id' => $intelkam->id,
            'jenis_kualifikasi_id' => $qualificationType->id,
            'tahun' => 2024,
        ]);
        KualifikasiPersonel::factory()->for($personC)->create([
            'bidang_fungsi_id' => $reskrim->id,
            'jenis_kualifikasi_id' => $qualificationType->id,
        ]);
        RiwayatJabatan::factory()->for($personA)->create([
            'bidang_fungsi_id' => $intelkam->id,
            'jenis_penugasan_id' => $assignmentType->id,
            'tanggal_mulai' => '2020-01-01',
            'tanggal_selesai' => '2022-12-31',
        ]);
        RiwayatJabatan::factory()->for($personB)->create([
            'bidang_fungsi_id' => $intelkam->id,
            'jenis_penugasan_id' => $assignmentType->id,
            'tanggal_mulai' => '2010-01-01',
            'tanggal_selesai' => '2020-12-31',
        ]);

        $byQualificationCount = $this->getJson(
            "/api/v1/personel?bidang_fungsi_id={$intelkam->id}&sort=jumlah_kualifikasi&direction=desc",
        );
        $byQualificationCount
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $personA->id)
            ->assertJsonPath('data.0.ringkasan_relevan.jumlah_kualifikasi', 2)
            ->assertJsonMissingPath('data.0.skor');

        $this->getJson(
            "/api/v1/personel?bidang_fungsi_id={$intelkam->id}&sort=durasi_pengalaman&direction=desc",
        )
            ->assertOk()
            ->assertJsonPath('data.0.id', $personB->id);
    }

    /** @return array<string, mixed> */
    private function validPayload(
        UnitOrganisasi $unit,
        Pangkat $pangkat,
        BidangFungsi $bidang,
        JenisPenugasan $jenisPenugasan,
    ): array {
        return [
            'jenis_personel' => 'polri',
            'nomor_identitas' => '12345678',
            'nama_lengkap' => 'Agus Setiawan',
            'pangkat_id' => $pangkat->id,
            'tempat_lahir' => 'Bengkulu',
            'tanggal_lahir' => '1990-01-01',
            'unit_organisasi_id' => $unit->id,
            'status' => 'aktif',
            'jabatan_utama' => [
                'nama_jabatan' => 'Banit Sat Intelkam',
                'bidang_fungsi_id' => $bidang->id,
                'jenis_penugasan_id' => $jenisPenugasan->id,
                'tanggal_mulai' => '2025-01-01',
            ],
        ];
    }
}
