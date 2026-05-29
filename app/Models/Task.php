<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    public const QUADRANT_DO = 'do';
    public const QUADRANT_SCHEDULE = 'schedule';
    public const QUADRANT_DELEGATE = 'delegate';
    public const QUADRANT_ELIMINATE = 'eliminate';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const URGENT_WINDOW_HOURS = 48;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_urgent',
        'is_important',
        'quadrant',
        'status',
        'due_at',
        'started_at',
        'completed_at',
        'estimated_minutes',
        'actual_minutes',
        'focus_minutes',
        'points_awarded',
        'priority_order',
    ];

    protected function casts(): array
    {
        return [
            'is_urgent' => 'boolean',
            'is_important' => 'boolean',
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_minutes' => 'integer',
            'actual_minutes' => 'integer',
            'focus_minutes' => 'integer',
            'points_awarded' => 'integer',
            'priority_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function focusSessions(): HasMany
    {
        return $this->hasMany(FocusSession::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public static function quadrantFor(bool $urgent, bool $important): string
    {
        return match (true) {
            $urgent && $important => self::QUADRANT_DO,
            ! $urgent && $important => self::QUADRANT_SCHEDULE,
            $urgent && ! $important => self::QUADRANT_DELEGATE,
            default => self::QUADRANT_ELIMINATE,
        };
    }

    public static function computeUrgent(
        DateTimeInterface|string|null $dueAt,
        ?int $estimatedMinutes = null,
        ?Carbon $now = null,
    ): bool {
        if ($dueAt === null) {
            return false;
        }

        $due = $dueAt instanceof DateTimeInterface ? Carbon::instance($dueAt) : Carbon::parse($dueAt);
        $now ??= Carbon::now();

        if ($due->lessThanOrEqualTo($now)) {
            return true;
        }

        $minutesUntilDue = $now->diffInMinutes($due, false);
        if ($minutesUntilDue <= self::URGENT_WINDOW_HOURS * 60) {
            return true;
        }

        if ($estimatedMinutes !== null && $estimatedMinutes > 0
            && $minutesUntilDue <= $estimatedMinutes * 2) {
            return true;
        }

        return false;
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && $this->status !== self::STATUS_COMPLETED;
    }

    public function completedOnTime(): bool
    {
        if ($this->status !== self::STATUS_COMPLETED || $this->completed_at === null) {
            return false;
        }
        if ($this->due_at === null) {
            return true;
        }

        return $this->completed_at->lessThanOrEqualTo($this->due_at);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)
            ->timezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s');
    }

}
