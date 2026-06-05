<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('command');
            $table->string('description')->nullable();
            $table->string('expression')->nullable();
            $table->string('status')->default('started'); // started, completed, failed, skipped
            $table->foreignUuid('exception_id')->nullable()->constrained('exceptions')->nullOnDelete();
            $table->text('output')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'command', 'started_at']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};