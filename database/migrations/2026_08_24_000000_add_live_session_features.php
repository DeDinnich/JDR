<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_skills', function (Blueprint $table) {
            $table->smallInteger('player_bonus')->default(0)->after('bonus');
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('participant_two_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['participant_one_id', 'participant_two_id']);
            $table->index(['last_message_at', 'id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
            $table->index(['sender_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversations');

        Schema::table('character_skills', function (Blueprint $table) {
            $table->dropColumn('player_bonus');
        });
    }
};
