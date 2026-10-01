<?php

namespace App\Enums;

enum JenisPersonel: string
{
    case Polri = 'polri';
    case Pns = 'pns';

    public function label(): string
    {
        return match ($this) {
            self::Polri => 'POLRI',
            self::Pns => 'PNS',
        };
    }
}
