<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductivityStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day',
        'tasks_completed',
        'tasks_on_time',
        'tasks_overdue',
        'focus_minutes',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'date',
            'tasks_completed' => 'integer',
            'tasks_on_time' => 'integer',
            'tasks_overdue' => 'integer',
            'focus_minutes' => 'integer',
            'points_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
