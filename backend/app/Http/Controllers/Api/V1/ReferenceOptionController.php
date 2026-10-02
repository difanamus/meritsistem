<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\JenisPenugasan;
use App\Models\Pangkat;
use App\Models\UnitOrganisasi;
use App\Services\OrganizationalScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReferenceOptionController extends Controller
{
    public function __invoke(Request $request, OrganizationalScopeService $scopeService): JsonResponse
    {
        $validated = $request->validate([
            'only' => ['sometimes', Rule::in(['bidang_fungsi', 'non_unit', 'unit_organisasi'])],
            'search' => ['required_if:only,unit_organisasi', 'string', 'min:3', 'max:100'],
        ]);

        if (($validated['only'] ?? null) === 'unit_organisasi') {
            $search = '%'.addcslashes($validated['search'], '%_\\').'%';
            $operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return response()->json([
                'success' => true,
                'message' => 'Opsi unit organisasi berhasil diambil.',
                'data' => ['unit_organisasi' => $scopeService->scopeUnitQuery(
                    UnitOrganisasi::query()->where('is_active', true),
                    $request->user(),
                )
                    ->where(function ($query) use ($operator, $search): void {
                        $query->where('nama', $operator, $search)
                            ->orWhere('kode', $operator, $search);
                    })
                    ->orderBy('nama')
                    ->limit(25)
                    ->get(['id', 'parent_id', 'kode', 'nama', 'jenis_unit'])],
            ]);
        }

        if (($validated['only'] ?? null) === 'bidang_fungsi') {
            return response()->json([
                'success' => true,
                'message' => 'Opsi referensi berhasil diambil.',
                'data' => ['bidang_fungsi' => BidangFungsi::query()
                    ->where('is_active', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama'])],
            ]);
        }

        $data = [
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
        ];

        if (($validated['only'] ?? null) !== 'non_unit') {
            $data['unit_organisasi'] = $scopeService->scopeUnitQuery(
                UnitOrganisasi::query()->where('is_active', true),
                $request->user(),
            )
                ->orderBy('nama')
                ->get(['id', 'parent_id', 'kode', 'nama', 'jenis_unit']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Opsi referensi berhasil diambil.',
            'data' => $data,
        ]);
    }
}
