<?php

namespace App\Services;

use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PositionService
{
    public function hasPrimaryOverlap(
        int $personelId,
        string $startDate,
        ?string $endDate,
        ?int $ignoreId = null,
    ): bool {
        return RiwayatJabatan::query()
            ->where('personel_id', $personelId)
            ->where('is_jabatan_utama', true)
            ->when($ignoreId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->where(function ($query) use ($endDate): void {
                if ($endDate !== null) {
                    $query->whereDate('tanggal_mulai', '<=', $endDate);
                }
            })
            ->where(function ($query) use ($startDate): void {
                $query->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', $startDate);
            })
            ->exists();
    }

    /**
     * Close the current primary position and create its replacement atomically.
     *
     * @param  array<string, mixed>  $newPositionData
     */
    public function replacePrimary(
        Personel $personel,
        array $newPositionData,
        User $actor,
        bool $isMutation,
    ): RiwayatJabatan {
        return DB::transaction(function () use ($personel, $newPositionData, $actor, $isMutation): RiwayatJabatan {
            $lockedPersonnel = Personel::query()->lockForUpdate()->findOrFail($personel->id);
            $currentPosition = RiwayatJabatan::query()
                ->where('personel_id', $lockedPersonnel->id)
                ->where('is_jabatan_utama', true)
                ->whereNull('tanggal_selesai')
                ->lockForUpdate()
                ->first();

            if (! $currentPosition) {
                throw new RuntimeException('Personel tidak memiliki jabatan utama aktif.');
            }

            $newStartDate = CarbonImmutable::parse($newPositionData['tanggal_mulai']);

            if ($newStartDate->lessThanOrEqualTo($currentPosition->tanggal_mulai)) {
                throw new RuntimeException('Tanggal jabatan baru harus setelah tanggal mulai jabatan aktif.');
            }

            $currentPosition->update([
                'tanggal_selesai' => $newStartDate->subDay()->toDateString(),
                'updated_by' => $actor->id,
            ]);

            if ($isMutation) {
                $lockedPersonnel->update([
                    'unit_organisasi_id' => $newPositionData['unit_organisasi_id'],
                    'updated_by' => $actor->id,
                ]);
            }

            return $lockedPersonnel->riwayatJabatan()->create([
                ...$newPositionData,
                'is_jabatan_utama' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }
}
