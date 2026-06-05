<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DashboardController: stats cards (new_exceptions_today), computeExceptionTrend
        // ExceptionsController: newTodayCount stat
        Schema::table('exceptions', function (Blueprint $table) {
            $table->index(['project_id', 'first_seen_at'], 'exceptions_project_first_seen_idx');
        });

        // DashboardController: queue_failures count WHERE status='failed' AND created_at >= since
        Schema::table('queue_jobs', function (Blueprint $table) {
            $table->index(['project_id', 'status', 'created_at'], 'queue_jobs_project_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropIndex('exceptions_project_first_seen_idx');
        });

        Schema::table('queue_jobs', function (Blueprint $table) {
            $table->dropIndex('queue_jobs_project_status_created_idx');
        });
    }
};