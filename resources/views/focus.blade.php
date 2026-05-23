@extends('layouts.app')

@section('title', 'Focus Mode')

@section('content')
    <header class="page-header">
        <div>
            <h1>Focus Mode</h1>
            <p class="muted">Lock in on one task at a time. Completed focus minutes turn into points.</p>
        </div>
    </header>

    <section class="focus-shell card" id="focus-shell"
             data-task-id="{{ request('task_id', '') }}">
        <div class="focus-config" id="focus-config">
            <label class="field">
                <span>Task</span>
                <select id="focus-task">
                    <option value="">— Standalone focus session —</option>
                    @foreach ($tasks as $task)
                        <option
                            value="{{ $task->id }}"
                            data-estimate="{{ $task->estimated_minutes ?? '' }}"
                            data-quadrant="{{ $task->quadrant }}"
                            @selected((string) request('task_id') === (string) $task->id)
                        >
                            [{{ ucfirst($task->quadrant) }}] {{ $task->title }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="field">
                <span>Duration (minutes)</span>
                <input type="number" id="focus-minutes" value="{{ $selectedTask?->estimated_minutes ?? 25 }}" min="1" max="180">
            </label>

            <div class="presets">
                <button type="button" class="btn btn-ghost" data-preset="15">15</button>
                <button type="button" class="btn btn-ghost" data-preset="25">25</button>
                <button type="button" class="btn btn-ghost" data-preset="45">45</button>
                <button type="button" class="btn btn-ghost" data-preset="60">60</button>
            </div>

            <button type="button" id="focus-start" class="btn btn-primary">Start focus</button>
        </div>

        <div class="focus-active hidden" id="focus-active">
            <div class="focus-task-display">
                <span class="focus-label">CURRENT FOCUS</span>
                <h2 id="focus-task-title">Standalone Focus Session</h2>
            </div>
            <div class="timer-wrap">
                <svg viewBox="0 0 200 200" class="ring">
                    <circle cx="100" cy="100" r="90" class="ring-bg"></circle>
                    <circle cx="100" cy="100" r="90" class="ring-progress" id="ring-progress"></circle>
                </svg>
                <div class="time-display" id="time-display">25:00</div>
                <div class="focus-status" id="focus-status">Stay focused.</div>
            </div>
            <div class="focus-actions">
                <button type="button" class="btn btn-ghost" id="focus-pause">Pause</button>
                <button type="button" class="btn btn-danger" id="focus-stop">End early</button>
            </div>
        </div>
    </section>

    <section class="card">
        <h2>Recent focus sessions</h2>
        @if ($recentSessions->isEmpty())
            <p class="muted">No focus sessions yet. Start one above.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Started</th>
                        <th>Task</th>
                        <th>Planned</th>
                        <th>Actual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentSessions as $session)
                        <tr>
                            <td>{{ $session->started_at->format('M j, H:i') }}</td>
                            <td>{{ $session->task?->title ?? '—' }}</td>
                            <td>{{ $session->planned_minutes }}m</td>
                            <td>{{ $session->actual_minutes }}m</td>
                            <td>
                                @if ($session->completed)
                                    <span class="tag success">Completed</span>
                                @elseif ($session->interrupted)
                                    <span class="tag warning">Interrupted</span>
                                @else
                                    <span class="tag">In progress</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <div id="toast-host" class="toast-host"></div>
@endsection

@push('scripts')
    @vite('resources/ts/focus.ts')
@endpush
