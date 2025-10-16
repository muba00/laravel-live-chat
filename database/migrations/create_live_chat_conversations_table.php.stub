<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user1_id');
            $table->unsignedBigInteger('user2_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user1_id', 'user2_id']);
            $table->index('last_message_at');

            // Ensure user1_id is always less than user2_id for consistency
            $table->unique(['user1_id', 'user2_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_chat_conversations');
    }
};
