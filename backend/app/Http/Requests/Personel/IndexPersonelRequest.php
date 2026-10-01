<?php

namespace App\Http\Requests\Personel;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use App\Models\Personel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPersonelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Personel::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'unit_organisasi_id' => ['sometimes', 'integer', 'exists:unit_organisasi,id'],
            'pangkat_id' => ['sometimes', 'integer', 'exists:pangkat,id'],
            'jenis_personel' => ['sometimes', Rule::enum(JenisPersonel::class)],
            'status' => ['sometimes', Rule::enum(StatusPersonel::class)],
            'sort' => ['sometimes', Rule::in(['nama', 'pangkat', 'terbaru'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
