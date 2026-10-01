<?php

namespace Database\Factories;

use App\Models\BidangFungsi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BidangFungsi>
 */
class BidangFungsiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('FUNGSI-####'),
            'nama' => fake()->words(2, true),
            'deskripsi' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
