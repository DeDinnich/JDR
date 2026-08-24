<?php

namespace App\Services\CharacterSheet;

use App\Enums\RevealState;
use App\Models\Character;
use App\Models\CharacterAbility;
use App\Models\CharacterAffinity;
use App\Models\CharacterAttribute;
use App\Models\CharacterMastery;
use App\Models\CharacterSkill;
use App\Models\CharacterState;
use App\Models\InventoryItem;
use Illuminate\Support\Collection;

/**
 * Construit la représentation de la fiche personnage.
 *
 * ── Point de sécurité central du module ───────────────────────────────────
 * La vue joueur n'est PAS la vue MJ à laquelle on aurait masqué des blocs en
 * CSS. Ce sont deux payloads distincts construits ici : forPlayer() ne place
 * jamais dans le tableau retourné une valeur que le personnage n'a pas encore
 * découverte. Ouvrir les outils de développement sur la fiche joueur ne montre
 * donc rien de plus que ce que la page affiche.
 *
 * Règles appliquées :
 *  - caractéristiques : toujours visibles, valeur comprise. Il n'existe pas de
 *    caractéristique cachée — c'est un choix de game design assumé ;
 *  - compétences, maîtrises, affinités, capacités : absentes du payload tant
 *    qu'elles sont cachées, le joueur ignore jusqu'à leur existence ;
 *  - inventaire : un objet non visible ne part jamais au joueur ;
 *  - notes MJ : jamais envoyées.
 */
class CharacterSheetPresenter
{
    public function __construct(private readonly StatFormulaService $formulas) {}

    /** Relations nécessaires au rendu complet d'une fiche. */
    public const RELATIONS = [
        'user',
        'attributes.definition',
        'skills.definition.primaryAttribute',
        'skills.definition.secondaryAttribute',
        'masteries.definition.magicSchool',
        'affinities.school',
        'abilities.definition.masteryDefinition',
        'states',
        'inventoryItems',
    ];

    /**
     * Fiche destinée au MJ : tout, sans filtre.
     *
     * @return array<string, mixed>
     */
    public function forGameMaster(Character $character): array
    {
        $values = $this->formulas->attributeValues($character);

        return [
            'identity' => $this->identity($character),
            'resources' => $this->resources($character),
            'attributes' => $this->attributes($character, forPlayer: false),
            'skills' => $this->skills($character, $values, forPlayer: false),
            'masteries' => $this->masteries($character, forPlayer: false),
            'affinities' => $this->affinities($character, forPlayer: false),
            'abilities' => $this->abilities($character, forPlayer: false),
            'states' => $this->states($character, forPlayer: false),
            'state_modifiers' => $this->formulas->stateModifiers($character),
            'inventory' => $this->inventory($character, forPlayer: false),
        ];
    }

    /**
     * Fiche destinée au joueur : uniquement ce que son personnage sait de
     * lui-même.
     *
     * @return array<string, mixed>
     */
    public function forPlayer(Character $character): array
    {
        $values = $this->formulas->attributeValues($character);

        return [
            'identity' => $this->identity($character),
            'resources' => $this->resources($character),
            'attributes' => $this->attributes($character, forPlayer: true),
            'skills' => $this->skills($character, $values, forPlayer: true),
            'masteries' => $this->masteries($character, forPlayer: true),
            'affinities' => $this->affinities($character, forPlayer: true),
            'abilities' => $this->abilities($character, forPlayer: true),
            'states' => $this->states($character, forPlayer: true),
            'inventory' => $this->inventory($character, forPlayer: true),
        ];
    }

    /**
     * Fiche d'un compagnon de route, vue par un autre joueur.
     *
     * Volontairement plus étroite que forPlayer() : on montre qui il est, sa
     * forme et ses chiffres — ce que des enfants qui grandissent ensemble
     * finissent par savoir les uns des autres — mais ni son inventaire, ni ses
     * notes, ni ce que le MJ lui garde caché. Les compétences reprennent le
     * filtre de SA fiche : ce qu'il ignore de lui-même ne fuite pas non plus.
     *
     * @return array<string, mixed>
     */
    public function forAlly(Character $character): array
    {
        $values = $this->formulas->attributeValues($character);

        return [
            'id' => $character->id,
            'player_name' => $character->user?->name,
            'house' => $character->house?->name,
            'identity' => $this->identity($character),
            'resources' => $this->resources($character),
            'attributes' => $this->attributes($character, forPlayer: true),
            'skills' => $this->skills($character, $values, forPlayer: true),
            'masteries' => $this->masteries($character, forPlayer: true),
            'states' => $this->states($character, forPlayer: true),
        ];
    }

    /** @return array<string, mixed> */
    private function identity(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->displayName(),
            'first_name' => $character->first_name,
            'last_name' => $character->last_name,
            'nickname' => $character->nickname,
            'portrait_path' => $character->portrait_path,
            'gender' => $character->gender,
            'age_label' => $character->ageLabel(),
            'age_years' => $character->age_years,
            'birth_date' => $character->birth_date,
            'race' => $character->ancestry,
            'origin' => $character->origin,
            'current_location' => $character->current_location,
            'occupation' => $character->occupation,
            'adventurer_title' => $character->adventurer_title,
            'background' => $character->background,
            'biography' => $character->biography,
            'traits' => $character->traits,
            'status' => $character->status,
            'initials' => $this->initials($character),
        ];
    }

    /** @return array<string, mixed> */
    public function resources(Character $character): array
    {
        $manaMax = $this->formulas->manaMax($character);
        $health = min($character->health, $character->max_health);
        $mana = min($character->mana_current, $manaMax);

        return [
            'health' => $health,
            'max_health' => $character->max_health,
            'health_percentage' => $character->max_health > 0
                ? min(100, (int) round(($health / $character->max_health) * 100))
                : 0,
            'mana' => $mana,
            'mana_max' => $manaMax,
            'mana_percentage' => $manaMax > 0
                ? min(100, (int) round(($mana / $manaMax) * 100))
                : 0,
            'armor' => $character->armor,
            'gold' => $character->gold,
        ];
    }

    /**
     * Les six caractéristiques, identiques pour le joueur et le MJ.
     *
     * Il n'y a délibérément aucun filtrage ici : une caractéristique présente
     * sur la fiche est toujours lisible par le joueur, qui peut d'ailleurs la
     * modifier lui-même.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function attributes(Character $character, bool $forPlayer): Collection
    {
        return $character->attributes
            ->filter(fn (CharacterAttribute $attribute) => $attribute->definition !== null)
            ->sortBy(fn (CharacterAttribute $attribute) => $attribute->definition->sort_order)
            ->values()
            ->map(function (CharacterAttribute $attribute) {
                $definition = $attribute->definition;

                return [
                    'id' => $attribute->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'abbreviation' => $definition->abbreviation,
                    'description' => $definition->description,
                    'value' => $attribute->value,
                    'modifier' => $attribute->modifier,
                    'effective_value' => $attribute->effectiveValue(),
                    'display' => (string) $attribute->effectiveValue(),
                ];
            });
    }

    /**
     * Compétences regroupées par catégorie d'affichage.
     *
     * Une compétence cachée est retirée du payload joueur, pas neutralisée :
     * ni son nom, ni sa valeur, ni son existence ne franchissent le serveur.
     * La valeur affichée est toujours base calculée + bonus manuel du MJ.
     *
     * @param  array<string, int>  $attributeValues
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    private function skills(Character $character, array $attributeValues, bool $forPlayer): Collection
    {
        return $character->skills
            ->filter(fn (CharacterSkill $skill) => $skill->definition?->is_active)
            ->when($forPlayer, fn (Collection $items) => $items->filter(
                fn (CharacterSkill $skill) => $skill->reveal_state->isDiscovered()
            ))
            ->sortBy(fn (CharacterSkill $skill) => $skill->definition->sort_order)
            ->map(function (CharacterSkill $skill) use ($attributeValues, $forPlayer) {
                $definition = $skill->definition;
                $value = $this->formulas->skillValue($skill, $attributeValues);

                $row = [
                    'id' => $skill->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'description' => $definition->description,
                    'category' => $definition->category->value,
                    'category_label' => $definition->category->label(),
                    'attributes' => $definition->attributeLabel(),
                    // Décomposition explicite : le joueur comprend d'où sort
                    // son pourcentage sans que la vue ait à calculer. On repart
                    // du service plutôt que de soustraire le bonus, car la
                    // valeur finale est bornée à 0–100.
                    'base_value' => $this->formulas->skillBaseValue($skill, $attributeValues),
                    'gm_bonus' => $skill->bonus,
                    'player_bonus' => $skill->player_bonus,
                    'bonus' => $skill->bonus + $skill->player_bonus,
                    'value' => $value,
                    'display' => $value.' %',
                ];

                return $forPlayer ? $row : [
                    ...$row,
                    'reveal_state' => $skill->reveal_state->value,
                    'gm_notes' => $skill->gm_notes,
                ];
            })
            ->groupBy('category_label');
    }

    /**
     * Maîtrises. Une maîtrise cachée est totalement absente du payload joueur.
     *
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    private function masteries(Character $character, bool $forPlayer): Collection
    {
        return $character->masteries
            ->filter(fn (CharacterMastery $mastery) => $mastery->definition !== null)
            ->when($forPlayer, fn (Collection $items) => $items->filter(
                fn (CharacterMastery $mastery) => $mastery->reveal_state->isDiscovered()
            ))
            ->sortBy(fn (CharacterMastery $mastery) => $mastery->definition->sort_order)
            ->values()
            ->map(function (CharacterMastery $mastery) use ($forPlayer) {
                $definition = $mastery->definition;

                $row = [
                    'id' => $mastery->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'description' => $definition->description,
                    'category' => $definition->category->value,
                    'category_label' => $definition->category->label(),
                    'school' => $definition->magicSchool?->name,
                    'rank_index' => $mastery->rank_index,
                    'rank_label' => $mastery->rankLabel(),
                    'progress' => $mastery->progress,
                    'reveal_state' => $mastery->reveal_state->value,
                ];

                return $forPlayer ? $row : [...$row, 'gm_notes' => $mastery->gm_notes];
            })
            ->groupBy('category_label');
    }

    /**
     * Affinités magiques. Idem : rien ne part tant que ce n'est pas découvert.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function affinities(Character $character, bool $forPlayer): Collection
    {
        return $character->affinities
            ->filter(fn (CharacterAffinity $affinity) => $affinity->school !== null)
            ->when($forPlayer, fn (Collection $items) => $items->filter(
                fn (CharacterAffinity $affinity) => $affinity->reveal_state->isDiscovered()
            ))
            ->sortBy(fn (CharacterAffinity $affinity) => $affinity->school->sort_order)
            ->values()
            ->map(function (CharacterAffinity $affinity) use ($forPlayer) {
                $row = [
                    'id' => $affinity->id,
                    'school' => $affinity->school->name,
                    'school_code' => $affinity->school->code,
                    'color' => $affinity->school->color,
                    'reveal_state' => $affinity->reveal_state->value,
                    'level_label' => $affinity->levelLabel(),
                    'level' => $affinity->affinity_level,
                ];

                // Une affinité seulement pressentie ne donne pas son niveau exact.
                if ($forPlayer && $affinity->reveal_state === RevealState::Approximate) {
                    return [...$row, 'level' => null, 'level_label' => 'Pressentie'];
                }

                return $forPlayer ? $row : [...$row, 'gm_notes' => $affinity->gm_notes];
            });
    }

    /**
     * Sorts et techniques connus.
     *
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    private function abilities(Character $character, bool $forPlayer): Collection
    {
        return $character->abilities
            ->filter(fn (CharacterAbility $ability) => $ability->definition !== null)
            ->when($forPlayer, fn (Collection $items) => $items->filter(
                fn (CharacterAbility $ability) => $ability->reveal_state->isDiscovered() && $ability->unlocked
            ))
            ->sortBy(fn (CharacterAbility $ability) => $ability->definition->sort_order)
            ->values()
            ->map(function (CharacterAbility $ability) use ($forPlayer) {
                $definition = $ability->definition;

                $row = [
                    'id' => $ability->id,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'description' => $definition->description,
                    'type' => $definition->type->value,
                    'type_label' => $definition->type->label(),
                    'mastery' => $definition->masteryDefinition?->name,
                    'minimum_rank' => $definition->minimumRankLabel(),
                    'mana_cost' => $definition->mana_cost,
                    'details' => $definition->details,
                    'unlocked' => $ability->unlocked,
                    'reveal_state' => $ability->reveal_state->value,
                ];

                return $forPlayer ? $row : [...$row, 'gm_notes' => $ability->gm_notes];
            })
            ->groupBy('type_label');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function states(Character $character, bool $forPlayer): Collection
    {
        return $character->states
            ->when($forPlayer, fn (Collection $items) => $items->filter(
                fn (CharacterState $state) => $state->visible_to_player && $state->is_active
            ))
            ->values()
            ->map(function (CharacterState $state) use ($forPlayer) {
                $row = [
                    'id' => $state->id,
                    'name' => $state->name,
                    'description' => $state->description,
                    'icon' => $state->icon,
                    'duration_label' => $state->duration_label,
                    'is_active' => $state->is_active,
                ];

                return $forPlayer ? $row : [
                    ...$row,
                    'visible_to_player' => $state->visible_to_player,
                    'modifiers' => $state->modifiers,
                    'modifier_summary' => $state->modifierSummary(),
                ];
            });
    }

    /**
     * Inventaire. Un objet marqué non visible — la lettre cousue dans la
     * doublure — est absent du payload joueur : il ne peut donc ni s'afficher,
     * ni être modifié ou supprimé par quelqu'un qui en ignore l'existence.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function inventory(Character $character, bool $forPlayer): Collection
    {
        return $character->inventoryItems
            ->when($forPlayer, fn (Collection $items) => $items->filter(
                fn (InventoryItem $item) => $item->is_visible_to_player
            ))
            ->values()
            ->map(function (InventoryItem $item) use ($forPlayer) {
                $row = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'equipped' => $item->equipped,
                ];

                return $forPlayer ? $row : [...$row, 'is_visible_to_player' => $item->is_visible_to_player];
            });
    }

    private function initials(Character $character): string
    {
        return collect(explode(' ', $character->displayName()))
            ->filter()
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->take(2)
            ->implode('');
    }
}
