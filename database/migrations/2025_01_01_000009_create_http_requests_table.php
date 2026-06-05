<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('http_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->uuid('trace_id');
            $table->string('method', 10);
            $table->text('url');
            $table->string('route_name')->nullable();
            $table->string('controller_action')->nullable();
            $table->integer('status_code')->default(200);
            $table->float('duration_ms')->default(0);
            $table->float('memory_usage_mb')->default(0);
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('user_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);
            $table->index(['project_id', 'route_name']);
            $table->index('trace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('http_requests');
    }
};