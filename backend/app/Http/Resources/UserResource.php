<?php

namespace App\Http\Resources;

use App\Models\Personel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

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
            'permissions' => $this->when($request->is('api/v1/auth/*'), fn (): array => [
                'create_personnel' => Gate::forUser($this->resource)->allows('create', Personel::class),
            ]),
            'scopes' => $this->whenLoaded('scopes', fn () => $this->scopes->map(fn ($scope) => [
                'id' => $scope->id,
                'scope_type' => $scope->scope_type->value,
                'scope_type_label' => $scope->scope_type->label(),
                'is_active' => $scope->is_active,
                'berlaku_mulai' => $scope->berlaku_mulai?->toDateString(),
                'berlaku_sampai' => $scope->berlaku_sampai?->toDateString(),
                'unit_organisasi' => [
                    'id' => $scope->unitOrganisasi->id,
                    'kode' => $scope->unitOrganisasi->kode,
                    'nama' => $scope->unitOrganisasi->nama,
                ],
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
