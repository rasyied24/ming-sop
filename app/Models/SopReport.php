<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopReport extends Model
{
    protected $fillable = [
        'sop_id',
        'user_id',
        'subject',
        'message',
        'status',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
        ];
    }

    public function sop(): BelongsTo
    {
        return $this->belongsTo(Sop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}