<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->uuid('batch_id')->nullable();
            $table->text('sql');
            $table->json('bindings')->nullable();
            $table->float('duration_ms')->default(0);
            $table->string('connection_name')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->boolean('is_slow')->default(false);
            $table->uuid('trace_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);
            $table->index(['project_id', 'is_slow']);
            $table->index(['project_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_queries');
    }
};