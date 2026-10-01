<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiwayatJabatanResource extends JsonResource
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
            'personel_id' => $this->personel_id,
            'nama_jabatan' => $this->nama_jabatan,
            'unit_organisasi' => $this->whenLoaded('unitOrganisasi', fn () => [
                'id' => $this->unitOrganisasi->id,
                'kode' => $this->unitOrganisasi->kode,
                'nama' => $this->unitOrganisasi->nama,
            ]),
            'bidang_fungsi' => $this->whenLoaded('bidangFungsi', fn () => [
                'id' => $this->bidangFungsi->id,
                'kode' => $this->bidangFungsi->kode,
                'nama' => $this->bidangFungsi->nama,
            ]),
            'jenis_penugasan' => $this->whenLoaded('jenisPenugasan', fn () => [
                'id' => $this->jenisPenugasan->id,
                'kode' => $this->jenisPenugasan->kode,
                'nama' => $this->jenisPenugasan->nama,
            ]),
            'tanggal_mulai' => $this->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            'nivelering' => $this->nivelering,
            'is_jabatan_utama' => $this->is_jabatan_utama,
            'keterangan' => $this->keterangan,
            'dokumen' => $this->dokumen_sk_path ? [
                'nama_asli' => $this->dokumen_sk_nama_asli,
                'mime' => $this->dokumen_sk_mime,
                'ukuran' => $this->dokumen_sk_ukuran,
                'download_url' => route('api.v1.riwayat-jabatan.download', $this->id),
            ] : null,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
