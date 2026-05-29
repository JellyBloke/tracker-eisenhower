<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\View\View;

class TodayController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $today = Carbon::today();

        $todayTasks = $user->tasks()
            ->with('tags')
            ->whereDate('due_at', $today)
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->orderBy('due_at')
            ->get();

        $upcomingTasks = $user->tasks()
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->whereDate('due_at', '>', $today)
            ->orderBy('due_at')
            ->limit(5)
            ->get();

        $urgentCount = $todayTasks
            ->where('is_urgent', true)
            ->count();

        $importantCount = $todayTasks
            ->where('is_important', true)
            ->count();

        $recommendedTask = $todayTasks
            ->sortByDesc(fn ($task) =>
                ($task->is_important ? 2 : 0)
                + ($task->is_urgent ? 1 : 0)
            )
            ->first();

        return view('today.index', [
            'todayTasks' => $todayTasks,
            'upcomingTasks' => $upcomingTasks,
            'urgentCount' => $urgentCount,
            'importantCount' => $importantCount,
            'recommendedTask' => $recommendedTask,
        ]);
    }
}