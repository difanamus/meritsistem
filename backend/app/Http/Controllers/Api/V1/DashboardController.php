<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\OrganizationalScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, OrganizationalScopeService $scope): JsonResponse
    {
        $user = $request->user();
        $personel = $scope->scopePersonelQuery(Personel::query(), $user);
        $personelIds = (clone $personel)->select('id');
        $statuses = (clone $personel)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $accounts = $user->role === UserRole::Operator ? null : User::query()
            ->when($user->role === UserRole::AdminSsdm, fn ($query) => $query->where('role', UserRole::Operator));

        return response()->json(['success' => true, 'message' => 'Ringkasan dashboard berhasil diambil.', 'data' => [
            'scope' => ['global' => $scope->isGlobal($user), 'unit_count' => $scope
                ->scopeUnitQuery(UnitOrganisasi::query(), $user)->count()],
            'personnel' => ['total' => (int) $statuses->sum(), 'aktif' => (int) ($statuses['aktif'] ?? 0),
                'pensiun' => (int) ($statuses['pensiun'] ?? 0), 'nonaktif' => (int) ($statuses['nonaktif'] ?? 0)],
            'qualifications' => KualifikasiPersonel::query()->whereIn('personel_id', clone $personelIds)->count(),
            'active_positions' => RiwayatJabatan::query()->whereIn('personel_id', clone $personelIds)
                ->whereDate('tanggal_mulai', '<=', today())->whereNull('tanggal_selesai')->count(),
            'accounts' => $accounts === null ? null : ['total' => (clone $accounts)->count(),
                'active' => (clone $accounts)->where('is_active', true)->count(),
                'label' => $user->role === UserRole::SystemAdmin ? 'Seluruh akun' : 'Akun Operator'],
            'recent_personnel' => (clone $personel)->with('unitOrganisasi')->orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get()
                ->map(fn (Personel $item): array => ['id' => $item->id, 'nama_lengkap' => $item->nama_lengkap,
                    'unit' => $item->unitOrganisasi->nama, 'status' => $item->status->value,
                    'updated_at' => $item->updated_at->toISOString()]),
        ]]);
    }
}
