<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReferenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\SaveReferenceRequest;
use App\Http\Resources\ReferenceResource;
use App\Services\OrganizationalScopeService;
use App\Services\ReferenceAdministrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReferenceController extends Controller
{
    public function index(Request $request, string $type, OrganizationalScopeService $scope): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $referenceType = ReferenceType::from($type);
        $class = $referenceType->modelClass();
        $query = $class::query();
        if ($referenceType === ReferenceType::UnitOrganisasi) {
            $query->with('parent');
            $scope->scopeUnitQuery($query, $request->user());
        }
        $query->when($filters['search'] ?? null, function (Builder $query, string $search): void {
            $search = '%'.Str::lower($search).'%';
            $query->where(fn (Builder $query) => $query
                ->whereRaw('LOWER(nama) LIKE ?', [$search])
                ->orWhereRaw('LOWER(kode) LIKE ?', [$search]));
        });
        if (isset($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return ReferenceResource::collection($query->orderBy('nama')->orderBy('id')
            ->paginate($filters['per_page'] ?? 15)->withQueryString())
            ->additional(['success' => true, 'message' => 'Daftar referensi berhasil diambil.']);
    }

    public function show(Request $request, string $type, int $reference, OrganizationalScopeService $scope): JsonResponse
    {
        $referenceType = ReferenceType::from($type);
        $class = $referenceType->modelClass();
        $record = $class::query()->findOrFail($reference);
        if ($referenceType === ReferenceType::UnitOrganisasi) {
            abort_unless($scope->canAccessUnit($request->user(), $reference), 403);
        }

        return $this->respond($referenceType, $record, 'Detail referensi berhasil diambil.');
    }

    public function store(SaveReferenceRequest $request, string $type, ReferenceAdministrationService $service): JsonResponse
    {
        $referenceType = ReferenceType::from($type);
        $record = $service->save($referenceType, $request->validated());

        return $this->respond($referenceType, $record, 'Referensi berhasil dibuat.', 201);
    }

    public function update(SaveReferenceRequest $request, string $type, int $reference, ReferenceAdministrationService $service): JsonResponse
    {
        $referenceType = ReferenceType::from($type);
        $record = $service->save($referenceType, $request->validated(), $reference);

        return $this->respond($referenceType, $record, 'Referensi berhasil diperbarui.');
    }

    public function destroy(string $type, int $reference, ReferenceAdministrationService $service): JsonResponse
    {
        Gate::authorize('manage-references');
        $service->delete(ReferenceType::from($type), $reference);

        return response()->json(['success' => true, 'message' => 'Referensi berhasil dihapus.']);
    }

    private function respond(ReferenceType $type, Model $record, string $message, int $status = 200): JsonResponse
    {
        if ($type === ReferenceType::UnitOrganisasi) {
            $record->load('parent');
        }

        return ReferenceResource::make($record)->additional(['success' => true, 'message' => $message])
            ->response()->setStatusCode($status);
    }
}
