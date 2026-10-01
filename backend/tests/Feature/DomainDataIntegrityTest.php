<?php

namespace Tests\Feature;

use App\Models\Personel;
use App\Models\RiwayatJabatan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DomainDataIntegrityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_rejects_second_active_primary_position_for_same_personnel(): void
    {
        $personel = Personel::factory()->create();
        RiwayatJabatan::factory()->for($personel)->create();

        $this->expectException(QueryException::class);

        RiwayatJabatan::factory()->for($personel)->create();
    }

    public function test_allows_active_additional_assignment_alongside_primary_position(): void
    {
        $personel = Personel::factory()->create();
        RiwayatJabatan::factory()->for($personel)->create();

        $additionalAssignment = RiwayatJabatan::factory()->for($personel)->create([
            'is_jabatan_utama' => false,
        ]);

        $this->assertModelExists($additionalAssignment);
        $this->assertSame(2, $personel->riwayatJabatan()->count());
    }

    public function test_allows_new_primary_position_after_previous_position_is_completed(): void
    {
        $personel = Personel::factory()->create();
        RiwayatJabatan::factory()->for($personel)->create([
            'tanggal_mulai' => '2020-01-01',
            'tanggal_selesai' => '2024-12-31',
        ]);

        $activePosition = RiwayatJabatan::factory()->for($personel)->create([
            'tanggal_mulai' => '2025-01-01',
        ]);

        $this->assertModelExists($activePosition);
        $this->assertSame($activePosition->id, $personel->jabatanUtamaAktif()->value('id'));
    }
}
