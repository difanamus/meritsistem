<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoryDetailApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{class-string, string, string, string}> */
    public static function histories(): array
    {
        return [
            'qualification' => [KualifikasiPersonel::class, 'kualifikasi', 'dokumen_pendukung', 'nama_kualifikasi'],
            'position' => [RiwayatJabatan::class, 'riwayat-jabatan', 'dokumen_sk', 'nama_jabatan'],
        ];
    }

    #[DataProvider('histories')]
    public function test_detail_requires_authentication_and_forbids_other_units_with_403(string $model, string $route, string $fileField, string $nameField): void
    {
        $person = Personel::factory()->create();
        $record = $model::factory()->for($person)->create([$nameField => 'Riwayat uji']);
        $this->getJson("/api/v1/{$route}/{$record->id}")->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));
        $this->getJson("/api/v1/{$route}/{$record->id}")->assertForbidden()->assertJsonMissing(['Riwayat uji']);
    }

    #[DataProvider('histories')]
    public function test_authorized_detail_includes_editable_fields_and_deleted_record_returns_404(string $model, string $route, string $fileField, string $nameField): void
    {
        $person = Personel::factory()->create();
        $record = $model::factory()->for($person)->create([$nameField => 'Riwayat uji', 'keterangan' => 'Catatan detail']);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->create(['user_id' => $operator->id, 'unit_organisasi_id' => $person->unit_organisasi_id]);
        Sanctum::actingAs($operator);

        $this->getJson("/api/v1/{$route}/{$record->id}")->assertOk()
            ->assertJsonPath('data.personel_id', $person->id)->assertJsonPath("data.{$nameField}", 'Riwayat uji')
            ->assertJsonPath('data.keterangan', 'Catatan detail')->assertJsonPath('data.dokumen', null);
        $record->delete();
        $this->getJson("/api/v1/{$route}/{$record->id}")->assertNotFound();
    }

    #[DataProvider('histories')]
    public function test_multipart_edit_replaces_private_pdf_preserves_unsent_fields_and_clears_optional_values(string $model, string $route, string $fileField, string $nameField): void
    {
        Storage::fake('local');
        $person = Personel::factory()->create();
        $oldPath = UploadedFile::fake()->createWithContent('old.pdf', "%PDF-1.4\nOld")->store('history', 'local');
        $record = $model::factory()->for($person)->create([
            $nameField => 'Riwayat uji', 'keterangan' => 'Catatan lama',
            "{$fileField}_path" => $oldPath, "{$fileField}_nama_asli" => 'old.pdf',
            "{$fileField}_mime" => 'application/pdf', "{$fileField}_ukuran" => 12,
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::SystemAdmin]));
        Request::enableHttpMethodParameterOverride();

        $this->post("/api/v1/{$route}/{$record->id}", [
            '_method' => 'PUT', 'keterangan' => '',
            $fileField => UploadedFile::fake()->createWithContent('new.pdf', "%PDF-1.4\nNew"),
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath("data.{$nameField}", 'Riwayat uji')->assertJsonPath('data.keterangan', null)
            ->assertJsonPath('data.dokumen.nama_asli', 'new.pdf')->assertJsonMissingPath("data.{$fileField}_path");
        $record->refresh();
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, 'keterangan' => null, "{$fileField}_nama_asli" => 'new.pdf']);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($record->getAttribute("{$fileField}_path"));
    }

    #[DataProvider('histories')]
    public function test_document_removal_clears_metadata_and_missing_document_returns_404(string $model, string $route, string $fileField, string $nameField): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->createWithContent('old.pdf', "%PDF-1.4\nOld")->store('history', 'local');
        $record = $model::factory()->create([
            "{$fileField}_path" => $path, "{$fileField}_nama_asli" => 'old.pdf',
            "{$fileField}_mime" => 'application/pdf', "{$fileField}_ukuran" => 12,
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->putJson("/api/v1/{$route}/{$record->id}", ['hapus_dokumen' => true])->assertOk()->assertJsonPath('data.dokumen', null);
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, "{$fileField}_path" => null, "{$fileField}_nama_asli" => null]);
        Storage::disk('local')->assertMissing($path);
        $this->getJson("/api/v1/{$route}/{$record->id}/dokumen")->assertNotFound();
    }

    #[DataProvider('histories')]
    public function test_pdf_content_disguised_as_executable_is_rejected_with_422(string $model, string $route, string $fileField, string $nameField): void
    {
        $record = $model::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->putJson("/api/v1/{$route}/{$record->id}", [
            $fileField => UploadedFile::fake()->createWithContent('danger.exe', "%PDF-1.4\nPDF contents"),
        ])->assertUnprocessable()->assertJsonValidationErrors($fileField);
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, "{$fileField}_path" => null]);
    }

    public function test_partial_qualification_date_edit_rejects_inverted_saved_period_with_422(): void
    {
        $record = KualifikasiPersonel::factory()->create(['tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-02-01']);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->putJson("/api/v1/kualifikasi/{$record->id}", ['tanggal_mulai' => '2024-03-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('tanggal_selesai')
            ->assertJsonPath('errors.tanggal_selesai.0', 'Tanggal selesai harus sama atau setelah tanggal mulai.');
        $this->assertSame('2024-01-01', $record->refresh()->tanggal_mulai->toDateString());
    }

    public function test_qualification_date_can_be_cleared_without_changing_other_date(): void
    {
        $record = KualifikasiPersonel::factory()->create(['tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-02-01']);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->putJson("/api/v1/kualifikasi/{$record->id}", ['tanggal_mulai' => null])->assertOk()->assertJsonPath('data.tanggal_mulai', null);
        $this->assertDatabaseHas('kualifikasi_personel', ['id' => $record->id, 'tanggal_mulai' => null]);
        $this->assertSame('2024-02-01', $record->refresh()->tanggal_selesai->toDateString());
    }

    #[DataProvider('histories')]
    public function test_oversized_pdf_is_rejected_without_storing_file(string $model, string $route, string $fileField, string $nameField): void
    {
        Storage::fake('local');
        $record = $model::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->putJson("/api/v1/{$route}/{$record->id}", [
            $fileField => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors($fileField);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    #[DataProvider('histories')]
    public function test_history_download_is_authorized_and_deleted_parent_cannot_be_accessed(string $model, string $route, string $fileField, string $nameField): void
    {
        Storage::fake('local');
        $person = Personel::factory()->create();
        $path = UploadedFile::fake()->createWithContent('test.pdf', "%PDF-1.4\nTest")->store('history', 'local');
        $record = $model::factory()->for($person)->create([
            "{$fileField}_path" => $path, "{$fileField}_nama_asli" => 'test.pdf',
            "{$fileField}_mime" => 'application/pdf', "{$fileField}_ukuran" => 13,
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->getJson("/api/v1/{$route}/{$record->id}/dokumen")->assertOk()->assertDownload('test.pdf');
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));
        $this->getJson("/api/v1/{$route}/{$record->id}/dokumen")->assertForbidden();
        $person->delete();
        $this->getJson("/api/v1/{$route}/{$record->id}")->assertForbidden();
        Storage::disk('local')->assertExists($path);
    }

    #[DataProvider('histories')]
    public function test_paginated_history_exposes_records_after_first_fifteen(string $model, string $route, string $fileField, string $nameField): void
    {
        $person = Personel::factory()->create();
        $attributes = $route === 'riwayat-jabatan' ? ['is_jabatan_utama' => false] : [];
        $model::factory()->count(16)->for($person)->create($attributes);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->getJson("/api/v1/personel/{$person->id}/{$route}")->assertOk()
            ->assertJsonCount(15, 'data')->assertJsonPath('meta.total', 16)->assertJsonPath('meta.last_page', 2);
        $this->getJson("/api/v1/personel/{$person->id}/{$route}?page=2")->assertOk()->assertJsonCount(1, 'data');
    }
}
