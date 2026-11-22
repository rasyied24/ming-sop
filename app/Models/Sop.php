<?php

namespace App\Models;

use App\Enums\SopStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sop extends Model
{
    protected $fillable = [
        'title',
        'document_number',
        'category',
        'content',
        'file_path',
        'version',
        'status',
        'revision_note',
        'created_by',
        'approved_by',
    ];

    protected $attributes = [
        'version' => '1.0',
        'status' => 'draft',
    ];

    public const CATEGORIES = ['HRD', 'Keuangan', 'IT', 'Operasional'];

    public function nextVersion(bool $major = false): string
    {
        [$majorNumber, $minorNumber] = array_pad(array_map('intval', explode('.', $this->version)), 2, 0);

        return $major
            ? ($majorNumber + 1).'.0'
            : $majorNumber.'.'.($minorNumber + 1);
    }

    protected function casts(): array
    {
        return [
            'status' => SopStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SopLog::class);
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(SopAcknowledgment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(SopReport::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', SopStatus::Published->value);
    }
}