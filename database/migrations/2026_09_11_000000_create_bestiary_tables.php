<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monsters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type')->nullable()->index();
            $table->string('size', 64)->nullable();
            $table->string('threat_level', 64)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('habitat')->nullable();
            $table->text('behavior')->nullable();
            $table->text('game_master_notes')->nullable();
            $table->string('portrait_path')->nullable();

            $table->unsignedInteger('health_current')->default(1);
            $table->unsignedInteger('health_max')->default(1);
            $table->unsignedInteger('mana_current')->default(0);
            $table->unsignedInteger('mana_max')->default(0);
            $table->unsignedSmallInteger('armor')->default(0);
            $table->unsignedSmallInteger('strength')->default(0);
            $table->unsignedSmallInteger('endurance')->default(0);
            $table->unsignedSmallInteger('dexterity')->default(0);
            $table->unsignedSmallInteger('intelligence')->default(0);
            $table->unsignedSmallInteger('willpower')->default(0);
            $table->unsignedSmallInteger('perception')->default(0);
            $table->string('damage_dice', 32)->nullable();
            $table->text('weakness')->nullable();
            $table->timestamps();
        });

        Schema::create('monster_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 64)->nullable();
            $table->text('description')->nullable();
            $table->string('dice_formula', 32)->nullable();
            $table->unsignedInteger('mana_cost')->default(0);
            $table->string('cooldown', 120)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['monster_id', 'sort_order']);
        });

        Schema::create('monster_user', function (Blueprint $table) {
            $table->foreignId('monster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('discovered_at')->useCurrent();
            $table->string('known_health')->nullable();
            $table->string('known_mana')->nullable();
            $table->text('known_abilities')->nullable();
            $table->string('known_damage')->nullable();
            $table->text('known_weakness')->nullable();
            $table->text('personal_notes')->nullable();
            $table->timestamps();

            $table->primary(['monster_id', 'user_id']);
            $table->index(['user_id', 'discovered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monster_user');
        Schema::dropIfExists('monster_abilities');
        Schema::dropIfExists('monsters');
    }
};
