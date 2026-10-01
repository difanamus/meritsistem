<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PenghargaanPersonel;
use App\Models\PenugasanOperasi;
use App\Models\Personel;
use App\Models\PrestasiPersonel;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MeritProfileApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function kinds(): array
    {
        return [
            'operation' => ['penugasan-operasi', PenugasanOperasi::class, [
                'nama' => 'Operasi Demo', 'tingkat' => 'nasional', 'jenis_operasi' => 'Operasi kewilayahan',
                'wilayah' => 'Papua', 'peran' => 'Anggota Satgas', 'satgas_unit' => 'Satgas Demo',
                'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-01-31']],
            'achievement' => ['prestasi', PrestasiPersonel::class, [
                'nama' => 'Juara Nasional Demo', 'tingkat' => 'nasional', 'kategori' => 'olahraga',
                'hasil' => 'Juara 1', 'penyelenggara' => 'Panitia Demo', 'tanggal' => '2024-01-05',
                'tahun' => 2024, 'peran' => 'individu']],
            'award' => ['penghargaan', PenghargaanPersonel::class, [
                'nama' => 'Penghargaan Demo', 'tingkat' => 'satker', 'pemberi' => 'Pimpinan Demo',
                'nomor_keputusan' => 'KEP-1', 'tanggal_keputusan' => '2024-01-05',
                'alasan' => 'Pengabdian tugas']],
        ];
    }

    #[DataProvider('kinds')]
    public function test_create_read_edit_archive_and_authorization_for_each_kind(string $kind, string $model, array $payload): void
    {
        $person = Personel::factory()->create();
        $other = Personel::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->create(['user_id' => $operator->id, 'unit_organisasi_id' => $person->unit_organisasi_id]);
        $url = "/api/v1/personel/{$person->id}/merit/{$kind}";
        $this->getJson($url)->assertUnauthorized();
        Sanctum::actingAs($operator);
        $this->postJson($url, $payload)->assertCreated()->assertJsonPath('data.nama', $payload['nama'])
            ->assertJsonPath('data.status_verifikasi', 'belum_diverifikasi');
        $record = $model::query()->firstOrFail();
        $this->assertSame($person->id, $record->personel_id);
        $this->getJson($url)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson("/api/v1/merit/{$kind}/{$record->id}")->assertOk()->assertJsonMissingPath('data.dokumen_path');
        $this->getJson("/api/v1/personel/{$other->id}/merit/{$kind}")->assertForbidden();
        $this->postJson("/api/v1/merit/{$kind}/{$record->id}/verifikasi", ['status_verifikasi' => 'terverifikasi'])->assertForbidden();
        $this->putJson("/api/v1/merit/{$kind}/{$record->id}", ['nama' => 'Riwayat dikoreksi'])->assertOk()
            ->assertJsonPath('data.nama', 'Riwayat dikoreksi');
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id, 'nama' => 'Riwayat dikoreksi']);
        $this->deleteJson("/api/v1/merit/{$kind}/{$record->id}")->assertOk();
        $this->getJson("/api/v1/merit/{$kind}/{$record->id}")->assertNotFound();
    }

    #[DataProvider('kinds')]
    public function test_private_pdf_verification_and_mutation_reset(string $kind, string $model, array $payload): void
    {
        Storage::fake('local');
        $person = Personel::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        Request::enableHttpMethodParameterOverride();
        $this->post("/api/v1/personel/{$person->id}/merit/{$kind}", [
            ...$payload, 'dokumen' => UploadedFile::fake()->createWithContent('bukti.pdf', "%PDF-1.4\nDemo"),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.dokumen.nama_asli', 'bukti.pdf');
        $record = $model::query()->firstOrFail();
        Storage::disk('local')->assertExists($record->dokumen_path);
        $this->getJson("/api/v1/merit/{$kind}/{$record->id}/dokumen")->assertOk()->assertDownload('bukti.pdf');
        $this->postJson("/api/v1/merit/{$kind}/{$record->id}/verifikasi", ['status_verifikasi' => 'terverifikasi'])
            ->assertOk()->assertJsonPath('data.status_verifikasi', 'terverifikasi');
        $this->assertNotNull($record->refresh()->verified_at);
        $this->putJson("/api/v1/merit/{$kind}/{$record->id}", ['nama' => 'Koreksi'])->assertOk()
            ->assertJsonPath('data.status_verifikasi', 'belum_diverifikasi');
        $this->assertNull($record->refresh()->verified_at);
        $this->putJson("/api/v1/merit/{$kind}/{$record->id}", ['hapus_dokumen' => true])->assertOk()
            ->assertJsonPath('data.dokumen', null);
        Storage::disk('local')->assertMissing($record->dokumen_path ?? '');
    }

    #[DataProvider('kinds')]
    public function test_invalid_pdf_and_verification_spoofing_return_422(string $kind, string $model, array $payload): void
    {
        Storage::fake('local');
        $person = Personel::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $this->postJson("/api/v1/personel/{$person->id}/merit/{$kind}", [
            ...$payload, 'dokumen' => UploadedFile::fake()->createWithContent('evil.exe', "%PDF-1.4\nDemo"),
        ])->assertUnprocessable()->assertJsonValidationErrors('dokumen');
        $this->postJson("/api/v1/personel/{$person->id}/merit/{$kind}", [
            ...$payload, 'status_verifikasi' => 'terverifikasi',
        ])->assertUnprocessable()->assertJsonValidationErrors('status_verifikasi');
        $this->assertSame(0, $model::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_operation_filters_counts_duration_sort_and_scope_are_factual(): void
    {
        $first = Personel::factory()->create();
        $second = Personel::factory()->create();
        $third = Personel::factory()->create();
        PenugasanOperasi::factory()->for($first)->create(['wilayah' => 'Papua', 'tingkat' => 'nasional', 'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-01-10']);
        PenugasanOperasi::factory()->for($first)->create(['wilayah' => 'Papua', 'tingkat' => 'nasional', 'tanggal_mulai' => '2024-02-01', 'tanggal_selesai' => '2024-02-20']);
        PenugasanOperasi::factory()->for($second)->create(['wilayah' => 'Aceh', 'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-01-05']);
        PenugasanOperasi::factory()->for($third)->create(['wilayah' => 'Papua', 'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-01-05']);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->create(['user_id' => $operator->id, 'unit_organisasi_id' => $first->unit_organisasi_id]);
        Sanctum::actingAs($operator);
        $this->getJson('/api/v1/personel?operasi_wilayah=Papua&operasi_tingkat=nasional&sort=durasi_operasi&direction=desc')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.0.ringkasan_operasi.jumlah', 2)
            ->assertJsonPath('data.0.ringkasan_operasi.total_durasi_hari', 30);
        $this->getJson('/api/v1/personel?min_jumlah_operasi=2')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/personel?min_durasi_operasi_hari=31')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/personel?sort=durasi_operasi%20DESC')->assertUnprocessable();
    }

    public function test_operation_period_and_achievement_year_are_consistent(): void
    {
        $person = Personel::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $this->postJson("/api/v1/personel/{$person->id}/merit/penugasan-operasi", [
            ...self::kinds()['operation'][2], 'tanggal_selesai' => '2023-12-31',
        ])->assertUnprocessable()->assertJsonValidationErrors('tanggal_selesai');
        $this->postJson("/api/v1/personel/{$person->id}/merit/prestasi", [
            ...self::kinds()['achievement'][2], 'tahun' => 2023,
        ])->assertUnprocessable()->assertJsonValidationErrors('tahun');
    }
}
