<?php

namespace App\Http\Resources;

use App\Models\BidangFungsi;
use App\Models\Pangkat;
use App\Models\UnitOrganisasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $data = ['id' => $this->id, 'kode' => $this->kode, 'nama' => $this->nama, 'is_active' => $this->is_active];
        if ($this->resource instanceof UnitOrganisasi) {
            $data['parent_id'] = $this->parent_id;
            $data['parent'] = $this->whenLoaded('parent', fn () => $this->parent
                ? ['id' => $this->parent->id, 'nama' => $this->parent->nama] : null);
            $data['jenis_unit'] = $this->jenis_unit->value;
            $data['jenis_unit_label'] = $this->jenis_unit->label();
        }
        if ($this->resource instanceof Pangkat) {
            $data['jenis_personel'] = $this->jenis_personel->value;
            $data['urutan'] = $this->urutan;
        }
        if ($this->resource instanceof BidangFungsi) {
            $data['deskripsi'] = $this->deskripsi;
        }

        return $data;
    }
}
