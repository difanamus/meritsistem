<?php

namespace App\Http\Requests\Jabatan;

use App\Models\Personel;
use App\Services\OrganizationalScopeService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MutasiRequest extends GantiJabatanRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'unit_organisasi_id' => ['required', 'integer', Rule::exists('unit_organisasi', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                /** @var Personel $personel */
                $personel = $this->route('personel');
                $destinationId = (int) $this->input('unit_organisasi_id');

                if ($destinationId === $personel->unit_organisasi_id) {
                    $validator->errors()->add('unit_organisasi_id', 'Unit tujuan harus berbeda dari unit personel saat ini.');
                }

                if ($destinationId > 0 && ! app(OrganizationalScopeService::class)->canAccessUnit($this->user(), $destinationId)) {
                    $validator->errors()->add('unit_organisasi_id', 'Unit tujuan berada di luar cakupan akses Anda.');
                }
            },
        ];
    }
}
