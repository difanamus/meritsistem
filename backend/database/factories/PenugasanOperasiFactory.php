<?php

namespace Database\Factories;

use App\Models\PenugasanOperasi;
use App\Models\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PenugasanOperasi> */
class PenugasanOperasiFactory extends Factory
{
    public function definition(): array
    {
        return ['personel_id' => Personel::factory(), 'nama' => fake()->sentence(3), 'tingkat' => 'nasional', 'jenis_operasi' => 'Operasi kepolisian', 'wilayah' => 'Papua', 'peran' => 'Anggota Satgas', 'satgas_unit' => 'Satgas Demo', 'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => '2024-01-31'];
    }
}
