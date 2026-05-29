<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('focus_sessions', function (Blueprint $table) {
            $table->integer('duration_minutes')
                ->default(0)
                ->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('focus_sessions', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};