<?php

namespace Database\Factories;

use App\Models\BidangFungsi;
use App\Models\JenisPenugasan;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatJabatan>
 */
class RiwayatJabatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personel_id' => Personel::factory(),
            'nama_jabatan' => fake()->jobTitle(),
            'unit_organisasi_id' => UnitOrganisasi::factory(),
            'bidang_fungsi_id' => BidangFungsi::factory(),
            'jenis_penugasan_id' => JenisPenugasan::factory(),
            'tanggal_mulai' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'tanggal_selesai' => null,
            'nivelering' => fake()->optional()->bothify('N-##'),
            'is_jabatan_utama' => true,
            'keterangan' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
