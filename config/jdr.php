<?php

return [
    'admin' => [
        'email' => env('ADMIN_MAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fiche personnage — règles configurables
    |--------------------------------------------------------------------------
    |
    | Tout ce qui touche à la progression cachée des personnages (enfance,
    | prédispositions, maîtrises, affinités magiques...) est centralisé ici et
    | dans les services du dossier App\Services\CharacterSheet. Rien n'est
    | codé en dur dans les vues : modifier ces tableaux ou les formules des
    | services suffit à faire évoluer la mécanique.
    |
    */
    'character' => [

        // Les 6 caractéristiques principales. L'ordre définit l'ordre d'affichage.
        // Ajouter une entrée ici + relancer le seeder AttributeDefinitionSeeder
        // suffit à ajouter une nouvelle caractéristique, sans migration.
        'attributes' => [
            ['code' => 'for', 'name' => 'Force', 'abbreviation' => 'FOR', 'description' => 'Puissance physique : porter, frapper, pousser, lutter.'],
            ['code' => 'end', 'name' => 'Endurance', 'abbreviation' => 'END', 'description' => 'Résistance physique, constitution, capacité à tenir un effort.'],
            ['code' => 'dex', 'name' => 'Dextérité', 'abbreviation' => 'DEX', 'description' => 'Agilité, précision, coordination, vitesse des mouvements.'],
            ['code' => 'int', 'name' => 'Intelligence', 'abbreviation' => 'INT', 'description' => 'Raisonnement, mémoire, compréhension, apprentissage.'],
            ['code' => 'cha', 'name' => 'Charisme', 'abbreviation' => 'CHA', 'description' => 'Présence, persuasion, intimidation, lecture des autres.'],
            ['code' => 'man', 'name' => 'Mana', 'abbreviation' => 'MAN', 'description' => "Potentiel magique naturel (n'est pas la réserve de mana actuelle)."],
        ],

        /*
         * Caractéristiques de départ selon l'origine familiale.
         *
         * Ce sont des enfants de huit ans : tout démarre à 1. Seules les deux
         * caractéristiques que la famille cultive vraiment partent à 2 — on
         * grandit là où l'on naît, mais à cet âge cela ne fait qu'une nuance.
         *
         * Traduit en compétences (× 5), cela donne 5 % partout et 10 % sur ce
         * que la maison enseigne. C'est volontairement très bas : au D100,
         * presque tout échoue, et chaque point gagné se sentira.
         *
         * `base` est la valeur de départ commune ; `strengths` liste les deux
         * caractéristiques portées à `bonus`.
         */
        'house_base_stats' => [
            'base' => 1,
            'bonus' => 2,

            'strengths' => [
                // Maison militaire : la cour d'armes avant la bibliothèque.
                'valtheris' => ['for', 'end'],

                // Lignée d'érudits et de mages.
                'aerendis' => ['int', 'man'],

                // Maison politique : on y apprend à lire les gens.
                'vaelmont' => ['cha', 'int'],

                // Origine réservée. Un potentiel magique que personne ne lui a
                // expliqué, et l'agilité d'une enfant qui grandit dehors.
                'veyre' => ['man', 'dex'],
            ],
        ],

        // États de visibilité d'une compétence, maîtrise, affinité ou capacité.
        // Les caractéristiques principales n'en ont pas : elles sont toujours
        // visibles par le joueur (voir CharacterSheetPresenter).
        'reveal_states' => [
            'hidden' => 'Inconnue',
            'approximate' => 'Approximative',
            'revealed' => 'Révélée',
        ],

        // Catégories utilisées pour regrouper les compétences secondaires à l'affichage.
        'skill_categories' => [
            'physique' => 'Physiques',
            'sociale' => 'Sociales',
            'connaissance' => 'Connaissances',
            'magie' => 'Magie',
            'artisanat' => 'Artisanat',
        ],

        // Rangs de maîtrise génériques, du plus faible au plus élevé.
        // L'index dans ce tableau est stocké en base (rank_index) : ajouter un
        // rang à la fin n'importe quand ne casse rien.
        'mastery_ranks' => [
            'Novice',
            'Intermédiaire',
            'Avancé',
            'Saint',
            'Roi',
            'Empereur',
            'Divin',
        ],

        'mastery_categories' => [
            'magie' => 'Magie',
            'arme' => 'Arme',
            'combat' => 'Combat',
            'artisanat' => 'Artisanat',
            'autre' => 'Autre',
        ],

        // Niveaux d'affinité magique, du plus faible au plus fort.
        'affinity_levels' => [
            'Inconnue',
            'Faible',
            'Normale',
            'Bonne',
            'Excellente',
        ],

        'ability_types' => [
            'sort' => 'Sort',
            'technique' => 'Technique martiale',
            'capacite' => 'Capacité spéciale',
            'talent' => 'Talent',
            'autre' => 'Autre',
        ],

        // Suggestions d'états rapides pour le MJ (voir CharacterStateService).
        // Le MJ peut toujours créer un état libre non listé ici.
        'state_presets' => [
            ['name' => 'Blessé', 'icon' => '♦', 'visible_to_player' => true],
            ['name' => 'Malade', 'icon' => '☓', 'visible_to_player' => true],
            ['name' => 'Fatigué', 'icon' => '☾', 'visible_to_player' => true],
            ['name' => 'Empoisonné', 'icon' => '☠', 'visible_to_player' => true],
            ['name' => 'Béni', 'icon' => '✦', 'visible_to_player' => true],
            ['name' => 'Maudit', 'icon' => '✖', 'visible_to_player' => true],
            ['name' => 'Effrayé', 'icon' => '!', 'visible_to_player' => true],
        ],

        // Formules de calcul — volontairement de simples nombres pour rester
        // faciles à ajuster. La logique qui les consomme vit dans
        // App\Services\CharacterSheet\StatFormulaService.
        'formulas' => [
            // Mana maximum = valeur de MAN * multiplicateur + bonus fixe. Le MJ
            // peut toujours forcer une valeur en renseignant mana_max.
            'mana_max_per_man' => 4,
            'mana_max_flat_bonus' => 0,

            /*
             * Compétences : un pourcentage de réussite, testé au D100.
             *
             * La moyenne des caractéristiques est multipliée par ce facteur
             * pour obtenir le pourcentage. Avec 5, une caractéristique de 20
             * (le plafond humain) donne 100 % : l'échelle des caractéristiques
             * et celle des compétences restent ainsi cohérentes.
             *
             * Des enfants de huit ans démarrent à 1, soit 5 % — c'est voulu :
             * ils ratent presque tout, et chaque point gagné se sent.
             */
            'skill_percentage_per_point' => 5,
            'skill_percentage_min' => 0,
            'skill_percentage_max' => 100,

            /*
             * Coup de pouce des débuts.
             *
             * En dessous du seuil, la compétence reçoit le bonus : des enfants
             * partiraient sinon à 5 %, ce qui ne se joue pas. Avec 20/20, ils
             * démarrent autour de 25–30 %.
             *
             * ⚠ La règle n'est pas un plancher : elle ajoute. Une compétence
             * calculée à 19 ressort donc à 39, tandis qu'une calculée à 20
             * reste à 20. Sans effet tant que les caractéristiques sont basses,
             * mais à surveiller quand elles monteront — passer à un plancher
             * sec revient à remplacer l'addition par un max() dans
             * StatFormulaService::skillBaseValue().
             */
            'skill_low_threshold' => 20,
            'skill_low_bonus' => 20,
        ],

        // Plafond d'une caractéristique principale. Les compétences, elles,
        // s'expriment en pourcentage et plafonnent à 100.
        'attribute_max' => 18,
    ],

    /*
    |--------------------------------------------------------------------------
    | Campagne — origines, maisons nobles et PNJ
    |--------------------------------------------------------------------------
    |
    | Les joueurs renaissent bébés dans le royaume d'Ashura. Trois d'entre eux
    | tirent au sort une grande maison noble ; un compte particulier reçoit une
    | origine réservée qui ne doit jamais apparaître dans le tirage. Les maisons
    | elles-mêmes vivent en base (table houses + HouseSeeder) pour pouvoir en
    | ajouter sans toucher à l'interface.
    |
    */
    'campaign' => [

        // Compte recevant l'origine réservée (famille Veyre). Ce joueur ne voit
        // jamais le tirage des grandes maisons. Le rapprochement se fait sur
        // l'e-mail, insensible à la casse (voir HouseAssignmentService).
        'special_origin' => [
            'email' => env('SPECIAL_ORIGIN_MAIL', 'jadelang25@gmail.com'),
            'house_slug' => 'veyre',
        ],

        // Statuts possibles d'un PNJ.
        'npc_statuses' => [
            'vivant' => 'Vivant',
            'mort' => 'Mort',
            'disparu' => 'Disparu',
            'inconnu' => 'Inconnu',
        ],

        // Importance narrative : sert au tri et au filtrage dans l'espace MJ.
        'npc_importances' => [
            'figurant' => 'Figurant',
            'secondaire' => 'Secondaire',
            'majeur' => 'Majeur',
            'central' => 'Central',
        ],

        // Catégories d'informations révélables progressivement sur un PNJ.
        'npc_information_categories' => [
            'identite' => 'Identité',
            'relation' => 'Relation',
            'profession' => 'Profession',
            'rumeur' => 'Rumeur',
            'histoire' => 'Histoire',
            'autre' => 'Autre',
        ],
    ],
];
