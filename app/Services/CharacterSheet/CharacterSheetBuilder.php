<?php

namespace App\Services\CharacterSheet;

use App\Enums\RevealState;
use App\Models\AbilityDefinition;
use App\Models\AttributeDefinition;
use App\Models\Character;
use App\Models\CharacterAbility;
use App\Models\CharacterAffinity;
use App\Models\CharacterMastery;
use App\Models\MagicSchool;
use App\Models\MasteryDefinition;
use App\Models\SkillDefinition;
use Illuminate\Support\Facades\DB;

/**
 * Prépare la fiche d'un personnage à partir du catalogue de la campagne.
 *
 * Un nouveau-né arrive avec toutes ses caractéristiques et compétences en
 * place mais entièrement inconnues de lui : c'est le jeu qui les révélera.
 * Appelé à l'inscription d'un joueur, et à nouveau après l'ajout d'une
 * définition au catalogue pour rattraper les fiches existantes.
 */
class CharacterSheetBuilder
{
    /**
     * Crée les lignes manquantes de la fiche sans jamais écraser l'existant.
     *
     * @param  int  $baseValue  valeur de départ des caractéristiques
     */
    public function initialize(Character $character, int $baseValue = 1): Character
    {
        return DB::transaction(function () use ($character, $baseValue): Character {
            $this->syncAttributes($character, $baseValue);
            $this->syncSkills($character);
            $this->syncAffinities($character);

            return $character->refresh();
        });
    }

    /** Crée les caractéristiques absentes. Elles sont toujours visibles. */
    public function syncAttributes(Character $character, int $baseValue = 1): void
    {
        $existing = $character->attributes()->pluck('attribute_definition_id')->all();

        AttributeDefinition::query()
            ->whereNotIn('id', $existing ?: [0])
            ->orderBy('sort_order')
            ->get()
            ->each(fn (AttributeDefinition $definition) => $character->attributes()->create([
                'attribute_definition_id' => $definition->id,
                'value' => $baseValue,
                'modifier' => 0,
            ]));
    }

    /**
     * Crée les compétences absentes, visibles par défaut.
     *
     * Une compétence se calcule à partir des caractéristiques, que le joueur
     * connaît : la cacher est l'exception, décidée au cas par cas par le MJ
     * pour une aptitude que le personnage n'a pas encore découverte.
     */
    public function syncSkills(Character $character): void
    {
        $existing = $character->skills()->pluck('skill_definition_id')->all();

        SkillDefinition::query()
            ->active()
            ->availableTo($character)
            ->whereNotIn('id', $existing ?: [0])
            ->orderBy('sort_order')
            ->get()
            ->each(fn (SkillDefinition $definition) => $character->skills()->create([
                'skill_definition_id' => $definition->id,
                'bonus' => 0,
                'reveal_state' => RevealState::Revealed,
            ]));
    }

    /**
     * Crée une ligne d'affinité par école de magie, au niveau « Inconnue ».
     * Le MJ décidera du potentiel réel de l'enfant.
     */
    public function syncAffinities(Character $character): void
    {
        $existing = $character->affinities()->pluck('magic_school_id')->all();

        MagicSchool::query()
            ->active()
            ->whereNotIn('id', $existing ?: [0])
            ->orderBy('sort_order')
            ->get()
            ->each(fn (MagicSchool $school) => $character->affinities()->create([
                'magic_school_id' => $school->id,
                'affinity_level' => 0,
                'reveal_state' => RevealState::Hidden,
            ]));
    }

    /** Attache une maîtrise au personnage (ou récupère celle déjà présente). */
    public function attachMastery(Character $character, MasteryDefinition $definition, array $attributes = []): CharacterMastery
    {
        return $character->masteries()->firstOrCreate(
            ['mastery_definition_id' => $definition->id],
            [
                'rank_index' => 0,
                'progress' => 0,
                'reveal_state' => RevealState::Hidden,
                ...$attributes,
            ]
        );
    }

    /** Attache une capacité au personnage (ou récupère celle déjà présente). */
    public function attachAbility(Character $character, AbilityDefinition $definition, array $attributes = []): CharacterAbility
    {
        return $character->abilities()->firstOrCreate(
            ['ability_definition_id' => $definition->id],
            [
                'unlocked' => true,
                'reveal_state' => RevealState::Hidden,
                ...$attributes,
            ]
        );
    }

    /** Attache une affinité précise (utilisé par les seeders et le MJ). */
    public function attachAffinity(Character $character, MagicSchool $school, array $attributes = []): CharacterAffinity
    {
        return $character->affinities()->firstOrCreate(
            ['magic_school_id' => $school->id],
            [
                'affinity_level' => 0,
                'reveal_state' => RevealState::Hidden,
                ...$attributes,
            ]
        );
    }
}
