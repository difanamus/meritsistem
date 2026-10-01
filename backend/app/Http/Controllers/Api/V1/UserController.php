<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $actor = $request->user();
        $query = User::query()->with('scopes.unitOrganisasi');

        if ($actor->role === UserRole::AdminSsdm) {
            $query->where('role', UserRole::Operator);
        }

        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $search = '%'.Str::lower($search).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereRaw('LOWER(name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('role', $role))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false));

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';

        return UserResource::collection(
            $query->orderBy($sort, $direction)->orderBy('id')->paginate($filters['per_page'] ?? 15)->withQueryString(),
        )->additional([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil.',
        ]);
    }

    public function store(StoreUserRequest $request, UserAdministrationService $service): JsonResponse
    {
        $user = $service->create($request->validated());

        return UserResource::make($user->load('scopes.unitOrganisasi'))
            ->additional(['success' => true, 'message' => 'Pengguna berhasil dibuat.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return UserResource::make($user->load('scopes.unitOrganisasi'))
            ->additional(['success' => true, 'message' => 'Detail pengguna berhasil diambil.'])
            ->response();
    }

    public function update(UpdateUserRequest $request, User $user, UserAdministrationService $service): JsonResponse
    {
        $updatedUser = $service->update($user, $request->validated());

        return UserResource::make($updatedUser->load('scopes.unitOrganisasi'))
            ->additional(['success' => true, 'message' => 'Pengguna berhasil diperbarui.'])
            ->response();
    }

    public function destroy(User $user, UserAdministrationService $service): JsonResponse
    {
        Gate::authorize('delete', $user);
        $service->deactivate($user);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna dinonaktifkan dan seluruh sesi login dicabut.',
        ]);
    }
}
