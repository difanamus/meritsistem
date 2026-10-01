<?php

namespace Database\Factories;

use App\Models\JenisKualifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisKualifikasi>
 */
class JenisKualifikasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('KUAL-####'),
            'nama' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
