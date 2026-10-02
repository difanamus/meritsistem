<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplineSnapshotResource;
use App\Models\DisciplineSnapshot;
use App\Models\Personel;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DisciplineSnapshotController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Personel $personel): AnonymousResourceCollection
    {
        Gate::authorize('view-discipline-prototype');
        Gate::authorize('view', $personel);

        return DisciplineSnapshotResource::collection(
            DisciplineSnapshot::query()->where('personel_id', $personel->id)
                ->where('source_system', 'prototype_demo')
                ->latest('tanggal_keputusan')->latest('id')->paginate(15),
        )->additional([
            'success' => true,
            'message' => 'DEMO — belum terhubung ke sistem Propam.',
            'integration' => ['status' => 'prototype', 'connected' => false, 'source' => 'prototype_demo', 'last_synced_at' => null],
        ]);
    }
}
