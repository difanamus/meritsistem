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
            ['BHARADA', 'Bhayangkara Dua'],
            ['BHARATU', 'Bhayangkara Satu'],
            ['BHARAKA', 'Bhayangkara Kepala'],
            ['ABRIPDA', 'Ajun Brigadir Polisi Dua'],
            ['ABRIPTU', 'Ajun Brigadir Polisi Satu'],
            ['ABRIP', 'Ajun Brigadir Polisi'],
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
            $this->saveRank($kode, $nama, JenisPersonel::Polri, $index + 1);
        }

        $pangkatPns = [
            ['PNS-IA', 'Juru Muda'],
            ['PNS-IB', 'Juru Muda Tingkat I'],
            ['PNS-IC', 'Juru'],
            ['PNS-ID', 'Juru Tingkat I'],
            ['PNS-IIA', 'Pengatur Muda'],
            ['PNS-IIB', 'Pengatur Muda Tingkat I'],
            ['PNS-IIC', 'Pengatur'],
            ['PNS-IID', 'Pengatur Tingkat I'],
            ['PNS-IIIA', 'Penata Muda'],
            ['PNS-IIIB', 'Penata Muda Tingkat I'],
            ['PNS-IIIC', 'Penata'],
            ['PNS-IIID', 'Penata Tingkat I'],
            ['PNS-IVA', 'Pembina'],
            ['PNS-IVB', 'Pembina Tingkat I'],
            ['PNS-IVC', 'Pembina Utama Muda'],
            ['PNS-IVD', 'Pembina Utama Madya'],
            ['PNS-IVE', 'Pembina Utama'],
        ];

        foreach ($pangkatPns as $index => [$kode, $nama]) {
            $this->saveRank($kode, $nama, JenisPersonel::Pns, $index + 1);
        }
    }

    private function saveRank(string $kode, string $nama, JenisPersonel $jenisPersonel, int $urutan): void
    {
        $rank = Pangkat::withTrashed()->firstOrNew(['kode' => $kode]);
        $rank->fill(['nama' => $nama, 'jenis_personel' => $jenisPersonel, 'urutan' => $urutan]);
        if (! $rank->exists) {
            $rank->is_active = true;
        }
        $rank->save();
    }
}
