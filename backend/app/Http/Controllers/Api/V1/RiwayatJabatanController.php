<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jabatan\StoreRiwayatJabatanRequest;
use App\Http\Requests\Jabatan\UpdateRiwayatJabatanRequest;
use App\Http\Resources\RiwayatJabatanResource;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatJabatanController extends Controller
{
    public function index(Personel $personel): AnonymousResourceCollection
    {
        Gate::authorize('view', $personel);

        return RiwayatJabatanResource::collection(
            $personel->riwayatJabatan()
                ->with(['unitOrganisasi', 'bidangFungsi', 'jenisPenugasan'])
                ->latest('tanggal_mulai')
                ->latest('id')
                ->paginate(15),
        )->additional([
            'success' => true,
            'message' => 'Riwayat jabatan personel berhasil diambil.',
        ]);
    }

    public function store(StoreRiwayatJabatanRequest $request, Personel $personel): JsonResponse
    {
        $data = $this->withStoredDocument($request->validated());
        $position = $personel->riwayatJabatan()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return RiwayatJabatanResource::make($this->loadRelations($position))
            ->additional([
                'success' => true,
                'message' => 'Riwayat jabatan berhasil ditambahkan.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateRiwayatJabatanRequest $request, RiwayatJabatan $riwayatJabatan): JsonResponse
    {
        $data = $request->validated();
        $file = Arr::pull($data, 'dokumen_sk');
        $removeDocument = (bool) Arr::pull($data, 'hapus_dokumen', false);
        $oldPath = $riwayatJabatan->dokumen_sk_path;

        if ($file) {
            $data = [...$data, ...$this->documentMetadata($file)];
        } elseif ($removeDocument) {
            $data = [
                ...$data,
                'dokumen_sk_path' => null,
                'dokumen_sk_nama_asli' => null,
                'dokumen_sk_mime' => null,
                'dokumen_sk_ukuran' => null,
            ];
        }

        $riwayatJabatan->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        if (($file || $removeDocument) && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return RiwayatJabatanResource::make($this->loadRelations($riwayatJabatan->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Riwayat jabatan berhasil diperbarui.',
            ])
            ->response();
    }

    public function destroy(RiwayatJabatan $riwayatJabatan): JsonResponse
    {
        Gate::authorize('delete', $riwayatJabatan);

        if ($riwayatJabatan->is_jabatan_utama && $riwayatJabatan->tanggal_selesai === null) {
            return response()->json([
                'success' => false,
                'message' => 'Jabatan utama aktif tidak dapat dihapus. Gunakan proses ganti jabatan, mutasi, atau perubahan status personel.',
            ], Response::HTTP_CONFLICT);
        }

        $riwayatJabatan->update(['updated_by' => request()->user()->id]);
        $riwayatJabatan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat jabatan berhasil dihapus.',
        ]);
    }

    public function download(RiwayatJabatan $riwayatJabatan): StreamedResponse
    {
        Gate::authorize('view', $riwayatJabatan);

        abort_unless(
            $riwayatJabatan->dokumen_sk_path
                && Storage::disk('local')->exists($riwayatJabatan->dokumen_sk_path),
            Response::HTTP_NOT_FOUND,
            'Dokumen SK tidak ditemukan.',
        );

        return Storage::disk('local')->download(
            $riwayatJabatan->dokumen_sk_path,
            $riwayatJabatan->dokumen_sk_nama_asli,
            ['Content-Type' => $riwayatJabatan->dokumen_sk_mime],
        );
    }

    /** @param array<string, mixed> $data */
    private function withStoredDocument(array $data): array
    {
        $file = Arr::pull($data, 'dokumen_sk');

        return $file ? [...$data, ...$this->documentMetadata($file)] : $data;
    }

    /** @return array<string, mixed> */
    private function documentMetadata(UploadedFile $file): array
    {
        return [
            'dokumen_sk_path' => $file->store('dokumen-sk-jabatan', 'local'),
            'dokumen_sk_nama_asli' => $file->getClientOriginalName(),
            'dokumen_sk_mime' => $file->getMimeType(),
            'dokumen_sk_ukuran' => $file->getSize(),
        ];
    }

    private function loadRelations(RiwayatJabatan $position): RiwayatJabatan
    {
        return $position->load(['unitOrganisasi', 'bidangFungsi', 'jenisPenugasan']);
    }
}
