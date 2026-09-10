<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        'password',
        'role',
        'is_approved',
        'state',
        'email_verified_at',
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
            'password' => 'hashed',
            'is_approved' => 'boolean',
        ];
    }

    public function isState(): bool
    {
        return $this->role === 'state_official';
    }

    public function isBB(): bool
    {
        return $this->role === 'board_official';
    }

    public function isMoJS(): bool
    {
        return $this->role === 'mojs_official' || $this->role === 'viewer';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // ── New Module Relations ──────────────────────────────────────────────────
    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class);
    }

    public function progressReports()
    {
        return $this->hasMany(ProgressReport::class);
    }

    public function bbMonitoringReports()
    {
        return $this->hasMany(BbMonitoringReport::class, 'bb_user_id');
    }
}