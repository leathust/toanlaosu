<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'status',
        'timezone',
        'preferences',
        'locked_at',
        'last_login_at',
        'last_login_ip',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'status' => UserStatus::class,
            'preferences' => 'array',
            'locked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['assigned_by', 'assigned_at']);
    }

    /**
     * Get the courses this user can manage or view as a teacher.
     */
    public function teachingCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_teachers')
            ->withPivot(['scope', 'assigned_by', 'assigned_at', 'revoked_at']);
    }

    /**
     * Get the course enrollments for the user.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->activeAt(now());
    }

    public function lessonBlockProgress(): HasMany
    {
        return $this->hasMany(LessonBlockProgress::class);
    }

    public function videoProgress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserAttempt::class);
    }

    public function masteryRecords(): HasMany
    {
        return $this->hasMany(UserMastery::class);
    }

    /**
     * Determine whether the account can authenticate normally.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active && $this->locked_at === null;
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(string ...$roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }
}
