<?php

namespace Database\Factories;

use App\Models\PenghargaanPersonel;
use App\Models\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PenghargaanPersonel> */
class PenghargaanPersonelFactory extends Factory
{
    public function definition(): array
    {
        return ['personel_id' => Personel::factory(), 'nama' => fake()->sentence(3), 'tingkat' => 'nasional', 'pemberi' => 'Institusi Demo', 'tanggal_keputusan' => '2024-01-01', 'alasan' => 'Pengabdian dalam tugas'];
    }
}
