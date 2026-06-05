<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auto_resolution_rules')) return;
        Schema::create('auto_resolution_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('rule_type'); // auto_resolve, auto_mute, auto_ignore
            $table->json('conditions');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_evaluated_at')->nullable();
            $table->integer('execution_count')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_resolution_rules');
    }
};