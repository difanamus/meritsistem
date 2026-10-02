<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusPersonel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Personel\IndexPersonelRequest;
use App\Http\Requests\Personel\StorePersonelRequest;
use App\Http\Requests\Personel\UpdatePersonelRequest;
use App\Http\Resources\PersonelResource;
use App\Models\KualifikasiPersonel;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\User;
use App\Services\MeritProfileService;
use App\Services\OrganizationalScopeService;
use App\Services\PersonnelRegistrationService;
use App\Services\UserAdministrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PersonelController extends Controller
{
    public function index(
        IndexPersonelRequest $request,
        OrganizationalScopeService $scopeService,
        MeritProfileService $meritProfile,
    ): AnonymousResourceCollection {
        $filters = $request->validated();
        $archived = $request->boolean('arsip');
        abort_if($archived && ! $scopeService->isGlobal($request->user()), 403);
        $functionId = $filters['bidang_fungsi_id'] ?? null;
        $query = $scopeService->scopePersonelQuery(
            Personel::query()
                ->with(['pangkat', 'unitOrganisasi', 'jabatanUtamaAktif.unitOrganisasi', 'jabatanUtamaAktif.bidangFungsi', 'jabatanUtamaAktif.jenisPenugasan'])
                ->withCount([
                    'kualifikasi',
                    'kualifikasi as jumlah_kualifikasi_relevan' => fn (Builder $query) => $query
                        ->when($functionId, fn (Builder $query, int $id) => $query->where('bidang_fungsi_id', $id)),
                ]),
            $request->user(),
        );
        if ($archived) {
            $query->onlyTrashed();
        }

        $durationExpression = DB::getDriverName() === 'pgsql'
            ? 'COALESCE(SUM((COALESCE(tanggal_selesai, CURRENT_DATE) - tanggal_mulai) + 1), 0)'
            : 'COALESCE(SUM(julianday(COALESCE(tanggal_selesai, CURRENT_DATE)) - julianday(tanggal_mulai) + 1), 0)';

        $query->addSelect([
            'durasi_pengalaman_hari' => RiwayatJabatan::query()
                ->selectRaw($durationExpression)
                ->whereColumn('riwayat_jabatan.personel_id', 'personel.id')
                ->when($functionId, fn (Builder $query, int $id) => $query->where('bidang_fungsi_id', $id)),
            'tahun_kualifikasi_terbaru' => KualifikasiPersonel::query()
                ->selectRaw('MAX(tahun)')
                ->whereColumn('kualifikasi_personel.personel_id', 'personel.id')
                ->when($functionId, fn (Builder $query, int $id) => $query->where('bidang_fungsi_id', $id)),
        ]);

        $meritProfile->summarizeOperations($query, $filters);

        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nomor_identitas', 'like', "%{$search}%");
                });
            })
            ->when($filters['unit_organisasi_id'] ?? null, fn (Builder $query, int $id) => $query->where('unit_organisasi_id', $id))
            ->when($filters['pangkat_id'] ?? null, fn (Builder $query, int $id) => $query->where('pangkat_id', $id))
            ->when($functionId, function (Builder $query, int $id): void {
                $query->where(function (Builder $query) use ($id): void {
                    $query
                        ->whereHas('kualifikasi', fn (Builder $query) => $query->where('bidang_fungsi_id', $id))
                        ->orWhereHas('riwayatJabatan', fn (Builder $query) => $query->where('bidang_fungsi_id', $id));
                });
            })
            ->when($filters['jenis_kualifikasi_id'] ?? null, fn (Builder $query, int $id) => $query
                ->whereHas('kualifikasi', fn (Builder $query) => $query->where('jenis_kualifikasi_id', $id)))
            ->when($filters['jenis_personel'] ?? null, fn (Builder $query, string $value) => $query->where('jenis_personel', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value));

        $direction = $filters['direction'] ?? 'asc';

        match ($filters['sort'] ?? 'nama') {
            'terbaru' => $query->orderBy('created_at', $direction),
            'pangkat' => $query->orderBy('jenis_personel')->orderBy(
                Pangkat::query()->select('urutan')->whereColumn('pangkat.id', 'personel.pangkat_id'),
                $direction,
            ),
            'jumlah_kualifikasi' => $query->orderBy('jumlah_kualifikasi_relevan', $direction),
            'durasi_pengalaman' => $query->orderBy('durasi_pengalaman_hari', $direction),
            'kualifikasi_terbaru' => $query->orderBy('tahun_kualifikasi_terbaru', $direction),
            'jumlah_operasi' => $query->orderBy('jumlah_operasi', $direction),
            'durasi_operasi' => $query->orderBy('durasi_operasi_hari', $direction),
            default => $query->orderBy('nama_lengkap', $direction),
        };

        return PersonelResource::collection(
            $query->orderBy('id')->paginate($filters['per_page'] ?? 15)->withQueryString(),
        )->additional([
            'success' => true,
            'message' => 'Daftar personel berhasil diambil.',
        ]);
    }

    public function store(StorePersonelRequest $request, PersonnelRegistrationService $registration): JsonResponse
    {
        $personel = $registration->create($request->validated(), $request->user());

        return PersonelResource::make($this->loadProfile($personel))
            ->additional([
                'success' => true,
                'message' => 'Data personel berhasil dibuat.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Personel $personel): JsonResponse
    {
        Gate::authorize('view', $personel);

        return PersonelResource::make($this->loadProfile($personel))
            ->additional([
                'success' => true,
                'message' => 'Profil personel berhasil diambil.',
            ])
            ->response();
    }

    public function update(UpdatePersonelRequest $request, Personel $personel): JsonResponse
    {
        DB::transaction(function () use ($request, $personel): void {
            $wasActive = $personel->status === StatusPersonel::Aktif;

            $personel->update([
                ...$request->validated(),
                'updated_by' => $request->user()->id,
            ]);
            User::query()->where('personel_id', $personel->id)->update(['name' => $personel->nama_lengkap]);

            if ($wasActive && $personel->status !== StatusPersonel::Aktif) {
                $personel->jabatanUtamaAktif()->update([
                    'tanggal_selesai' => today(),
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        return PersonelResource::make($this->loadProfile($personel->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Data personel berhasil diperbarui.',
            ])
            ->response();
    }

    public function destroy(Request $request, Personel $personel, UserAdministrationService $users): JsonResponse
    {
        Gate::authorize('delete', $personel);

        $data = $request->validate(['alasan_arsip' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($personel, $request, $data, $users): void {
            $locked = Personel::query()->lockForUpdate()->findOrFail($personel->id);
            $locked->update([...$data, 'archived_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            foreach (User::query()->where('personel_id', $locked->id)->lockForUpdate()->get() as $account) {
                Gate::authorize('update', $account);
                $users->deactivate($account);
            }
            $locked->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Personel diarsipkan tanpa mengubah riwayat karier. Akun terkait dinonaktifkan.',
        ]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $person = Personel::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $person);
        DB::transaction(function () use ($person, $request): void {
            $locked = Personel::onlyTrashed()->lockForUpdate()->findOrFail($person->id);
            $locked->restore();
            $locked->update(['updated_by' => $request->user()->id]);
        });

        return response()->json(['success' => true, 'message' => 'Personel dipulihkan. Akun tidak otomatis diaktifkan kembali.']);
    }

    public function options(Request $request): JsonResponse
    {
        Gate::authorize('create', User::class);
        $data = $request->validate(['search' => ['required', 'string', 'min:3', 'max:100']]);
        $term = '%'.mb_strtolower($data['search']).'%';
        $people = Personel::query()->where('status', 'aktif')
            ->where(fn (Builder $query) => $query->whereRaw('LOWER(nama_lengkap) LIKE ?', [$term])->orWhere('nomor_identitas', 'like', $term))
            ->orderBy('nama_lengkap')->orderBy('id')->limit(25)->get(['id', 'nama_lengkap', 'nomor_identitas']);

        return response()->json(['data' => $people]);
    }

    private function loadProfile(Personel $personel): Personel
    {
        return $personel->load([
            'pangkat',
            'unitOrganisasi',
            'jabatanUtamaAktif.unitOrganisasi',
            'jabatanUtamaAktif.bidangFungsi',
            'jabatanUtamaAktif.jenisPenugasan',
            'penugasanTambahanAktif',
        ])->loadCount('kualifikasi');
    }
}
