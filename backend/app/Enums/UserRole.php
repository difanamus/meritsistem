<?php

namespace App\Enums;

enum UserRole: string
{
    case SystemAdmin = 'system_admin';
    case AdminSsdm = 'admin_ssdm';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Admin',
            self::AdminSsdm => 'Admin SSDM',
            self::Operator => 'Operator',
        };
    }
}
