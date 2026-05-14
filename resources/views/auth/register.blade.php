@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <div class="auth-card">
        <h1>Create your account</h1>
        <p class="muted">Start tracking your productivity today.</p>

        @if ($errors->any())
            <div class="alert error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="form" data-form="register">
            @csrf
            <label class="field">
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            </label>
            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" required minlength="8">
            </label>
            <label class="field">
                <span>Confirm password</span>
                <input type="password" name="password_confirmation" required minlength="8">
            </label>
            <button type="submit" class="btn btn-primary">Create account</button>
        </form>
        <p class="muted center">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
    </div>
@endsection

@push('scripts')
    @vite('resources/ts/auth.ts')
@endpush
