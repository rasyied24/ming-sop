<?php

namespace App\Models;

use App\Enums\SopAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopLog extends Model
{
    protected $fillable = [
        'sop_id',
        'user_id',
        'action',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action' => SopAction::class,
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