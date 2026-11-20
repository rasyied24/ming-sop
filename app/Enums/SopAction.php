<?php

namespace App\Enums;

enum SopAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case SubmittedForReview = 'submitted_for_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Dibuat',
            self::Updated => 'Diperbarui',
            self::SubmittedForReview => 'Diajukan untuk Review',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Archived => 'Diarsipkan',
        };
    }
}