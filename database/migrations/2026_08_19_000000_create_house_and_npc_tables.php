<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Origines nobles et base de PNJ.
 *
 * Deux principes structurent ce lot :
 *
 *  - une maison est une donnée, pas du code : ajouter une grande famille se
 *    fait par une ligne dans `houses`, sans migration ni retouche des vues ;
 *  - tout ce qui est secret vit dans une table séparée (npc_secrets) ou
 *    derrière un pivot de révélation (npc_information_user), jamais dans une
 *    colonne que l'on risquerait d'envoyer par mégarde au joueur.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createHouses();
        $this->linkCharactersToHouses();
        $this->extendNpcs();
        $this->createNpcKnowledgeTables();
    }

    private function createHouses(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('motto')->nullable();

            // Ce que le joueur peut lire avant même d'avoir reçu son origine.
            $table->text('public_description')->nullable();
            // Réservé au MJ : ne doit jamais partir dans un payload joueur.
            $table->text('game_master_description')->nullable();

            $table->string('emblem_path')->nullable();
            // Couleur d'accent de la carte de maison (hex court ou long).
            $table->string('color', 16)->nullable();
            $table->string('reputation')->nullable();
            $table->string('specialty')->nullable();

            $table->boolean('is_active')->default(true);
            // Une maison réservée est exclue du tirage aléatoire : c'est le cas
            // de la famille Veyre, attribuée nominativement à un seul compte.
            $table->boolean('is_reserved')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_reserved']);
        });
    }

    private function linkCharactersToHouses(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->after('user_id')
                ->constrained()->nullOnDelete();
        });
    }

    private function extendNpcs(): void
    {
        // La table npcs existait déjà avec name / role / description /
        // game_master_notes / portrait_path. On l'enrichit sans rien retirer :
        // `description` reste la description publique et `game_master_notes`
        // les notes privées, les vues actuelles continuent de fonctionner.
        Schema::table('npcs', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->after('location_id')
                ->constrained()->nullOnDelete();

            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('nickname')->nullable()->after('last_name');
            $table->string('title')->nullable()->after('nickname');
            $table->unsignedSmallInteger('age')->nullable()->after('title');
            $table->string('gender', 32)->nullable()->after('age');
            $table->string('race')->nullable()->after('gender');
            $table->string('profession')->nullable()->after('race');
            // Rôle familial vis-à-vis d'un personnage joueur : pere, mere...
            $table->string('family_role', 32)->nullable()->after('profession');

            $table->text('personality')->nullable()->after('description');
            $table->string('status', 32)->default('vivant')->after('personality');
            $table->string('importance', 32)->default('secondaire')->after('status');
            $table->json('tags')->nullable()->after('importance');

            $table->index('importance');
            $table->index('status');
        });
    }

    private function createNpcKnowledgeTables(): void
    {
        // Secrets strictement MJ. Table à part : aucun scope joueur ne la
        // charge, donc aucun risque de fuite par un with() distrait.
        Schema::create('npc_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Informations que le MJ dévoile au compte-gouttes. Une information
        // n'est visible que par les joueurs présents dans le pivot ci-dessous.
        Schema::create('npc_informations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('category', 32)->default('autre');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['npc_id', 'sort_order']);
        });

        Schema::create('npc_information_user', function (Blueprint $table) {
            $table->foreignId('npc_information_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('revealed_at')->useCurrent();
            $table->primary(['npc_information_id', 'user_id'], 'npc_information_user_primary');
        });

        // Relations entre PNJ (père de, sert, rival de...). Une relation peut
        // elle-même être un secret MJ.
        Schema::create('npc_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_npc_id')->constrained('npcs')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_secret')->default(false);
            $table->timestamps();

            $table->unique(['npc_id', 'related_npc_id', 'label'], 'npc_relations_unique');
        });

        // Lien direct parent -> personnage joueur, pour afficher les parents
        // sur la fiche de l'enfant sans passer par la maison.
        Schema::create('character_npc', function (Blueprint $table) {
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('npc_id')->constrained()->cascadeOnDelete();
            $table->string('relation', 32)->default('parent');
            $table->primary(['character_id', 'npc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_npc');
        Schema::dropIfExists('npc_relations');
        Schema::dropIfExists('npc_information_user');
        Schema::dropIfExists('npc_informations');
        Schema::dropIfExists('npc_secrets');

        Schema::table('npcs', function (Blueprint $table) {
            $table->dropIndex(['importance']);
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('house_id');
            $table->dropColumn([
                'first_name', 'last_name', 'nickname', 'title', 'age', 'gender',
                'race', 'profession', 'family_role', 'personality', 'status',
                'importance', 'tags',
            ]);
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('house_id');
        });

        Schema::dropIfExists('houses');
    }
};
