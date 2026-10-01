<?php

namespace Database\Factories;

use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KualifikasiPersonel>
 */
class KualifikasiPersonelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personel_id' => Personel::factory(),
            'jenis_kualifikasi_id' => JenisKualifikasi::factory(),
            'bidang_fungsi_id' => BidangFungsi::factory(),
            'nama_kualifikasi' => fake()->sentence(3),
            'jenjang' => null,
            'bidang_studi' => null,
            'institusi_penyelenggara' => fake()->company(),
            'tanggal_mulai' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'tanggal_selesai' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'tahun' => fake()->numberBetween(2015, 2026),
            'nomor_dokumen' => fake()->optional()->bothify('DOC-####/????'),
            'keterangan' => fake()->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
