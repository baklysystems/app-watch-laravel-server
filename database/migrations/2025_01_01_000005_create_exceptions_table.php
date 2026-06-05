<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('class');
            $table->text('message')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->json('code_snippet')->nullable();
            $table->json('stack_trace')->nullable();
            $table->json('request_data')->nullable();
            $table->json('user_data')->nullable();
            $table->json('breadcrumbs')->nullable();
            $table->string('environment')->default('production');
            $table->string('release')->nullable();
            $table->string('severity')->default('error'); // debug, info, warning, error, critical
            $table->string('status')->default('unresolved'); // unresolved, resolved, ignored, muted
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'fingerprint']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'last_seen_at']);
            $table->index(['project_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};