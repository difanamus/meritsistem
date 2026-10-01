<?php

namespace Database\Seeders;

use App\Enums\JenisUnit;
use App\Models\UnitOrganisasi;
use Illuminate\Database\Seeder;

class UnitOrganisasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polri = $this->createUnit(null, 'POLRI', 'Kepolisian Negara Republik Indonesia', JenisUnit::Root);
        $mabes = $this->createUnit($polri->id, 'MABES', 'Markas Besar Polri', JenisUnit::Mabes);
        $this->createUnit($mabes->id, 'BARESKRIM', 'Badan Reserse Kriminal Polri', JenisUnit::SatkerMabes);
        $this->createUnit($mabes->id, 'BIK', 'Biro Informasi Kepegawaian SSDM Polri', JenisUnit::SatkerMabes);

        $poldaBengkulu = $this->createUnit($polri->id, 'POLDA-BENGKULU', 'Polda Bengkulu', JenisUnit::Polda);
        $this->createUnit($poldaBengkulu->id, 'DITINTELKAM-BENGKULU', 'Ditintelkam Polda Bengkulu', JenisUnit::SatkerPolda);

        $polresBengkuluTengah = $this->createUnit(
            $poldaBengkulu->id,
            'POLRES-BENTENG',
            'Polres Bengkulu Tengah',
            JenisUnit::Polres,
        );
        $this->createUnit($polresBengkuluTengah->id, 'SATINTEL-BENTENG', 'Sat Intelkam Polres Bengkulu Tengah', JenisUnit::SatkerPolres);
        $this->createUnit($polresBengkuluTengah->id, 'SATRES-BENTENG', 'Sat Reskrim Polres Bengkulu Tengah', JenisUnit::SatkerPolres);
        $this->createUnit($polresBengkuluTengah->id, 'POLSEK-TALANG-EMPAT', 'Polsek Talang Empat', JenisUnit::Polsek);
    }

    private function createUnit(?int $parentId, string $kode, string $nama, JenisUnit $jenisUnit): UnitOrganisasi
    {
        return UnitOrganisasi::query()->updateOrCreate(
            ['kode' => $kode],
            [
                'parent_id' => $parentId,
                'nama' => $nama,
                'jenis_unit' => $jenisUnit,
                'is_active' => true,
            ],
        );
    }
}
