<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PersonnelImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PersonnelIntegrationController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manage-personnel-integration');

        return response()->json(['data' => ['prototype' => true, 'connected' => false,
            'enabled' => app()->environment(['local', 'testing']), 'source' => PersonnelImportService::SOURCE,
            'checkpoint' => (int) DB::table('personnel_sync_checkpoints')->where('source', PersonnelImportService::SOURCE)->value('version'),
            'runs' => DB::table('personnel_import_runs')->orderByDesc('id')->limit(10)->get()]]);
    }

    public function preview(Request $request, PersonnelImportService $service): JsonResponse
    {
        $this->authorizeWrite();
        $data = $request->validate(['mode' => ['required', Rule::in(['initial', 'delta'])], 'version' => ['required', 'integer', Rule::in([1, 2])]]);
        $id = $service->preview($request->user(), $data['mode'], $data['version']);

        return response()->json(['data' => $service->report($id)], 201);
    }

    public function show(int $run, Request $request, PersonnelImportService $service): JsonResponse
    {
        Gate::authorize('manage-personnel-integration');
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:10000']]);

        return response()->json(['data' => $service->report($run, $data['page'] ?? 1)]);
    }

    public function apply(int $run, Request $request, PersonnelImportService $service): JsonResponse
    {
        $this->authorizeWrite();
        $data = $request->validate(['confirmed' => ['required', 'accepted'], 'batch_size' => ['sometimes', 'integer', 'min:1', 'max:25']]);
        $service->apply($run, $request->user(), $data['batch_size'] ?? 25);

        return response()->json(['data' => $service->report($run)]);
    }

    private function authorizeWrite(): void
    {
        Gate::authorize('manage-personnel-integration');
        abort_unless(app()->environment(['local', 'testing']), 503, 'Sumber simulasi hanya diaktifkan pada lingkungan local/testing.');
    }
}
