<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('key');
            $table->string('key_prefix', 8);
            $table->string('name');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('key');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};