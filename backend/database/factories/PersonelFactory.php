<?php

namespace Database\Factories;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Personel>
 */
class PersonelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis_personel' => JenisPersonel::Polri,
            'nomor_identitas' => fake()->unique()->numerify('########'),
            'nama_lengkap' => fake()->name(),
            'pangkat_id' => Pangkat::factory(),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-55 years', '-20 years')->format('Y-m-d'),
            'unit_organisasi_id' => UnitOrganisasi::factory(),
            'status' => StatusPersonel::Aktif,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
