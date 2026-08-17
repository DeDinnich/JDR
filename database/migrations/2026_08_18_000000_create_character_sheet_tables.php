<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fiche personnage approfondie.
 *
 * Architecture retenue : des tables de DÉFINITION (le catalogue de la
 * campagne : caractéristiques, compétences, maîtrises, écoles de magie,
 * capacités) et des tables de DONNÉES PERSONNAGE qui les référencent avec
 * leurs métadonnées propres (valeur, XP, prédisposition, état de révélation).
 *
 * Conséquences voulues :
 *  - ajouter une compétence, une maîtrise ou une école de magie = insérer une
 *    ligne de définition, pas une migration ;
 *  - aucune table à 80 colonnes du type strength / strength_xp / strength_hidden ;
 *  - pas d'EAV non plus : chaque table reste typée et lisible.
 *
 * Les définitions portent un `character_id` nullable : NULL = définition
 * globale de campagne, renseigné = définition sur mesure créée par le MJ pour
 * un seul personnage (compétence exotique, technique unique...).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createDefinitionTables();
        $this->extendCharactersTable();
        $this->upgradeCharacterAttributes();
        $this->createCharacterSheetTables();

        // L'ancienne table `skills` stockait des compétences libres saisies à la
        // main par le MJ, sans lien avec les caractéristiques. Elle est
        // entièrement remplacée par skill_definitions + character_skills.
        Schema::dropIfExists('skills');
    }

    private function createDefinitionTables(): void
    {
        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->string('abbreviation', 8);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('magic_schools', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 16)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('skill_definitions', function (Blueprint $table) {
            $table->id();
            // NULL = compétence commune à toute la campagne.
            $table->foreignId('character_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 32)->default('physique');
            $table->foreignId('primary_attribute_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->foreignId('secondary_attribute_id')->nullable()->constrained('attribute_definitions')->nullOnDelete();
            // Clé de formule interprétée par StatFormulaService (défaut : moyenne).
            $table->string('formula', 32)->default('average');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['character_id', 'code']);
            $table->index(['category', 'sort_order']);
        });

        Schema::create('mastery_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 32)->default('autre');
            // Une maîtrise magique peut être rattachée à une école (affinité).
            $table->foreignId('magic_school_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['character_id', 'code']);
            $table->index('category');
        });

        Schema::create('ability_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 32)->default('sort');
            $table->foreignId('mastery_definition_id')->nullable()->constrained()->nullOnDelete();
            // Index de rang minimum dans config('jdr.character.mastery_ranks').
            $table->unsignedTinyInteger('minimum_rank_index')->nullable();
            $table->unsignedSmallInteger('mana_cost')->nullable();
            // Champ d'extension : portée, durée, composantes, effets chiffrés...
            $table->json('details')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['character_id', 'code']);
            $table->index('type');
        });
    }

    private function extendCharactersTable(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            // Identité — le personnage commence bébé : presque tout est nullable.
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('nickname')->nullable()->after('last_name');
            $table->string('portrait_path')->nullable()->after('nickname');
            $table->string('gender', 32)->nullable()->after('portrait_path');
            $table->date('birth_date')->nullable()->after('gender');
            $table->unsignedSmallInteger('age_years')->default(0)->after('birth_date');
            $table->string('origin')->nullable()->after('ancestry');
            $table->string('current_location')->nullable()->after('origin');
            $table->string('occupation')->nullable()->after('current_location');
            $table->string('adventurer_title')->nullable()->after('occupation');
            $table->text('traits')->nullable()->after('biography');

            // Ressources magiques. mana_max est NULL par défaut : la valeur est
            // alors dérivée de MAN par StatFormulaService. Le MJ peut forcer
            // une valeur en la renseignant.
            $table->unsignedSmallInteger('mana_current')->default(0)->after('max_health');
            $table->unsignedSmallInteger('mana_max')->nullable()->after('mana_current');
        });

        // Un bébé n'a ni classe ni ascendance déclarée : ces champs deviennent
        // facultatifs sans casser les personnages déjà créés.
        Schema::table('characters', function (Blueprint $table) {
            $table->string('archetype')->nullable()->change();
            $table->string('ancestry')->nullable()->change();
        });
    }

    private function upgradeCharacterAttributes(): void
    {
        Schema::table('character_attributes', function (Blueprint $table) {
            $table->foreignId('attribute_definition_id')->nullable()->after('character_id')
                ->constrained()->cascadeOnDelete();
            $table->unsignedInteger('development_xp')->default(0)->after('value');
            $table->smallInteger('predisposition')->default(0)->after('development_xp');
            $table->string('reveal_state', 16)->default('hidden')->after('modifier');
        });

        // Reprise des lignes existantes : on rattache chaque caractéristique à
        // sa définition via l'abréviation, en créant la définition au besoin.
        $existing = DB::table('character_attributes')->get();

        if ($existing->isNotEmpty()) {
            foreach ($existing->unique('abbreviation') as $row) {
                $code = mb_strtolower($row->abbreviation);

                $definitionId = DB::table('attribute_definitions')->where('code', $code)->value('id')
                    ?? DB::table('attribute_definitions')->insertGetId([
                        'code' => $code,
                        'name' => $row->name,
                        'abbreviation' => $row->abbreviation,
                        'sort_order' => $row->sort_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('character_attributes')
                    ->where('abbreviation', $row->abbreviation)
                    ->update(['attribute_definition_id' => $definitionId]);
            }

            // Les fiches déjà jouées gardent leurs valeurs visibles.
            DB::table('character_attributes')->update(['reveal_state' => 'revealed']);
        }

        // Le nouvel index doit exister AVANT la suppression de l'ancien :
        // (character_id, name) est le seul index dont character_id est la
        // colonne de tête, donc InnoDB s'en sert pour porter la clé étrangère
        // character_id. Le supprimer en premier déclenche une erreur 1553.
        Schema::table('character_attributes', function (Blueprint $table) {
            $table->unique(['character_id', 'attribute_definition_id'], 'character_attributes_character_definition_unique');
        });

        Schema::table('character_attributes', function (Blueprint $table) {
            // L'index d'origine peut avoir déjà disparu si la migration a été
            // rejouée après un rollback : on ne le supprime que s'il existe.
            if (Schema::hasIndex('character_attributes', 'character_attributes_character_id_name_unique')) {
                $table->dropUnique(['character_id', 'name']);
            }

            $table->dropColumn(['name', 'abbreviation', 'sort_order']);
        });
    }

    private function createCharacterSheetTables(): void
    {
        Schema::create('character_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_definition_id')->constrained()->cascadeOnDelete();
            // Bonus personnel accordé par le MJ, ajouté à la valeur calculée.
            $table->smallInteger('bonus')->default(0);
            $table->unsignedInteger('development_xp')->default(0);
            $table->string('reveal_state', 16)->default('hidden');
            $table->text('gm_notes')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'skill_definition_id'], 'character_skills_unique');
        });

        Schema::create('character_masteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mastery_definition_id')->constrained()->cascadeOnDelete();
            // Index dans config('jdr.character.mastery_ranks') : 0 = Novice.
            $table->unsignedTinyInteger('rank_index')->default(0);
            // Progression vers le rang suivant, en pourcentage.
            $table->unsignedTinyInteger('progress')->default(0);
            $table->smallInteger('predisposition')->default(0);
            $table->string('reveal_state', 16)->default('hidden');
            $table->text('gm_notes')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'mastery_definition_id'], 'character_masteries_unique');
        });

        Schema::create('character_affinities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('magic_school_id')->constrained()->cascadeOnDelete();
            // Index dans config('jdr.character.affinity_levels') : 0 = Inconnue.
            $table->unsignedTinyInteger('affinity_level')->default(0);
            $table->string('reveal_state', 16)->default('hidden');
            $table->text('gm_notes')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'magic_school_id'], 'character_affinities_unique');
        });

        Schema::create('character_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ability_definition_id')->constrained()->cascadeOnDelete();
            // Le personnage possède-t-il réellement la capacité ?
            $table->boolean('unlocked')->default(true);
            // Une capacité peut être possédée sans que le joueur le sache encore.
            $table->string('reveal_state', 16)->default('hidden');
            $table->text('gm_notes')->nullable();
            $table->timestamps();
            $table->unique(['character_id', 'ability_definition_id'], 'character_abilities_unique');
        });

        Schema::create('character_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 8)->nullable();
            $table->string('duration_label', 64)->nullable();
            $table->boolean('visible_to_player')->default(true);
            $table->boolean('is_active')->default(true);
            // Modificateurs libres : ['for' => -1, 'dex' => -2]
            $table->json('modifiers')->nullable();
            $table->timestamps();
            $table->index(['character_id', 'is_active']);
        });

        Schema::create('character_progress_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entry_type', 32)->default('attribute_xp');
            $table->integer('amount')->default(0);
            $table->string('label')->nullable();
            $table->text('reason')->nullable();
            // Le journal est d'abord un outil MJ ; une entrée peut être ouverte
            // au joueur plus tard.
            $table->boolean('visible_to_player')->default(false);
            $table->timestamps();
            $table->index(['character_id', 'created_at']);
        });
    }

    public function down(): void
    {
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

        Schema::dropIfExists('character_progress_entries');
        Schema::dropIfExists('character_states');
        Schema::dropIfExists('character_abilities');
        Schema::dropIfExists('character_affinities');
        Schema::dropIfExists('character_masteries');
        Schema::dropIfExists('character_skills');

        // Les ajouts et les suppressions de colonnes sont séparés en deux
        // blueprints : dans un blueprint unique, Laravel exécute d'abord les
        // ajouts implicites, ce qui laisserait la table à moitié convertie.
        Schema::table('character_attributes', function (Blueprint $table) {
            $table->dropUnique('character_attributes_character_definition_unique');
            $table->dropConstrainedForeignId('attribute_definition_id');
            $table->dropColumn(['development_xp', 'predisposition', 'reveal_state']);
        });

        Schema::table('character_attributes', function (Blueprint $table) {
            $table->string('name')->default('');
            $table->string('abbreviation', 6)->default('');
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        // Restaure l'index unique d'origine, sans quoi rejouer la migration
        // échouerait en tentant de supprimer un index absent.
        Schema::table('character_attributes', function (Blueprint $table) {
            $table->unique(['character_id', 'name']);
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'nickname', 'portrait_path', 'gender', 'birth_date',
                'age_years', 'origin', 'current_location', 'occupation', 'adventurer_title',
                'traits', 'mana_current', 'mana_max',
            ]);
        });

        Schema::dropIfExists('ability_definitions');
        Schema::dropIfExists('mastery_definitions');
        Schema::dropIfExists('skill_definitions');
        Schema::dropIfExists('magic_schools');
        Schema::dropIfExists('attribute_definitions');
    }
};
