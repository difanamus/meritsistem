<?php

namespace Database\Seeders;

use App\Models\BidangFungsi;
use App\Models\PenghargaanPersonel;
use App\Models\PenugasanOperasi;
use App\Models\Personel;
use App\Models\PrestasiPersonel;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeritProfileSeeder extends Seeder
{
    public function run(): void
    {
        $person = Personel::query()->where('nomor_identitas', '86010001')->firstOrFail();
        $actor = User::query()->where('email', 'admin.ssdm@example.test')->firstOrFail();
        $field = BidangFungsi::query()->where('kode', 'INTELKAM')->firstOrFail();
        PenugasanOperasi::query()->updateOrCreate(
            ['personel_id' => $person->id, 'nama' => 'Operasi Nusantara (demo)'],
            ['jenis_operasi' => 'Operasi kewilayahan', 'tingkat' => 'nasional', 'wilayah' => 'Papua',
                'peran' => 'Anggota Satgas', 'satgas_unit' => 'Satgas Nusantara', 'tanggal_mulai' => '2023-01-01',
                'tanggal_selesai' => '2023-03-31', 'bidang_fungsi_id' => $field->id,
                'nomor_surat_perintah' => 'SPRIN-DEMO/2023', 'keterangan' => 'Data fiktif untuk demonstrasi.',
                'created_by' => $actor->id, 'updated_by' => $actor->id],
        );
        PrestasiPersonel::query()->updateOrCreate(
            ['personel_id' => $person->id, 'nama' => 'Juara bela diri tingkat nasional (demo)'],
            ['kategori' => 'olahraga', 'tingkat' => 'nasional', 'hasil' => 'Juara 1',
                'penyelenggara' => 'Kejuaraan Demo', 'tanggal' => '2022-08-01', 'tahun' => 2022,
                'peran' => 'individu', 'bidang_fungsi_id' => null,
                'keterangan' => 'Data fiktif untuk demonstrasi.', 'created_by' => $actor->id, 'updated_by' => $actor->id],
        );
        PenghargaanPersonel::query()->updateOrCreate(
            ['personel_id' => $person->id, 'nama' => 'Penghargaan pengabdian (demo)'],
            ['tingkat' => 'satker', 'pemberi' => 'Pimpinan Satker Demo',
                'nomor_keputusan' => 'KEP-DEMO/2024', 'tanggal_keputusan' => '2024-01-15',
                'alasan' => 'Pengabdian dalam tugas (data fiktif).',
                'created_by' => $actor->id, 'updated_by' => $actor->id],
        );
    }
}
