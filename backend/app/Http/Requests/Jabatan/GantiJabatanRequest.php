<?php

namespace App\Http\Requests\Jabatan;

use App\Enums\StatusPersonel;
use App\Models\Personel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GantiJabatanRequest extends FormRequest
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
            'nama_jabatan' => ['required', 'string', 'max:255'],
            'bidang_fungsi_id' => ['required', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'jenis_penugasan_id' => ['required', 'integer', Rule::exists('jenis_penugasan', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tanggal_mulai' => ['required', 'date', 'before_or_equal:today'],
            'nivelering' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'dokumen_sk' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Personel $personel */
            $personel = $this->route('personel');
            $currentPosition = $personel->jabatanUtamaAktif()->first();

            if ($personel->status !== StatusPersonel::Aktif) {
                $validator->errors()->add('personel', 'Pergantian jabatan hanya dapat dilakukan untuk personel aktif.');
            }

            if (! $currentPosition) {
                $validator->errors()->add('personel', 'Personel tidak memiliki jabatan utama aktif.');

                return;
            }

            if (! $validator->errors()->has('tanggal_mulai')
                && $this->date('tanggal_mulai')?->lessThanOrEqualTo($currentPosition->tanggal_mulai)) {
                $validator->errors()->add('tanggal_mulai', 'Tanggal jabatan baru harus setelah tanggal mulai jabatan aktif.');
            }
        }];
    }
}
