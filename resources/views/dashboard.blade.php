@extends('layouts.app')

@section('title', 'Eisenhower Matrix')

@section('content')
    <header class="page-header">
        <div>
            <h1>Eisenhower Matrix</h1>
            <p class="muted">Sort what you do by urgency and importance. Drag tasks between quadrants.</p>
        </div>
        <div class="header-stats">
            <div class="stat">
                <span class="value">{{ $totalPending }}</span>
                <span class="label">open</span>
            </div>
            <div class="stat">
                <span class="value">{{ $totalCompleted }}</span>
                <span class="label">done</span>
            </div>
            <div class="stat">
                <span class="value">{{ $todayStat?->points_earned ?? 0 }}</span>
                <span class="label">pts today</span>
            </div>
        </div>
    </header>

    <section class="task-create card">
        <form id="task-form" class="task-form">
            <div class="row">
                <input type="text" name="title" placeholder="What needs doing?" required maxlength="255">
                <input type="datetime-local" name="due_at" title="Due date (optional)">
                <input type="number" name="estimated_minutes" min="1" max="1440" placeholder="Est. min">
            </div>
            <div class="row">
                <textarea name="description" placeholder="Notes (optional)" rows="2" maxlength="2000"></textarea>
            </div>
            <div class="row toggles">
                <label class="checkbox">
                    <input type="checkbox" name="is_important" value="1">
                    <span>Important</span>
                </label>
                <span class="muted small" title="Urgency is inferred from the due date and estimated time.">
                    Urgency is auto-detected from your due date.
                </span>
                <span class="quadrant-preview" data-preview>→ Eliminate</span>
                <button type="submit" class="btn btn-primary">Add task</button>
            </div>
        </form>
    </section>

    <section class="matrix" id="matrix">
        @php
            $titles = [
                'do' => ['Do', 'Urgent & Important'],
                'schedule' => ['Schedule', 'Important, Not Urgent'],
                'delegate' => ['Delegate', 'Urgent, Not Important'],
                'eliminate' => ['Eliminate', 'Neither'],
            ];
        @endphp

        @foreach ($titles as $key => [$label, $sub])
            <div class="quadrant quadrant-{{ $key }}" data-quadrant="{{ $key }}">
                <header>
                    <h2>{{ $label }}</h2>
                    <span class="muted">{{ $sub }}</span>
                </header>
                <ul class="task-list" data-dropzone="{{ $key }}">
                    @foreach ($quadrants[$key] as $task)
                        <li class="task @if($task->status === 'completed') completed @endif"
                            data-task-id="{{ $task->id }}"
                            data-quadrant="{{ $task->quadrant }}"
                            draggable="true">
                            <div class="task-main">
                                <label class="checkbox">
                                    <input type="checkbox"
                                           data-action="complete"
                                           @if($task->status === 'completed') checked disabled @endif>
                                    <span class="task-title">{{ $task->title }}</span>
                                </label>
                                @if ($task->due_at)
                                    <span class="due @if($task->isOverdue()) overdue @endif">
                                        Due {{ $task->due_at->format('M j, H:i') }}
                                    </span>
                                @endif
                            </div>
                            @if ($task->description)
                                <p class="task-desc">{{ $task->description }}</p>
                            @endif
                            <div class="task-meta">
                                @if ($task->estimated_minutes)
                                    <span title="Estimate">⏱ {{ $task->estimated_minutes }}m</span>
                                @endif
                                @if ($task->focus_minutes > 0)
                                    <span title="Focused">🎯 {{ $task->focus_minutes }}m</span>
                                @endif
                                @if ($task->points_awarded > 0)
                                    <span title="Points earned">⭐ {{ $task->points_awarded }}</span>
                                @endif
                                <button type="button" class="link" data-action="focus" data-task-id="{{ $task->id }}">Focus</button>
                                <button type="button" class="link danger" data-action="delete">Delete</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </section>

    <div id="toast-host" class="toast-host"></div>
@endsection

@push('scripts')
    @vite('resources/ts/dashboard.ts')
@endpush
