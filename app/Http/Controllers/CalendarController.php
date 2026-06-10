<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month');

        $currentMonth = $month
            ? Carbon::parse($month)
            : now();

        $start = $currentMonth->copy()->startOfMonth();
        $end = $currentMonth->copy()->endOfMonth();

        $tasks = $request->user()
            ->tasks()
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$start, $end])
            ->orderBy('due_at')
            ->get();

        return view('calendar.index', [
            'currentMonth' => $currentMonth,
            'tasks' => $tasks,
        ]);
    }
}