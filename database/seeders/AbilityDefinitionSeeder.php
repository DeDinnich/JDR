<?php

namespace Database\Seeders;

use App\Enums\AbilityType;
use App\Models\AbilityDefinition;
use App\Models\MasteryDefinition;
use Illuminate\Database\Seeder;

/**
 * Sorts et techniques de départ.
 *
 * Le rang minimum est un index dans config('jdr.character.mastery_ranks') :
 * 0 = Novice, 1 = Intermédiaire, 2 = Avancé...
 */
class AbilityDefinitionSeeder extends Seeder
{
    /** [code, nom, type, maîtrise, rang mini, coût en mana, description, détails] */
    private const ABILITIES = [
        // Magie de l'eau
        ['creation-eau', 'Création d’eau', AbilityType::Spell, 'magie-eau', 0, 2, 'Faire apparaître un peu d’eau pure dans la paume.', ['portee' => 'Contact']],
        ['projectile-eau', 'Projectile d’eau', AbilityType::Spell, 'magie-eau', 1, 5, 'Projeter une masse d’eau compacte.', ['portee' => '15 m']],
        ['mur-eau', 'Mur d’eau', AbilityType::Spell, 'magie-eau', 2, 12, 'Dresser une paroi mouvante qui absorbe les chocs.', ['portee' => '5 m', 'duree' => 'Quelques instants']],

        // Magie du feu
        ['etincelle', 'Étincelle', AbilityType::Spell, 'magie-feu', 0, 1, 'Allumer une flamme au bout des doigts.', ['portee' => 'Contact']],
        ['boule-de-feu', 'Boule de feu', AbilityType::Spell, 'magie-feu', 1, 8, 'Concentrer et lancer une sphère de flammes.', ['portee' => '20 m']],

        // Soin
        ['fermeture-plaie', 'Fermeture de plaie', AbilityType::Spell, 'magie-soin', 0, 4, 'Refermer une coupure nette.', ['portee' => 'Contact']],

        // Techniques martiales
        ['frappe-rapide', 'Frappe rapide', AbilityType::Technique, 'epee', 0, null, 'Une attaque courte, avant que l’adversaire ne se replace.', []],
        ['parade', 'Parade', AbilityType::Technique, 'epee', 0, null, 'Dévier la lame plutôt que l’arrêter.', []],
        ['attaque-circulaire', 'Attaque circulaire', AbilityType::Technique, 'epee', 1, null, 'Balayage large pour tenir plusieurs adversaires à distance.', []],
        ['tir-instinctif', 'Tir instinctif', AbilityType::Technique, 'arc', 1, null, 'Décocher sans viser consciemment.', []],
    ];

    public function run(): void
    {
        $masteries = MasteryDefinition::query()->whereNull('character_id')->pluck('id', 'code');

        foreach (self::ABILITIES as $index => [$code, $name, $type, $masteryCode, $rank, $manaCost, $description, $details]) {
            AbilityDefinition::query()->updateOrCreate(
                ['character_id' => null, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'type' => $type,
                    'mastery_definition_id' => $masteries[$masteryCode] ?? null,
                    'minimum_rank_index' => $rank,
                    'mana_cost' => $manaCost,
                    'details' => $details ?: null,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
