<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('chat_id');
            $table->string('chat_type')->default('private'); // private, group, channel
            $table->string('chat_name')->nullable();
            $table->json('subscribed_events')->nullable(); // ["alerts", "daily_digest", "deployments"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('chat_id');
            $table->index(['project_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_subscriptions');
    }
};