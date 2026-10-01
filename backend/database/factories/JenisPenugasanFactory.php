<?php

namespace Database\Factories;

use App\Models\JenisPenugasan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisPenugasan>
 */
class JenisPenugasanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('TUGAS-####'),
            'nama' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
