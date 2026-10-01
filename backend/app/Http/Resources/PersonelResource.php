<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis_personel' => $this->jenis_personel->value,
            'jenis_personel_label' => $this->jenis_personel->label(),
            'nomor_identitas' => $this->nomor_identitas,
            'nama_lengkap' => $this->nama_lengkap,
            'pangkat' => $this->whenLoaded('pangkat', fn () => [
                'id' => $this->pangkat->id,
                'kode' => $this->pangkat->kode,
                'nama' => $this->pangkat->nama,
            ]),
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir->toDateString(),
            'unit_organisasi' => $this->whenLoaded('unitOrganisasi', fn () => [
                'id' => $this->unitOrganisasi->id,
                'kode' => $this->unitOrganisasi->kode,
                'nama' => $this->unitOrganisasi->nama,
            ]),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'jumlah_kualifikasi' => $this->whenCounted('kualifikasi'),
            'jabatan_utama_aktif' => $this->whenLoaded('jabatanUtamaAktif', fn () => $this->jabatanUtamaAktif ? [
                'id' => $this->jabatanUtamaAktif->id,
                'nama_jabatan' => $this->jabatanUtamaAktif->nama_jabatan,
                'tanggal_mulai' => $this->jabatanUtamaAktif->tanggal_mulai->toDateString(),
                'unit_organisasi' => [
                    'id' => $this->jabatanUtamaAktif->unitOrganisasi->id,
                    'kode' => $this->jabatanUtamaAktif->unitOrganisasi->kode,
                    'nama' => $this->jabatanUtamaAktif->unitOrganisasi->nama,
                ],
                'bidang_fungsi' => [
                    'id' => $this->jabatanUtamaAktif->bidangFungsi->id,
                    'kode' => $this->jabatanUtamaAktif->bidangFungsi->kode,
                    'nama' => $this->jabatanUtamaAktif->bidangFungsi->nama,
                ],
                'jenis_penugasan' => [
                    'id' => $this->jabatanUtamaAktif->jenisPenugasan->id,
                    'kode' => $this->jabatanUtamaAktif->jenisPenugasan->kode,
                    'nama' => $this->jabatanUtamaAktif->jenisPenugasan->nama,
                ],
            ] : null),
            'penugasan_tambahan_aktif' => $this->whenLoaded('penugasanTambahanAktif', fn () => $this->penugasanTambahanAktif->map(fn ($assignment) => [
                'id' => $assignment->id,
                'nama_jabatan' => $assignment->nama_jabatan,
                'tanggal_mulai' => $assignment->tanggal_mulai->toDateString(),
            ])),
            'kualifikasi' => $this->whenLoaded('kualifikasi', fn () => $this->kualifikasi->map(fn ($qualification) => [
                'id' => $qualification->id,
                'nama_kualifikasi' => $qualification->nama_kualifikasi,
                'tahun' => $qualification->tahun,
                'jenis_kualifikasi' => $qualification->jenisKualifikasi->nama,
                'bidang_fungsi' => $qualification->bidangFungsi?->nama,
            ])),
            'riwayat_jabatan' => $this->whenLoaded('riwayatJabatan', fn () => $this->riwayatJabatan->map(fn ($position) => [
                'id' => $position->id,
                'nama_jabatan' => $position->nama_jabatan,
                'is_jabatan_utama' => $position->is_jabatan_utama,
                'tanggal_mulai' => $position->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $position->tanggal_selesai?->toDateString(),
                'unit_organisasi' => $position->unitOrganisasi->nama,
                'bidang_fungsi' => $position->bidangFungsi->nama,
                'jenis_penugasan' => $position->jenisPenugasan->nama,
            ])),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
