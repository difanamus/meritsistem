<?php

namespace App\Enums;

use App\Models\BidangFungsi;
use App\Models\JenisKualifikasi;
use App\Models\JenisPenugasan;
use App\Models\Pangkat;
use App\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Model;

enum ReferenceType: string
{
    case UnitOrganisasi = 'unit-organisasi';
    case Pangkat = 'pangkat';
    case BidangFungsi = 'bidang-fungsi';
    case JenisKualifikasi = 'jenis-kualifikasi';
    case JenisPenugasan = 'jenis-penugasan';

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::UnitOrganisasi => UnitOrganisasi::class,
            self::Pangkat => Pangkat::class,
            self::BidangFungsi => BidangFungsi::class,
            self::JenisKualifikasi => JenisKualifikasi::class,
            self::JenisPenugasan => JenisPenugasan::class,
        };
    }

    public function table(): string
    {
        $class = $this->modelClass();

        return (new $class)->getTable();
    }

    public function codeLength(): int
    {
        return match ($this) {
            self::UnitOrganisasi, self::JenisKualifikasi => 50,
            self::JenisPenugasan => 20,
            default => 30,
        };
    }
}
