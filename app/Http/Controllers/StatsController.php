<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $start = Carbon::today()->subDays(13);

        $rows = $user->productivityStats()
            ->whereDate('day', '>=', $start)
            ->orderBy('day')
            ->get()
            ->keyBy(fn ($row) => $row->day->toDateString());

        $series = [];
        for ($i = 0; $i < 14; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->toDateString();
            $row = $rows->get($key);
            $series[] = [
                'day' => $key,
                'label' => $date->format('M j'),
                'tasks_completed' => $row->tasks_completed ?? 0,
                'tasks_on_time' => $row->tasks_on_time ?? 0,
                'tasks_overdue' => $row->tasks_overdue ?? 0,
                'focus_minutes' => $row->focus_minutes ?? 0,
                'points_earned' => $row->points_earned ?? 0,
            ];
        }

        $totals = [
            'total_points' => $user->points,
            'level' => $user->level,
            'progress_to_next' => $user->progressToNextLevel(),
            'current_level_xp' => $user->currentLevelXp(),
            'next_level_xp' => $user->nextLevelXp(),
            'streak_days' => $user->streak_days,
            'tasks_completed' => $user->tasks()->where('status', Task::STATUS_COMPLETED)->count(),
            'tasks_pending' => $user->tasks()->where('status', '!=', Task::STATUS_COMPLETED)->count(),
            'focus_minutes_14d' => array_sum(array_column($series, 'focus_minutes')),
            'on_time_14d' => array_sum(array_column($series, 'tasks_on_time')),
            'overdue_14d' => array_sum(array_column($series, 'tasks_overdue')),
        ];

        return view('stats', [
            'series' => $series,
            'totals' => $totals,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'points' => $user->points,
            'level' => $user->level,
            'progress_to_next' => $user->progressToNextLevel(),
            'streak_days' => $user->streak_days,
        ]);
    }
}
