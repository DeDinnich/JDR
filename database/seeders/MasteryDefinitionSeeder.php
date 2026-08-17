<?php

namespace Database\Seeders;

use App\Enums\MasteryCategory;
use App\Models\MagicSchool;
use App\Models\MasteryDefinition;
use Illuminate\Database\Seeder;

/**
 * Maîtrises de départ : armes, disciplines de combat, écoles magiques,
 * artisanats. Le MJ attache ensuite celles qui concernent un personnage.
 */
class MasteryDefinitionSeeder extends Seeder
{
    /** [code, nom, catégorie, école de magie éventuelle, description] */
    private const MASTERIES = [
        ['epee', 'Épée', MasteryCategory::Weapon, null, 'Lame droite, garde, taille et estoc.'],
        ['arc', 'Arc', MasteryCategory::Weapon, null, 'Tir tendu, tir courbe, lecture du vent.'],
        ['lance', 'Lance', MasteryCategory::Weapon, null, 'Allonge, formation, charge.'],
        ['dague', 'Dague', MasteryCategory::Weapon, null, 'Arme courte, rapide, discrète.'],
        ['corps-a-corps', 'Corps à corps', MasteryCategory::Combat, null, 'Lutte, saisies, frappes à mains nues.'],
        ['bouclier', 'Bouclier', MasteryCategory::Combat, null, 'Couvrir, dévier, tenir une ligne.'],
        ['magie-feu', 'Magie du feu', MasteryCategory::Magic, 'feu', 'Appeler et contenir la flamme.'],
        ['magie-eau', 'Magie de l’eau', MasteryCategory::Magic, 'eau', 'Appeler l’eau, la geler, la diriger.'],
        ['magie-terre', 'Magie de la terre', MasteryCategory::Magic, 'terre', 'Soulever la pierre, durcir le sol.'],
        ['magie-air', 'Magie de l’air', MasteryCategory::Magic, 'air', 'Déplacer l’air, étouffer un son, porter une voix.'],
        ['magie-foudre', 'Magie de la foudre', MasteryCategory::Magic, 'foudre', 'Décharge brève et brutale.'],
        ['magie-nature', 'Magie de la nature', MasteryCategory::Magic, 'nature', 'Croissance, apaisement des bêtes.'],
        ['magie-soin', 'Magie du soin', MasteryCategory::Magic, 'soin', 'Refermer, purifier, soutenir un corps.'],
        ['forge', 'Forge', MasteryCategory::Craft, null, 'Travail du métal chaud.'],
        ['herboristerie', 'Herboristerie', MasteryCategory::Craft, null, 'Récolte, séchage, préparations.'],
        ['calligraphie', 'Calligraphie', MasteryCategory::Craft, null, 'Tracé des lettres et des signes.'],
    ];

    public function run(): void
    {
        $schools = MagicSchool::query()->pluck('id', 'code');

        foreach (self::MASTERIES as $index => [$code, $name, $category, $schoolCode, $description]) {
            MasteryDefinition::query()->updateOrCreate(
                ['character_id' => null, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'magic_school_id' => $schoolCode ? ($schools[$schoolCode] ?? null) : null,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
