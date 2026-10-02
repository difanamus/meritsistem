<?php

namespace Database\Factories;

use App\Models\DisciplineSnapshot;
use App\Models\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisciplineSnapshot>
 */
class DisciplineSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personel_id' => Personel::factory(), 'source_system' => 'prototype_demo',
            'source_record_id' => fake()->unique()->uuid(), 'jenis' => 'disiplin',
            'ringkasan' => 'Contoh sintetis DEMO, bukan perkara nyata.',
            'nomor_keputusan' => 'DEMO/001', 'tanggal_keputusan' => '2024-01-01',
            'sanksi' => 'Sanksi contoh DEMO', 'instansi_penerbit' => 'Instansi Fiktif DEMO',
            'status_keputusan' => 'final',
        ];
    }
}
