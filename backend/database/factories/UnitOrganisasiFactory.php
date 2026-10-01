<?php

namespace Database\Factories;

use App\Enums\JenisUnit;
use App\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitOrganisasi>
 */
class UnitOrganisasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => fake()->company(),
            'jenis_unit' => JenisUnit::SatkerPolres,
            'is_active' => true,
        ];
    }
}
