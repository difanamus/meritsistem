<?php

namespace App\Services;

use App\Enums\JenisUnit;
use App\Enums\ReferenceType;
use App\Models\KualifikasiPersonel;
use App\Models\Personel;
use App\Models\RiwayatJabatan;
use App\Models\UnitOrganisasi;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReferenceAdministrationService
{
    /** @param array<string, mixed> $data */
    public function save(ReferenceType $type, array $data, ?int $id = null): Model
    {
        return DB::transaction(function () use ($type, $data, $id): Model {
            $class = $type->modelClass();
            // Serialize organization edits before validating the whole parent chain.
            if ($type === ReferenceType::UnitOrganisasi) {
                UnitOrganisasi::query()->orderBy('id')->lockForUpdate()->get();
            }
            $record = $id === null ? new $class : $class::query()->lockForUpdate()->findOrFail($id);
            if ($type === ReferenceType::UnitOrganisasi) {
                $this->validateHierarchy($data, $id);
            }
            if ($type === ReferenceType::Pangkat && $record->exists
                && $record->jenis_personel->value !== $data['jenis_personel']
                && Personel::withTrashed()->where('pangkat_id', $record->id)->exists()) {
                throw ValidationException::withMessages([
                    'jenis_personel' => 'Jenis personel tidak dapat diubah karena pangkat sudah dipakai.',
                ]);
            }
            $record->fill($data)->save();

            return $record;
        });
    }

    public function delete(ReferenceType $type, int $id): void
    {
        DB::transaction(function () use ($type, $id): void {
            $class = $type->modelClass();
            $record = $class::query()->lockForUpdate()->findOrFail($id);
            if ($this->isUsed($type, $id)) {
                throw new ConflictHttpException('Referensi masih digunakan. Nonaktifkan agar tidak dipilih untuk data baru.');
            }
            $record->delete();
        });
    }

    /** @param array<string, mixed> $data */
    private function validateHierarchy(array $data, ?int $id): void
    {
        $parentId = $data['parent_id'] ?? null;
        if ($data['jenis_unit'] === JenisUnit::Root->value && $parentId !== null) {
            throw ValidationException::withMessages(['parent_id' => 'Unit POLRI tidak boleh memiliki induk.']);
        }
        if ($data['jenis_unit'] !== JenisUnit::Root->value && $parentId === null) {
            throw ValidationException::withMessages(['parent_id' => 'Unit selain POLRI wajib memiliki induk.']);
        }
        $visited = [];
        while ($parentId !== null) {
            if ($parentId === $id || in_array($parentId, $visited, true)) {
                throw ValidationException::withMessages(['parent_id' => 'Induk unit tidak boleh membentuk siklus hierarki.']);
            }
            $visited[] = $parentId;
            $parent = UnitOrganisasi::query()->find($parentId);
            if ($parent === null || ($data['is_active'] && ! $parent->is_active)) {
                throw ValidationException::withMessages(['parent_id' => 'Induk unit harus tersedia dan aktif untuk unit aktif.']);
            }
            $parentId = $parent->parent_id;
        }
        if (! $data['is_active'] && $id !== null
            && UnitOrganisasi::query()->where('parent_id', $id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Nonaktifkan unit bawahan terlebih dahulu.']);
        }
    }

    private function isUsed(ReferenceType $type, int $id): bool
    {
        return match ($type) {
            ReferenceType::UnitOrganisasi => UnitOrganisasi::withTrashed()->where('parent_id', $id)->exists()
                || Personel::withTrashed()->where('unit_organisasi_id', $id)->exists()
                || RiwayatJabatan::withTrashed()->where('unit_organisasi_id', $id)->exists()
                || UserScope::query()->where('unit_organisasi_id', $id)->exists(),
            ReferenceType::Pangkat => Personel::withTrashed()->where('pangkat_id', $id)->exists(),
            ReferenceType::BidangFungsi => KualifikasiPersonel::withTrashed()->where('bidang_fungsi_id', $id)->exists()
                || RiwayatJabatan::withTrashed()->where('bidang_fungsi_id', $id)->exists(),
            ReferenceType::JenisKualifikasi => KualifikasiPersonel::withTrashed()->where('jenis_kualifikasi_id', $id)->exists(),
            ReferenceType::JenisPenugasan => RiwayatJabatan::withTrashed()->where('jenis_penugasan_id', $id)->exists(),
        };
    }
}
