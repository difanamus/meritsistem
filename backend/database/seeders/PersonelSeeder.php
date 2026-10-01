<?php

namespace Database\Seeders;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Illuminate\Database\Seeder;

class PersonelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin.ssdm@example.test')->value('id');

        $personel = [
            [
                'jenis_personel' => JenisPersonel::Polri,
                'nomor_identitas' => '86010001',
                'nama_lengkap' => 'Agus Setiawan',
                'pangkat' => 'AKP',
                'tempat_lahir' => 'Bengkulu',
                'tanggal_lahir' => '1986-01-10',
                'unit' => 'SATINTEL-BENTENG',
            ],
            [
                'jenis_personel' => JenisPersonel::Polri,
                'nomor_identitas' => '90020002',
                'nama_lengkap' => 'Budi Santoso',
                'pangkat' => 'IPTU',
                'tempat_lahir' => 'Curup',
                'tanggal_lahir' => '1990-02-14',
                'unit' => 'SATRES-BENTENG',
            ],
            [
                'jenis_personel' => JenisPersonel::Pns,
                'nomor_identitas' => '198705152010011001',
                'nama_lengkap' => 'Citra Lestari',
                'pangkat' => 'PNS-IIIC',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1987-05-15',
                'unit' => 'BIK',
            ],
        ];

        foreach ($personel as $data) {
            Personel::query()->updateOrCreate(
                ['nomor_identitas' => $data['nomor_identitas']],
                [
                    'jenis_personel' => $data['jenis_personel'],
                    'nama_lengkap' => $data['nama_lengkap'],
                    'pangkat_id' => Pangkat::query()->where('kode', $data['pangkat'])->valueOrFail('id'),
                    'tempat_lahir' => $data['tempat_lahir'],
                    'tanggal_lahir' => $data['tanggal_lahir'],
                    'unit_organisasi_id' => UnitOrganisasi::query()->where('kode', $data['unit'])->valueOrFail('id'),
                    'status' => StatusPersonel::Aktif,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }
    }
}
