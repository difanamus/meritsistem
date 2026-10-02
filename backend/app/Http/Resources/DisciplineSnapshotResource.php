<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisciplineSnapshotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'source_system' => $this->source_system, 'source_record_id' => $this->source_record_id,
            'jenis' => $this->jenis, 'ringkasan' => $this->ringkasan,
            'nomor_keputusan' => $this->nomor_keputusan, 'tanggal_keputusan' => $this->tanggal_keputusan->toDateString(),
            'sanksi' => $this->sanksi, 'instansi_penerbit' => $this->instansi_penerbit,
            'status_keputusan' => $this->status_keputusan, 'keterangan_pembatalan' => $this->keterangan_pembatalan,
            'source_updated_at' => $this->source_updated_at?->toISOString(), 'synced_at' => $this->synced_at?->toISOString(),
        ];
    }
}
