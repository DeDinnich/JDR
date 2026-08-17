<?php

namespace Database\Seeders;

use App\Enums\SkillCategory;
use App\Models\AttributeDefinition;
use App\Models\SkillDefinition;
use Illuminate\Database\Seeder;

/**
 * Catalogue des compétences secondaires de la campagne.
 *
 * Chaque entrée déclare simplement ses deux caractéristiques ; la formule de
 * calcul reste dans StatFormulaService. Renommer une compétence, changer ses
 * caractéristiques ou en ajouter une nouvelle se fait ici, sans migration ni
 * retouche de l'interface.
 */
class SkillDefinitionSeeder extends Seeder
{
    /** [code, nom, caractéristique A, caractéristique B, catégorie, description] */
    private const SKILLS = [
        // Physiques
        ['combat-rapproche', 'Combat rapproché', 'for', 'dex', SkillCategory::Physical, 'Se battre au corps à corps, arme en main ou non.'],
        ['combat-distance', 'Combat à distance', 'dex', 'int', SkillCategory::Physical, 'Viser juste à l’arc, à la fronde ou au javelot.'],
        ['athletisme', 'Athlétisme', 'for', 'end', SkillCategory::Physical, 'Courir, grimper, nager, forcer un passage.'],
        ['esquive', 'Esquive', 'dex', 'int', SkillCategory::Physical, 'Lire un mouvement et s’en écarter à temps.'],
        ['reflexes', 'Réflexes', 'dex', 'int', SkillCategory::Physical, 'Réagir avant d’avoir compris.'],
        ['pilotage-monture', 'Pilotage / Monture', 'dex', 'end', SkillCategory::Physical, 'Mener une monture ou un attelage.'],
        ['discretion', 'Discrétion', 'dex', 'cha', SkillCategory::Physical, 'Se déplacer sans être remarqué.'],
        ['vol-pickpocket', 'Vol / Pickpocket', 'dex', 'cha', SkillCategory::Physical, 'Prendre sans être vu.'],
        ['survie', 'Survie', 'end', 'int', SkillCategory::Physical, 'Tenir dehors, se nourrir, s’orienter.'],

        // Sociales
        ['intimidation', 'Intimidation', 'for', 'cha', SkillCategory::Social, 'Imposer sa présence ou sa menace.'],
        ['mentir-convaincre', 'Mentir / Convaincre', 'int', 'cha', SkillCategory::Social, 'Faire accepter sa version des faits.'],
        ['psychologie', 'Psychologie', 'int', 'cha', SkillCategory::Social, 'Comprendre ce que l’autre ne dit pas.'],

        // Connaissances
        ['perception', 'Perception', 'int', 'cha', SkillCategory::Knowledge, 'Remarquer le détail qui cloche.'],
        ['connaissance-nature', 'Connaissance de la nature', 'int', 'end', SkillCategory::Knowledge, 'Plantes, bêtes, saisons et terrains.'],
        ['connaissance-secrets', 'Connaissance des secrets', 'int', 'cha', SkillCategory::Knowledge, 'Rumeurs, cultes, savoirs qu’on ne consigne pas.'],
        ['droit-societe', 'Droit / Société', 'int', 'cha', SkillCategory::Knowledge, 'Coutumes, hiérarchies, lois des royaumes.'],
        ['lire-ecrire', 'Lire / Écrire', 'int', 'cha', SkillCategory::Knowledge, 'Déchiffrer et rédiger.'],
        ['soins', 'Soins', 'int', 'dex', SkillCategory::Knowledge, 'Recoudre, réduire une fracture, arrêter un poison.'],

        // Artisanat
        ['artisanat-construction', 'Artisanat / Construction', 'int', 'dex', SkillCategory::Craft, 'Fabriquer, réparer, bâtir.'],
        ['serrures-pieges', 'Serrures / Pièges', 'dex', 'int', SkillCategory::Craft, 'Ouvrir ce qui est fermé, désarmer ce qui blesse.'],

        // Magie — MAN est le potentiel magique, jamais la réserve de mana.
        ['manipulation-mana', 'Manipulation du mana', 'man', 'int', SkillCategory::Magic, 'Mobiliser et façonner son mana intérieur.'],
        ['controle-magique', 'Contrôle magique', 'man', 'dex', SkillCategory::Magic, 'Doser un sort, le maintenir, l’interrompre.'],
        ['connaissance-magique', 'Connaissance magique', 'int', 'man', SkillCategory::Magic, 'Théorie, écoles, limites et dangers de la magie.'],
        ['detection-mana', 'Détection du mana', 'man', 'int', SkillCategory::Magic, 'Sentir le mana d’un lieu, d’un objet, d’un être.'],
        ['enchantement', 'Enchantement', 'man', 'dex', SkillCategory::Magic, 'Inscrire un effet durable dans la matière.'],
        ['incantation', 'Incantation', 'man', 'cha', SkillCategory::Magic, 'Porter la formule, la voix et l’intention.'],
    ];

    public function run(): void
    {
        $attributes = AttributeDefinition::query()->pluck('id', 'code');

        foreach (self::SKILLS as $index => [$code, $name, $primary, $secondary, $category, $description]) {
            // Une compétence dont les caractéristiques n'existent pas encore est
            // ignorée plutôt que de faire échouer tout le seeding.
            if (! isset($attributes[$primary], $attributes[$secondary])) {
                continue;
            }

            SkillDefinition::query()->updateOrCreate(
                ['character_id' => null, 'code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'category' => $category,
                    'primary_attribute_id' => $attributes[$primary],
                    'secondary_attribute_id' => $attributes[$secondary],
                    'formula' => 'average',
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );
        }
    }
}
