<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::InProgress => 'Diproses',
            self::Resolved => 'Selesai',
        };
    }

    /**
     * Status tujuan yang boleh dipilih dari status ini.
     *
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Resolved],
            self::InProgress => [self::Resolved],
            self::Resolved => [self::InProgress],
        };
    }
}