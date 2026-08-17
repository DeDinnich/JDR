<?php

namespace Database\Seeders;

use App\Models\MagicSchool;
use Illuminate\Database\Seeder;

/**
 * Écoles de magie de l'univers. Ajouter une école plus tard ne demande qu'une
 * ligne supplémentaire ici — aucune migration, aucune modification de vue.
 */
class MagicSchoolSeeder extends Seeder
{
    private const SCHOOLS = [
        ['code' => 'feu', 'name' => 'Feu', 'color' => '#c2643f', 'description' => 'Combustion, chaleur, lumière brûlante.'],
        ['code' => 'eau', 'name' => 'Eau', 'color' => '#4f8ba3', 'description' => 'Flux, glace, brume et courants.'],
        ['code' => 'terre', 'name' => 'Terre', 'color' => '#8a7247', 'description' => 'Pierre, métal brut, racines et stabilité.'],
        ['code' => 'air', 'name' => 'Air', 'color' => '#9fb0a8', 'description' => 'Souffle, pression, vitesse et silence.'],
        ['code' => 'foudre', 'name' => 'Foudre', 'color' => '#c9b45c', 'description' => 'Décharge, réflexe, fulgurance.'],
        ['code' => 'nature', 'name' => 'Nature', 'color' => '#6c9464', 'description' => 'Croissance, bêtes, cycles vivants.'],
        ['code' => 'soin', 'name' => 'Soin / Sacré', 'color' => '#d3c5a0', 'description' => 'Réparation des corps, protection, lumière apaisante.'],
    ];

    public function run(): void
    {
        foreach (self::SCHOOLS as $index => $school) {
            MagicSchool::query()->updateOrCreate(
                ['code' => $school['code']],
                [
                    'name' => $school['name'],
                    'description' => $school['description'],
                    'color' => $school['color'],
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
