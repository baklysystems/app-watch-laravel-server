<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->uuid('batch_id')->nullable();
            $table->string('level')->default('info'); // debug, info, warning, error, critical
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->string('channel')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->uuid('trace_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'level', 'occurred_at']);
            $table->index(['project_id', 'batch_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_entries');
    }
};