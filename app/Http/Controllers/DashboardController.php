<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tasks = $user->tasks()
            ->with('tags')
            ->orderBy('priority_order')
            ->orderByDesc('created_at')
            ->get();
        
        $tags = $user->tags()
            ->orderBy('name')
            ->get();

        $grouped = [
            Task::QUADRANT_DO => $tasks->where('quadrant', Task::QUADRANT_DO)->values(),
            Task::QUADRANT_SCHEDULE => $tasks->where('quadrant', Task::QUADRANT_SCHEDULE)->values(),
            Task::QUADRANT_DELEGATE => $tasks->where('quadrant', Task::QUADRANT_DELEGATE)->values(),
            Task::QUADRANT_ELIMINATE => $tasks->where('quadrant', Task::QUADRANT_ELIMINATE)->values(),
        ];

        $today = Carbon::today();
        $todayStat = $user->productivityStats()->whereDate('day', $today)->first();

        return view('dashboard', [
            'user' => $user,
            'tasks' => $tasks,
            'quadrants' => $grouped,
            'tags' => $tags,
            'todayStat' => $todayStat,
            'totalPending' => $tasks->where('status', '!=', Task::STATUS_COMPLETED)->count(),
            'totalCompleted' => $tasks->where('status', Task::STATUS_COMPLETED)->count(),
        ]);
    }
}
