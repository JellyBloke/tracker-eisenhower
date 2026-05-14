@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="muted">Sign in to your Eisenhower Tracker.</p>

        @if ($errors->any())
            <div class="alert error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="form" data-form="login">
            @csrf
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>
            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
        <p class="muted center">Need an account? <a href="{{ route('register') }}">Register</a></p>
    </div>
@endsection

@push('scripts')
    @vite('resources/ts/auth.ts')
@endpush
