<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SystemStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('view-system-status');
        try {
            DB::select('SELECT 1');
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Koneksi database tidak tersedia.'], 503);
        }

        return response()->json(['success' => true, 'message' => 'Status sistem berhasil diambil.', 'data' => [
            'database' => 'connected',
            'database_driver' => DB::connection()->getDriverName(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'checked_at' => now()->toISOString(),
        ]]);
    }
}
