<?php

namespace App\Enums;

enum StatusPersonel: string
{
    case Aktif = 'aktif';
    case Pensiun = 'pensiun';
    case Nonaktif = 'nonaktif';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Pensiun => 'Pensiun',
            self::Nonaktif => 'Nonaktif',
        };
    }
}
