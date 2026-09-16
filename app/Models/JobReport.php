<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'user_id', 'reason', 'note', 'ip_address'])]
class JobReport extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_UPHELD = 'upheld';

    public const STATUS_DISMISSED = 'dismissed';

    /** The reasons offered on the report dialog, in the order they appear. */
    public const REASONS = [
        'fake', 'fees', 'duplicate', 'filled', 'offensive', 'wrong_category', 'other',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_REVIEWING]);
    }
}
