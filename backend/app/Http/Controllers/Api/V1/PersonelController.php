<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StatusPersonel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Personel\IndexPersonelRequest;
use App\Http\Requests\Personel\StorePersonelRequest;
use App\Http\Requests\Personel\UpdatePersonelRequest;
use App\Http\Resources\PersonelResource;
use App\Models\Pangkat;
use App\Models\Personel;
use App\Services\OrganizationalScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PersonelController extends Controller
{
    public function index(
        IndexPersonelRequest $request,
        OrganizationalScopeService $scopeService,
    ): AnonymousResourceCollection {
        $filters = $request->validated();
        $query = $scopeService->scopePersonelQuery(
            Personel::query()
                ->with(['pangkat', 'unitOrganisasi', 'jabatanUtamaAktif.unitOrganisasi', 'jabatanUtamaAktif.bidangFungsi', 'jabatanUtamaAktif.jenisPenugasan'])
                ->withCount('kualifikasi'),
            $request->user(),
        );

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
            ->when($filters['jenis_personel'] ?? null, fn (Builder $query, string $value) => $query->where('jenis_personel', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value));

        $direction = $filters['direction'] ?? 'asc';

        match ($filters['sort'] ?? 'nama') {
            'terbaru' => $query->orderBy('created_at', $direction),
            'pangkat' => $query->orderBy(
                Pangkat::query()->select('urutan')->whereColumn('pangkat.id', 'personel.pangkat_id'),
                $direction,
            ),
            default => $query->orderBy('nama_lengkap', $direction),
        };

        return PersonelResource::collection(
            $query->orderBy('id')->paginate($filters['per_page'] ?? 15)->withQueryString(),
        )->additional([
            'success' => true,
            'message' => 'Daftar personel berhasil diambil.',
        ]);
    }

    public function store(StorePersonelRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $positionData = Arr::pull($validated, 'jabatan_utama');

        $personel = DB::transaction(function () use ($request, $validated, $positionData): Personel {
            $personel = Personel::query()->create([
                ...$validated,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            if ($positionData !== null) {
                $personel->riwayatJabatan()->create([
                    ...$positionData,
                    'unit_organisasi_id' => $personel->unit_organisasi_id,
                    'is_jabatan_utama' => true,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }

            return $personel;
        });

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

    public function destroy(Personel $personel): JsonResponse
    {
        Gate::authorize('delete', $personel);

        DB::transaction(function () use ($personel): void {
            $personel->jabatanUtamaAktif()->update([
                'tanggal_selesai' => today(),
                'updated_by' => request()->user()->id,
            ]);
            $personel->update(['updated_by' => request()->user()->id]);
            $personel->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data personel berhasil dihapus.',
        ]);
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
            'kualifikasi' => fn ($query) => $query->latest('tanggal_selesai')->latest('id'),
            'kualifikasi.jenisKualifikasi',
            'kualifikasi.bidangFungsi',
            'riwayatJabatan' => fn ($query) => $query->latest('tanggal_mulai')->latest('id'),
            'riwayatJabatan.unitOrganisasi',
            'riwayatJabatan.bidangFungsi',
            'riwayatJabatan.jenisPenugasan',
        ])->loadCount('kualifikasi');
    }
}
