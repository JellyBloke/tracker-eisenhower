<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_important')->default(false);
            $table->enum('quadrant', ['do', 'schedule', 'delegate', 'eliminate'])->default('schedule');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->dateTime('due_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('actual_minutes')->default(0);
            $table->unsignedInteger('focus_minutes')->default(0);
            $table->unsignedInteger('points_awarded')->default(0);
            $table->unsignedSmallInteger('priority_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'quadrant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
