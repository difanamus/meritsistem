<?php

namespace App\Http\Requests\Reference;

use App\Enums\JenisPersonel;
use App\Enums\JenisUnit;
use App\Enums\ReferenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-reference', $this->route('type'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = ReferenceType::from($this->route('type'));
        $unique = Rule::unique($type->table(), 'kode');
        if ($this->route('reference') !== null) {
            $unique->ignore((int) $this->route('reference'));
        }
        $rules = [
            'kode' => ['required', 'string', 'max:'.$type->codeLength(), $unique],
            'nama' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'parent_id' => ['prohibited'],
            'jenis_unit' => ['prohibited'],
            'jenis_personel' => ['prohibited'],
            'urutan' => ['prohibited'],
            'deskripsi' => ['prohibited'],
        ];
        if ($type === ReferenceType::UnitOrganisasi) {
            $rules['parent_id'] = ['nullable', 'integer', Rule::exists('unit_organisasi', 'id')->whereNull('deleted_at')];
            $rules['jenis_unit'] = ['required', Rule::enum(JenisUnit::class)];
        }
        if ($type === ReferenceType::Pangkat) {
            $rules['jenis_personel'] = ['required', Rule::enum(JenisPersonel::class)];
            $rules['urutan'] = ['required', 'integer', 'min:0', 'max:65535'];
        }
        if ($type === ReferenceType::BidangFungsi) {
            $rules['deskripsi'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
