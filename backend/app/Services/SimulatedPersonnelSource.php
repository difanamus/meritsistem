<?php

namespace App\Services;

class SimulatedPersonnelSource implements PersonnelSource
{
    public function fetch(int $version, int $afterVersion, int $page, int $limit): array
    {
        $records = [];
        foreach (range(1, $version === 2 ? 4 : 3) as $number) {
            $revision = $version === 2 && in_array($number, [1, 3, 4], true) ? 2 : 1;
            if ($revision <= $afterVersion) {
                continue;
            }
            $records[] = ['source_id' => 'simulation-'.$number, 'revision' => $revision,
                'deleted' => $version === 2 && $number === 3,
                'personnel' => ['jenis_personel' => 'polri', 'nomor_identitas' => '9999100'.$number,
                    'nama_lengkap' => 'Personel Simulasi '.sprintf('%03d', $number).($revision === 2 && $number === 1 ? ' Diperbarui' : '').' (IMPORT DEMO)',
                    'pangkat_kode' => 'BRIPDA', 'unit_kode' => 'SATINTEL-BENTENG',
                    'tempat_lahir' => 'Bengkulu', 'tanggal_lahir' => '1995-01-01', 'status' => 'aktif',
                    'jabatan_utama' => ['nama_jabatan' => 'Banit Sat Intelkam', 'bidang_fungsi_kode' => 'INTELKAM', 'jenis_penugasan_kode' => 'DEFINITIF', 'tanggal_mulai' => '2020-01-01'],
                    'pendidikan_umum' => ['nama_kualifikasi' => 'SMA (SIMULASI)', 'jenjang' => 'SMA', 'tahun' => 2013],
                    'pendidikan_polri' => ['nama_kualifikasi' => 'Diktuk Bintara (SIMULASI)', 'tahun' => 2014]]];
        }
        $offset = ($page - 1) * $limit;

        return ['records' => array_slice($records, $offset, $limit), 'next_page' => $offset + $limit < count($records) ? $page + 1 : null];
    }
}
