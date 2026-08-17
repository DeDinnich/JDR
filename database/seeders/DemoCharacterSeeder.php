<?php

namespace Database\Seeders;

use App\Enums\RevealState;
use App\Enums\UserRole;
use App\Models\AbilityDefinition;
use App\Models\AttributeDefinition;
use App\Models\Character;
use App\Models\MagicSchool;
use App\Models\MasteryDefinition;
use App\Models\SkillDefinition;
use App\Models\User;
use App\Services\Campaign\CharacterCreationService;
use App\Services\CharacterSheet\CharacterSheetBuilder;
use Illuminate\Database\Seeder;

/**
 * Personnage de démonstration : Kael, six ans.
 *
 * Sert à comparer immédiatement la vue joueur et la vue MJ. Kael sait qu'il est
 * adroit et vif d'esprit ; il ignore encore tout du potentiel magique
 * considérable que le MJ lui a attribué, et de son affinité pour l'eau.
 *
 * Lancement :  php artisan db:seed --class=DemoCharacterSeeder
 */
class DemoCharacterSeeder extends Seeder
{
    public function run(CharacterSheetBuilder $builder): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'kael@demo.test'],
            [
                'name' => 'Kael',
                'password' => 'DemoKael123',
                'role' => UserRole::Player,
                'email_verified_at' => now(),
            ]
        );

        $character = Character::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Kael Vantriss',
                'first_name' => 'Kael',
                'last_name' => 'Vantriss',
                'age_years' => 6,
                'birth_date' => now()->subYears(6)->startOfYear(),
                'gender' => 'Garçon',
                'ancestry' => 'Humain',
                'origin' => 'Hameau de Bruyère-Basse',
                'current_location' => 'Ferme familiale',
                'occupation' => null,
                'archetype' => null,
                'background' => 'Enfant de fermiers',
                'health' => 9,
                'max_health' => 12,
                'mana_current' => 20,
                'armor' => 0,
                'gold' => 3,
                'status' => 'En forme',
                'biography' => 'Kael passe ses journées entre la grange et le ruisseau qui borde le champ nord. '
                    ."Il parle peu, observe beaucoup, et sa mère répète qu'il apprend les choses trop vite pour son âge.",
                'traits' => "Gaucher. Une cicatrice fine au genou droit. Dort mal les nuits d'orage.",
            ]
        );

        // Crée les lignes de fiche manquantes, toutes cachées par défaut.
        $builder->initialize($character);

        // Une origine est indispensable : sans elle, le middleware renvoie le
        // compte de démo vers l'écran de naissance et rien n'est consultable.
        // On passe par le service pour que Kael rencontre aussi ses parents et
        // que son glossaire ne soit pas vide.
        if ($character->house_id === null) {
            app(CharacterCreationService::class)->chooseOrigin($character, $user, 'aerendis');
        }

        $this->setAttributes($character);
        $this->revealSomeSkills($character);
        $this->setAffinities($character);
        $this->addMasteryAndAbilities($character, $builder);
        $this->addState($character);
        $this->addInventory($character);
    }

    /**
     * Valeurs réelles décidées par le MJ. Seules DEX et INT ont été révélées à
     * Kael : le reste de la fiche lui apparaît en « ? ».
     */
    private function setAttributes(Character $character): void
    {
        // Kael a six ans : tout est au plancher. Seules la dextérité et
        // l'intelligence, que sa mère remarque, dépassent d'un point — ce qui
        // fait 10 % au D100 sur les compétences concernées, contre 5 % ailleurs.
        $values = [
            'for' => 1,
            'end' => 1,
            'dex' => 2,
            'int' => 2,
            'cha' => 1,
            'man' => 1,
        ];

        $definitions = AttributeDefinition::query()->pluck('id', 'code');

        foreach ($values as $code => $value) {
            if (! isset($definitions[$code])) {
                continue;
            }

            $character->attributes()
                ->where('attribute_definition_id', $definitions[$code])
                ->update(['value' => $value]);
        }
    }

    /** Une aptitude que Kael n'a pas encore découverte reste cachée. */
    private function revealSomeSkills(Character $character): void
    {
        // Les compétences sont visibles par défaut ; on en dissimule quelques-unes
        // pour illustrer la découverte narrative côté MJ.
        $hidden = ['detection-mana', 'manipulation-mana'];

        $definitions = SkillDefinition::query()->whereNull('character_id')->pluck('id', 'code');

        $character->skills()
            ->whereIn('skill_definition_id', collect($hidden)->map(fn ($code) => $definitions[$code] ?? 0))
            ->update(['reveal_state' => RevealState::Hidden]);

        // Un léger talent manuel, accordé par le MJ.
        if (isset($definitions['artisanat-construction'])) {
            $character->skills()
                ->where('skill_definition_id', $definitions['artisanat-construction'])
                ->update(['bonus' => 1, 'gm_notes' => 'Bricole sans arrêt avec les outils de son père.']);
        }
    }

    /** Affinité très forte pour l'eau — totalement inconnue de l'enfant. */
    private function setAffinities(Character $character): void
    {
        $schools = MagicSchool::query()->pluck('id', 'code');

        $levels = [
            'eau' => [4, RevealState::Hidden, 'Affinité exceptionnelle. Se manifestera près du ruisseau.'],
            'air' => [3, RevealState::Hidden, null],
            'feu' => [2, RevealState::Hidden, null],
            'terre' => [1, RevealState::Hidden, null],
            'soin' => [0, RevealState::Hidden, 'Jamais testée.'],
        ];

        foreach ($levels as $code => [$level, $state, $notes]) {
            if (! isset($schools[$code])) {
                continue;
            }

            $character->affinities()
                ->where('magic_school_id', $schools[$code])
                ->update(['affinity_level' => $level, 'reveal_state' => $state, 'gm_notes' => $notes]);
        }
    }

    private function addMasteryAndAbilities(Character $character, CharacterSheetBuilder $builder): void
    {
        $water = MasteryDefinition::query()->whereNull('character_id')->where('code', 'magie-eau')->first();

        if ($water) {
            $mastery = $builder->attachMastery($character, $water);
            $mastery->update([
                'rank_index' => 0, // Novice
                'progress' => 35,
                'reveal_state' => RevealState::Hidden,
                'gm_notes' => 'A fait trembler la surface du seau sans le toucher. Personne ne l’a vu.',
            ]);
        }

        // Une capacité déjà acquise mais que Kael n'a pas encore comprise.
        $spell = AbilityDefinition::query()->whereNull('character_id')->where('code', 'creation-eau')->first();

        if ($spell) {
            $builder->attachAbility($character, $spell)->update([
                'unlocked' => true,
                'reveal_state' => RevealState::Hidden,
                'gm_notes' => 'Se déclenche seul quand il a soif. Il croit que c’est la pluie.',
            ]);
        }
    }

    private function addState(Character $character): void
    {
        $character->states()->updateOrCreate(
            ['name' => 'Fatigué'],
            [
                'description' => 'A veillé une partie de la nuit pour observer l’orage.',
                'icon' => '☾',
                'duration_label' => 'Jusqu’au prochain repos',
                'visible_to_player' => true,
                'is_active' => true,
                'modifiers' => ['end' => -1, 'dex' => -1],
            ]
        );
    }

    /** Quelques possessions, dont un objet que Kael ignore transporter. */
    private function addInventory(Character $character): void
    {
        $items = [
            ['Couteau de poche', 'Outils', 'Lame ébréchée, manche en corne.', 1, true, true],
            ['Bille de verre bleue', 'Curiosités', 'Trouvée dans le lit du ruisseau.', 1, false, true],
            // Objet caché : le MJ sait, le joueur non. Il ne part pas au front.
            ['Lettre cousue dans la doublure', 'Secrets', "Un pli scellé, glissé dans son sac sans qu'il le sache.", 1, false, false],
        ];

        foreach ($items as [$name, $category, $description, $quantity, $equipped, $visible]) {
            $character->inventoryItems()->updateOrCreate(
                ['name' => $name],
                [
                    'category' => $category,
                    'description' => $description,
                    'quantity' => $quantity,
                    'equipped' => $equipped,
                    'is_visible_to_player' => $visible,
                ]
            );
        }
    }
}
