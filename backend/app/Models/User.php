<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'status',
        'organization_id',
        'invite_token',
        'invite_expires_at',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    /**
     * Branch context used across the app (orders, payments, stock, shifts…).
     *
     * Users have no `branch_id` column — membership lives on the `branch_user`
     * pivot. This resolves the branch the user operates in: the first attached
     * branch, in attachment order. Active-branch selection is a client-side
     * concern today; wiring an `X-Branch-Id` header is the future path for
     * multi-branch switching.
     */
    public function getBranchIdAttribute(): ?int
    {
        return $this->branches()->orderBy('branch_user.id')->first()?->id;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'invite_token',
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
            'password' => 'hashed',
            'invite_expires_at' => 'datetime',
        ];
    }

    /**
     * Issue a fresh staff-invite token valid for 48 hours.
     */
    public function issueInviteToken(): string
    {
        $token = Str::random(56);

        $this->forceFill([
            'invite_token'       => hash('sha256', $token),
            'invite_expires_at'  => now()->addHours(48),
        ])->save();

        return $token;
    }

    /**
     * Whether a raw invite token maps to this user and is unexpired.
     */
    public function matchesInviteToken(string $token): bool
    {
        return hash_equals((string) $this->invite_token, hash('sha256', $token))
            && $this->invite_expires_at
            && now()->lt($this->invite_expires_at);
    }
}
