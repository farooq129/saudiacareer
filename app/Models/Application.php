<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'job_id', 'user_id', 'source', 'cv_path', 'cover_letter',
    'cover_letter_ai_generated', 'status', 'ip_address',
])]
class Application extends Model
{
    use HasFactory;

    public const STATUS_SENT = 'sent';

    public const STATUS_VIEWED = 'viewed';

    public const STATUS_SHORTLISTED = 'shortlisted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_HIRED = 'hired';

    protected function casts(): array
    {
        return [
            'cover_letter_ai_generated' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Applications that arrived through the site, carrying a CV. A WhatsApp tap
     * is recorded too, but it is a much weaker signal and is counted apart.
     */
    #[Scope]
    protected function onSite(Builder $query): void
    {
        $query->where('source', 'site');
    }
}
