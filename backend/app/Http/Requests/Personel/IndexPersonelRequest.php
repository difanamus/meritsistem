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
            'arsip' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:100'],
            'unit_organisasi_id' => ['sometimes', 'integer', 'exists:unit_organisasi,id'],
            'pangkat_id' => ['sometimes', 'integer', 'exists:pangkat,id'],
            'bidang_fungsi_id' => ['sometimes', 'integer', 'exists:bidang_fungsi,id'],
            'jenis_kualifikasi_id' => ['sometimes', 'integer', 'exists:jenis_kualifikasi,id'],
            'operasi_wilayah' => ['sometimes', 'string', 'max:255'],
            'operasi_tingkat' => ['sometimes', Rule::in(['satker', 'kabupaten_kota', 'provinsi', 'nasional', 'internasional'])],
            'operasi_bidang_fungsi_id' => ['sometimes', 'integer', 'exists:bidang_fungsi,id'],
            'operasi_verifikasi' => ['sometimes', Rule::in(['belum_diverifikasi', 'terverifikasi'])],
            'min_jumlah_operasi' => ['sometimes', 'integer', 'min:1'],
            'min_durasi_operasi_hari' => ['sometimes', 'integer', 'min:1'],
            'jenis_personel' => ['sometimes', Rule::enum(JenisPersonel::class)],
            'status' => ['sometimes', Rule::enum(StatusPersonel::class)],
            'sort' => ['sometimes', Rule::in([
                'nama',
                'jabatan',
                'satker',
                'pangkat',
                'terbaru',
                'jumlah_kualifikasi',
                'durasi_pengalaman',
                'kualifikasi_terbaru',
                'jumlah_operasi',
                'durasi_operasi',
            ])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
