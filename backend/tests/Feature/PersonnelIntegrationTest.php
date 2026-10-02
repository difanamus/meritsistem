<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\User;
use App\Services\PersonnelRegistrationService;
use App\Services\PersonnelSource;
use App\Services\SimulatedPersonnelSource;
use Database\Seeders\BidangFungsiSeeder;
use Database\Seeders\JenisKualifikasiSeeder;
use Database\Seeders\JenisPenugasanSeeder;
use Database\Seeders\PangkatSeeder;
use Database\Seeders\UnitOrganisasiSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonnelIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function loginAdmin(): void
    {
        $this->seed([PangkatSeeder::class, BidangFungsiSeeder::class, JenisKualifikasiSeeder::class, JenisPenugasanSeeder::class, UnitOrganisasiSeeder::class]);
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
    }

    private function preview(int $version = 1, string $mode = 'initial'): int
    {
        return $this->postJson('/api/v1/personnel-integration/preview', compact('version', 'mode'))->assertCreated()->json('data.run.id');
    }

    private function apply(int $run, int $size = 25): TestResponse
    {
        return $this->postJson('/api/v1/personnel-integration/'.$run.'/apply', ['confirmed' => true, 'batch_size' => $size]);
    }

    public function test_initial_import_is_previewed_batched_confirmed_and_idempotent(): void
    {
        $this->loginAdmin();
        $run = $this->preview();
        $this->assertDatabaseCount('personel', 0);
        $this->postJson('/api/v1/personnel-integration/'.$run.'/apply', ['confirmed' => false])->assertUnprocessable();
        $this->apply($run, 2)->assertOk()->assertJsonPath('data.run.status', 'running')->assertJsonPath('data.counts.pending', 1);
        $this->assertDatabaseCount('personel', 2);
        $this->apply($run)->assertOk()->assertJsonPath('data.run.status', 'completed')->assertJsonPath('data.checkpoint', 1);
        $this->apply($run)->assertOk();
        $this->assertDatabaseCount('personel', 3);
        $this->assertDatabaseCount('riwayat_jabatan', 3);
        $this->assertDatabaseCount('kualifikasi_personel', 6);
        $repeat = $this->preview();
        $this->apply($repeat)->assertOk()->assertJsonPath('data.counts.skipped', 3);
        $this->assertDatabaseCount('personel', 3);
    }

    public function test_delta_updates_only_identity_and_preserves_tombstones_and_local_histories(): void
    {
        $this->loginAdmin();
        $this->apply($this->preview())->assertOk();
        $run = $this->preview(2, 'delta');
        $this->getJson('/api/v1/personnel-integration/'.$run)->assertOk()->assertJsonPath('data.meta.total', 3);
        $this->apply($run)->assertOk()->assertJsonPath('data.checkpoint', 2)->assertJsonPath('data.counts.succeeded', 2)->assertJsonPath('data.counts.skipped', 1);
        $this->assertDatabaseCount('personel', 4);
        $this->assertDatabaseCount('riwayat_jabatan', 4);
        $this->assertDatabaseCount('kualifikasi_personel', 8);
        $this->assertDatabaseHas('personel', ['nomor_identitas' => '99991001', 'nama_lengkap' => 'Personel Simulasi 001 Diperbarui (IMPORT DEMO)']);
        $this->assertDatabaseHas('personel', ['nomor_identitas' => '99991003', 'deleted_at' => null]);
        $empty = $this->preview(2, 'delta');
        $this->apply($empty)->assertOk()->assertJsonPath('data.meta.total', 0)->assertJsonPath('data.run.status', 'completed');
    }

    public function test_existing_nrp_is_not_automatically_adopted_or_overwritten(): void
    {
        $this->loginAdmin();
        $manual = Personel::factory()->create(['nomor_identitas' => '99991001', 'nama_lengkap' => 'Nama manual']);
        $run = $this->preview();
        $this->apply($run)->assertOk()->assertJsonPath('data.run.status', 'completed_with_errors')->assertJsonPath('data.checkpoint', 0);
        $this->assertSame('Nama manual', $manual->fresh()->nama_lengkap);
        $this->assertDatabaseCount('personnel_source_links', 2);
    }

    public function test_archive_and_local_edits_are_never_overwritten(): void
    {
        $this->loginAdmin();
        $this->apply($this->preview())->assertOk();
        Personel::where('nomor_identitas', '99991001')->firstOrFail()->update(['nama_lengkap' => 'Perubahan lokal']);
        Personel::where('nomor_identitas', '99991002')->firstOrFail()->delete();
        $run = $this->preview(2);
        $this->apply($run)->assertOk()->assertJsonPath('data.counts.conflict', 2)->assertJsonPath('data.checkpoint', 1);
        $this->assertDatabaseHas('personel', ['nomor_identitas' => '99991001', 'nama_lengkap' => 'Perubahan lokal']);
        $this->assertNotNull(Personel::withTrashed()->where('nomor_identitas', '99991002')->firstOrFail()->deleted_at);
    }

    public function test_changes_after_preview_and_stale_checkpoints_are_rejected(): void
    {
        $this->loginAdmin();
        $this->apply($this->preview())->assertOk();
        $run = $this->preview(2, 'delta');
        Personel::where('nomor_identitas', '99991001')->firstOrFail()->update(['nama_lengkap' => 'Ubah sesudah preview']);
        $this->apply($run)->assertOk()->assertJsonPath('data.counts.conflict', 1)->assertJsonPath('data.checkpoint', 1);
        $stale = $this->preview(2, 'delta');
        DB::table('personnel_sync_checkpoints')->update(['version' => 2]);
        $this->apply($stale)->assertConflict();
    }

    public function test_source_placement_changes_require_manual_domain_process(): void
    {
        $this->loginAdmin();
        $this->apply($this->preview())->assertOk();
        $this->app->bind(PersonnelSource::class, fn () => new class implements PersonnelSource
        {
            public function fetch(int $version, int $afterVersion, int $page, int $limit): array
            {
                $result = (new SimulatedPersonnelSource)->fetch($version, $afterVersion, $page, $limit);
                $result['records'][0]['personnel']['unit_kode'] = 'POLRES-BENTENG';

                return $result;
            }
        });
        $this->apply($this->preview(2, 'delta'))->assertOk()->assertJsonPath('data.counts.conflict', 1)->assertJsonPath('data.checkpoint', 1);
    }

    public function test_missing_reference_is_reported_and_never_creates_partial_personnel(): void
    {
        $this->loginAdmin();
        DB::table('pangkat')->where('kode', 'BRIPDA')->update(['is_active' => false]);
        $this->apply($this->preview())->assertOk()->assertJsonPath('data.counts.failed', 3)->assertJsonPath('data.checkpoint', 0);
        $this->assertDatabaseCount('personel', 0);
        $this->assertDatabaseCount('riwayat_jabatan', 0);
    }

    public function test_reference_remapping_after_preview_requires_new_confirmation(): void
    {
        $this->loginAdmin();
        $run = $this->preview();
        DB::table('pangkat')->where('kode', 'BRIPDA')->update(['kode' => 'BRIPDA-OLD']);
        Pangkat::factory()->create(['kode' => 'BRIPDA']);

        $this->apply($run)->assertOk()->assertJsonPath('data.counts.conflict', 3)->assertJsonPath('data.checkpoint', 0);
        $this->assertDatabaseCount('personel', 0);
        $this->assertDatabaseCount('personnel_source_links', 0);
    }

    public function test_auth_permissions_input_and_production_guard(): void
    {
        $this->getJson('/api/v1/personnel-integration')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::Operator]));
        $this->getJson('/api/v1/personnel-integration')->assertForbidden();
        $this->postJson('/api/v1/personnel-integration/preview', [])->assertForbidden();
        $this->postJson('/api/v1/personnel-integration/1/apply', ['confirmed' => true])->assertForbidden();
        $this->getJson('/api/v1/personnel-integration/1')->assertForbidden();
        $this->loginAdmin();
        $this->postJson('/api/v1/personnel-integration/preview', ['mode' => 'remote', 'version' => 3])->assertUnprocessable();
        $this->getJson('/api/v1/personnel-integration/99999')->assertNotFound();
        $run = $this->preview();
        $this->postJson('/api/v1/personnel-integration/'.$run.'/apply', ['confirmed' => true, 'batch_size' => 26])->assertUnprocessable();
        $this->app->instance('env', 'production');
        $this->postJson('/api/v1/personnel-integration/preview', ['mode' => 'initial', 'version' => 1])->assertStatus(503);
        $this->apply($run)->assertStatus(503);
    }

    public function test_source_and_report_pagination_remain_bounded_across_batches(): void
    {
        $this->loginAdmin();
        $this->app->bind(PersonnelSource::class, fn () => new class implements PersonnelSource
        {
            public function fetch(int $version, int $afterVersion, int $page, int $limit): array
            {
                $template = (new SimulatedPersonnelSource)->fetch(1, 0, 1, 25)['records'][0];
                $records = [];
                foreach (range(($page - 1) * $limit + 1, min($page * $limit, 35)) as $number) {
                    $record = $template;
                    $record['source_id'] = 'paged-'.$number;
                    $record['personnel']['nomor_identitas'] = (string) (99992000 + $number);
                    $records[] = $record;
                }

                return ['records' => $records, 'next_page' => $page === 1 ? 2 : null];
            }
        });
        $run = $this->preview();
        $report = $this->getJson('/api/v1/personnel-integration/'.$run)->assertOk()->assertJsonCount(25, 'data.items')->assertJsonPath('data.meta.total', 35);
        $this->assertArrayNotHasKey('payload', $report->json('data.items.0'));
        $this->getJson('/api/v1/personnel-integration/'.$run.'?page=2')->assertOk()->assertJsonCount(10, 'data.items');
        $this->apply($run)->assertOk()->assertJsonPath('data.counts.pending', 10)->assertJsonPath('data.checkpoint', 0);
        $this->assertDatabaseCount('personel', 25);
        $this->apply($run)->assertOk()->assertJsonPath('data.counts.succeeded', 35)->assertJsonPath('data.checkpoint', 1);
        $this->assertDatabaseCount('personel', 35);
    }

    public function test_duplicate_source_ids_are_rejected_before_staging(): void
    {
        $this->loginAdmin();
        $this->app->bind(PersonnelSource::class, fn () => new class implements PersonnelSource
        {
            public function fetch(int $version, int $afterVersion, int $page, int $limit): array
            {
                $result = (new SimulatedPersonnelSource)->fetch($version, $afterVersion, $page, $limit);
                $result['records'][1]['source_id'] = $result['records'][0]['source_id'];

                return $result;
            }
        });
        $this->postJson('/api/v1/personnel-integration/preview', ['mode' => 'initial', 'version' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('personnel_import_runs', 0);
        $this->assertDatabaseCount('personel', 0);
    }

    public function test_failed_item_rolls_back_domain_data_and_retry_does_not_duplicate_successes(): void
    {
        $this->loginAdmin();
        $original = new PersonnelRegistrationService;
        $this->mock(PersonnelRegistrationService::class, function ($mock) use ($original): void {
            $mock->shouldReceive('create')->andReturnUsing(function (array $data, User $actor) use ($original): Personel {
                $person = $original->create($data, $actor);
                if ($data['nomor_identitas'] === '99991001') {
                    throw new \RuntimeException('Synthetic transaction failure');
                }

                return $person;
            });
        });
        $this->apply($this->preview())->assertOk()->assertJsonPath('data.counts.failed', 1)->assertJsonPath('data.counts.succeeded', 2)->assertJsonPath('data.checkpoint', 0);
        $this->assertDatabaseMissing('personel', ['nomor_identitas' => '99991001']);
        $this->assertDatabaseCount('personel', 2);
        $this->assertDatabaseCount('riwayat_jabatan', 2);
        $this->assertDatabaseCount('kualifikasi_personel', 4);
        $this->assertDatabaseCount('personnel_source_links', 2);
        $this->app->instance(PersonnelRegistrationService::class, $original);
        $this->apply($this->preview())->assertOk()->assertJsonPath('data.counts.succeeded', 1)->assertJsonPath('data.counts.skipped', 2)->assertJsonPath('data.checkpoint', 1);
        $this->assertDatabaseCount('personel', 3);
        $this->assertDatabaseCount('riwayat_jabatan', 3);
        $this->assertDatabaseCount('kualifikasi_personel', 6);
    }

    public function test_restored_personnel_can_be_found_case_insensitively(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::AdminSsdm]));
        $person = Personel::factory()->create(['nama_lengkap' => 'Diva Namus Akbar']);
        $person->delete();
        $this->postJson('/api/v1/personel/'.$person->id.'/restore')->assertOk();
        foreach (['diva namus', 'DIVA NAMUS', 'DiVa NaMuS'] as $name) {
            $this->getJson('/api/v1/personel?search='.urlencode($name))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $person->id);
        }
    }
}
