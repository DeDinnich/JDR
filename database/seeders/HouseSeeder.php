<?php

namespace Database\Seeders;

use App\Models\House;
use Illuminate\Database\Seeder;

/**
 * Origines familiales de la campagne d'Ashura.
 *
 * Les trois grandes maisons entrent dans le tirage aléatoire ; la famille
 * Veyre est marquée `is_reserved` et reste donc invisible pour les joueurs
 * standards. Idempotent : rejouer le seeder met à jour sans dupliquer.
 */
class HouseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->houses() as $house) {
            House::updateOrCreate(['slug' => $house['slug']], $house);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function houses(): array
    {
        return [
            [
                'slug' => 'valtheris',
                'name' => 'Maison Valtheris',
                'motto' => 'Le devoir avant le nom.',
                'public_description' => "Ancienne maison militaire reconnue pour sa fidélité à la couronne, ses officiers et son sens du devoir. On y grandit entre les armes d'apparat et les récits de campagne.",
                'game_master_description' => 'Le père doute en privé du commandement royal et de la tournure de la guerre contre Rania.',
                'color' => '#7f1d1d',
                'reputation' => 'Respectée pour sa loyauté militaire',
                'specialty' => 'Commandement et art de la guerre',
                'is_active' => true,
                'is_reserved' => false,
                'sort_order' => 1,
            ],
            [
                'slug' => 'aerendis',
                'name' => 'Maison Aerendis',
                'motto' => 'Comprendre, puis agir.',
                'public_description' => "Lignée d'érudits et de mages ayant servi la cour pendant plusieurs générations. La demeure familiale tient davantage de la bibliothèque que du manoir.",
                'game_master_description' => "Les deux parents s'intéresseraient de très près à un enfant au potentiel magique inhabituel.",
                'color' => '#1e3a8a',
                'reputation' => 'Réputée pour son savoir magique',
                'specialty' => 'Magie élémentaire et recherche',
                'is_active' => true,
                'is_reserved' => false,
                'sort_order' => 2,
            ],
            [
                'slug' => 'vaelmont',
                'name' => 'Maison Vaelmont',
                'motto' => 'Un mot bien placé vaut mille lames.',
                'public_description' => 'Maison politique influente, très présente à la cour et dans les cercles diplomatiques. On y apprend à écouter avant de parler.',
                'game_master_description' => "Le père entretient une correspondance secrète avec quelqu'un du royaume de Rania. Sa nature exacte reste indéterminée.",
                'color' => '#4c1d95',
                'reputation' => 'Influente à la cour',
                'specialty' => 'Diplomatie et intrigue',
                'is_active' => true,
                'is_reserved' => false,
                'sort_order' => 3,
            ],
            [
                'slug' => 'veyre',
                'name' => 'Famille Veyre',
                'motto' => 'Les livres gardent ce que les hommes oublient.',
                'public_description' => "Famille non noble attachée à la Grande Bibliothèque royale d'Ashura. Une position discrète mais rare, qui ouvre des portes que bien des nobles ne franchissent jamais.",
                'game_master_description' => "Origine réservée, hors tirage. Le père de l'enfant est le roi d'Ashura : secret strictement MJ.",
                'color' => '#155e75',
                'reputation' => 'Discrète, respectée pour son savoir',
                'specialty' => 'Archives et connaissances rares',
                'is_active' => true,
                'is_reserved' => true,
                'sort_order' => 4,
            ],
        ];
    }
}
