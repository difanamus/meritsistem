<?php

namespace Database\Seeders;

use App\Models\JenisPenugasan;
use Illuminate\Database\Seeder;

class JenisPenugasanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisPenugasan = [
            ['DEFINITIF', 'Definitif'],
            ['PS', 'Pemangku Sementara'],
            ['PLT', 'Pelaksana Tugas'],
            ['PLH', 'Pelaksana Harian'],
        ];

        foreach ($jenisPenugasan as [$kode, $nama]) {
            JenisPenugasan::query()->updateOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'is_active' => true],
            );
        }
    }
}
