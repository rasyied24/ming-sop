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
            self::Draft => 'Draft',
            self::Review => 'Review',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function tabLabel(): string
    {
        return $this === self::Review ? 'Review / Pending' : $this->label();
    }
}