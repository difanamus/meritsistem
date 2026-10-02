<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merit\SaveMeritRecordRequest;
use App\Http\Resources\MeritRecordResource;
use App\Models\MeritRecord;
use App\Models\Personel;
use App\Services\MeritProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeritRecordController extends Controller
{
    public function index(Request $request, Personel $personel, string $type): AnonymousResourceCollection
    {
        Gate::authorize('view', $personel);
        $filters = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'wilayah' => ['sometimes', 'string', 'max:255'],
            'tingkat' => ['sometimes', Rule::in(['satker', 'kabupaten_kota', 'provinsi', 'nasional', 'internasional'])],
            'bidang_fungsi_id' => ['sometimes', 'integer', 'exists:bidang_fungsi,id'],
            'status_verifikasi' => ['sometimes', Rule::in(['belum_diverifikasi', 'terverifikasi'])],
            'kategori' => ['sometimes', Rule::in(['olahraga', 'akademik', 'operasional', 'inovasi', 'pelayanan', 'lainnya'])],
        ]);
        $class = MeritProfileService::modelClass($type);
        $query = $class::query()->where('personel_id', $personel->id)->with(['bidangFungsi', 'diverifikasiOleh']);
        foreach (['tingkat', 'bidang_fungsi_id', 'status_verifikasi'] as $key) {
            if (isset($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }
        if ($type === 'penugasan-operasi' && isset($filters['wilayah'])) {
            $query->where('wilayah', $filters['wilayah']);
        }
        if ($type === 'prestasi' && isset($filters['kategori'])) {
            $query->where('kategori', $filters['kategori']);
        }
        $date = match ($type) {
            'penugasan-operasi' => 'tanggal_mulai', 'prestasi' => 'tahun', default => 'tanggal_keputusan'
        };

        return MeritRecordResource::collection($query->orderByDesc($date)->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15)->withQueryString())
            ->additional(['success' => true, 'message' => 'Riwayat merit berhasil diambil.']);
    }

    public function store(SaveMeritRecordRequest $request, Personel $personel, string $type, MeritProfileService $service): JsonResponse
    {
        return $this->respond($service->save($type, $personel, $request->validated(), $request->user()), 'Riwayat merit berhasil ditambahkan.', 201);
    }

    public function show(string $type, int $record): JsonResponse
    {
        $item = MeritProfileService::find($type, $record);
        MeritProfileService::authorize($item, 'view');

        return $this->respond($item, 'Detail merit berhasil diambil.');
    }

    public function update(SaveMeritRecordRequest $request, string $type, int $record, MeritProfileService $service): JsonResponse
    {
        $item = MeritProfileService::find($type, $record);

        return $this->respond($service->save($type, $item->personel, $request->validated(), $request->user(), $record), 'Riwayat merit berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, int $record): JsonResponse
    {
        DB::transaction(function () use ($request, $type, $record): void {
            $class = MeritProfileService::modelClass($type);
            $item = $class::query()->lockForUpdate()->findOrFail($record);
            MeritProfileService::authorize($item, 'update');
            $item->update(['updated_by' => $request->user()->id]);
            $item->delete();
        });

        return response()->json(['success' => true, 'message' => 'Riwayat merit berhasil diarsipkan.']);
    }

    public function verify(Request $request, string $type, int $record): JsonResponse
    {
        abort_unless(in_array($request->user()->role, [UserRole::AdminSsdm, UserRole::SystemAdmin], true), 403);
        $data = $request->validate(['status_verifikasi' => ['required', Rule::in(['belum_diverifikasi', 'terverifikasi'])]]);
        $item = DB::transaction(function () use ($request, $type, $record, $data): MeritRecord {
            $class = MeritProfileService::modelClass($type);
            $item = $class::query()->lockForUpdate()->findOrFail($record);
            MeritProfileService::authorize($item, 'update');
            $verified = $data['status_verifikasi'] === 'terverifikasi';
            $item->update([...$data, 'verified_by' => $verified ? $request->user()->id : null,
                'verified_at' => $verified ? now() : null, 'updated_by' => $request->user()->id]);

            return $item;
        });

        return $this->respond($item, 'Status verifikasi berhasil diperbarui.');
    }

    public function download(string $type, int $record): StreamedResponse
    {
        $item = MeritProfileService::find($type, $record);
        MeritProfileService::authorize($item, 'view');
        abort_unless($item->dokumen_path && Storage::disk('local')->exists($item->dokumen_path), 404, 'Dokumen tidak ditemukan.');

        return Storage::disk('local')->download($item->dokumen_path, $item->dokumen_nama_asli, ['Content-Type' => 'application/pdf']);
    }

    private function respond(MeritRecord $record, string $message, int $status = 200): JsonResponse
    {
        return MeritRecordResource::make($record->load(['bidangFungsi', 'diverifikasiOleh']))
            ->additional(['success' => true, 'message' => $message])->response()->setStatusCode($status);
    }
}
