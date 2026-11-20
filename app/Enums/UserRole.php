<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case PicSop = 'pic_sop';
    case Manager = 'manager';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Karyawan',
            self::PicSop => 'PIC SOP',
            self::Manager => 'Manager',
            self::Auditor => 'Auditor / HRD',
        };
    }
}