<?php

namespace Database\Seeders;

use App\Models\FocusSession;
use App\Models\Task;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoProductivitySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@test.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'points' => 840,
                'level' => 5,
                'streak_days' => 8,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Tags
        |--------------------------------------------------------------------------
        */

        $study = Tag::create([
            'user_id' => $user->id,
            'name' => 'Study',
            'color' => '#3B82F6',
        ]);

        $work = Tag::create([
            'user_id' => $user->id,
            'name' => 'Work',
            'color' => '#EF4444',
        ]);

        $health = Tag::create([
            'user_id' => $user->id,
            'name' => 'Health',
            'color' => '#10B981',
        ]);

        $personal = Tag::create([
            'user_id' => $user->id,
            'name' => 'Personal',
            'color' => '#F59E0B',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Tasks
        |--------------------------------------------------------------------------
        */

        $tasks = [

            // DO
            [
                'title' => 'Finish final report',
                'description' => 'Submit before client meeting.',
                'important' => true,
                'due' => Carbon::now()->addHours(5),
                'minutes' => 180,
                'tags' => [$work],
            ],

            [
                'title' => 'Study database normalization',
                'description' => 'Exam tomorrow morning.',
                'important' => true,
                'due' => Carbon::now()->addHours(10),
                'minutes' => 120,
                'tags' => [$study],
            ],

            // SCHEDULE
            [
                'title' => 'Prepare portfolio website',
                'description' => 'Long-term career improvement.',
                'important' => true,
                'due' => Carbon::now()->addDays(5),
                'minutes' => 240,
                'tags' => [$work, $personal],
            ],

            [
                'title' => 'Gym workout',
                'description' => 'Leg day.',
                'important' => true,
                'due' => Carbon::now()->addDays(2),
                'minutes' => 90,
                'tags' => [$health],
            ],

            // DELEGATE
            [
                'title' => 'Reply to routine emails',
                'description' => 'Can be delegated later.',
                'important' => false,
                'due' => Carbon::now()->addHours(8),
                'minutes' => 20,
                'tags' => [$work],
            ],

            [
                'title' => 'Pay internet bill',
                'description' => 'Quick but urgent.',
                'important' => false,
                'due' => Carbon::now()->addHours(12),
                'minutes' => 10,
                'tags' => [$personal],
            ],

            // ELIMINATE
            [
                'title' => 'Organize meme folder',
                'description' => 'Not important.',
                'important' => false,
                'due' => Carbon::now()->addDays(14),
                'minutes' => 30,
                'tags' => [$personal],
            ],

            [
                'title' => 'Browse random YouTube videos',
                'description' => 'Low-value activity.',
                'important' => false,
                'due' => null,
                'minutes' => 60,
                'tags' => [$personal],
            ],
        ];

        foreach ($tasks as $item) {

            $urgent = Task::computeUrgent(
                $item['due'],
                $item['minutes']
            );

            $task = Task::create([
                'user_id' => $user->id,
                'title' => $item['title'],
                'description' => $item['description'],
                'is_urgent' => $urgent,
                'is_important' => $item['important'],
                'quadrant' => Task::quadrantFor(
                    $urgent,
                    $item['important']
                ),
                'status' => Task::STATUS_PENDING,
                'due_at' => $item['due'],
                'estimated_minutes' => $item['minutes'],
                'focus_minutes' => rand(0, 120),
                'points_awarded' => rand(5, 50),
            ]);

            $task->tags()->attach(
                collect($item['tags'])->pluck('id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Completed Tasks
        |--------------------------------------------------------------------------
        */

        for ($i = 1; $i <= 5; $i++) {

            $task = Task::create([
                'user_id' => $user->id,
                'title' => "Completed Task {$i}",
                'description' => 'Already completed productivity task.',
                'is_urgent' => true,
                'is_important' => true,
                'quadrant' => 'do',
                'status' => Task::STATUS_COMPLETED,
                'due_at' => Carbon::now()->subDays($i),
                'completed_at' => Carbon::now()->subDays($i),
                'estimated_minutes' => 60,
                'actual_minutes' => rand(30, 90),
                'focus_minutes' => rand(20, 80),
                'points_awarded' => rand(15, 40),
            ]);

            $task->tags()->attach($study->id);

            FocusSession::create([
                'task_id' => $task->id,
                'started_at' => Carbon::now()->subDays($i)->subHour(),
                'ended_at' => Carbon::now()->subDays($i),
                'duration_minutes' => rand(25, 60),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Overdue Task
        |--------------------------------------------------------------------------
        */

        Task::create([
            'user_id' => $user->id,
            'title' => 'Overdue Assignment',
            'description' => 'Missed submission deadline.',
            'is_urgent' => true,
            'is_important' => true,
            'quadrant' => 'do',
            'status' => Task::STATUS_PENDING,
            'due_at' => Carbon::now()->subHours(6),
            'estimated_minutes' => 120,
        ]);
    }
}