<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopAcknowledgment extends Model
{
    protected $fillable = [
        'sop_id',
        'user_id',
        'sop_version',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
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