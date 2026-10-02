<?php

namespace Tests\Feature;

use App\Enums\JenisUnit;
use App\Enums\ScopeType;
use App\Enums\UserRole;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserScope;
use Database\Seeders\BidangFungsiSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoPersonnelSeeder;
use Database\Seeders\JenisKualifikasiSeeder;
use Database\Seeders\JenisPenugasanSeeder;
use Database\Seeders\PangkatSeeder;
use Database\Seeders\UnitOrganisasiSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class DemoPersonnelSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_creates_100_synthetic_people_with_varied_education_career_and_merit(): void
    {
        $this->baseReferences();

        $this->seed(DemoPersonnelSeeder::class);

        $this->assertSame(100, Personel::withTrashed()->count());
        $this->assertSame(80, Personel::withTrashed()->where('jenis_personel', 'polri')->count());
        $this->assertSame(20, Personel::withTrashed()->where('jenis_personel', 'pns')->count());
        $this->assertSame(85, Personel::query()->where('status', 'aktif')->count());
        $this->assertSame(5, Personel::query()->where('status', 'nonaktif')->count());
        $this->assertSame(5, Personel::query()->where('status', 'pensiun')->count());
        $this->assertSame(5, Personel::onlyTrashed()->count());
        $this->assertSame(380, KualifikasiPersonel::query()->count());
        $this->assertSame(175, RiwayatJabatan::query()->count());
        $this->assertSame(85, RiwayatJabatan::query()->where('is_jabatan_utama', true)->whereNull('tanggal_selesai')->count());
        $this->assertDatabaseCount('penugasan_operasi', 33);
        $this->assertDatabaseCount('prestasi_personel', 25);
        $this->assertDatabaseCount('penghargaan_personel', 20);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('user_scopes', 0);
        $this->assertDatabaseCount('unit_organisasi', 22);
        $this->assertDatabaseHas('personel', ['nomor_identitas' => '99990001', 'nama_lengkap' => 'Aditya Pratama (DEMO 001)']);
        $this->assertDatabaseHas('personel', ['nomor_identitas' => '999900000000000081', 'jenis_personel' => 'pns']);

        $generalId = JenisKualifikasi::query()->where('kode', 'PENDIDIKAN_UMUM')->value('id');
        $policeId = JenisKualifikasi::query()->where('kode', 'PENDIDIKAN_POLRI')->value('id');
        $this->assertSame(100, KualifikasiPersonel::query()->where('jenis_kualifikasi_id', $generalId)->count());
        $this->assertSame(80, KualifikasiPersonel::query()->where('jenis_kualifikasi_id', $policeId)->count());
        $this->assertSame(0, RiwayatJabatan::query()->whereColumn('tanggal_selesai', '<', 'tanggal_mulai')->count());
        foreach (['penugasan_operasi', 'prestasi_personel', 'penghargaan_personel'] as $table) {
            $this->assertSame(0, DB::table($table)->where('status_verifikasi', '!=', 'belum_diverifikasi')->count());
            $this->assertSame(0, DB::table($table)->whereNotNull('dokumen_path')->count());
        }
    }

    public function test_rerun_does_not_duplicate_overwrite_edits_or_resurrect_archives_and_deleted_histories(): void
    {
        $this->baseReferences();
        $existing = Personel::factory()->create(['nama_lengkap' => 'Data milik pengguna']);
        $existingState = $existing->refresh()->getAttributes();
        $this->seed(DemoPersonnelSeeder::class);
        $demo = Personel::query()->where('nomor_identitas', '99990001')->firstOrFail();
        $demo->update(['nama_lengkap' => 'Demo yang sudah diedit', 'alasan_arsip' => 'Arsip hasil testing']);
        $demo->kualifikasi()->firstOrFail()->delete();
        $demo->delete();
        $demoState = $demo->refresh()->getAttributes();
        $tables = ['personel', 'unit_organisasi', 'kualifikasi_personel', 'riwayat_jabatan', 'penugasan_operasi', 'prestasi_personel', 'penghargaan_personel', 'users'];
        $before = [];
        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all();
        }

        $this->seed(DemoPersonnelSeeder::class);

        foreach ($tables as $table) {
            $this->assertSame($before[$table], DB::table($table)->orderBy('id')->get()->map(static fn (object $row): array => (array) $row)->all(), $table);
        }
        $this->assertSame($existingState, $existing->refresh()->getAttributes());
        $this->assertSame($demoState, $demo->refresh()->getAttributes());
        $this->assertSame(101, Personel::withTrashed()->count());
        $this->assertSoftDeleted($demo);
    }

    public function test_demo_pagination_filters_archives_and_operator_scope_use_real_api(): void
    {
        $this->baseReferences();
        $this->seed(DemoPersonnelSeeder::class);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));

        $this->getJson('/api/v1/personel?per_page=10&page=2')->assertOk()->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 95)->assertJsonPath('meta.current_page', 2)->assertJsonPath('meta.last_page', 10);
        $this->getJson('/api/v1/personel?status=pensiun')->assertOk()->assertJsonPath('meta.total', 5);
        $this->getJson('/api/v1/personel?arsip=1')->assertOk()->assertJsonPath('meta.total', 5);
        $this->getJson('/api/v1/personel?operasi_wilayah=Papua')->assertOk()->assertJsonPath('meta.total', 7);
        $intelId = BidangFungsi::query()->where('kode', 'INTELKAM')->value('id');
        $intel = $this->getJson("/api/v1/personel?bidang_fungsi_id={$intelId}&sort=jumlah_kualifikasi&direction=desc")
            ->assertOk()->json('meta.total');
        $this->assertGreaterThan(10, $intel);

        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $unit = UnitOrganisasi::query()->where('kode', 'SATINTEL-BENTENG')->firstOrFail();
        UserScope::factory()->for($operator)->for($unit)->create(['scope_type' => ScopeType::OwnUnit]);
        Sanctum::actingAs($operator);
        $response = $this->getJson('/api/v1/personel?per_page=100')->assertOk();
        $this->assertSame(8, $response->json('meta.total'));
        foreach ($response->json('data') as $person) {
            $this->assertSame($unit->id, $person['unit_organisasi']['id']);
        }
    }

    public function test_missing_references_abort_without_creating_people_or_units(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Referensi aktif BHARADA belum tersedia');

        try {
            $this->seed(DemoPersonnelSeeder::class);
        } finally {
            $this->assertDatabaseCount('personel', 0);
            $this->assertDatabaseCount('unit_organisasi', 0);
        }
    }

    public function test_unit_conflict_rolls_back_new_demo_units_without_overwriting_existing_unit(): void
    {
        $this->baseReferences();
        $root = UnitOrganisasi::query()->where('kode', 'POLRI')->firstOrFail();
        $conflicting = UnitOrganisasi::factory()->for($root, 'parent')->create([
            'kode' => 'DEMO-POLDA-TIMUR', 'jenis_unit' => JenisUnit::Polda, 'is_active' => false,
        ]);
        $before = $conflicting->refresh()->getAttributes();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DEMO-POLDA-TIMUR sudah ada dengan kondisi berbeda');

        try {
            $this->seed(DemoPersonnelSeeder::class);
        } finally {
            $this->assertDatabaseCount('unit_organisasi', 11);
            $this->assertDatabaseMissing('unit_organisasi', ['kode' => 'DEMO-POLDA-BARAT']);
            $this->assertDatabaseCount('personel', 0);
            $this->assertSame($before, $conflicting->refresh()->getAttributes());
        }
    }

    public function test_production_environment_refuses_demo_data(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Data DEMO hanya boleh dibuat pada environment local/testing.');

        $this->app->make(DemoPersonnelSeeder::class)->run();
    }

    public function test_identity_collision_skips_existing_non_demo_person_without_attaching_histories(): void
    {
        $this->baseReferences();
        $existing = Personel::factory()->create(['nomor_identitas' => '99990001', 'nama_lengkap' => 'Catatan yang sudah ada']);
        $before = $existing->refresh()->getAttributes();

        $this->seed(DemoPersonnelSeeder::class);

        $this->assertSame($before, $existing->refresh()->getAttributes());
        $this->assertSame(100, Personel::withTrashed()->count());
        $this->assertSame(0, $existing->kualifikasi()->count());
        $this->assertSame(0, $existing->riwayatJabatan()->count());
        $this->assertSame(0, $existing->penugasanOperasi()->count());
    }

    public function test_standard_installation_does_not_run_optional_100_person_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('personel', 3);
        $this->assertDatabaseMissing('personel', ['nomor_identitas' => '99990001']);
        $this->assertDatabaseMissing('unit_organisasi', ['kode' => 'DEMO-POLDA-BARAT']);
    }

    private function baseReferences(): void
    {
        $this->seed([PangkatSeeder::class, BidangFungsiSeeder::class, JenisKualifikasiSeeder::class, JenisPenugasanSeeder::class, UnitOrganisasiSeeder::class]);
    }
}
