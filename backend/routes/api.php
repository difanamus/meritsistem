<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\KualifikasiPersonelController;
use App\Http\Controllers\Api\V1\MutasiController;
use App\Http\Controllers\Api\V1\PersonelController;
use App\Http\Controllers\Api\V1\ReferenceOptionController;
use App\Http\Controllers\Api\V1\RiwayatJabatanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active.user', 'throttle:api'])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::apiResource('personel', PersonelController::class);
        Route::get('/reference-options', ReferenceOptionController::class);
        Route::get('/personel/{personel}/kualifikasi', [KualifikasiPersonelController::class, 'index']);
        Route::post('/personel/{personel}/kualifikasi', [KualifikasiPersonelController::class, 'store']);
        Route::put('/kualifikasi/{kualifikasi}', [KualifikasiPersonelController::class, 'update']);
        Route::delete('/kualifikasi/{kualifikasi}', [KualifikasiPersonelController::class, 'destroy']);
        Route::get('/kualifikasi/{kualifikasi}/dokumen', [KualifikasiPersonelController::class, 'download'])
            ->name('api.v1.kualifikasi.download');
        Route::get('/personel/{personel}/riwayat-jabatan', [RiwayatJabatanController::class, 'index']);
        Route::post('/personel/{personel}/riwayat-jabatan', [RiwayatJabatanController::class, 'store']);
        Route::put('/riwayat-jabatan/{riwayatJabatan}', [RiwayatJabatanController::class, 'update']);
        Route::delete('/riwayat-jabatan/{riwayatJabatan}', [RiwayatJabatanController::class, 'destroy']);
        Route::get('/riwayat-jabatan/{riwayatJabatan}/dokumen', [RiwayatJabatanController::class, 'download'])
            ->name('api.v1.riwayat-jabatan.download');
        Route::post('/personel/{personel}/ganti-jabatan', [MutasiController::class, 'changePosition']);
        Route::post('/personel/{personel}/mutasi', [MutasiController::class, 'mutate']);
    });
});
