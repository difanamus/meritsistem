<?php

namespace App\Http\Requests\Kualifikasi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKualifikasiRequest extends FormRequest
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
            'jenis_kualifikasi_id' => ['required', 'integer', Rule::exists('jenis_kualifikasi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'bidang_fungsi_id' => ['nullable', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'nama_kualifikasi' => ['required', 'string', 'max:255'],
            'jenjang' => ['nullable', 'string', 'max:100'],
            'bidang_studi' => ['nullable', 'string', 'max:255'],
            'institusi_penyelenggara' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'nomor_dokumen' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'dokumen_pendukung' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
        ];
    }
}
