<?php

namespace App\Http\Requests\Personel;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePersonelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('personel'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis_personel' => ['sometimes', Rule::enum(JenisPersonel::class)],
            'nomor_identitas' => [
                'sometimes',
                'string',
                'regex:/^\d{6,30}$/',
                Rule::unique('personel', 'nomor_identitas')->ignore($this->route('personel')),
            ],
            'nama_lengkap' => ['sometimes', 'string', 'max:255'],
            'pangkat_id' => ['sometimes', 'integer', Rule::exists('pangkat', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tempat_lahir' => ['sometimes', 'string', 'max:100'],
            'tanggal_lahir' => ['sometimes', 'date', 'before:today'],
            'status' => ['sometimes', Rule::enum(StatusPersonel::class)],
            'unit_organisasi_id' => ['prohibited'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Personel $personel */
                $personel = $this->route('personel');
                $jenisPersonel = $this->input('jenis_personel', $personel->jenis_personel->value);
                $pangkatId = $this->input('pangkat_id', $personel->pangkat_id);
                $pangkat = Pangkat::query()->find($pangkatId);

                if ($pangkat && $pangkat->jenis_personel->value !== $jenisPersonel) {
                    $validator->errors()->add('pangkat_id', 'Pangkat tidak sesuai dengan jenis personel.');
                }

                if ($personel->status !== StatusPersonel::Aktif && $this->input('status') === StatusPersonel::Aktif->value) {
                    $validator->errors()->add('status', 'Aktivasi kembali harus dilakukan bersama penetapan jabatan utama.');
                }
            },
        ];
    }
}
