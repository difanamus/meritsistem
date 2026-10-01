<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\JenisPenugasan;
use App\Models\Pangkat;
use App\Models\UnitOrganisasi;
use App\Services\OrganizationalScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceOptionController extends Controller
{
    public function __invoke(Request $request, OrganizationalScopeService $scopeService): JsonResponse
    {
        $accessibleUnitIds = $scopeService->accessibleUnitIds($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Opsi referensi berhasil diambil.',
            'data' => [
                'unit_organisasi' => UnitOrganisasi::query()
                    ->where('is_active', true)
                    ->when($accessibleUnitIds !== null, fn (Builder $query) => $query->whereIn('id', $accessibleUnitIds))
                    ->orderBy('nama')
                    ->get(['id', 'parent_id', 'kode', 'nama', 'jenis_unit']),
                'pangkat' => Pangkat::query()
                    ->where('is_active', true)
                    ->orderBy('jenis_personel')
                    ->orderBy('urutan')
                    ->get(['id', 'kode', 'nama', 'jenis_personel', 'urutan']),
                'bidang_fungsi' => BidangFungsi::query()
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama']),
                'jenis_kualifikasi' => JenisKualifikasi::query()
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama']),
                'jenis_penugasan' => JenisPenugasan::query()
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama']),
            ],
        ]);
    }
}
