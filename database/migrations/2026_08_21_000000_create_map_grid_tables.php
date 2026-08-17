<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cartes découpées en quadrillage, et points posés dessus.
 *
 * Une carte est découpée en tuiles à l'upload. Une case non révélée n'est pas
 * masquée à l'affichage : sa tuile n'est jamais servie. Le brouillard côté
 * client n'est donc qu'un habillage — la donnée, elle, reste sur le serveur.
 *
 * Les cases révélées sont stockées en « liste blanche » : une ligne = une case
 * ouverte. Une carte fraîchement importée est donc entièrement dans le noir,
 * ce qui est le comportement voulu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->unsignedSmallInteger('grid_columns')->default(8)->after('image_path');
            $table->unsignedSmallInteger('grid_rows')->default(6)->after('grid_columns');
            // Dimensions de l'image source, pour calculer les tuiles sans
            // relire le fichier à chaque affichage.
            $table->unsignedInteger('image_width')->nullable()->after('grid_rows');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');
        });

        Schema::create('map_cell_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->unsignedSmallInteger('column');
            $table->unsignedSmallInteger('row');
            $table->timestamps();

            // Une case ne peut être ouverte qu'une fois : la contrainte évite
            // les doublons si deux clics partent en même temps.
            $table->unique(['map_id', 'column', 'row']);
        });

        Schema::create('map_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('maps')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('color', 16)->default('#c9a227');
            // Position en pourcentage : la carte reste lisible à toute taille.
            $table->decimal('x_position', 5, 2);
            $table->decimal('y_position', 5, 2);
            // Un point du MJ peut rester privé le temps de préparer la séance.
            $table->boolean('is_visible_to_players')->default(false);
            $table->timestamps();

            $table->index(['map_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_points');
        Schema::dropIfExists('map_cell_reveals');

        Schema::table('maps', function (Blueprint $table) {
            $table->dropColumn(['grid_columns', 'grid_rows', 'image_width', 'image_height']);
        });
    }
};
