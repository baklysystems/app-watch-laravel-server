<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // exception_rate, log_level, queue_failure, query_slow, metric_threshold, etc.
            $table->json('conditions');
            $table->json('channels'); // [ "mail", "slack", "webhook" ]
            $table->integer('cooldown_minutes')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'type']);
            $table->index(['project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};