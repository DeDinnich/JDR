<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retour à un compagnon de partie, et non à un moteur de JDR.
 *
 * Le site accompagne une table physique : le MJ pilote tout à la main. On
 * retire donc les mécaniques automatiques (XP de développement, prédispositions,
 * niveaux, journal de progression) et la notion de caractéristique cachée —
 * une caractéristique présente sur la fiche est toujours lisible par le joueur.
 *
 * Ce qui RESTE volontairement :
 *  - le bonus/malus manuel sur les compétences (posé par le MJ) ;
 *  - la visibilité par compétence, maîtrise, affinité et capacité, qui sert la
 *    découverte narrative ;
 *  - la visibilité par objet d'inventaire, désormais nommée explicitement.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Journal d'XP : supprimé, la progression n'est plus tracée par le site.
        Schema::dropIfExists('character_progress_entries');

        // Chaque suppression est gardée : MySQL ne sait pas annuler un DDL, donc
        // une migration interrompue en cours de route doit pouvoir être rejouée
        // sans buter sur une colonne déjà retirée.
        $this->dropColumnsIfPresent('character_attributes', ['development_xp', 'predisposition', 'reveal_state']);
        $this->dropColumnsIfPresent('character_skills', ['development_xp']);
        $this->dropColumnsIfPresent('character_masteries', ['development_xp', 'predisposition']);
        $this->dropColumnsIfPresent('characters', ['level', 'experience']);

        $this->renameInventoryVisibility();
    }

    /** @param  array<int, string>  $columns */
    private function dropColumnsIfPresent(string $table, array $columns): void
    {
        $present = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($table, $column),
        ));

        if ($present === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($present) {
            $blueprint->dropColumn($present);
        });
    }

    /**
     * `secretly_granted` décrivait l'origine de l'objet ; la vue joueur
     * l'affichait malgré tout. On bascule sur une intention explicite —
     * is_visible_to_player — que le présentateur applique réellement.
     */
    private function renameInventoryVisibility(): void
    {
        if (! Schema::hasColumn('inventory_items', 'is_visible_to_player')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->boolean('is_visible_to_player')->default(true)->after('equipped');
            });
        }

        if (Schema::hasColumn('inventory_items', 'secretly_granted')) {
            DB::table('inventory_items')
                ->where('secretly_granted', true)
                ->update(['is_visible_to_player' => false]);

            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('secretly_granted');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('secretly_granted')->default(false)->after('equipped');
        });

        DB::table('inventory_items')
            ->where('is_visible_to_player', false)
            ->update(['secretly_granted' => true]);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('is_visible_to_player');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedSmallInteger('level')->default(1)->after('background');
            $table->unsignedInteger('experience')->default(0)->after('level');
        });

        Schema::table('character_masteries', function (Blueprint $table) {
            $table->unsignedInteger('development_xp')->default(0);
            $table->smallInteger('predisposition')->default(0);
        });

        Schema::table('character_skills', function (Blueprint $table) {
            $table->unsignedInteger('development_xp')->default(0);
        });

        Schema::table('character_attributes', function (Blueprint $table) {
            $table->unsignedInteger('development_xp')->default(0);
            $table->smallInteger('predisposition')->default(0);
            $table->string('reveal_state', 16)->default('hidden');
        });

        Schema::create('character_progress_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entry_type', 32)->default('note');
            $table->integer('amount')->default(0);
            $table->text('reason')->nullable();
            $table->boolean('visible_to_player')->default(false);
            $table->timestamps();
        });
    }
};
