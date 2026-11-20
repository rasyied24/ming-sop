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

    public function update(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::PicSop) && $sop->status === SopStatus::Draft;
    }

    public function submitForReview(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::PicSop) && $sop->status === SopStatus::Draft;
    }

    /**
     * Berlaku juga untuk aksi reject, karena syaratnya sama.
     */
    public function approve(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::Manager) && $sop->status === SopStatus::Review;
    }

    public function acknowledge(User $user, Sop $sop): bool
    {
        return $user->hasRole(UserRole::Employee) && $sop->status === SopStatus::Published;
    }
}