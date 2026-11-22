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

        /**
     * Menu sidebar per role. 'active' berisi nama route yang membuat menu tampil aktif.
     *
     * @return array<int, array{label: string, route: string, icon: string, active: array<int, string>}>
     */
    public function navigation(): array
    {
        // PIC SOP dan Manager bekerja dari Direktori, jadi detail SOP dihitung bagian dari Direktori.
        $usesDirectory = in_array($this, [self::PicSop, self::Manager], true);

        $list = [
            'label' => 'Daftar SOP',
            'route' => 'sops.index',
            'icon' => 'document-text',
            'active' => $usesDirectory ? ['sops.index'] : ['sops.index', 'sops.show'],
        ];

        $directory = [
            'label' => 'Direktori SOP',
            'route' => 'sops.manage',
            'icon' => 'folder',
            'active' => ['sops.manage', 'sops.create', 'sops.edit', 'sops.show'],
        ];

        return match ($this) {
            self::Employee => [
                $list,
                ['label' => 'Laporan Saya', 'route' => 'reports.index', 'icon' => 'exclamation-triangle', 'active' => ['reports.index', 'reports.create']],
            ],
            self::PicSop => [$directory],
            self::Manager => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'active' => ['dashboard']],
                ['label' => 'Pengajuan & Persetujuan', 'route' => 'approvals.index', 'icon' => 'check-circle', 'active' => ['approvals.index']],
                $directory,
            ],
            self::Auditor => [
                ['label' => 'Kepatuhan', 'route' => 'compliance', 'icon' => 'shield-check', 'active' => ['compliance']],
                ['label' => 'Laporan Masuk', 'route' => 'reports.inbox', 'icon' => 'inbox', 'active' => ['reports.inbox']],
                $list,
            ],
        };
    }
}