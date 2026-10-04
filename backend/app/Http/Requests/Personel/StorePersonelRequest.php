<?php

namespace App\Http\Requests\Personel;

use App\Enums\JenisPersonel;
use App\Enums\StatusPersonel;
use App\Http\Requests\Jabatan\StoreRiwayatJabatanRequest;
use App\Http\Requests\Kualifikasi\StoreKualifikasiRequest;
use App\Http\Requests\Merit\SaveMeritRecordRequest;
use App\Models\JenisKualifikasi;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Services\OrganizationalScopeService;
use Carbon\CarbonImmutable;
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
        $rules = [
            'foto' => ['prohibited'],
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
            'jabatan_utama.dokumen_sk' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
            'pendidikan_umum' => ['required', 'array'],
            'pendidikan_polri' => ['required_if:jenis_personel,polri', 'nullable', 'array'],
        ];

        $qualificationRules = (new StoreKualifikasiRequest)->rules();
        foreach (['pendidikan_umum', 'pendidikan_polri'] as $section) {
            if (is_array($this->input($section))) {
                $isList = array_is_list($this->input($section));
                $prefix = $isList ? $section.'.*' : $section;
                if ($isList) {
                    $rules[$section] = [...$rules[$section], 'list', 'max:20'];
                    $rules[$prefix] = ['required', 'array:'.implode(',', array_keys($qualificationRules))];
                } else {
                    $rules[$section] = [...$rules[$section], 'array:'.implode(',', array_keys($qualificationRules))];
                }
                $rules += $this->nestedRules($prefix, $qualificationRules);
                $rules[$prefix.'.tahun'] = ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)];
            }
        }
        $groups = ['kualifikasi' => $qualificationRules, 'riwayat_jabatan' => (new StoreRiwayatJabatanRequest)->rules()];
        foreach (['penugasan_operasi' => 'penugasan-operasi', 'prestasi' => 'prestasi', 'penghargaan' => 'penghargaan'] as $key => $type) {
            $groups[$key] = SaveMeritRecordRequest::recordRules($type);
        }
        foreach ($groups as $key => $childRules) {
            $rules[$key] = ['sometimes', 'array', 'max:20'];
            $rules[$key.'.*'] = ['required', 'array:'.implode(',', array_keys($childRules))];
            $rules += $this->nestedRules($key.'.*', $childRules);
        }
        // Initial job histories must be completed; active appointments use the main-job section.
        $rules['riwayat_jabatan.*.tanggal_selesai'] = ['required', 'date', 'after_or_equal:riwayat_jabatan.*.tanggal_mulai', 'before_or_equal:today'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $types = JenisKualifikasi::query()->where('is_active', true)->whereIn('kode', ['PENDIDIKAN_UMUM', 'PENDIDIKAN_POLRI'])->pluck('id', 'kode');
        foreach (['pendidikan_umum' => 'PENDIDIKAN_UMUM', 'pendidikan_polri' => 'PENDIDIKAN_POLRI'] as $section => $code) {
            if (is_array($this->input($section))) {
                $education = $this->input($section);
                if (array_is_list($education)) {
                    $education = array_map(fn ($record) => is_array($record)
                        ? [...$record, 'jenis_kualifikasi_id' => $types[$code] ?? null] : $record, $education);
                } else {
                    $education['jenis_kualifikasi_id'] = $types[$code] ?? null;
                }
                $this->merge([$section => $education]);
            }
        }
    }

    /** @return array<string, mixed> */
    private function nestedRules(string $prefix, array $rules): array
    {
        $result = [];
        foreach ($rules as $field => $validators) {
            $result[$prefix.'.'.$field] = array_map(fn ($rule) => is_string($rule) && str_starts_with($rule, 'after_or_equal:')
                ? 'after_or_equal:'.$prefix.'.'.substr($rule, 15) : $rule, $validators);
        }

        return $result;
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
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $periods = [];
                if ($this->filled('jabatan_utama')) {
                    $periods[] = [CarbonImmutable::parse($this->input('jabatan_utama.tanggal_mulai')), null];
                    if ($this->input('status') !== 'aktif') {
                        $validator->errors()->add('jabatan_utama', 'Personel nonaktif tidak dapat memiliki jabatan utama aktif.');
                    }
                }
                foreach ($this->input('riwayat_jabatan', []) as $index => $job) {
                    if (! app(OrganizationalScopeService::class)->canAccessUnit($this->user(), (int) $job['unit_organisasi_id'])) {
                        $validator->errors()->add("riwayat_jabatan.$index.unit_organisasi_id", 'Unit jabatan di luar cakupan akses Anda.');
                    }
                    if ($job['is_jabatan_utama']) {
                        $jobStart = CarbonImmutable::parse($job['tanggal_mulai']);
                        $jobEnd = CarbonImmutable::parse($job['tanggal_selesai']);
                        foreach ($periods as [$start, $end]) {
                            if (($end === null || $jobStart->lessThanOrEqualTo($end)) && $jobEnd->greaterThanOrEqualTo($start)) {
                                $validator->errors()->add("riwayat_jabatan.$index.tanggal_mulai", 'Periode jabatan utama bertumpang tindih.');
                            }
                        }
                        $periods[] = [$jobStart, $jobEnd];
                    }
                }
                foreach ($this->input('penugasan_operasi', []) as $index => $operation) {
                    if (! empty($operation['tanggal_selesai']) && CarbonImmutable::parse($operation['tanggal_selesai'])->lessThan(CarbonImmutable::parse($operation['tanggal_mulai']))) {
                        $validator->errors()->add("penugasan_operasi.$index.tanggal_selesai", 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.');
                    }
                }
                foreach ($this->input('prestasi', []) as $index => $record) {
                    if (! empty($record['tanggal']) && CarbonImmutable::parse($record['tanggal'])->year !== (int) $record['tahun']) {
                        $validator->errors()->add("prestasi.$index.tahun", 'Tahun harus sesuai dengan tanggal prestasi.');
                    }
                }
            },
        ];
    }
}
