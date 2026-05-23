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

        while ($this->points >= $this->xpRequiredForLevel($this->level + 1)) {
            $this->level++;
        }

        $this->save();
    }

    public function xpRequiredForLevel(int $level): int
    {
        return (int) (100 * pow($level, 1.5));
    }

    public function currentLevelXp(): int
    {
        return $this->xpRequiredForLevel($this->level);
    }

    public function nextLevelXp(): int
    {
        return $this->xpRequiredForLevel($this->level + 1);
    }

    public function progressToNextLevel(): int
    {
        $currentLevelXp = $this->currentLevelXp();
        $nextLevelXp = $this->nextLevelXp();

        $xpIntoLevel = $this->points - $currentLevelXp;
        $xpRequired = $nextLevelXp - $currentLevelXp;

        return (int) round(($xpIntoLevel / $xpRequired) * 100);
    }
}
