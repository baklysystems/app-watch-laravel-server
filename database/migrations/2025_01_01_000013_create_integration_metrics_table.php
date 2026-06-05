<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('integration'); // google_analytics, stripe, cloudflare, etc.
            $table->string('metric_name');
            $table->float('metric_value')->default(0);
            $table->string('unit')->nullable();
            $table->json('dimensions')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'integration', 'metric_name', 'recorded_at'], 'int_metrics_proj_int_name_date_index');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_metrics');
    }
};