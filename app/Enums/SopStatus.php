<?php

namespace App\Enums;

enum SopStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Review => 'Menunggu Persetujuan',
            self::Published => 'Terbit',
            self::Archived => 'Diarsipkan',
        };
    }
}