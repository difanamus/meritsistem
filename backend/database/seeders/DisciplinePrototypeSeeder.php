<?php

namespace Database\Seeders;

use App\Models\DisciplineSnapshot;
use App\Models\Personel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DisciplinePrototypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Prototype disiplin DEMO hanya untuk local/testing.');
        }

        DB::transaction(function (): void {
            $person = Personel::query()->where('nomor_identitas', '99990001')
                ->where('nama_lengkap', 'Aditya Pratama (DEMO 001)')->first();
            if (! $person) {
                throw new RuntimeException('Personel DEMO 001 belum tersedia. Jalankan DemoPersonnelSeeder terlebih dahulu.');
            }
            foreach (['final', 'dibatalkan'] as $index => $status) {
                DisciplineSnapshot::query()->firstOrCreate(
                    ['source_system' => 'prototype_demo', 'source_record_id' => 'DEMO-DISIPLIN-'.($index + 1)],
                    [
                        'personel_id' => $person->id, 'jenis' => $index === 0 ? 'disiplin' : 'kode_etik',
                        'ringkasan' => 'Simulasi keputusan sintetis untuk demonstrasi, bukan perkara nyata.',
                        'nomor_keputusan' => 'DEMO/KEP/00'.($index + 1), 'tanggal_keputusan' => $index === 0 ? '2023-01-10' : '2024-02-20',
                        'sanksi' => 'Contoh sanksi fiktif DEMO', 'instansi_penerbit' => 'Instansi Fiktif DEMO',
                        'status_keputusan' => $status,
                        'keterangan_pembatalan' => $index === 1 ? 'Simulasi pembatalan berdasarkan DEMO/BATAL/001; bukan sanksi aktif.' : null,
                    ],
                );
            }
        });
    }
}
