<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'phone_verified_at',
        'is_active',
        'is_password_set',
        'role',
        'client_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_password_set' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function tier(): string
    {
        $count = $this->responses()->count();
        if ($count >= 30) return 'platinum';
        if ($count >= 15) return 'gold';
        if ($count >= 5)  return 'silver';
        return 'bronze';
    }

    public function tierMultiplier(): float
    {
        return match($this->tier()) {
            'platinum' => 1.15,
            'gold'     => 1.10,
            'silver'   => 1.05,
            default    => 1.0,
        };
    }

    public function isResearcher()
    {
        return $this->role === 'researcher' || $this->isAdmin();
    }

    public function isMember()
    {
        return $this->role === 'member';
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }

    public function responses()
    {
        return $this->hasMany(Response::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function prizeDrawEntries()
    {
        return $this->hasMany(PrizeDrawEntry::class);
    }

    public function completedSurveys()
    {
        return $this->belongsToMany(Survey::class, 'responses')->withTimestamps();
    }

    public function surveyNotificationLogs()
    {
        return $this->hasMany(SurveyNotificationLog::class);
    }
}
