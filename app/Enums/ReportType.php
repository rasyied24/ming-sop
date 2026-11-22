<?php

namespace App\Enums;

enum ReportType: string
{
    case SopIssue = 'kendala_sop';
    case FieldMismatch = 'ketidaksesuaian';
    case Violation = 'dugaan_pelanggaran';

    public function label(): string
    {
        return match ($this) {
            self::SopIssue => 'Kendala SOP',
            self::FieldMismatch => 'Ketidaksesuaian di Lapangan',
            self::Violation => 'Dugaan Pelanggaran',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SopIssue => 'SOP tidak jelas, sulit, atau tidak bisa dijalankan.',
            self::FieldMismatch => 'Praktik di lapangan berbeda dari isi SOP.',
            self::Violation => 'Ada ketentuan SOP yang dilanggar, baik yang Anda saksikan maupun yang Anda lakukan sendiri.',
        };
    }
}