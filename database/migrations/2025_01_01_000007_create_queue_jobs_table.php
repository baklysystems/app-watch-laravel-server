<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('connection')->nullable();
            $table->string('queue')->nullable();
            $table->string('job_name');
            $table->json('payload')->nullable();
            $table->integer('attempt')->default(0);
            $table->integer('max_attempts')->default(1);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->foreignUuid('exception_id')->nullable()->constrained('exceptions')->nullOnDelete();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'queue', 'status']);
            $table->index(['project_id', 'job_name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_jobs');
    }
};