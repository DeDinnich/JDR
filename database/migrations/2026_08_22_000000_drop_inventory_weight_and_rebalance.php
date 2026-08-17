<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retrait de la gestion de charge.
 *
 * Personne ne pèse son sac à une table physique : la colonne partait donc
 * alimenter un calcul que la fiche n'a jamais eu besoin d'afficher. Un objet
 * garde son nom, sa catégorie, sa quantité et sa description — c'est tout ce
 * qu'un compagnon de partie doit retenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventory_items', 'weight')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropColumn('weight');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->default(0)->after('quantity');
        });
    }
};
