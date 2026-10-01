<?php

namespace Database\Seeders;

use App\Models\BidangFungsi;
use App\Models\JenisPenugasan;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Illuminate\Database\Seeder;

class RiwayatJabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin.ssdm@example.test')->value('id');

        $data = [
            ['86010001', 'Banit Sat Intelkam', 'SATINTEL-BENTENG', 'INTELKAM', 'DEFINITIF', '2016-01-01', '2020-12-31', true],
            ['86010001', 'Panit Sat Intelkam', 'SATINTEL-BENTENG', 'INTELKAM', 'DEFINITIF', '2021-01-01', '2024-06-30', true],
            ['86010001', 'Kanit Sat Intelkam', 'SATINTEL-BENTENG', 'INTELKAM', 'DEFINITIF', '2024-07-01', null, true],
            ['86010001', 'Plt. Kasat Intelkam', 'POLRES-BENTENG', 'INTELKAM', 'PLT', '2026-08-01', null, false],
            ['90020002', 'Kanit Sat Reskrim', 'SATRES-BENTENG', 'RESKRIM', 'DEFINITIF', '2023-01-10', null, true],
            ['198705152010011001', 'Analis Sistem Informasi', 'BIK', 'TIK', 'DEFINITIF', '2021-02-01', null, true],
        ];

        foreach ($data as [$nomorIdentitas, $namaJabatan, $kodeUnit, $kodeBidang, $kodePenugasan, $tanggalMulai, $tanggalSelesai, $isJabatanUtama]) {
            $personelId = Personel::query()->where('nomor_identitas', $nomorIdentitas)->valueOrFail('id');

            RiwayatJabatan::query()->updateOrCreate(
                [
                    'personel_id' => $personelId,
                    'nama_jabatan' => $namaJabatan,
                    'tanggal_mulai' => $tanggalMulai,
                ],
                [
                    'unit_organisasi_id' => UnitOrganisasi::query()->where('kode', $kodeUnit)->valueOrFail('id'),
                    'bidang_fungsi_id' => BidangFungsi::query()->where('kode', $kodeBidang)->valueOrFail('id'),
                    'jenis_penugasan_id' => JenisPenugasan::query()->where('kode', $kodePenugasan)->valueOrFail('id'),
                    'tanggal_selesai' => $tanggalSelesai,
                    'is_jabatan_utama' => $isJabatanUtama,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }
    }
}
