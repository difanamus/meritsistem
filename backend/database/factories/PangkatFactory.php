<?php

namespace Database\Factories;

use App\Enums\JenisPersonel;
use App\Models\Pangkat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pangkat>
 */
class PangkatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('PGK-####'),
            'nama' => fake()->jobTitle(),
            'jenis_personel' => JenisPersonel::Polri,
            'urutan' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
