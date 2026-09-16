<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'phone', 'email', 'role', 'password', 'locale', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EMPLOYER = 'employer';

    /**
     * A moderator works the review queues and nothing else — no accounts, no
     * verified badges, no creating other staff. They live in the admin area
     * alongside admins, which is why this is a `role` and not a permission.
     */
    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_SEEKER = 'seeker';

    /** Everyone who belongs in the admin area. */
    public const STAFF_ROLES = [self::ROLE_ADMIN, self::ROLE_MODERATOR];

    /** Every role an account can hold. */
    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MODERATOR,
        self::ROLE_EMPLOYER,
        self::ROLE_SEEKER,
    ];

    /**
     * Defaults that match the column defaults.
     *
     * Without these a freshly created User has `is_active` as null until it is
     * read back from the database — and null is falsy, so any check made in the
     * same request (EnsureRole, say) treats a brand new account as suspended.
     * Declaring them here means the model is right the moment it exists.
     */
    protected $attributes = [
        'role' => self::ROLE_SEEKER,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function seekerPosts(): HasMany
    {
        return $this->hasMany(JobSeekerPost::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function savedJobs(): HasMany
    {
        return $this->hasMany(SavedJob::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    /**
     * Works in the admin area, whether as an admin or a moderator.
     *
     * Use this for "can this account see the queues"; use isAdmin() for the
     * narrower "can this account change who else works here".
     */
    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true);
    }

    public function isEmployer(): bool
    {
        return $this->role === self::ROLE_EMPLOYER;
    }

    public function isSeeker(): bool
    {
        return $this->role === self::ROLE_SEEKER;
    }
}
