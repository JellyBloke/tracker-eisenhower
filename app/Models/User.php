<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
        'level',
        'streak_days',
        'last_completed_on',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_completed_on' => 'date',
            'password' => 'hashed',
            'points' => 'integer',
            'level' => 'integer',
            'streak_days' => 'integer',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function focusSessions(): HasMany
    {
        return $this->hasMany(FocusSession::class);
    }

    public function productivityStats(): HasMany
    {
        return $this->hasMany(ProductivityStat::class);
    }

    public function awardPoints(int $points): void
    {
        $this->points += $points;
        $this->level = max(1, (int) floor($this->points / 100) + 1);
        $this->save();
    }

    public function progressToNextLevel(): int
    {
        return $this->points % 100;
    }
}
