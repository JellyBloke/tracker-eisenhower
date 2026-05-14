<?php

namespace App\Services;

use App\Models\ProductivityStat;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

class ProductivityService
{
    public const BASE_POINTS = [
        Task::QUADRANT_DO => 25,
        Task::QUADRANT_SCHEDULE => 20,
        Task::QUADRANT_DELEGATE => 10,
        Task::QUADRANT_ELIMINATE => 5,
    ];

    public const ON_TIME_BONUS = 15;
    public const EARLY_BONUS = 10;
    public const OVERDUE_PENALTY = 5;
    public const STREAK_BONUS_PER_DAY = 2;
    public const FOCUS_POINTS_PER_MINUTE = 1;

    public function pointsForCompletion(Task $task, Carbon $completedAt): int
    {
        $base = self::BASE_POINTS[$task->quadrant] ?? 10;
        $bonus = 0;

        if ($task->due_at !== null) {
            if ($completedAt->lessThanOrEqualTo($task->due_at)) {
                $bonus += self::ON_TIME_BONUS;
                $hoursEarly = $completedAt->diffInHours($task->due_at, false);
                if ($hoursEarly >= 24) {
                    $bonus += self::EARLY_BONUS;
                }
            } else {
                $bonus -= self::OVERDUE_PENALTY;
            }
        } else {
            $bonus += 5;
        }

        if ($task->estimated_minutes !== null && $task->actual_minutes > 0
            && $task->actual_minutes <= $task->estimated_minutes) {
            $bonus += 5;
        }

        return max(1, $base + $bonus);
    }

    public function recordCompletion(Task $task, User $user): int
    {
        $completedAt = $task->completed_at ?? Carbon::now();
        $points = $this->pointsForCompletion($task, $completedAt);

        $this->updateStreak($user, $completedAt);
        $streakBonus = max(0, ($user->streak_days - 1)) * self::STREAK_BONUS_PER_DAY;
        $points += $streakBonus;

        $task->points_awarded = $points;
        $task->save();

        $user->awardPoints($points);

        $stat = ProductivityStat::firstOrNew([
            'user_id' => $user->id,
            'day' => $completedAt->toDateString(),
        ]);
        $stat->tasks_completed = ($stat->tasks_completed ?? 0) + 1;
        if ($task->completedOnTime()) {
            $stat->tasks_on_time = ($stat->tasks_on_time ?? 0) + 1;
        } else {
            $stat->tasks_overdue = ($stat->tasks_overdue ?? 0) + 1;
        }
        $stat->points_earned = ($stat->points_earned ?? 0) + $points;
        $stat->save();

        return $points;
    }

    public function recordFocusMinutes(User $user, int $minutes, Carbon $when): int
    {
        $minutes = max(0, $minutes);
        if ($minutes === 0) {
            return 0;
        }

        $points = $minutes * self::FOCUS_POINTS_PER_MINUTE;
        $user->awardPoints($points);

        $stat = ProductivityStat::firstOrNew([
            'user_id' => $user->id,
            'day' => $when->toDateString(),
        ]);
        $stat->focus_minutes = ($stat->focus_minutes ?? 0) + $minutes;
        $stat->points_earned = ($stat->points_earned ?? 0) + $points;
        $stat->save();

        return $points;
    }

    private function updateStreak(User $user, Carbon $completedAt): void
    {
        $today = $completedAt->copy()->startOfDay();
        $last = $user->last_completed_on?->startOfDay();

        if ($last === null) {
            $user->streak_days = 1;
        } elseif ($last->equalTo($today)) {
            $user->streak_days = max(1, $user->streak_days);
        } elseif ($last->copy()->addDay()->equalTo($today)) {
            $user->streak_days = $user->streak_days + 1;
        } else {
            $user->streak_days = 1;
        }

        $user->last_completed_on = $today;
        $user->save();
    }
}
