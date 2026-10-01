<?php

namespace App\Http\Requests\Kualifikasi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKualifikasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('kualifikasi'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis_kualifikasi_id' => ['sometimes', 'integer', Rule::exists('jenis_kualifikasi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'bidang_fungsi_id' => ['sometimes', 'nullable', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'nama_kualifikasi' => ['sometimes', 'string', 'max:255'],
            'jenjang' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bidang_studi' => ['sometimes', 'nullable', 'string', 'max:255'],
            'institusi_penyelenggara' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['sometimes', 'nullable', 'date'],
            'tanggal_selesai' => ['sometimes', 'nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tahun' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'nomor_dokumen' => ['sometimes', 'nullable', 'string', 'max:100'],
            'keterangan' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'dokumen_pendukung' => ['sometimes', 'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
            'hapus_dokumen' => ['sometimes', 'boolean'],
        ];
    }
}
