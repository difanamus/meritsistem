<?php

namespace Database\Seeders;

use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\User;
use Illuminate\Database\Seeder;

class KualifikasiPersonelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminId = User::query()->where('email', 'admin.ssdm@example.test')->value('id');
        $intelkamId = BidangFungsi::query()->where('kode', 'INTELKAM')->valueOrFail('id');
        $reskrimId = BidangFungsi::query()->where('kode', 'RESKRIM')->valueOrFail('id');
        $tikId = BidangFungsi::query()->where('kode', 'TIK')->valueOrFail('id');

        $data = [
            ['86010001', 'PENDIDIKAN_UMUM', null, 'S2 Manajemen Sumber Daya Manusia', 'S2', 'Manajemen SDM', 'Universitas Nusantara', null, '2022-08-20', 2022],
            ['86010001', 'PENDIDIKAN_KEJURUAN', $intelkamId, 'Kejuruan Intelijen Dasar', null, null, 'Lemdiklat Polri', '2014-02-01', '2014-04-30', 2014],
            ['86010001', 'PENDIDIKAN_KEJURUAN', $intelkamId, 'Kejuruan Intelijen Lanjutan', null, null, 'Lemdiklat Polri', '2019-03-01', '2019-05-31', 2019],
            ['86010001', 'PELATIHAN', $intelkamId, 'Pelatihan Analisis Intelijen', null, null, 'Pusdik Intelkam', '2024-06-03', '2024-06-14', 2024],
            ['90020002', 'PENDIDIKAN_KEJURUAN', $reskrimId, 'Kejuruan Reserse Kriminal', null, null, 'Lemdiklat Polri', '2018-01-08', '2018-04-20', 2018],
            ['198705152010011001', 'PENDIDIKAN_UMUM', $tikId, 'S2 Teknologi Informasi', 'S2', 'Teknologi Informasi', 'Universitas Teknologi Indonesia', null, '2020-09-15', 2020],
        ];

        foreach ($data as [$nomorIdentitas, $kodeJenis, $bidangFungsiId, $nama, $jenjang, $bidangStudi, $institusi, $tanggalMulai, $tanggalSelesai, $tahun]) {
            $personelId = Personel::query()->where('nomor_identitas', $nomorIdentitas)->valueOrFail('id');
            $jenisId = JenisKualifikasi::query()->where('kode', $kodeJenis)->valueOrFail('id');

            KualifikasiPersonel::query()->updateOrCreate(
                [
                    'personel_id' => $personelId,
                    'jenis_kualifikasi_id' => $jenisId,
                    'nama_kualifikasi' => $nama,
                    'tahun' => $tahun,
                ],
                [
                    'bidang_fungsi_id' => $bidangFungsiId,
                    'jenjang' => $jenjang,
                    'bidang_studi' => $bidangStudi,
                    'institusi_penyelenggara' => $institusi,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }
    }
}
