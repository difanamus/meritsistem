<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'is_active' => $this->is_active,
            'scopes' => $this->whenLoaded('scopes', fn () => $this->scopes->map(fn ($scope) => [
                'id' => $scope->id,
                'scope_type' => $scope->scope_type->value,
                'scope_type_label' => $scope->scope_type->label(),
                'unit_organisasi' => [
                    'id' => $scope->unitOrganisasi->id,
                    'kode' => $scope->unitOrganisasi->kode,
                    'nama' => $scope->unitOrganisasi->nama,
                ],
            ])),
        ];
    }
}
