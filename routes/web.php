<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FocusController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/focus', [FocusController::class, 'index'])->name('focus');
    Route::post('/focus/start', [FocusController::class, 'start'])->name('focus.start');
    Route::post('/focus/{session}/finish', [FocusController::class, 'finish'])->name('focus.finish');

    Route::get('/stats', [StatsController::class, 'index'])->name('stats');
    Route::get('/api/stats/summary', [StatsController::class, 'summary'])->name('stats.summary');

    Route::prefix('api/tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::patch('/{task}', [TaskController::class, 'update'])->name('update');
        Route::patch('/{task}/quadrant', [TaskController::class, 'moveQuadrant'])->name('quadrant');
        Route::post('/{task}/start', [TaskController::class, 'start'])->name('start');
        Route::post('/{task}/complete', [TaskController::class, 'complete'])->name('complete');
        Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
    });
});
