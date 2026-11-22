<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SopReport;
use App\Models\User;

class SopReportPolicy
{
    /**
     * Daftar semua laporan masuk hanya untuk Auditor.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Auditor);
    }

    /**
     * Auditor membaca semua laporan. Karyawan hanya laporannya sendiri.
     */
    public function view(User $user, SopReport $report): bool
    {
        return $user->hasRole(UserRole::Auditor)
            || ($user->hasRole(UserRole::Employee) && $report->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Employee);
    }

    public function updateStatus(User $user, SopReport $report): bool
    {
        return $user->hasRole(UserRole::Auditor);
    }
}