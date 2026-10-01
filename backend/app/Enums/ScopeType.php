<?php

namespace App\Enums;

enum ScopeType: string
{
    case OwnUnit = 'own_unit';
    case UnitAndDescendants = 'unit_and_descendants';

    public function label(): string
    {
        return match ($this) {
            self::OwnUnit => 'Unit Sendiri',
            self::UnitAndDescendants => 'Unit dan Seluruh Bawahannya',
        };
    }
}
