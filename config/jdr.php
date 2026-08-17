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
         * On grandit là où l'on naît : un enfant Valtheris passe ses journées
         * dans une cour d'armes, un Aerendis dans une bibliothèque. Les écarts
         * restent légers — c'est un point de départ, pas une classe. Le MJ
         * ajuste ensuite librement.
         *
         * `default` sert de filet si une maison n'a pas d'entrée ici.
         */
        'house_base_stats' => [
            'default' => ['for' => 8, 'end' => 8, 'dex' => 8, 'int' => 8, 'cha' => 8, 'man' => 8],

            // Maison militaire : on y tient une épée avant de tenir un livre.
            'valtheris' => ['for' => 10, 'end' => 10, 'dex' => 9, 'int' => 7, 'cha' => 8, 'man' => 6],

            // Lignée d'érudits et de mages.
            'aerendis' => ['for' => 6, 'end' => 7, 'dex' => 8, 'int' => 11, 'cha' => 9, 'man' => 10],

            // Maison politique : on y apprend à lire les gens.
            'vaelmont' => ['for' => 7, 'end' => 8, 'dex' => 9, 'int' => 9, 'cha' => 11, 'man' => 8],

            // Origine réservée. Un potentiel magique inhabituel, que personne
            // ne lui a expliqué.
            'veyre' => ['for' => 7, 'end' => 9, 'dex' => 10, 'int' => 9, 'cha' => 8, 'man' => 11],
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
        ],
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
