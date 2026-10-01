<?php

namespace Tests\Feature;

use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KualifikasiPersonelApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_operator_can_create_multiple_qualifications_with_same_name_and_private_pdf(): void
    {
        Storage::fake('local');
        [$operator, $personel] = $this->operatorAndPersonnelInSameUnit();
        Sanctum::actingAs($operator);
        $jenis = JenisKualifikasi::factory()->create();
        $bidang = BidangFungsi::factory()->create();

        foreach ([2024, 2025] as $year) {
            $response = $this->postJson("/api/v1/personel/{$personel->id}/kualifikasi", [
                'jenis_kualifikasi_id' => $jenis->id,
                'bidang_fungsi_id' => $bidang->id,
                'nama_kualifikasi' => 'Kejuruan Intelijen',
                'tahun' => $year,
                'dokumen_pendukung' => UploadedFile::fake()->createWithContent(
                    "sertifikat-{$year}.pdf",
                    "%PDF-1.4\nDokumen uji {$year}",
                ),
            ]);

            $response
                ->assertCreated()
                ->assertJsonPath('data.nama_kualifikasi', 'Kejuruan Intelijen')
                ->assertJsonPath('data.tahun', $year)
                ->assertJsonMissingPath('data.dokumen.path');

            Storage::disk('local')->assertExists(
                KualifikasiPersonel::query()->latest('id')->value('dokumen_pendukung_path'),
            );
        }

        $this->assertDatabaseCount('kualifikasi_personel', 2);
    }

    public function test_qualification_validation_rejects_invalid_dates_and_non_pdf_file(): void
    {
        [$operator, $personel] = $this->operatorAndPersonnelInSameUnit();
        Sanctum::actingAs($operator);

        $this->postJson("/api/v1/personel/{$personel->id}/kualifikasi", [
            'jenis_kualifikasi_id' => JenisKualifikasi::factory()->create()->id,
            'nama_kualifikasi' => 'Pelatihan A',
            'tanggal_mulai' => '2025-05-10',
            'tanggal_selesai' => '2025-05-01',
            'dokumen_pendukung' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal_selesai', 'dokumen_pendukung']);
    }

    public function test_operator_cannot_manage_qualification_for_personnel_outside_scope(): void
    {
        $allowedUnit = UnitOrganisasi::factory()->create();
        $outsidePersonnel = Personel::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($allowedUnit)->create();
        Sanctum::actingAs($operator);

        $this->getJson("/api/v1/personel/{$outsidePersonnel->id}/kualifikasi")->assertForbidden();
        $this->postJson("/api/v1/personel/{$outsidePersonnel->id}/kualifikasi", [
            'jenis_kualifikasi_id' => JenisKualifikasi::factory()->create()->id,
            'nama_kualifikasi' => 'Pelatihan A',
        ])->assertForbidden();
    }

    public function test_authorized_user_can_update_and_soft_delete_qualification(): void
    {
        [$operator, $personel] = $this->operatorAndPersonnelInSameUnit();
        Sanctum::actingAs($operator);
        $qualification = KualifikasiPersonel::factory()->for($personel)->create();

        $this->putJson("/api/v1/kualifikasi/{$qualification->id}", [
            'nama_kualifikasi' => 'Nama Kualifikasi Diperbarui',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_kualifikasi', 'Nama Kualifikasi Diperbarui');

        $this->deleteJson("/api/v1/kualifikasi/{$qualification->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted($qualification);
    }

    public function test_private_document_download_checks_organizational_scope(): void
    {
        Storage::fake('local');
        [$operator, $personel] = $this->operatorAndPersonnelInSameUnit();
        $path = UploadedFile::fake()
            ->createWithContent('sertifikat.pdf', "%PDF-1.4\nDokumen uji")
            ->store('dokumen-kualifikasi', 'local');
        $qualification = KualifikasiPersonel::factory()->for($personel)->create([
            'dokumen_pendukung_path' => $path,
            'dokumen_pendukung_nama_asli' => 'sertifikat.pdf',
            'dokumen_pendukung_mime' => 'application/pdf',
            'dokumen_pendukung_ukuran' => 24,
        ]);

        Sanctum::actingAs($operator);
        $this->get("/api/v1/kualifikasi/{$qualification->id}/dokumen")
            ->assertOk()
            ->assertDownload('sertifikat.pdf');

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));
        $this->getJson("/api/v1/kualifikasi/{$qualification->id}/dokumen")->assertForbidden();
    }

    /** @return array{User, Personel} */
    private function operatorAndPersonnelInSameUnit(): array
    {
        $unit = UnitOrganisasi::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator)->for($unit)->create([
            'scope_type' => ScopeType::OwnUnit,
        ]);

        return [$operator, Personel::factory()->for($unit)->create()];
    }
}
