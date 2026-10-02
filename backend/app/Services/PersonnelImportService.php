<?php

namespace App\Services;

use App\Http\Requests\Personel\StorePersonelRequest;
use App\Http\Requests\Personel\UpdatePersonelRequest;
use App\Models\Personel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonnelImportService
{
    public const SOURCE = 'prototype_simulation';

    private const OWNED = ['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'pangkat_id'];

    public function __construct(private PersonnelSource $source, private PersonnelRegistrationService $registration) {}

    public function preview(User $actor, string $mode, int $version): int
    {
        $records = [];
        $baseline = (int) DB::table('personnel_sync_checkpoints')->where('source', self::SOURCE)->value('version');
        abort_if($version < $baseline, 422, 'Versi sumber tidak boleh lebih lama dari checkpoint.');
        $page = 1;
        do {
            abort_if($page > 4, 422, 'Simulasi dibatasi empat halaman sumber.');
            $result = $this->source->fetch($version, $mode === 'delta' ? $baseline : 0, $page, 25);
            $records = [...$records, ...$result['records']];
            abort_if(count($records) > 100 || ($result['next_page'] !== null && $result['next_page'] <= $page), 422, 'Manifest simulasi melebihi batas atau cursor tidak valid.');
            $page = $result['next_page'];
        } while ($page !== null);

        Validator::make(['records' => $records], [
            'records' => ['array', 'max:100'],
            'records.*' => ['required', 'array:source_id,revision,deleted,personnel'],
            'records.*.source_id' => ['required', 'string', 'max:100', 'distinct:strict'],
            'records.*.revision' => ['required', 'integer', 'min:1', 'max:'.$version],
            'records.*.deleted' => ['required', 'boolean'],
            'records.*.personnel' => ['required', 'array:jenis_personel,nomor_identitas,nama_lengkap,pangkat_kode,unit_kode,tempat_lahir,tanggal_lahir,status,jabatan_utama,pendidikan_umum,pendidikan_polri'],
        ])->validate();

        return DB::transaction(function () use ($actor, $mode, $version, $baseline, $records): int {
            DB::table('personnel_sync_checkpoints')->insertOrIgnore(['source' => self::SOURCE, 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('personnel_import_runs')->insertGetId(['source' => self::SOURCE, 'mode' => $mode, 'version' => $version,
                'baseline_checkpoint' => $baseline, 'status' => 'preview', 'actor_id' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($records as $record) {
                $decision = $this->classify($record, $actor);
                DB::table('personnel_import_items')->insert(['run_id' => $id, 'source_record_id' => $record['source_id'],
                    'payload' => json_encode($record, JSON_THROW_ON_ERROR), ...Arr::only($decision, ['action', 'status', 'reason', 'fingerprint'])]);
            }

            return $id;
        });
    }

    /** @return array<string, mixed> */
    public function report(int $id, int $page = 1): array
    {
        $run = DB::table('personnel_import_runs')->find($id);
        abort_unless($run, 404);
        $items = DB::table('personnel_import_items')->where('run_id', $id)->orderBy('id')->paginate(25, ['*'], 'page', $page);
        $counts = DB::table('personnel_import_items')->where('run_id', $id)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return ['run' => $run, 'counts' => $counts, 'checkpoint' => (int) DB::table('personnel_sync_checkpoints')->where('source', self::SOURCE)->value('version'),
            'items' => array_map(function ($item): array {
                $payload = json_decode($item->payload, true, flags: JSON_THROW_ON_ERROR);

                return ['id' => $item->id, 'source_id' => $item->source_record_id, 'nama_lengkap' => $payload['personnel']['nama_lengkap'] ?? '',
                    'nomor_identitas' => $payload['personnel']['nomor_identitas'] ?? '', 'action' => $item->action, 'status' => $item->status, 'reason' => $item->reason];
            }, $items->items()), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]];
    }

    public function apply(int $id, User $actor, int $limit = 25): void
    {
        DB::transaction(function () use ($id, $actor, $limit): void {
            $checkpoint = DB::table('personnel_sync_checkpoints')->where('source', self::SOURCE)->lockForUpdate()->first();
            $run = DB::table('personnel_import_runs')->where('id', $id)->lockForUpdate()->first();
            abort_unless($run, 404);
            if (in_array($run->status, ['completed', 'completed_with_errors'], true)) {
                return;
            }
            abort_unless($checkpoint && $checkpoint->version === $run->baseline_checkpoint, 409, 'Checkpoint berubah. Buat pratinjau baru.');
            DB::table('personnel_import_runs')->where('id', $id)->update(['status' => 'running', 'updated_at' => now()]);
            foreach (DB::table('personnel_import_items')->where('run_id', $id)->where('status', 'pending')->orderBy('id')->limit($limit)->get() as $item) {
                try {
                    DB::transaction(function () use ($item, $actor): void {
                        $record = json_decode($item->payload, true, flags: JSON_THROW_ON_ERROR);
                        $decision = $this->classify($record, $actor, true);
                        if ($decision['status'] !== 'pending' || $decision['fingerprint'] !== $item->fingerprint || $decision['action'] !== $item->action) {
                            DB::table('personnel_import_items')->where('id', $item->id)->update(['status' => 'conflict', 'reason' => 'Data berubah sejak pratinjau. Buat pratinjau baru; data lokal tidak ditimpa.']);

                            return;
                        }
                        if ($decision['action'] === 'create') {
                            $person = $this->registration->create($decision['validated'], $actor);
                        } else {
                            $person = $decision['person'];
                            $person->update([...$decision['validated'], 'updated_by' => $actor->id]);
                        }
                        DB::table('personnel_source_links')->updateOrInsert(['source' => self::SOURCE, 'source_record_id' => $record['source_id']],
                            ['personel_id' => $person->id, 'revision' => $record['revision'], 'last_source_payload' => json_encode($record['personnel'], JSON_THROW_ON_ERROR), 'local_hash' => $this->localHash($person->fresh())]);
                        DB::table('personnel_import_items')->where('id', $item->id)->update(['status' => 'succeeded', 'reason' => 'Berhasil diproses.']);
                    });
                } catch (Throwable $exception) {
                    report($exception);
                    DB::table('personnel_import_items')->where('id', $item->id)->update(['status' => 'failed', 'reason' => 'Pemrosesan gagal; item dibatalkan. Periksa log layanan dan buat pratinjau baru.']);
                }
            }
            if (! DB::table('personnel_import_items')->where('run_id', $id)->where('status', 'pending')->exists()) {
                $errors = DB::table('personnel_import_items')->where('run_id', $id)->whereIn('status', ['failed', 'conflict'])->exists();
                DB::table('personnel_import_runs')->where('id', $id)->update(['status' => $errors ? 'completed_with_errors' : 'completed', 'updated_at' => now()]);
                if (! $errors) {
                    DB::table('personnel_sync_checkpoints')->where('source', self::SOURCE)->update(['version' => $run->version, 'updated_at' => now()]);
                }
            }
        });
    }

    /** @return array<string, mixed> */
    private function classify(array $record, User $actor, bool $lock = false): array
    {
        $decision = ['action' => 'conflict', 'status' => 'conflict', 'reason' => '', 'fingerprint' => null];
        if ($record['deleted'] ?? false) {
            return [...$decision, 'action' => 'ignored', 'status' => 'skipped', 'reason' => 'Tombstone sumber diabaikan; personel lokal tidak dihapus/diarsipkan otomatis.'];
        }
        $linkQuery = DB::table('personnel_source_links')->where('source', self::SOURCE)->where('source_record_id', $record['source_id']);
        $link = ($lock ? $linkQuery->lockForUpdate() : $linkQuery)->first();
        $query = Personel::withTrashed()->where($link ? 'id' : 'nomor_identitas', $link ? $link->personel_id : ($record['personnel']['nomor_identitas'] ?? ''));
        $person = ($lock ? $query->lockForUpdate() : $query)->first();
        if ($person && (! $link || $person->trashed())) {
            return [...$decision, 'reason' => 'NRP/NIP sudah ada tanpa pemetaan sumber, atau personel diarsipkan. Perlu pemeriksaan manual.'];
        }
        try {
            $data = $this->mapReferences($record['personnel']);
            if (! $link) {
                $validated = $this->validate(new StorePersonelRequest, $data, $actor);

                return [...$decision, 'action' => 'create', 'status' => 'pending', 'reason' => 'Personel baru; pendidikan dan jabatan awal ikut diimpor.', 'fingerprint' => hash('sha256', json_encode($validated, JSON_THROW_ON_ERROR)), 'validated' => $validated];
            }
            $previous = json_decode($link->last_source_payload, true, flags: JSON_THROW_ON_ERROR);
            $sourceOwned = ['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'pangkat_kode'];
            if (! $person || Arr::except($previous, $sourceOwned) !== Arr::except($record['personnel'], $sourceOwned)
                || $this->localHash($person) !== $link->local_hash) {
                return [...$decision, 'reason' => 'Penempatan/jabatan/pendidikan/identitas berubah atau ada perubahan lokal. Gunakan proses domain dan pemeriksaan manual.'];
            }
            if ($record['revision'] < $link->revision || ($record['revision'] === $link->revision && $previous !== $record['personnel'])) {
                return [...$decision, 'reason' => 'Revisi sumber mundur atau payload berubah tanpa revisi baru.'];
            }
            if ($previous === $record['personnel'] && $record['revision'] === $link->revision) {
                return [...$decision, 'action' => 'unchanged', 'status' => 'skipped', 'reason' => 'Tidak ada perubahan.'];
            }
            $route = new Route('PUT', '/personel/{personel}', fn () => null);
            $route->bind(Request::create('/personel/'.$person->id, 'PUT'));
            $route->setParameter('personel', $person);
            $request = new UpdatePersonelRequest;
            $request->setRouteResolver(fn () => $route);
            $validated = $this->validate($request, Arr::only($data, self::OWNED), $actor);

            return [...$decision, 'action' => 'update', 'status' => 'pending', 'reason' => 'Pembaruan identitas dasar saja; riwayat lokal tetap utuh.',
                'fingerprint' => hash('sha256', $this->localHash($person).':'.$person->getRawOriginal('updated_at').':'.$link->revision.':'.json_encode($validated, JSON_THROW_ON_ERROR)), 'validated' => $validated, 'person' => $person];
        } catch (ValidationException $exception) {
            return [...$decision, 'action' => 'invalid', 'status' => 'failed', 'reason' => 'Data sumber tidak valid: '.implode(', ', array_keys($exception->errors()))];
        }
    }

    private function localHash(Personel $person): string
    {
        return hash('sha256', json_encode(Arr::only($person->getRawOriginal(), [...self::OWNED, 'jenis_personel', 'nomor_identitas', 'unit_organisasi_id', 'status']), JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function validate(FormRequest $request, array $data, User $actor): array
    {
        $request->replace($data);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $actor);
        $request->validateResolved();

        return $request->validated();
    }

    /** @return array<string, mixed> */
    private function mapReferences(array $data): array
    {
        foreach (['pangkat_kode' => ['pangkat', 'pangkat_id'], 'unit_kode' => ['unit_organisasi', 'unit_organisasi_id']] as $key => [$table, $field]) {
            $data[$field] = DB::table($table)->where('kode', $data[$key] ?? '')->where('is_active', true)->whereNull('deleted_at')->value('id');
            unset($data[$key]);
        }
        if (is_array($data['jabatan_utama'] ?? null)) {
            foreach (['bidang_fungsi', 'jenis_penugasan'] as $table) {
                $data['jabatan_utama'][$table.'_id'] = DB::table($table)->where('kode', $data['jabatan_utama'][$table.'_kode'] ?? '')->where('is_active', true)->whereNull('deleted_at')->value('id');
                unset($data['jabatan_utama'][$table.'_kode']);
            }
        }

        return $data;
    }
}
