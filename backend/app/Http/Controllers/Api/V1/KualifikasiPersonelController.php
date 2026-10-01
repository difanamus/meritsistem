<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kualifikasi\StoreKualifikasiRequest;
use App\Http\Requests\Kualifikasi\UpdateKualifikasiRequest;
use App\Http\Resources\KualifikasiPersonelResource;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KualifikasiPersonelController extends Controller
{
    public function index(Personel $personel): AnonymousResourceCollection
    {
        Gate::authorize('view', $personel);

        $qualifications = $personel->kualifikasi()
            ->with(['jenisKualifikasi', 'bidangFungsi'])
            ->latest('tanggal_selesai')
            ->latest('tahun')
            ->latest('id')
            ->paginate(15);

        return KualifikasiPersonelResource::collection($qualifications)->additional([
            'success' => true,
            'message' => 'Daftar kualifikasi personel berhasil diambil.',
        ]);
    }

    public function store(StoreKualifikasiRequest $request, Personel $personel): JsonResponse
    {
        $data = $request->validated();
        $file = Arr::pull($data, 'dokumen_pendukung');

        if ($file) {
            $data = [
                ...$data,
                'dokumen_pendukung_path' => $file->store('dokumen-kualifikasi', 'local'),
                'dokumen_pendukung_nama_asli' => $file->getClientOriginalName(),
                'dokumen_pendukung_mime' => $file->getMimeType(),
                'dokumen_pendukung_ukuran' => $file->getSize(),
            ];
        }

        $qualification = $personel->kualifikasi()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return KualifikasiPersonelResource::make($this->loadRelations($qualification))
            ->additional([
                'success' => true,
                'message' => 'Kualifikasi personel berhasil ditambahkan.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateKualifikasiRequest $request, KualifikasiPersonel $kualifikasi): JsonResponse
    {
        $data = $request->validated();
        $file = Arr::pull($data, 'dokumen_pendukung');
        $removeDocument = (bool) Arr::pull($data, 'hapus_dokumen', false);
        $oldPath = $kualifikasi->dokumen_pendukung_path;

        if ($file) {
            $data = [
                ...$data,
                'dokumen_pendukung_path' => $file->store('dokumen-kualifikasi', 'local'),
                'dokumen_pendukung_nama_asli' => $file->getClientOriginalName(),
                'dokumen_pendukung_mime' => $file->getMimeType(),
                'dokumen_pendukung_ukuran' => $file->getSize(),
            ];
        } elseif ($removeDocument) {
            $data = [
                ...$data,
                'dokumen_pendukung_path' => null,
                'dokumen_pendukung_nama_asli' => null,
                'dokumen_pendukung_mime' => null,
                'dokumen_pendukung_ukuran' => null,
            ];
        }

        $kualifikasi->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        if (($file || $removeDocument) && $oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return KualifikasiPersonelResource::make($this->loadRelations($kualifikasi->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Kualifikasi personel berhasil diperbarui.',
            ])
            ->response();
    }

    public function destroy(KualifikasiPersonel $kualifikasi): JsonResponse
    {
        Gate::authorize('delete', $kualifikasi);
        $kualifikasi->update(['updated_by' => request()->user()->id]);
        $kualifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kualifikasi personel berhasil dihapus.',
        ]);
    }

    public function download(KualifikasiPersonel $kualifikasi): StreamedResponse
    {
        Gate::authorize('view', $kualifikasi);

        abort_unless(
            $kualifikasi->dokumen_pendukung_path
                && Storage::disk('local')->exists($kualifikasi->dokumen_pendukung_path),
            Response::HTTP_NOT_FOUND,
            'Dokumen tidak ditemukan.',
        );

        return Storage::disk('local')->download(
            $kualifikasi->dokumen_pendukung_path,
            $kualifikasi->dokumen_pendukung_nama_asli,
            ['Content-Type' => $kualifikasi->dokumen_pendukung_mime],
        );
    }

    private function loadRelations(KualifikasiPersonel $qualification): KualifikasiPersonel
    {
        return $qualification->load(['jenisKualifikasi', 'bidangFungsi']);
    }
}
