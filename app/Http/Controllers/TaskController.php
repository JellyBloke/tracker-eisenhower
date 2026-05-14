<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\ProductivityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly ProductivityService $productivity)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tasks = $request->user()->tasks()
            ->orderBy('priority_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_important' => ['sometimes', 'boolean'],
            'due_at' => ['nullable', 'date'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        $dueAt = $data['due_at'] ?? null;
        $estimate = $data['estimated_minutes'] ?? null;
        $important = (bool) ($data['is_important'] ?? false);
        $urgent = Task::computeUrgent($dueAt, $estimate);

        $task = $request->user()->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_urgent' => $urgent,
            'is_important' => $important,
            'quadrant' => Task::quadrantFor($urgent, $important),
            'status' => Task::STATUS_PENDING,
            'due_at' => $dueAt,
            'estimated_minutes' => $estimate,
        ]);

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($request, $task);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_important' => ['sometimes', 'boolean'],
            'due_at' => ['nullable', 'date'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'priority_order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);

        $touchesUrgency = array_key_exists('due_at', $data)
            || array_key_exists('estimated_minutes', $data);
        $touchesImportance = array_key_exists('is_important', $data);

        if ($touchesUrgency || $touchesImportance) {
            $dueAt = array_key_exists('due_at', $data) ? $data['due_at'] : $task->due_at;
            $estimate = array_key_exists('estimated_minutes', $data)
                ? $data['estimated_minutes']
                : $task->estimated_minutes;
            $important = $touchesImportance ? (bool) $data['is_important'] : (bool) $task->is_important;

            $data['is_urgent'] = Task::computeUrgent($dueAt, $estimate);
            $data['is_important'] = $important;
            $data['quadrant'] = Task::quadrantFor($data['is_urgent'], $important);
        }

        $task->fill($data)->save();

        return response()->json(['task' => $task->fresh()]);
    }

    public function moveQuadrant(Request $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($request, $task);

        $data = $request->validate([
            'quadrant' => ['required', 'in:do,schedule,delegate,eliminate'],
        ]);

        $quadrant = $data['quadrant'];
        $task->quadrant = $quadrant;
        $task->is_urgent = in_array($quadrant, [Task::QUADRANT_DO, Task::QUADRANT_DELEGATE], true);
        $task->is_important = in_array($quadrant, [Task::QUADRANT_DO, Task::QUADRANT_SCHEDULE], true);
        $task->save();

        return response()->json(['task' => $task]);
    }

    public function complete(Request $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($request, $task);

        if ($task->status === Task::STATUS_COMPLETED) {
            return response()->json([
                'task' => $task,
                'points_awarded' => 0,
                'message' => 'Already completed.',
            ]);
        }

        $now = Carbon::now();
        $task->status = Task::STATUS_COMPLETED;
        $task->completed_at = $now;
        if ($task->started_at !== null) {
            $task->actual_minutes += max(0, (int) round($task->started_at->diffInMinutes($now)));
        }
        $task->save();

        $points = $this->productivity->recordCompletion($task, $request->user());

        return response()->json([
            'task' => $task->fresh(),
            'points_awarded' => $points,
            'user' => $request->user()->fresh(),
        ]);
    }

    public function start(Request $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($request, $task);

        if ($task->status === Task::STATUS_COMPLETED) {
            return response()->json(['message' => 'Cannot start a completed task.'], 422);
        }

        $task->status = Task::STATUS_IN_PROGRESS;
        $task->started_at = $task->started_at ?? Carbon::now();
        $task->save();

        return response()->json(['task' => $task]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($request, $task);
        $task->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeOwnership(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 403);
    }
}
