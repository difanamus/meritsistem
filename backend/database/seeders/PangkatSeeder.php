<?php

namespace Database\Seeders;

use App\Enums\JenisPersonel;
use App\Models\Pangkat;
use Illuminate\Database\Seeder;

class PangkatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pangkatPolri = [
            ['BRIPDA', 'Brigadir Polisi Dua'],
            ['BRIPTU', 'Brigadir Polisi Satu'],
            ['BRIGPOL', 'Brigadir Polisi'],
            ['BRIPKA', 'Brigadir Polisi Kepala'],
            ['AIPDA', 'Ajun Inspektur Polisi Dua'],
            ['AIPTU', 'Ajun Inspektur Polisi Satu'],
            ['IPDA', 'Inspektur Polisi Dua'],
            ['IPTU', 'Inspektur Polisi Satu'],
            ['AKP', 'Ajun Komisaris Polisi'],
            ['KOMPOL', 'Komisaris Polisi'],
            ['AKBP', 'Ajun Komisaris Besar Polisi'],
            ['KOMBES', 'Komisaris Besar Polisi'],
            ['BRIGJEN', 'Brigadir Jenderal Polisi'],
            ['IRJEN', 'Inspektur Jenderal Polisi'],
            ['KOMJEN', 'Komisaris Jenderal Polisi'],
            ['JENDERAL', 'Jenderal Polisi'],
        ];

        foreach ($pangkatPolri as $index => [$kode, $nama]) {
            Pangkat::query()->updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'jenis_personel' => JenisPersonel::Polri,
                    'urutan' => $index + 1,
                    'is_active' => true,
                ],
            );
        }

        $pangkatPns = [
            ['PNS-IIIA', 'Penata Muda'],
            ['PNS-IIIB', 'Penata Muda Tingkat I'],
            ['PNS-IIIC', 'Penata'],
            ['PNS-IIID', 'Penata Tingkat I'],
            ['PNS-IVA', 'Pembina'],
        ];

        foreach ($pangkatPns as $index => [$kode, $nama]) {
            Pangkat::query()->updateOrCreate(
                ['kode' => $kode],
                [
                    'nama' => $nama,
                    'jenis_personel' => JenisPersonel::Pns,
                    'urutan' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
