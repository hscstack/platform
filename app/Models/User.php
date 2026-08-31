<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    use HasRoles;

    protected $fillable = [
        'name',
        'username',
        'email',
        'receive_emails',
        'google_id',
        'email_verified_at',
        'banned_until',
        'image_path',
        'about',
        'title',
        'institution',
        'facebook',
        'instagram',
        'github',
    ];

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if (! $user->username) {
                $user->updateQuietly(['username' => "student_{$user->id}"]);
                $user->username = "student_{$user->id}";
            }
        });
    }

    protected $appends = [
        'image_url',
        'is_verified',
        'is_banned',
    ];

    public function getIsVerifiedAttribute(): bool
    {
        return $this->relationLoaded('roles')
            ? $this->roles->isNotEmpty()
            : $this->roles()->exists();
    }

    public function isBanned(): bool
    {
        return $this->banned_until !== null && $this->banned_until->isFuture();
    }

    public function getIsBannedAttribute(): bool
    {
        return $this->isBanned();
    }

    public static function getSystemBot(): ?self
    {
        $botUsername = AppSetting::get('global_chat_bot_username');

        return $botUsername ? static::where('username', $botUsername)->first() : null;
    }

    public function getImageUrlAttribute()
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::url($this->image_path);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_until' => 'datetime',
            'password' => 'hashed',
            'receive_emails' => 'boolean',
        ];
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasmany(Blog::class);
    }

    public function resourceCompletions(): HasMany
    {
        return $this->hasMany(ResourceCompletion::class);
    }

    public function completedResources()
    {
        return $this->belongsToMany(Resource::class, 'resource_completions');
    }

    public function nodeVotes(): HasMany
    {
        return $this->hasMany(NodeVote::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class);
    }

    public function appreciationsReceived(): HasMany
    {
        return $this->hasMany(UserAppreciation::class, 'user_id');
    }

    public function appreciationsGiven(): HasMany
    {
        return $this->hasMany(UserAppreciation::class, 'appreciator_id');
    }

    public function appreciators()
    {
        return $this->belongsToMany(User::class, 'user_appreciations', 'user_id', 'appreciator_id');
    }

    public function appreciatingUsers()
    {
        return $this->belongsToMany(User::class, 'user_appreciations', 'appreciator_id', 'user_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
