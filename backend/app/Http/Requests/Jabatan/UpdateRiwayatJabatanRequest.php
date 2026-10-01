<?php

namespace App\Http\Requests\Jabatan;

use App\Models\RiwayatJabatan;
use App\Services\OrganizationalScopeService;
use App\Services\PositionService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRiwayatJabatanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('riwayatJabatan'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_jabatan' => ['sometimes', 'string', 'max:255'],
            'unit_organisasi_id' => ['sometimes', 'integer', Rule::exists('unit_organisasi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'bidang_fungsi_id' => ['sometimes', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'jenis_penugasan_id' => ['sometimes', 'integer', Rule::exists('jenis_penugasan', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'tanggal_mulai' => ['sometimes', 'date'],
            'tanggal_selesai' => ['sometimes', 'nullable', 'date'],
            'nivelering' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_jabatan_utama' => ['prohibited'],
            'keterangan' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'dokumen_sk' => ['sometimes', 'nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
            'hapus_dokumen' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var RiwayatJabatan $position */
            $position = $this->route('riwayatJabatan');
            $startDate = (string) $this->input('tanggal_mulai', $position->tanggal_mulai->toDateString());
            $endDate = $this->has('tanggal_selesai')
                ? $this->input('tanggal_selesai')
                : $position->tanggal_selesai?->toDateString();
            $unitId = (int) $this->input('unit_organisasi_id', $position->unit_organisasi_id);

            if (! $validator->errors()->hasAny(['tanggal_mulai', 'tanggal_selesai'])
                && $endDate !== null
                && CarbonImmutable::parse($endDate)->lessThan(CarbonImmutable::parse($startDate))) {
                $validator->errors()->add('tanggal_selesai', 'Tanggal selesai harus sama atau setelah tanggal mulai.');
            }

            if (! app(OrganizationalScopeService::class)->canAccessUnit($this->user(), $unitId)) {
                $validator->errors()->add('unit_organisasi_id', 'Unit jabatan berada di luar cakupan akses Anda.');
            }

            if ($position->is_jabatan_utama && $position->tanggal_selesai === null && $unitId !== $position->unit_organisasi_id) {
                $validator->errors()->add('unit_organisasi_id', 'Unit jabatan utama aktif hanya dapat diubah melalui proses mutasi.');
            }

            if ($position->is_jabatan_utama && $position->tanggal_selesai === null && $this->filled('tanggal_selesai')) {
                $validator->errors()->add('tanggal_selesai', 'Jabatan utama aktif hanya dapat diakhiri melalui proses ganti jabatan, mutasi, atau perubahan status personel.');
            }

            if (! $validator->errors()->hasAny(['tanggal_mulai', 'tanggal_selesai'])
                && $position->is_jabatan_utama && app(PositionService::class)->hasPrimaryOverlap(
                    $position->personel_id,
                    $startDate,
                    $endDate,
                    $position->id,
                )) {
                $validator->errors()->add('tanggal_mulai', 'Periode jabatan utama bertumpang tindih dengan riwayat yang sudah ada.');
            }
        }];
    }
}
