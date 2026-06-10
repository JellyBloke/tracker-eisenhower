<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
    @stack('head')
</head>

<div id="tutorial-modal" class="modal hidden">
    <div class="modal-content card">

        <h2>Welcome to Eisenhower Tracker</h2>

        <p id="tutorial-text"></p>

        <div class="modal-actions">
            <button id="tutorial-next" class="btn btn-primary">Next</button>
            <button id="tutorial-skip" class="btn">Skip</button>
        </div>

    </div>
</div>

<body class="app-body">
    @auth
        <nav class="topbar">
            <div class="brand">
                <a href="{{ route('dashboard') }}">{{ config('app.name') }}</a>
            </div>
            <ul class="nav-links">
                <li><a href="{{ route('today') }}" class="@if(request()->routeIs('today')) active @endif">Dashboard</a></li>
                <li><a href="{{ route('dashboard') }}" class="@if(request()->routeIs('dashboard')) active @endif">Matrix</a></li>
                <li><a href="{{ route('focus') }}" class="@if(request()->routeIs('focus')) active @endif">Focus</a></li>
                <li><a href="{{ route('calendar') }}" class="{{ request()->routeIs('calendar') ? 'active' : '' }}">Calendar</a></li>
                <li><a href="{{ route('stats') }}" class="@if(request()->routeIs('stats')) active @endif">Stats</a></li>
            </ul>
            <div class="user-panel">
                <span class="badge level">Lv {{ auth()->user()->level }}</span>
                <span class="badge points">{{ auth()->user()->points }} pts</span>
                <span class="badge streak" title="Current streak">🔥 {{ auth()->user()->streak_days }}</span>
                <span class="user-name">{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                    @csrf
                    <button type="submit" class="btn btn-ghost">Logout</button>
                </form>
            </div>
        </nav>
    @endauth

    <main class="container">
        @if (session('status'))
            <div class="alert success">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
