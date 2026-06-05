<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->float('value')->default(0);
            $table->string('unit')->nullable();
            $table->json('tags')->nullable();
            $table->string('type')->default('gauge'); // gauge, counter, histogram
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'name', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};