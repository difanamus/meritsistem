<?php

namespace App\Http\Resources;

use App\Services\MeritProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class MeritRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $type = $request->route('type');
        $data = Arr::only($this->resource->attributesToArray(), MeritProfileService::fields($type));
        unset($data['bidang_fungsi_id']);
        foreach (['tanggal_mulai', 'tanggal_selesai', 'tanggal', 'tanggal_keputusan'] as $date) {
            if (array_key_exists($date, $data)) {
                $data[$date] = $this->resource->getAttribute($date)?->toDateString();
            }
        }

        return [
            'id' => $this->id, 'personel_id' => $this->personel_id, ...$data,
            'bidang_fungsi' => $this->bidangFungsi ? Arr::only($this->bidangFungsi->toArray(), ['id', 'nama', 'kode']) : null,
            'status_verifikasi' => $this->status_verifikasi,
            'verified_by' => $this->diverifikasiOleh ? ['id' => $this->diverifikasiOleh->id, 'nama' => $this->diverifikasiOleh->name] : null,
            'verified_at' => $this->verified_at?->toISOString(),
            'dokumen' => $this->dokumen_path ? ['nama_asli' => $this->dokumen_nama_asli, 'mime' => $this->dokumen_mime,
                'ukuran' => $this->dokumen_ukuran, 'download_url' => route('api.v1.merit.download', ['type' => $type, 'record' => $this->id])] : null,
            'created_at' => $this->created_at->toISOString(), 'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
