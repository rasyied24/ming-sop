<?php

namespace App\Policies;

use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Models\Sop;
use App\Models\User;

class SopPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * SOP yang sudah terbit boleh dibaca semua role.
     * Draf dan SOP dalam review hanya untuk PIC SOP dan Manager.
     */
    public function view(User $user, Sop $sop): bool
    {
        if ($sop->status === SopStatus::Published) {
            return true;
        }

        return $user->hasRole(UserRole::PicSop, UserRole::Manager);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::PicSop);
    }

    /**
     * Draf hanya bisa diubah saat berstatus draft. Saat review, dikunci
     * supaya yang dibaca Manager sama dengan yang nanti disetujui.
     */
    public function update(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::PicSop) && $sop->status === SopStatus::Draft;
    }

    public function submitForReview(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::PicSop) && $sop->status === SopStatus::Draft;
    }

    public function approve(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::Manager) && $sop->status === SopStatus::Review;
    }

    /**
     * Syaratnya sama dengan approve. Gate menolak ability yang belum didefinisikan,
     * jadi reject harus ada sebagai method sendiri.
     */
    public function reject(User $user, Sop $sop): bool
    {
        return $this->approve($user, $sop);
    }

    /**
     * PIC SOP memulai revisi atas SOP yang sudah terbit.
     */
    public function revise(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::PicSop) && $sop->status === SopStatus::Published;
    }

    /**
     * Untuk mengizinkan PIC juga mengarsipkan, ubah menjadi:
     * $user->hasRole(UserRole::PicSop, UserRole::Manager) && ...
     */
    public function archive(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::Manager) && $sop->status === SopStatus::Published;
    }

    public function acknowledge(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::Employee) && $sop->status === SopStatus::Published;
    }

    /**
     * Riwayat perubahan (audit trail) hanya untuk PIC SOP, Manager, dan Auditor.
     */
    public function viewHistory(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::PicSop, UserRole::Manager, UserRole::Auditor);
    }
}