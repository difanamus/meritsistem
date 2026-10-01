<?php

namespace App\Http\Requests\Jabatan;

use App\Enums\StatusPersonel;
use App\Models\Personel;
use App\Services\OrganizationalScopeService;
use App\Services\PositionService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRiwayatJabatanRequest extends FormRequest
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
            'unit_organisasi_id' => ['required', 'integer', Rule::exists('unit_organisasi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'bidang_fungsi_id' => ['required', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'jenis_penugasan_id' => ['required', 'integer', Rule::exists('jenis_penugasan', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'nivelering' => ['nullable', 'string', 'max:50'],
            'is_jabatan_utama' => ['required', 'boolean'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'dokumen_sk' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Personel $personel */
            $personel = $this->route('personel');
            $unitId = (int) $this->input('unit_organisasi_id');

            if ($unitId > 0 && ! app(OrganizationalScopeService::class)->canAccessUnit($this->user(), $unitId)) {
                $validator->errors()->add('unit_organisasi_id', 'Unit jabatan berada di luar cakupan akses Anda.');
            }

            if ($this->boolean('is_jabatan_utama') && $this->filled('tanggal_mulai')) {
                if (! $this->filled('tanggal_selesai') && $personel->status !== StatusPersonel::Aktif) {
                    $validator->errors()->add('tanggal_selesai', 'Personel nonaktif tidak dapat memiliki jabatan utama aktif.');
                }

                $overlaps = app(PositionService::class)->hasPrimaryOverlap(
                    $personel->id,
                    (string) $this->input('tanggal_mulai'),
                    $this->input('tanggal_selesai'),
                );

                if ($overlaps) {
                    $validator->errors()->add('tanggal_mulai', 'Periode jabatan utama bertumpang tindih dengan riwayat yang sudah ada.');
                }
            }
        }];
    }
}
