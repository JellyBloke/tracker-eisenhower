<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productivity_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('tasks_completed')->default(0);
            $table->unsignedInteger('tasks_on_time')->default(0);
            $table->unsignedInteger('tasks_overdue')->default(0);
            $table->unsignedInteger('focus_minutes')->default(0);
            $table->unsignedInteger('points_earned')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productivity_stats');
    }
};
