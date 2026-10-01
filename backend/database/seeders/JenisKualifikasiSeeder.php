<?php

namespace Database\Seeders;

use App\Models\JenisKualifikasi;
use Illuminate\Database\Seeder;

class JenisKualifikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisKualifikasi = [
            ['PENDIDIKAN_POLRI', 'Pendidikan Kepolisian'],
            ['PENDIDIKAN_UMUM', 'Pendidikan Umum'],
            ['PENDIDIKAN_KEJURUAN', 'Pendidikan Kejuruan'],
            ['PELATIHAN', 'Pelatihan'],
            ['SERTIFIKASI', 'Sertifikasi'],
            ['KOMPETENSI', 'Kompetensi/Keahlian'],
        ];

        foreach ($jenisKualifikasi as [$kode, $nama]) {
            JenisKualifikasi::query()->updateOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'is_active' => true],
            );
        }
    }
}
