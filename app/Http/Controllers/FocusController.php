<?php

namespace App\Http\Controllers;

use App\Models\FocusSession;
use App\Models\Task;
use App\Services\ProductivityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FocusController extends Controller
{
    public function __construct(private readonly ProductivityService $productivity)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $tasks = $user->tasks()
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->orderByRaw("FIELD(quadrant, 'do','schedule','delegate','eliminate')")
            ->orderBy('due_at')
            ->get();

        $recent = $user->focusSessions()
            ->latest('started_at')
            ->limit(5)
            ->get();

        $selectedTask = null;

        if ($request->filled('task_id')) {
            $selectedTask = $user->tasks()
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->find($request->integer('task_id'));
        }

        return view('focus', [
            'tasks' => $tasks,
            'recentSessions' => $recent,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'planned_minutes' => ['required', 'integer', 'min:1', 'max:180'],
        ]);

        if (! empty($data['task_id'])) {
            $task = Task::findOrFail($data['task_id']);
            abort_unless($task->user_id === $request->user()->id, 403);
            if ($task->started_at === null) {
                $task->started_at = Carbon::now();
                $task->status = Task::STATUS_IN_PROGRESS;
                $task->save();
            }
        }

        $session = FocusSession::create([
            'user_id' => $request->user()->id,
            'task_id' => $data['task_id'] ?? null,
            'started_at' => Carbon::now(),
            'planned_minutes' => $data['planned_minutes'],
        ]);

        return response()->json(['session' => $session], 201);
    }

    public function finish(Request $request, FocusSession $session): JsonResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'completed' => ['required', 'boolean'],
            'actual_minutes' => ['required', 'integer', 'min:0', 'max:480'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $now = Carbon::now();
        $session->ended_at = $now;
        $session->actual_minutes = $data['actual_minutes'];
        $session->completed = $data['completed'];
        $session->interrupted = ! $data['completed'];
        $session->notes = $data['notes'] ?? null;
        $session->save();

        if ($session->task_id !== null) {
            /** @var Task $task */
            $task = Task::find($session->task_id);
            if ($task !== null) {
                $task->focus_minutes += $session->actual_minutes;
                $task->actual_minutes += $session->actual_minutes;
                $task->save();
            }
        }

        $points = $session->completed
            ? $this->productivity->recordFocusMinutes($request->user(), $session->actual_minutes, $now)
            : 0;

        return response()->json([
            'session' => $session->fresh(),
            'points_awarded' => $points,
            'user' => $request->user()->fresh(),
        ]);
    }
}
