<?php

namespace Database\Factories;

use App\Models\Personel;
use App\Models\PrestasiPersonel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PrestasiPersonel> */
class PrestasiPersonelFactory extends Factory
{
    public function definition(): array
    {
        return ['personel_id' => Personel::factory(), 'nama' => fake()->sentence(3), 'tingkat' => 'nasional', 'kategori' => 'olahraga', 'hasil' => 'Juara 1', 'penyelenggara' => 'Penyelenggara Demo', 'tahun' => 2024, 'peran' => 'individu'];
    }
}
