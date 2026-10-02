<?php

namespace App\Http\Requests\Merit;

use App\Models\MeritRecord;
use App\Services\MeritProfileService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveMeritRecordRequest extends FormRequest
{
    private ?MeritRecord $record = null;

    public function authorize(): bool
    {
        if ($this->route('record') !== null) {
            $this->record = MeritProfileService::find($this->route('type'), (int) $this->route('record'));
            MeritProfileService::authorize($this->record, 'update');

            return true;
        }

        return $this->user()->can('update', $this->route('personel'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return self::recordRules($this->route('type'), $this->record !== null);
    }

    /** @return array<string, mixed> */
    public static function recordRules(string $type, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';
        $rules = [
            'nama' => [$required, 'required', 'string', 'max:255'],
            'tingkat' => [$required, 'required', Rule::in(['satker', 'kabupaten_kota', 'provinsi', 'nasional', 'internasional'])],
            'bidang_fungsi_id' => ['sometimes', 'nullable', 'integer', Rule::exists('bidang_fungsi', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'keterangan' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'dokumen' => ['sometimes', 'nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:5120'],
            'hapus_dokumen' => ['sometimes', 'boolean'],
            'status_verifikasi' => ['prohibited'], 'verified_by' => ['prohibited'], 'verified_at' => ['prohibited'],
            'personel_id' => ['prohibited'], 'created_by' => ['prohibited'], 'updated_by' => ['prohibited'],
        ];
        $domain = match ($type) {
            'penugasan-operasi' => [
                'kode_operasi' => ['sometimes', 'nullable', 'string', 'max:100'],
                'jenis_operasi' => [$required, 'required', 'string', 'max:100'],
                'wilayah' => [$required, 'required', 'string', 'max:255'],
                'peran' => [$required, 'required', 'string', 'max:255'],
                'satgas_unit' => [$required, 'required', 'string', 'max:255'],
                'tanggal_mulai' => [$required, 'required', 'date'],
                'tanggal_selesai' => ['sometimes', 'nullable', 'date'],
                'nomor_surat_perintah' => ['sometimes', 'nullable', 'string', 'max:100'],
            ],
            'prestasi' => [
                'kategori' => [$required, 'required', Rule::in(['olahraga', 'akademik', 'operasional', 'inovasi', 'pelayanan', 'lainnya'])],
                'hasil' => [$required, 'required', 'string', 'max:255'],
                'penyelenggara' => [$required, 'required', 'string', 'max:255'],
                'tanggal' => ['sometimes', 'nullable', 'date'],
                'tahun' => [$required, 'required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
                'peran' => [$required, 'required', Rule::in(['individu', 'tim'])],
            ],
            'penghargaan' => [
                'pemberi' => [$required, 'required', 'string', 'max:255'],
                'nomor_keputusan' => ['sometimes', 'nullable', 'string', 'max:100'],
                'tanggal_keputusan' => [$required, 'required', 'date'],
                'alasan' => [$required, 'required', 'string', 'max:1000'],
            ],
            default => abort(404),
        };

        return [...$rules, ...$domain];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['tanggal_mulai', 'tanggal_selesai', 'tanggal', 'tahun'])) {
                return;
            }
            $effective = fn (string $key) => $this->has($key) ? $this->input($key) : $this->record?->getAttribute($key);
            if ($this->route('type') === 'penugasan-operasi') {
                $start = $effective('tanggal_mulai');
                $end = $effective('tanggal_selesai');
                if ($start && $end && CarbonImmutable::parse($end)->lessThan(CarbonImmutable::parse($start))) {
                    $validator->errors()->add('tanggal_selesai', 'Tanggal selesai harus sama atau setelah tanggal mulai.');
                }
            }
            if ($this->route('type') === 'prestasi' && $effective('tanggal')
                && CarbonImmutable::parse($effective('tanggal'))->year !== (int) $effective('tahun')) {
                $validator->errors()->add('tahun', 'Tahun harus sesuai dengan tanggal prestasi.');
            }
        }];
    }
}
