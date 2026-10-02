<?php

namespace Database\Seeders;

use App\Enums\JenisUnit;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\JenisPenugasan;
use App\Models\KualifikasiPersonel;
use App\Models\Pangkat;
use App\Models\PenghargaanPersonel;
use App\Models\PenugasanOperasi;
use App\Models\Personel;
use App\Models\PrestasiPersonel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoPersonnelSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Data DEMO hanya boleh dibuat pada environment local/testing.');
        }

        DB::transaction(function (): void {
            $rankIds = Pangkat::query()->where('is_active', true)->pluck('id', 'kode')->all();
            $fieldIds = BidangFungsi::query()->where('is_active', true)->pluck('id', 'kode')->all();
            $qualificationIds = JenisKualifikasi::query()->where('is_active', true)->pluck('id', 'kode')->all();
            $assignmentIds = JenisPenugasan::query()->where('is_active', true)->pluck('id', 'kode')->all();
            $policeRanks = ['BHARADA', 'BHARATU', 'BHARAKA', 'ABRIPDA', 'ABRIPTU', 'ABRIP', 'BRIPDA', 'BRIPTU', 'BRIGPOL', 'BRIPKA', 'AIPDA', 'AIPTU', 'IPDA', 'IPTU', 'AKP', 'KOMPOL', 'AKBP', 'KOMBES'];
            $civilRanks = ['PNS-IA', 'PNS-IB', 'PNS-IC', 'PNS-ID', 'PNS-IIA', 'PNS-IIB', 'PNS-IIC', 'PNS-IID', 'PNS-IIIA', 'PNS-IIIB', 'PNS-IIIC', 'PNS-IIID', 'PNS-IVA', 'PNS-IVB', 'PNS-IVC', 'PNS-IVD', 'PNS-IVE'];
            foreach ([[$rankIds, [...$policeRanks, ...$civilRanks]], [$fieldIds, ['INTELKAM', 'RESKRIM', 'LANTAS', 'SDM', 'TIK']], [$qualificationIds, ['PENDIDIKAN_UMUM', 'PENDIDIKAN_POLRI', 'PENDIDIKAN_KEJURUAN', 'PELATIHAN']], [$assignmentIds, ['DEFINITIF', 'PS']]] as [$ids, $codes]) {
                foreach ($codes as $code) {
                    if (! isset($ids[$code])) {
                        throw new RuntimeException("Referensi aktif {$code} belum tersedia. Lengkapi seed dasar sebelum DemoPersonnelSeeder.");
                    }
                }
            }
            $units = $this->placementUnits();
            $actorId = User::query()->where('email', 'admin.ssdm@example.test')->value('id');
            $actor = ['created_by' => $actorId, 'updated_by' => $actorId];
            $firstNames = ['Aditya', 'Bayu', 'Dimas', 'Eka', 'Farhan', 'Gita', 'Hendra', 'Indah', 'Joko', 'Kartika'];
            $lastNames = ['Pratama', 'Saputra', 'Lestari', 'Wibowo', 'Permata', 'Nugraha', 'Kusuma', 'Utami', 'Ramadhan', 'Wijaya'];
            $created = 0;
            for ($number = 1; $number <= 100; $number++) {
                $isPolice = $number <= 80;
                $identity = $isPolice ? sprintf('9999%04d', $number) : sprintf('99990000000000%04d', $number);
                if (Personel::withTrashed()->where('nomor_identitas', $identity)->exists()) {
                    continue;
                }
                $rankIndex = $isPolice ? ($number - 1) % count($policeRanks) : ($number - 81) % count($civilRanks);
                [$unitId, $fieldCode] = $units[($number - 1) % count($units)];
                $fieldId = $fieldIds[$fieldCode];
                $status = $number > 90 ? 'pensiun' : ($number > 85 ? 'nonaktif' : 'aktif');
                $person = Personel::query()->create([
                    'nomor_identitas' => $identity, 'jenis_personel' => $isPolice ? 'polri' : 'pns',
                    'nama_lengkap' => sprintf('%s %s (DEMO %03d)', $firstNames[($number - 1) % 10], $lastNames[intdiv($number - 1, 10)], $number),
                    'pangkat_id' => $rankIds[$isPolice ? $policeRanks[$rankIndex] : $civilRanks[$rankIndex]],
                    'tempat_lahir' => 'Kota Fiktif DEMO',
                    'tanggal_lahir' => sprintf('%d-05-15', $status === 'pensiun' ? 1965 + $number % 3 : 1978 + $number % 8),
                    'unit_organisasi_id' => $unitId, 'status' => $status, ...$actor,
                ]);
                $history = ['personel_id' => $person->id, 'keterangan' => 'Data sintetis DEMO, bukan catatan personel nyata.', ...$actor];
                $degrees = [['SMA', 2003], ['D3', 2007], ['S1', 2010], ['S2', 2022]];
                [$degree, $graduationYear] = $degrees[($number - 1) % 4];
                KualifikasiPersonel::query()->create([
                    ...$history, 'jenis_kualifikasi_id' => $qualificationIds['PENDIDIKAN_UMUM'],
                    'nama_kualifikasi' => "{$degree} Pendidikan Umum (DEMO)", 'jenjang' => $degree,
                    'bidang_studi' => $degree === 'SMA' ? 'Umum' : 'Administrasi dan Teknologi',
                    'institusi_penyelenggara' => 'Lembaga Pendidikan Fiktif DEMO', 'tahun' => $graduationYear,
                    'tanggal_selesai' => "{$graduationYear}-06-30",
                ]);
                if ($isPolice) {
                    $policeEducation = $rankIndex < 6 ? 'Dik Tamtama' : ($rankIndex < 12 ? 'Dik Bintara' : 'Dik Pembentukan Perwira');
                    KualifikasiPersonel::query()->create([
                        ...$history, 'jenis_kualifikasi_id' => $qualificationIds['PENDIDIKAN_POLRI'],
                        'nama_kualifikasi' => "{$policeEducation} (DEMO)", 'institusi_penyelenggara' => 'Lembaga Dik Polri Fiktif DEMO',
                        'tahun' => 2005, 'tanggal_mulai' => '2005-01-01', 'tanggal_selesai' => '2005-12-31',
                    ]);
                }
                for ($course = 0; $course < $number % 5; $course++) {
                    $year = 2018 + $course;
                    KualifikasiPersonel::query()->create([
                        ...$history, 'jenis_kualifikasi_id' => $qualificationIds[$course % 2 === 0 ? 'PELATIHAN' : 'PENDIDIKAN_KEJURUAN'],
                        'bidang_fungsi_id' => $fieldId, 'nama_kualifikasi' => "Pengembangan {$fieldCode} tahap ".($course + 1).' (DEMO)',
                        'institusi_penyelenggara' => 'Pusat Pelatihan Fiktif DEMO', 'tahun' => $year,
                        'tanggal_mulai' => "{$year}-03-01", 'tanggal_selesai' => "{$year}-03-15",
                    ]);
                }
                $position = [...$history, 'unit_organisasi_id' => $unitId, 'bidang_fungsi_id' => $fieldId,
                    'jenis_penugasan_id' => $assignmentIds['DEFINITIF'], 'is_jabatan_utama' => true];
                if ($number % 3 !== 0) {
                    $previousUnitId = $units[$number % count($units)][0];
                    RiwayatJabatan::query()->create([
                        ...$position, 'unit_organisasi_id' => $previousUnitId, 'nama_jabatan' => 'Pelaksana tugas kedinasan (DEMO)',
                        'tanggal_mulai' => '2019-01-01', 'tanggal_selesai' => '2023-12-31',
                    ]);
                }
                $jobName = ! $isPolice ? 'Pengelola Administrasi' : ($rankIndex < 12 ? 'Anggota Pelaksana' : 'Perwira Pelaksana');
                RiwayatJabatan::query()->create([
                    ...$position, 'nama_jabatan' => "{$jobName} {$fieldCode} (DEMO)",
                    'tanggal_mulai' => '2024-01-01', 'tanggal_selesai' => $status === 'aktif' ? null : '2025-12-31',
                ]);
                if ($status === 'aktif' && $number % 10 === 0) {
                    RiwayatJabatan::query()->create([
                        ...$position, 'nama_jabatan' => 'Koordinator Tim Sementara (DEMO)', 'is_jabatan_utama' => false,
                        'jenis_penugasan_id' => $assignmentIds['PS'], 'tanggal_mulai' => '2025-01-01', 'tanggal_selesai' => null,
                    ]);
                }
                $this->meritHistories($number, $history, $fieldId);
                if ($number > 95) {
                    $person->update(['alasan_arsip' => 'Contoh arsip sintetis DEMO untuk pengujian pemulihan.', 'archived_by' => $actorId]);
                    $person->delete();
                }
                $created++;
            }
            $this->command?->info("DEMO: {$created} personel baru; ".(100 - $created).' identitas yang sudah ada dilewati. Tidak ada akun atau dokumen dibuat.');
        });
    }

    /** @return list<array{0: int, 1: string}> */
    private function placementUnits(): array
    {
        $units = [];
        foreach ([['BIK', 'SDM'], ['BARESKRIM', 'RESKRIM'], ['SATINTEL-BENTENG', 'INTELKAM'], ['SATRES-BENTENG', 'RESKRIM'], ['POLSEK-TALANG-EMPAT', 'LANTAS']] as [$code, $field]) {
            $units[] = [UnitOrganisasi::query()->where('kode', $code)->where('is_active', true)->valueOrFail('id'), $field];
        }
        $rootId = UnitOrganisasi::query()->where('kode', 'POLRI')->where('is_active', true)->valueOrFail('id');
        foreach (['BARAT', 'TIMUR'] as $region) {
            $polda = $this->demoUnit($rootId, "DEMO-POLDA-{$region}", "Polda Wilayah {$region} (DEMO)", JenisUnit::Polda);
            $polres = $this->demoUnit($polda->id, "DEMO-POLRES-{$region}", "Polres Kota {$region} (DEMO)", JenisUnit::Polres);
            foreach ([['INTEL', 'INTELKAM'], ['RESKRIM', 'RESKRIM']] as [$suffix, $field]) {
                $unit = $this->demoUnit($polres->id, "DEMO-{$suffix}-{$region}", "Sat {$field} Kota {$region} (DEMO)", JenisUnit::SatkerPolres);
                $units[] = [$unit->id, $field];
            }
            $polsek = $this->demoUnit($polres->id, "DEMO-POLSEK-{$region}", "Polsek Kecamatan {$region} (DEMO)", JenisUnit::Polsek);
            $units[] = [$polsek->id, 'LANTAS'];
            $dit = $this->demoUnit($polda->id, "DEMO-DITINTEL-{$region}", "Ditintelkam Wilayah {$region} (DEMO)", JenisUnit::SatkerPolda);
            $units[] = [$dit->id, 'INTELKAM'];
        }

        return $units;
    }

    private function demoUnit(int $parentId, string $code, string $name, JenisUnit $type): UnitOrganisasi
    {
        $unit = UnitOrganisasi::withTrashed()->firstOrCreate(['kode' => $code], [
            'parent_id' => $parentId, 'nama' => $name, 'jenis_unit' => $type, 'is_active' => true,
        ]);
        if ($unit->trashed() || ! $unit->is_active || $unit->parent_id !== $parentId || $unit->jenis_unit !== $type) {
            throw new RuntimeException("Unit {$code} sudah ada dengan kondisi berbeda; dibatalkan tanpa menimpanya.");
        }

        return $unit;
    }

    /** @param array<string, mixed> $history */
    private function meritHistories(int $number, array $history, int $fieldId): void
    {
        if ($number % 3 === 0) {
            $regions = ['Papua', 'Aceh', 'Jakarta', 'Bengkulu'];
            PenugasanOperasi::query()->create([
                ...$history, 'bidang_fungsi_id' => $fieldId, 'nama' => 'Operasi Pengamanan Wilayah (DEMO)',
                'jenis_operasi' => 'Pengamanan (DEMO)', 'tingkat' => 'nasional', 'wilayah' => $regions[$number % 4],
                'peran' => 'Anggota Satgas (DEMO)', 'satgas_unit' => 'Satgas Fiktif DEMO',
                'tanggal_mulai' => '2023-01-01', 'tanggal_selesai' => $number % 2 === 0 ? '2023-03-31' : '2023-06-30',
                'nomor_surat_perintah' => "SPRIN-DEMO-{$number}/2023", 'status_verifikasi' => 'belum_diverifikasi',
            ]);
        }
        if ($number % 4 === 0) {
            PrestasiPersonel::query()->create([
                ...$history, 'nama' => 'Juara Bela Diri (DEMO)', 'kategori' => 'olahraga',
                'tingkat' => $number % 8 === 0 ? 'nasional' : 'provinsi', 'hasil' => 'Juara 1 (DEMO)',
                'penyelenggara' => 'Kejuaraan Fiktif DEMO', 'tanggal' => '2022-08-01', 'tahun' => 2022,
                'peran' => 'individu', 'status_verifikasi' => 'belum_diverifikasi',
            ]);
        }
        if ($number % 5 === 0) {
            PenghargaanPersonel::query()->create([
                ...$history, 'nama' => 'Apresiasi Pengabdian (DEMO)', 'tingkat' => 'satker',
                'pemberi' => 'Pimpinan Fiktif DEMO', 'nomor_keputusan' => "KEP-DEMO-{$number}/2024",
                'tanggal_keputusan' => '2024-01-15', 'alasan' => 'Contoh penghargaan sintetis, bukan keputusan resmi.',
                'status_verifikasi' => 'belum_diverifikasi',
            ]);
        }
    }
}
