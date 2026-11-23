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
        /**
     * Menu sidebar per role. 'active' berisi nama route yang membuat menu tampil aktif.
     * Halaman detail SOP (sops.show) dan detail laporan (reports.show) ditangani layout
     * karena menu aktifnya bergantung pada asal klik atau role.
     *
     * @return array<int, array{label: string, route: string, icon: string, active: array<int, string>}>
     */
    public function navigation(): array
    {
        $list = [
            'label' => 'Daftar SOP',
            'route' => 'sops.index',
            'icon' => 'document-text',
            'active' => ['sops.index'],
        ];

        $directory = [
            'label' => 'Direktori SOP',
            'route' => 'sops.manage',
            'icon' => 'folder',
            'active' => ['sops.manage', 'sops.create', 'sops.edit'],
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
                ['label' => 'Kepatuhan', 'route' => 'compliance', 'icon' => 'shield-check', 'active' => ['compliance', 'compliance.show']],
                ['label' => 'Laporan Masuk', 'route' => 'reports.inbox', 'icon' => 'inbox', 'active' => ['reports.inbox']],
                $list,
            ],
        };
    }
}