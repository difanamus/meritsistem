<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jabatan\GantiJabatanRequest;
use App\Http\Requests\Jabatan\MutasiRequest;
use App\Http\Resources\RiwayatJabatanResource;
use App\Models\Personel;
use App\Services\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class MutasiController extends Controller
{
    public function changePosition(
        GantiJabatanRequest $request,
        Personel $personel,
        PositionService $positionService,
    ): JsonResponse {
        $data = $this->preparePositionData($request->validated());
        $data['unit_organisasi_id'] = $personel->unit_organisasi_id;
        $newPosition = $positionService->replacePrimary($personel, $data, $request->user(), false);

        return RiwayatJabatanResource::make(
            $newPosition->load(['unitOrganisasi', 'bidangFungsi', 'jenisPenugasan']),
        )->additional([
            'success' => true,
            'message' => 'Jabatan utama berhasil diganti.',
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function mutate(
        MutasiRequest $request,
        Personel $personel,
        PositionService $positionService,
    ): JsonResponse {
        $newPosition = $positionService->replacePrimary(
            $personel,
            $this->preparePositionData($request->validated()),
            $request->user(),
            true,
        );

        return RiwayatJabatanResource::make(
            $newPosition->load(['unitOrganisasi', 'bidangFungsi', 'jenisPenugasan']),
        )->additional([
            'success' => true,
            'message' => 'Mutasi personel berhasil diproses.',
        ])->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /** @param array<string, mixed> $data */
    private function preparePositionData(array $data): array
    {
        /** @var UploadedFile|null $file */
        $file = Arr::pull($data, 'dokumen_sk');

        if (! $file) {
            return $data;
        }

        return [
            ...$data,
            'dokumen_sk_path' => $file->store('dokumen-sk-jabatan', 'local'),
            'dokumen_sk_nama_asli' => $file->getClientOriginalName(),
            'dokumen_sk_mime' => $file->getMimeType(),
            'dokumen_sk_ukuran' => $file->getSize(),
        ];
    }
}
