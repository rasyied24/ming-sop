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

    public function homeRoute(): string
    {
        return match ($this) {
            self::Employee => 'sops.index',
            self::PicSop => 'sops.manage',
            self::Manager => 'dashboard',
            self::Auditor => 'compliance',
        };
    }

    public function navigation(): array
    {
        $sops = ['label' => 'Daftar SOP', 'route' => 'sops.index'];

        return match ($this) {
            self::Employee => [$sops],
            self::PicSop => [$sops, ['label' => 'Kelola SOP', 'route' => 'sops.manage']],
            self::Manager => [['label' => 'Dashboard', 'route' => 'dashboard'], $sops],
            self::Auditor => [['label' => 'Kepatuhan', 'route' => 'compliance'], $sops],
        };
    }
}