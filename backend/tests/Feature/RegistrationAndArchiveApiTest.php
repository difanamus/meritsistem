<?php

namespace Tests\Feature;

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
use App\Services\PersonnelRegistrationService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RegistrationAndArchiveApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_saves_education_and_all_optional_histories_with_private_documents(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['role' => UserRole::AdminSsdm]);
        Sanctum::actingAs($actor);
        $data = $this->payload();
        $data['pendidikan_umum']['dokumen_pendukung'] = UploadedFile::fake()->createWithContent('ijazah.pdf', "%PDF-1.4\nUji");
        $data['jabatan_utama']['dokumen_sk'] = UploadedFile::fake()->createWithContent('sk.pdf', "%PDF-1.4\nUji");
        $data['kualifikasi'] = [['jenis_kualifikasi_id' => JenisKualifikasi::factory()->create()->id, 'nama_kualifikasi' => 'Intelijen', 'tahun' => 2023]];
        $data['riwayat_jabatan'] = [[...$data['jabatan_utama'], 'unit_organisasi_id' => $data['unit_organisasi_id'], 'is_jabatan_utama' => true, 'tanggal_mulai' => '2019-01-01', 'tanggal_selesai' => '2024-12-31']];
        $data['penugasan_operasi'] = [['nama' => 'Operasi Papua', 'tingkat' => 'nasional', 'jenis_operasi' => 'Pengamanan', 'wilayah' => 'Papua', 'peran' => 'Anggota', 'satgas_unit' => 'Satgas A', 'tanggal_mulai' => '2022-01-01', 'tanggal_selesai' => '2022-03-01']];
        $data['prestasi'] = [['nama' => 'Juara bela diri', 'tingkat' => 'nasional', 'kategori' => 'olahraga', 'hasil' => 'Juara 1', 'penyelenggara' => 'Panitia', 'tahun' => 2024, 'peran' => 'individu']];
        $data['penghargaan'] = [['nama' => 'Penghargaan pengabdian', 'tingkat' => 'satker', 'pemberi' => 'Pimpinan', 'tanggal_keputusan' => '2024-01-01', 'alasan' => 'Pengabdian']];
        $response = $this->post('/api/v1/personel', $data, ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.jumlah_kualifikasi', 3);
        $id = $response->json('data.id');
        $this->assertDatabaseCount('riwayat_jabatan', 2);
        foreach (['penugasan_operasi', 'prestasi_personel', 'penghargaan_personel'] as $table) {
            $this->assertDatabaseHas($table, ['personel_id' => $id, 'created_by' => $actor->id, 'status_verifikasi' => 'belum_diverifikasi']);
        }
        Storage::disk('local')->assertExists(KualifikasiPersonel::query()->where('nama_kualifikasi', 'SMA')->value('dokumen_pendukung_path'));
        Storage::disk('local')->assertExists(RiwayatJabatan::query()->whereNull('tanggal_selesai')->value('dokumen_sk_path'));
    }

    public static function educationCases(): array
    {
        return [['pendidikan_umum'], ['pendidikan_polri']];
    }

    #[DataProvider('educationCases')]
    public function test_missing_required_education_returns_422_without_partial_registration(string $section): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $data = $this->payload();
        unset($data[$section]);
        $this->postJson('/api/v1/personel', $data)->assertUnprocessable()->assertJsonValidationErrors($section);
        $this->assertDatabaseCount('personel', 0);
        $this->assertDatabaseCount('riwayat_jabatan', 0);
    }

    public function test_pns_registration_requires_only_general_education_and_omitted_histories_create_no_rows(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $data = $this->payload();
        $data['jenis_personel'] = 'pns';
        $data['pangkat_id'] = Pangkat::factory()->create(['jenis_personel' => 'pns'])->id;
        unset($data['pendidikan_polri']);
        $this->postJson('/api/v1/personel', $data)->assertCreated()->assertJsonPath('data.jumlah_kualifikasi', 1);
        $this->assertDatabaseCount('kualifikasi_personel', 1);
        $this->assertDatabaseCount('penugasan_operasi', 0);
        $this->assertDatabaseCount('prestasi_personel', 0);
        $this->assertDatabaseCount('penghargaan_personel', 0);
    }

    public function test_overlapping_initial_primary_histories_return_422(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $data = $this->payload();
        $data['riwayat_jabatan'] = [[...$data['jabatan_utama'], 'unit_organisasi_id' => $data['unit_organisasi_id'], 'is_jabatan_utama' => true, 'tanggal_selesai' => '2025-03-01']];
        $this->postJson('/api/v1/personel', $data)->assertUnprocessable()->assertJsonValidationErrors('riwayat_jabatan.0.tanggal_mulai');
        $this->assertDatabaseCount('personel', 0);
    }

    public function test_initial_histories_validate_nested_dates_and_reject_injected_verification(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $data = $this->payload();
        $data['pendidikan_umum']['tanggal_mulai'] = '2010-01-01';
        $data['pendidikan_umum']['tanggal_selesai'] = '2009-01-01';
        $data['prestasi'] = [['nama' => 'Juara', 'tingkat' => 'nasional', 'kategori' => 'olahraga', 'hasil' => 'Juara', 'penyelenggara' => 'Panitia', 'tahun' => 2024, 'peran' => 'individu', 'status_verifikasi' => 'terverifikasi']];
        $this->postJson('/api/v1/personel', $data)->assertUnprocessable()->assertJsonValidationErrors(['pendidikan_umum.tanggal_selesai', 'prestasi.0.status_verifikasi']);
        $this->assertDatabaseCount('personel', 0);
    }

    public function test_transaction_failure_removes_created_rows_and_uploaded_files(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['role' => UserRole::AdminSsdm]);
        $data = $this->payload();
        $data['pendidikan_umum']['jenis_kualifikasi_id'] = JenisKualifikasi::query()->where('kode', 'PENDIDIKAN_UMUM')->value('id');
        $data['pendidikan_umum']['dokumen_pendukung'] = UploadedFile::fake()->createWithContent('ijazah.pdf', "%PDF-1.4\nUji");
        KualifikasiPersonel::creating(function (): void {
            throw new RuntimeException('Simulasi kegagalan database');
        });
        try {
            (new PersonnelRegistrationService)->create($data, $actor);
            $this->fail('Transaksi harus gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan database', $exception->getMessage());
        } finally {
            KualifikasiPersonel::flushEventListeners();
        }
        $this->assertDatabaseCount('personel', 0);
        $this->assertDatabaseCount('riwayat_jabatan', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_operator_cannot_register_initial_job_history_outside_scope(): void
    {
        $data = $this->payload();
        $actor = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($actor, 'user')->create(['unit_organisasi_id' => $data['unit_organisasi_id']]);
        Sanctum::actingAs($actor);
        $data['riwayat_jabatan'] = [[...$data['jabatan_utama'], 'unit_organisasi_id' => UnitOrganisasi::factory()->create()->id,
            'is_jabatan_utama' => true, 'tanggal_mulai' => '2019-01-01', 'tanggal_selesai' => '2024-12-31']];
        $this->postJson('/api/v1/personel', $data)->assertUnprocessable()->assertJsonValidationErrors('riwayat_jabatan.0.unit_organisasi_id');
        $this->assertDatabaseCount('personel', 0);
    }

    public function test_initial_operation_period_and_achievement_year_are_consistent(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $data = $this->payload();
        $data['penugasan_operasi'] = [['nama' => 'Operasi', 'tingkat' => 'nasional', 'jenis_operasi' => 'Pengamanan', 'wilayah' => 'Papua', 'peran' => 'Anggota', 'satgas_unit' => 'Satgas A', 'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2023-01-01']];
        $data['prestasi'] = [['nama' => 'Juara', 'tingkat' => 'nasional', 'kategori' => 'olahraga', 'hasil' => 'Juara', 'penyelenggara' => 'Panitia', 'tahun' => 2024, 'tanggal' => '2023-01-01', 'peran' => 'individu']];
        $this->postJson('/api/v1/personel', $data)->assertUnprocessable()->assertJsonValidationErrors(['penugasan_operasi.0.tanggal_selesai', 'prestasi.0.tahun']);
        $this->assertDatabaseCount('personel', 0);
    }

    public function test_rank_sort_groups_personnel_types_before_ordering_their_rank_scale(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $pns = Personel::factory()->create(['jenis_personel' => 'pns', 'pangkat_id' => Pangkat::factory()->create(['jenis_personel' => 'pns', 'urutan' => 100])->id]);
        $polri = Personel::factory()->create(['jenis_personel' => 'polri', 'pangkat_id' => Pangkat::factory()->create(['jenis_personel' => 'polri', 'urutan' => 1])->id]);
        $this->getJson('/api/v1/personel?sort=pangkat&direction=asc')->assertOk()
            ->assertJsonPath('data.0.id', $pns->id)->assertJsonPath('data.1.id', $polri->id);
    }

    public function test_archive_and_restore_preserve_career_and_do_not_reactivate_the_linked_account(): void
    {
        $person = Personel::factory()->create();
        $account = User::factory()->create(['role' => UserRole::Operator, 'personel_id' => $person->id]);
        $scope = UserScope::factory()->for($account, 'user')->for($person->unitOrganisasi)->create();
        $token = $account->createToken('test');
        $position = RiwayatJabatan::factory()->for($person)->create(['is_jabatan_utama' => true, 'tanggal_mulai' => '2020-01-01', 'tanggal_selesai' => null]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $this->deleteJson("/api/v1/personel/{$person->id}", ['alasan_arsip' => 'Duplikat pencatatan'])->assertOk();
        $this->assertSoftDeleted($person);
        $this->assertNull($position->refresh()->tanggal_selesai);
        $this->assertFalse($account->refresh()->is_active);
        $this->assertFalse($scope->refresh()->is_active);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->getJson('/api/v1/personel?arsip=1')->assertOk()->assertJsonPath('data.0.alasan_arsip', 'Duplikat pencatatan');
        $this->postJson("/api/v1/personel/{$person->id}/restore")->assertOk();
        $this->assertFalse($person->refresh()->trashed());
        $this->assertFalse($account->refresh()->is_active);
        $this->assertSame('2020-01-01', $position->refresh()->tanggal_mulai->toDateString());
        $this->assertNull($position->tanggal_selesai);
    }

    public function test_operator_cannot_archive_list_archives_or_restore_even_inside_scope(): void
    {
        $person = Personel::factory()->create();
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        UserScope::factory()->for($operator, 'user')->for($person->unitOrganisasi)->create();
        Sanctum::actingAs($operator);
        $this->deleteJson("/api/v1/personel/{$person->id}", ['alasan_arsip' => 'Duplikat'])->assertForbidden();
        $this->getJson('/api/v1/personel?arsip=1')->assertForbidden();
        $person->delete();
        $this->postJson("/api/v1/personel/{$person->id}/restore")->assertForbidden();
        $this->assertSoftDeleted($person);
    }

    public function test_archive_requires_a_reason_and_cannot_bypass_user_management_permissions(): void
    {
        $person = Personel::factory()->create();
        User::factory()->create(['role' => UserRole::SystemAdmin, 'personel_id' => $person->id]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $this->deleteJson("/api/v1/personel/{$person->id}")->assertUnprocessable()->assertJsonValidationErrors('alasan_arsip');
        $this->deleteJson("/api/v1/personel/{$person->id}", ['alasan_arsip' => 'Duplikat'])->assertForbidden();
        $this->assertFalse($person->refresh()->trashed());
    }

    public function test_personnel_archiving_policy_matrix_and_permanent_deletion_are_explicit(): void
    {
        $person = Personel::factory()->create();
        foreach ([UserRole::SystemAdmin, UserRole::AdminSsdm, UserRole::Operator] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->assertSame($role !== UserRole::Operator, Gate::forUser($actor)->allows('delete', $person));
            $this->assertSame($role !== UserRole::Operator, Gate::forUser($actor)->allows('restore', $person));
            $this->assertFalse(Gate::forUser($actor)->allows('forceDelete', $person));
        }
    }

    private function payload(): array
    {
        foreach (['PENDIDIKAN_UMUM', 'PENDIDIKAN_POLRI'] as $code) {
            JenisKualifikasi::factory()->create(['kode' => $code]);
        }

        return [
            'jenis_personel' => 'polri', 'nomor_identitas' => '87654321', 'nama_lengkap' => 'Personel Registrasi',
            'pangkat_id' => Pangkat::factory()->create()->id, 'tempat_lahir' => 'Bengkulu', 'tanggal_lahir' => '1990-01-01',
            'unit_organisasi_id' => UnitOrganisasi::factory()->create()->id, 'status' => 'aktif',
            'jabatan_utama' => ['nama_jabatan' => 'Banit', 'bidang_fungsi_id' => BidangFungsi::factory()->create()->id, 'jenis_penugasan_id' => JenisPenugasan::factory()->create()->id, 'tanggal_mulai' => '2025-01-01'],
            'pendidikan_umum' => ['nama_kualifikasi' => 'SMA', 'tahun' => 2008],
            'pendidikan_polri' => ['nama_kualifikasi' => 'SPN', 'tahun' => 2009],
        ];
    }
}
