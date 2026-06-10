@extends('layouts.app')

@section('title', 'Calendar')

@section('content')

<div class="calendar-page">

    <div class="page-header">

        <div>
            <h1>Calendar</h1>
            <p class="muted">
                View upcoming tasks by due date.
            </p>
        </div>

       <div class="calendar-nav">

            <div class="calendar-nav-buttons">

                <a
                    href="?month={{ $currentMonth->copy()->subMonth()->format('Y-m') }}"
                    class="btn"
                >
                    ← Previous
                </a>

                <a
                    href="?month={{ now()->format('Y-m') }}"
                    class="btn btn-primary"
                >
                    Today
                </a>

                <a
                    href="?month={{ $currentMonth->copy()->addMonth()->format('Y-m') }}"
                    class="btn"
                >
                    Next →
                </a>

            </div>

            <h2 class="calendar-month">
                {{ $currentMonth->format('F Y') }}
            </h2>

        </div>

    </div>

    <div class="calendar-grid">

        @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName)
            <div class="calendar-header">
                {{ $dayName }}
            </div>
        @endforeach

        @php
            $firstDay = $currentMonth->copy()->startOfMonth();
            $offset = $firstDay->dayOfWeek;
            $daysInMonth = $currentMonth->daysInMonth;
        @endphp

        @for ($i = 0; $i < $offset; $i++)
            <div class="calendar-cell empty"></div>
        @endfor

        @for ($day = 1; $day <= $daysInMonth; $day++)

            @php
                $date = $currentMonth
                    ->copy()
                    ->day($day);

                $dayTasks = $tasks->filter(
                    fn ($task) =>
                        $task->due_at?->isSameDay($date)
                );
            @endphp

            <div class="
                calendar-cell
                {{ $date->isToday() ? 'today' : '' }}
            ">

                <div class="calendar-cell-header">

                    <div class="calendar-date">
                        {{ $day }}
                    </div>

                    @if ($dayTasks->count())
                        <span class="calendar-task-count">
                            {{ $dayTasks->count() }}
                        </span>
                    @endif

                </div>

                @forelse ($dayTasks as $task)

                    <div
                        class="calendar-task quadrant-{{ $task->quadrant }}"
                        data-task-id="{{ $task->id }}"
                    >

                        <div class="calendar-task-title">
                            {{ $task->title }}
                        </div>

                        <div class="calendar-task-icons">

                            @if ($task->isOverdue())
                                <span
                                    class="calendar-overdue"
                                    title="Overdue"
                                >
                                    ⚠
                                </span>
                            @elseif (
                                $task->due_at &&
                                $task->due_at->isTomorrow()
                            )
                                <span
                                    class="calendar-warning"
                                    title="Due Tomorrow"
                                >
                                    🔔
                                </span>
                            @endif

                        </div>

                    </div>

                @empty

                    <div class="calendar-empty">
                        —
                    </div>

                @endforelse

            </div>

        @endfor

    </div>

</div>

{{-- TASK DETAILS MODAL --}}

<div
    id="calendar-task-modal"
    class="modal hidden"
>

    <div class="modal-content card">

        <h2 id="calendar-task-title">
            Task
        </h2>

        <div class="calendar-modal-body">

            <p id="calendar-task-desc"></p>

            <p>
                <strong>Due:</strong>
                <span id="calendar-task-due"></span>
            </p>

            <p>
                <strong>Quadrant:</strong>
                <span id="calendar-task-quadrant"></span>
            </p>

            <p>
                <strong>Estimated:</strong>
                <span id="calendar-task-estimate"></span>
            </p>

        </div>

        <div class="modal-actions">

            <button
                type="button"
                id="calendar-close"
                class="btn"
            >
                Close
            </button>

        </div>

    </div>

</div>

@endsection

@push('scripts')
    @vite('resources/ts/calendar.ts')
@endpush