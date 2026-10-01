<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KualifikasiPersonelResource extends JsonResource
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
            'jenis_kualifikasi' => $this->whenLoaded('jenisKualifikasi', fn () => [
                'id' => $this->jenisKualifikasi->id,
                'kode' => $this->jenisKualifikasi->kode,
                'nama' => $this->jenisKualifikasi->nama,
            ]),
            'bidang_fungsi' => $this->whenLoaded('bidangFungsi', fn () => $this->bidangFungsi ? [
                'id' => $this->bidangFungsi->id,
                'kode' => $this->bidangFungsi->kode,
                'nama' => $this->bidangFungsi->nama,
            ] : null),
            'nama_kualifikasi' => $this->nama_kualifikasi,
            'jenjang' => $this->jenjang,
            'bidang_studi' => $this->bidang_studi,
            'institusi_penyelenggara' => $this->institusi_penyelenggara,
            'tanggal_mulai' => $this->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $this->tanggal_selesai?->toDateString(),
            'tahun' => $this->tahun,
            'nomor_dokumen' => $this->nomor_dokumen,
            'keterangan' => $this->keterangan,
            'dokumen' => $this->dokumen_pendukung_path ? [
                'nama_asli' => $this->dokumen_pendukung_nama_asli,
                'mime' => $this->dokumen_pendukung_mime,
                'ukuran' => $this->dokumen_pendukung_ukuran,
                'download_url' => route('api.v1.kualifikasi.download', $this->id),
            ] : null,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
