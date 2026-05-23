@extends('layouts.app')

@section('title', 'Productivity Stats')

@section('content')
    <header class="page-header">
        <div>
            <h1>Productivity Stats</h1>
            <p class="muted">Last 14 days of completed tasks, focus time, and points earned.</p>
        </div>
    </header>

    <section class="stat-grid">
        <section class="highlights-grid">
            <div class="highlight-card">
                <span class="highlight-label">Best Focus Day</span>

                @php
                    $bestFocus = collect($series)->sortByDesc('focus_minutes')->first();
                @endphp

                <span class="highlight-value">
                    {{ $bestFocus['label'] }}
                </span>

                <span class="highlight-sub">
                    {{ $bestFocus['focus_minutes'] }} minutes focused
                </span>
            </div>

            <div class="highlight-card">
                <span class="highlight-label">Highest Points</span>

                @php
                    $bestPoints = collect($series)->sortByDesc('points_earned')->first();
                @endphp

                <span class="highlight-value">
                    {{ $bestPoints['points_earned'] }}
                </span>

                <span class="highlight-sub">
                    earned on {{ $bestPoints['label'] }}
                </span>
            </div>

            <div class="highlight-card">
                <span class="highlight-label">Completion Rate</span>

                @php
                    $completed = $totals['tasks_completed'];
                    $pending = $totals['tasks_pending'];

                    $rate = ($completed + $pending) > 0
                        ? round(($completed / ($completed + $pending)) * 100)
                        : 0;
                @endphp

                <span class="highlight-value">
                    {{ $rate }}%
                </span>

                <span class="highlight-sub">
                    task completion success
                </span>
            </div>
        </section>
        <section class="card productivity-score">

            @php
                $score =
                    ($totals['focus_minutes_14d'] * 0.3) +
                    ($totals['tasks_completed'] * 5) +
                    ($totals['streak_days'] * 4);

                $score = min(100, round($score));
            @endphp

            <div class="score-header">
                <div>
                    <h2>Productivity Score</h2>
                    <p class="muted">
                        Calculated from focus time, streaks, and completed tasks.
                    </p>
                </div>

                <div class="score-circle">
                    {{ $score }}
                </div>
            </div>

            <div class="progress-bar large">
                <div class="progress-fill" style="width: {{ $score }}%"></div>
            </div>
        </section>
        <div class="stat-card">
            <span class="label">Total points</span>
            <span class="value big">{{ $totals['total_points'] }}</span>
        </div>
        <div class="stat-card">
            <span class="label">Level</span>
            <span class="value big">Lv {{ $totals['level'] }}</span>
            <div class="progress-bar"><div class="progress-fill" style="width: {{ $totals['progress_to_next'] }}%"></div></div>
            <span class="muted small">
                {{ $totals['total_points'] - $totals['current_level_xp'] }}
                /
                {{ $totals['next_level_xp'] - $totals['current_level_xp'] }}
                XP to next level
            </span>
        </div>
        <div class="stat-card">
            <span class="label">Streak</span>
            <span class="value big">🔥 {{ $totals['streak_days'] }} days</span>
        </div>
        <div class="stat-card">
            <span class="label">Completed (all-time)</span>
            <span class="value big">{{ $totals['tasks_completed'] }}</span>
            <span class="trend positive">
                ↑ Productivity improving
            </span>
        </div>
        <div class="stat-card">
            <span class="label">Focus minutes (14d)</span>
            <span class="value big">{{ $totals['focus_minutes_14d'] }}</span>
        </div>
        <div class="stat-card">
            <span class="label">On time (14d)</span>
            <span class="value big">{{ $totals['on_time_14d'] }}</span>
            <span class="trend warning">
                {{ $totals['overdue_14d'] }} overdue
            </span>
        </div>
    </section>

    <section class="card">
        <h2>Daily activity</h2>
        <div class="chart-wrap">
            <canvas id="stats-chart" width="900" height="320"></canvas>
        </div>
        <div class="legend">
            <span><span class="dot points"></span>Points</span>
            <span><span class="dot focus"></span>Focus min</span>
            <span><span class="dot ontime"></span>On-time tasks</span>
        </div>
    </section>

    <section class="card">
        <h2>Day-by-day</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Completed</th>
                    <th>On time</th>
                    <th>Overdue</th>
                    <th>Focus</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                @foreach (array_reverse($series) as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['tasks_completed'] }}</td>
                        <td>{{ $row['tasks_on_time'] }}</td>
                        <td>{{ $row['tasks_overdue'] }}</td>
                        <td>{{ $row['focus_minutes'] }}m</td>
                        <td>{{ $row['points_earned'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <script id="stats-data" type="application/json">{!! json_encode($series) !!}</script>
@endsection

@push('scripts')
    @vite('resources/ts/stats.ts')
@endpush
