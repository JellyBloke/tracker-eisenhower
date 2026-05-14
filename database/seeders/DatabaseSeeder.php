<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'demo@example.com')->exists()) {
            return;
        }

        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $samples = [
            ['Fix production bug', true, true, 60, '+2 hours'],
            ['Quarterly planning doc', false, true, 120, '+3 days'],
            ['Reply to non-urgent emails', true, false, 30, '+5 hours'],
            ['Scroll social media less', false, false, null, null],
            ['Schedule annual checkup', false, true, 15, '+10 days'],
        ];

        foreach ($samples as [$title, $urgent, $important, $est, $due]) {
            Task::create([
                'user_id' => $user->id,
                'title' => $title,
                'is_urgent' => $urgent,
                'is_important' => $important,
                'quadrant' => Task::quadrantFor($urgent, $important),
                'estimated_minutes' => $est,
                'due_at' => $due !== null ? now()->modify($due) : null,
            ]);
        }
    }
}
