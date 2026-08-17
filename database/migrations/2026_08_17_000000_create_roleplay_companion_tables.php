<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('player')->index();
        });

        Schema::create('maps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('current_map_id')->nullable()->constrained('maps')->nullOnDelete();
            $table->string('name');
            $table->string('archetype');
            $table->string('ancestry');
            $table->string('background')->nullable();
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedInteger('experience')->default(0);
            $table->unsignedSmallInteger('health');
            $table->unsignedSmallInteger('max_health');
            $table->unsignedSmallInteger('armor')->default(0);
            $table->integer('gold')->default(0);
            $table->string('status')->default('En forme');
            $table->text('biography')->nullable();
            $table->timestamps();
        });

        Schema::create('character_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('abbreviation', 6);
            $table->smallInteger('value')->default(10);
            $table->smallInteger('modifier')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['character_id', 'name']);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->smallInteger('value')->default(0);
            $table->boolean('mastered')->default(false);
            $table->boolean('secretly_granted')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['character_id', 'name']);
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 64)->default('Divers');
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight', 8, 2)->default(0);
            $table->boolean('equipped')->default(false);
            $table->boolean('secretly_granted')->default(false);
            $table->timestamps();
            $table->index(['character_id', 'category']);
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 64)->default('Lieu');
            $table->text('description')->nullable();
            $table->decimal('x_position', 5, 2)->default(50);
            $table->decimal('y_position', 5, 2)->default(50);
            $table->timestamps();
            $table->index(['map_id', 'name']);
        });

        Schema::create('npcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->text('description')->nullable();
            $table->text('game_master_notes')->nullable();
            $table->string('portrait_path')->nullable();
            $table->timestamps();
        });

        Schema::create('map_user', function (Blueprint $table) {
            $table->foreignId('map_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('discovered_at')->useCurrent();
            $table->primary(['map_id', 'user_id']);
        });

        Schema::create('location_user', function (Blueprint $table) {
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('discovered_at')->useCurrent();
            $table->primary(['location_id', 'user_id']);
        });

        Schema::create('npc_user', function (Blueprint $table) {
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 32)->default('neutre');
            $table->text('personal_notes')->nullable();
            $table->timestamp('discovered_at')->useCurrent();
            $table->timestamps();
            $table->primary(['npc_id', 'user_id']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'pinned']);
        });

        Schema::create('secret_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('priority', 24)->default('important');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secret_messages');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('npc_user');
        Schema::dropIfExists('location_user');
        Schema::dropIfExists('map_user');
        Schema::dropIfExists('npcs');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('character_attributes');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('maps');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
