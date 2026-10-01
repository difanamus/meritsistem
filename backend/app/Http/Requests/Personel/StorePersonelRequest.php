<?php

namespace App\Http\Requests\Personel;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Services\OrganizationalScopeService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePersonelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Personel::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis_personel' => ['required', Rule::enum(JenisPersonel::class)],
            'nomor_identitas' => ['required', 'string', 'regex:/^\d{6,30}$/', 'unique:personel,nomor_identitas'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'pangkat_id' => ['required', 'integer', Rule::exists('pangkat', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'unit_organisasi_id' => ['required', 'integer', Rule::exists('unit_organisasi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'status' => ['required', Rule::enum(StatusPersonel::class)],
            'jabatan_utama' => ['required_if:status,aktif', 'nullable', 'array'],
            'jabatan_utama.nama_jabatan' => ['required_with:jabatan_utama', 'string', 'max:255'],
            'jabatan_utama.bidang_fungsi_id' => ['required_with:jabatan_utama', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'jabatan_utama.jenis_penugasan_id' => ['required_with:jabatan_utama', 'integer', Rule::exists('jenis_penugasan', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'jabatan_utama.tanggal_mulai' => ['required_with:jabatan_utama', 'date', 'before_or_equal:today'],
            'jabatan_utama.nivelering' => ['nullable', 'string', 'max:50'],
            'jabatan_utama.keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unitId = (int) $this->input('unit_organisasi_id');

                if ($unitId > 0 && ! app(OrganizationalScopeService::class)->canAccessUnit($this->user(), $unitId)) {
                    $validator->errors()->add('unit_organisasi_id', 'Unit organisasi berada di luar cakupan akses Anda.');
                }

                $pangkat = Pangkat::query()->find($this->input('pangkat_id'));

                if ($pangkat && $pangkat->jenis_personel->value !== $this->input('jenis_personel')) {
                    $validator->errors()->add('pangkat_id', 'Pangkat tidak sesuai dengan jenis personel.');
                }
            },
        ];
    }
}
