@extends('layouts.app')

@section('title', 'Today')

@section('content')

<div class="today-page">

    <section class="today-hero card">
        <h1>
            Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 18 ? 'Afternoon' : 'Evening') }}
        </h1>

        <p class="muted">
            You have {{ $todayTasks->count() }} tasks today
        </p>

        <div class="today-stats">
            <div class="badge">
                {{ $urgentCount }} urgent
            </div>

            <div class="badge">
                {{ $importantCount }} important
            </div>
        </div>
    </section>

    @if ($recommendedTask)
        <section class="card">
            <h2>Recommended Focus</h2>

            <div class="recommended-task">
                <h3>{{ $recommendedTask->title }}</h3>

                @if ($recommendedTask->estimated_minutes)
                    <p class="muted">
                        Estimated {{ $recommendedTask->estimated_minutes }} min
                    </p>
                @endif

                <a
                    href="/focus?task_id={{ $recommendedTask->id }}"
                    class="btn btn-primary"
                >
                    Start Focus
                </a>
            </div>
        </section>
    @endif

    <section class="card">
        <h2>Today's Timeline</h2>

        <div class="timeline">
            @forelse ($todayTasks as $task)
                <div class="timeline-item">

                    <div class="timeline-time">
                        {{ $task->due_at?->format('H:i') ?? '--:--' }}
                    </div>

                    <div class="timeline-content">
                        <strong>{{ $task->title }}</strong>

                        @if ($task->description)
                            <p class="muted">
                                {{ $task->description }}
                            </p>
                        @endif
                    </div>

                </div>
            @empty
                <p class="muted">
                    No tasks scheduled today.
                </p>
            @endforelse
        </div>
    </section>

    <section class="card">
        <h2>Upcoming</h2>

        @if ($upcomingTasks->isNotEmpty())

            <div class="upcoming-grid">

                @foreach ($upcomingTasks as $task)

                    <div class="upcoming-card">

                        <div class="upcoming-card-header">

                            <h3>{{ $task->title }}</h3>

                            <span
                                class="quadrant-badge quadrant-{{ $task->quadrant }}"
                            >
                                {{ ucfirst($task->quadrant) }}
                            </span>

                        </div>

                        @if ($task->description)
                            <p class="upcoming-desc">
                                {{ Str::limit($task->description, 80) }}
                            </p>
                        @endif

                        <div class="upcoming-meta">

                            @if ($task->due_at)
                                <span>
                                    ⏰ {{ $task->due_at->format('M d, H:i') }}
                                </span>
                            @endif

                            @if ($task->estimated_minutes)
                                <span>
                                    ⏱ {{ $task->estimated_minutes }}m
                                </span>
                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <p class="muted">
                Nothing upcoming.
            </p>

        @endif
    </section>  

</div>

@endsection