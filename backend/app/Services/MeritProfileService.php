<?php

namespace App\Services;

use App\Models\MeritRecord;
use App\Models\PenghargaanPersonel;
use App\Models\PenugasanOperasi;
use App\Models\Personel;
use App\Models\PrestasiPersonel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MeritProfileService
{
    /** @return class-string<MeritRecord> */
    public static function modelClass(string $type): string
    {
        return match ($type) {
            'penugasan-operasi' => PenugasanOperasi::class,
            'prestasi' => PrestasiPersonel::class,
            'penghargaan' => PenghargaanPersonel::class,
            default => abort(404),
        };
    }

    /** @return list<string> */
    public static function fields(string $type): array
    {
        return ['nama', 'tingkat', 'bidang_fungsi_id', 'keterangan', ...match ($type) {
            'penugasan-operasi' => ['kode_operasi', 'jenis_operasi', 'wilayah', 'peran', 'satgas_unit', 'tanggal_mulai', 'tanggal_selesai', 'nomor_surat_perintah'],
            'prestasi' => ['kategori', 'hasil', 'penyelenggara', 'tanggal', 'tahun', 'peran'],
            'penghargaan' => ['pemberi', 'nomor_keputusan', 'tanggal_keputusan', 'alasan'],
            default => abort(404),
        }];
    }

    public static function find(string $type, int $id): MeritRecord
    {
        $class = self::modelClass($type);

        return $class::query()->findOrFail($id);
    }

    public static function authorize(MeritRecord $record, string $ability): void
    {
        abort_unless($record->personel !== null, 403, 'Profil personel telah diarsipkan.');
        Gate::authorize($ability, $record->personel);
    }

    /** @param array<string, mixed> $data */
    public function save(string $type, Personel $person, array $data, User $actor, ?int $id = null): MeritRecord
    {
        $file = Arr::pull($data, 'dokumen');
        $remove = (bool) Arr::pull($data, 'hapus_dokumen', false);
        $newPath = null;
        $oldPath = null;
        try {
            if ($file instanceof UploadedFile) {
                $newPath = $file->store('dokumen-merit/'.$type, 'local');
            }
            $record = DB::transaction(function () use ($type, $person, $data, $actor, $id, $file, $remove, $newPath, &$oldPath): MeritRecord {
                if ($id !== null) {
                    $class = self::modelClass($type);
                    $record = $class::query()->lockForUpdate()->findOrFail($id);
                    self::authorize($record, 'update');
                } else {
                    Gate::authorize('update', $person);
                    $class = self::modelClass($type);
                    $record = new $class;
                    $record->personel_id = $person->id;
                    $record->created_by = $actor->id;
                }
                $record->fill($data);
                $oldPath = $record->dokumen_path;
                if ($newPath) {
                    $record->fill(['dokumen_path' => $newPath, 'dokumen_nama_asli' => $file->getClientOriginalName(),
                        'dokumen_mime' => $file->getMimeType(), 'dokumen_ukuran' => $file->getSize()]);
                } elseif ($remove) {
                    $record->fill(['dokumen_path' => null, 'dokumen_nama_asli' => null, 'dokumen_mime' => null, 'dokumen_ukuran' => null]);
                }
                if (! $record->exists || $record->isDirty()) {
                    $record->fill(['status_verifikasi' => 'belum_diverifikasi', 'verified_by' => null, 'verified_at' => null, 'updated_by' => $actor->id]);
                }
                $record->save();

                return $record;
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
        if (($newPath || $remove) && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $record->refresh();
    }

    /** @param array<string, mixed> $filters */
    public static function filterOperations(Builder $query, array $filters): Builder
    {
        foreach (['operasi_wilayah' => 'wilayah', 'operasi_tingkat' => 'tingkat',
            'operasi_bidang_fungsi_id' => 'bidang_fungsi_id', 'operasi_verifikasi' => 'status_verifikasi'] as $key => $column) {
            if (isset($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function summarizeOperations(Builder $query, array $filters): void
    {
        $query->withCount(['penugasanOperasi as jumlah_operasi' => fn (Builder $operations) => self::filterOperations($operations, $filters)]);
        $days = DB::getDriverName() === 'pgsql'
            ? 'COALESCE(SUM(GREATEST(0, (LEAST(COALESCE(tanggal_selesai, CURRENT_DATE), CURRENT_DATE) - tanggal_mulai) + 1)), 0)'
            : 'COALESCE(SUM(MAX(0, julianday(MIN(COALESCE(tanggal_selesai, CURRENT_DATE), CURRENT_DATE)) - julianday(tanggal_mulai) + 1)), 0)';
        $duration = self::filterOperations(PenugasanOperasi::query()->selectRaw($days)
            ->whereColumn('penugasan_operasi.personel_id', 'personel.id'), $filters);
        $query->addSelect(['durasi_operasi_hari' => $duration]);
        if (array_intersect(array_keys($filters), ['operasi_wilayah', 'operasi_tingkat', 'operasi_bidang_fungsi_id', 'operasi_verifikasi'])) {
            $query->whereHas('penugasanOperasi', fn (Builder $operations) => self::filterOperations($operations, $filters));
        }
        if (isset($filters['min_jumlah_operasi'])) {
            $query->has('penugasanOperasi', '>=', $filters['min_jumlah_operasi'], 'and', fn (Builder $operations) => self::filterOperations($operations, $filters));
        }
        if (isset($filters['min_durasi_operasi_hari'])) {
            $query->where($duration, '>=', $filters['min_durasi_operasi_hari']);
        }
    }
}
