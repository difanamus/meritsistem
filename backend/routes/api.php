<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DisciplineSnapshotController;
use App\Http\Controllers\Api\V1\KualifikasiPersonelController;
use App\Http\Controllers\Api\V1\MeritRecordController;
use App\Http\Controllers\Api\V1\MutasiController;
use App\Http\Controllers\Api\V1\PersonelController;
use App\Http\Controllers\Api\V1\PersonnelIntegrationController;
use App\Http\Controllers\Api\V1\ReferenceController;
use App\Http\Controllers\Api\V1\ReferenceOptionController;
use App\Http\Controllers\Api\V1\RiwayatJabatanController;
use App\Http\Controllers\Api\V1\SystemStatusController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active.user', 'throttle:api'])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/system-status', SystemStatusController::class);
        Route::get('/personnel-integration', [PersonnelIntegrationController::class, 'index']);
        Route::post('/personnel-integration/preview', [PersonnelIntegrationController::class, 'preview']);
        Route::get('/personnel-integration/{run}', [PersonnelIntegrationController::class, 'show'])->whereNumber('run');
        Route::post('/personnel-integration/{run}/apply', [PersonnelIntegrationController::class, 'apply'])->whereNumber('run');
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/personel-options', [PersonelController::class, 'options']);
        Route::post('/personel/{id}/restore', [PersonelController::class, 'restore'])->whereNumber('id');
        Route::apiResource('personel', PersonelController::class);
        Route::apiResource('users', UserController::class);
        Route::get('/reference-options', ReferenceOptionController::class);
        Route::prefix('references/{type}')
            ->whereIn('type', ['unit-organisasi', 'pangkat', 'bidang-fungsi', 'jenis-kualifikasi', 'jenis-penugasan'])
            ->group(function (): void {
                Route::get('/', [ReferenceController::class, 'index']);
                Route::post('/', [ReferenceController::class, 'store']);
                Route::get('/{reference}', [ReferenceController::class, 'show'])->whereNumber('reference');
                Route::put('/{reference}', [ReferenceController::class, 'update'])->whereNumber('reference');
                Route::delete('/{reference}', [ReferenceController::class, 'destroy'])->whereNumber('reference');
            });
        Route::get('/personel/{personel}/kualifikasi', [KualifikasiPersonelController::class, 'index']);
        Route::get('/personel/{personel}/disiplin-prototype', DisciplineSnapshotController::class);
        Route::post('/personel/{personel}/kualifikasi', [KualifikasiPersonelController::class, 'store']);
        Route::put('/kualifikasi/{kualifikasi}', [KualifikasiPersonelController::class, 'update']);
        Route::get('/kualifikasi/{kualifikasi}', [KualifikasiPersonelController::class, 'show']);
        Route::delete('/kualifikasi/{kualifikasi}', [KualifikasiPersonelController::class, 'destroy']);
        Route::get('/kualifikasi/{kualifikasi}/dokumen', [KualifikasiPersonelController::class, 'download'])
            ->name('api.v1.kualifikasi.download');
        Route::get('/personel/{personel}/riwayat-jabatan', [RiwayatJabatanController::class, 'index']);
        Route::post('/personel/{personel}/riwayat-jabatan', [RiwayatJabatanController::class, 'store']);
        Route::put('/riwayat-jabatan/{riwayatJabatan}', [RiwayatJabatanController::class, 'update']);
        Route::get('/riwayat-jabatan/{riwayatJabatan}', [RiwayatJabatanController::class, 'show']);
        Route::delete('/riwayat-jabatan/{riwayatJabatan}', [RiwayatJabatanController::class, 'destroy']);
        Route::get('/riwayat-jabatan/{riwayatJabatan}/dokumen', [RiwayatJabatanController::class, 'download'])
            ->name('api.v1.riwayat-jabatan.download');
        Route::get('/personel/{personel}/merit/{type}', [MeritRecordController::class, 'index'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan']);
        Route::post('/personel/{personel}/merit/{type}', [MeritRecordController::class, 'store'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan']);
        Route::get('/merit/{type}/{record}', [MeritRecordController::class, 'show'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan'])->whereNumber('record');
        Route::put('/merit/{type}/{record}', [MeritRecordController::class, 'update'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan'])->whereNumber('record');
        Route::delete('/merit/{type}/{record}', [MeritRecordController::class, 'destroy'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan'])->whereNumber('record');
        Route::post('/merit/{type}/{record}/verifikasi', [MeritRecordController::class, 'verify'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan'])->whereNumber('record');
        Route::get('/merit/{type}/{record}/dokumen', [MeritRecordController::class, 'download'])->whereIn('type', ['penugasan-operasi', 'prestasi', 'penghargaan'])->whereNumber('record')->name('api.v1.merit.download');
        Route::post('/personel/{personel}/ganti-jabatan', [MutasiController::class, 'changePosition']);
        Route::post('/personel/{personel}/mutasi', [MutasiController::class, 'mutate']);
    });
});
