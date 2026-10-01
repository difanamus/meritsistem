<?php

namespace App\Enums;

enum JenisUnit: string
{
    case Root = 'root';
    case Mabes = 'mabes';
    case SatkerMabes = 'satker_mabes';
    case Polda = 'polda';
    case SatkerPolda = 'satker_polda';
    case Polres = 'polres';
    case SatkerPolres = 'satker_polres';
    case Polsek = 'polsek';

    public function label(): string
    {
        return match ($this) {
            self::Root => 'POLRI',
            self::Mabes => 'Mabes Polri',
            self::SatkerMabes => 'Satker Mabes Polri',
            self::Polda => 'Polda',
            self::SatkerPolda => 'Satker Polda',
            self::Polres => 'Polres',
            self::SatkerPolres => 'Satker Polres',
            self::Polsek => 'Polsek',
        };
    }
}
