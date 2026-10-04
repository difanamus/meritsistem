<?php

namespace App\Services;

use App\Models\Personel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PersonnelRegistrationService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): Personel
    {
        $paths = [];
        try {
            return DB::transaction(function () use ($data, $actor, &$paths): Personel {
                $person = Personel::query()->create([
                    ...Arr::only($data, ['jenis_personel', 'nomor_identitas', 'nama_lengkap', 'pangkat_id', 'tempat_lahir', 'tanggal_lahir', 'unit_organisasi_id', 'status']),
                    'created_by' => $actor->id, 'updated_by' => $actor->id,
                ]);
                $audit = ['created_by' => $actor->id, 'updated_by' => $actor->id];
                if (! empty($data['jabatan_utama'])) {
                    $job = Arr::only($data['jabatan_utama'], ['nama_jabatan', 'bidang_fungsi_id', 'jenis_penugasan_id', 'tanggal_mulai', 'nivelering', 'keterangan', 'dokumen_sk']);
                    $person->riwayatJabatan()->create([...$this->document($job, 'dokumen_sk', 'dokumen-sk', $paths), ...$audit,
                        'unit_organisasi_id' => $person->unit_organisasi_id, 'is_jabatan_utama' => true]);
                }
                $qualifications = $data['kualifikasi'] ?? [];
                foreach (['pendidikan_umum', 'pendidikan_polri'] as $section) {
                    if (! empty($data[$section])) {
                        $education = $data[$section];
                        $qualifications = [...$qualifications, ...(array_is_list($education) ? $education : [$education])];
                    }
                }
                foreach ($qualifications as $record) {
                    $record = Arr::only($record, ['jenis_kualifikasi_id', 'bidang_fungsi_id', 'nama_kualifikasi', 'jenjang', 'bidang_studi', 'institusi_penyelenggara', 'tanggal_mulai', 'tanggal_selesai', 'tahun', 'nomor_dokumen', 'keterangan', 'dokumen_pendukung']);
                    $person->kualifikasi()->create([...$this->document($record, 'dokumen_pendukung', 'dokumen-kualifikasi', $paths), ...$audit]);
                }
                foreach ($data['riwayat_jabatan'] ?? [] as $record) {
                    $person->riwayatJabatan()->create([...$this->document($record, 'dokumen_sk', 'dokumen-sk', $paths), ...$audit]);
                }
                foreach (['penugasan_operasi' => 'penugasan-operasi', 'prestasi' => 'prestasi', 'penghargaan' => 'penghargaan'] as $section => $type) {
                    $class = MeritProfileService::modelClass($type);
                    foreach ($data[$section] ?? [] as $record) {
                        $record = Arr::only($record, [...MeritProfileService::fields($type), 'dokumen']);
                        $class::query()->create([...$this->document($record, 'dokumen', 'dokumen-merit/'.$type, $paths), ...$audit,
                            'personel_id' => $person->id, 'status_verifikasi' => 'belum_diverifikasi']);
                    }
                }

                return $person;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    /** @param list<string> $paths @return array<string, mixed> */
    private function document(array $data, string $key, string $folder, array &$paths): array
    {
        $file = Arr::pull($data, $key);
        if ($file instanceof UploadedFile) {
            $path = $file->store($folder, 'local');
            $paths[] = $path;
            $data += [$key.'_path' => $path, $key.'_nama_asli' => $file->getClientOriginalName(),
                $key.'_mime' => $file->getMimeType(), $key.'_ukuran' => $file->getSize()];
        }

        return $data;
    }
}
