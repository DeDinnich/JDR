<?php

namespace App\Services\CharacterSheet;

use App\Models\Character;
use App\Models\CharacterAttribute;
use App\Models\CharacterSkill;
use Illuminate\Support\Collection;

/**
 * Toutes les formules chiffrées de la fiche personnage.
 *
 * C'est le SEUL endroit où l'on calcule quoi que ce soit : ni les vues ni les
 * contrôleurs ne font d'arithmétique. Faire évoluer la mécanique de jeu revient
 * donc à modifier ce fichier (ou les valeurs de config('jdr.character.formulas')).
 */
class StatFormulaService
{
    /**
     * Pourcentage de réussite d'une compétence, testé au D100.
     *
     * Deux temps : on combine les caractéristiques concernées (moyenne par
     * défaut), puis on convertit le résultat en pourcentage. Le bonus du MJ
     * s'ajoute en points de pourcentage — un « +10 » vaut donc dix points de
     * réussite, pas dix points de caractéristique.
     *
     * Le résultat est borné : une compétence ne descend pas sous 0 % et ne
     * dépasse pas 100 %, sans quoi le jet de dé n'aurait plus de sens.
     *
     * @param  array<string, int>  $attributeValues  valeurs indexées par code de caractéristique
     */
    public function skillValue(CharacterSkill $skill, array $attributeValues): int
    {
        return $this->clampPercentage($this->skillBaseValue($skill, $attributeValues) + $skill->bonus);
    }

    /**
     * Pourcentage issu des seules caractéristiques, avant bonus du MJ.
     *
     * @param  array<string, int>  $attributeValues
     */
    public function skillBaseValue(CharacterSkill $skill, array $attributeValues): int
    {
        $definition = $skill->definition;

        $primary = $attributeValues[$definition->primaryAttribute->code] ?? 0;
        $secondary = $definition->secondaryAttribute
            ? ($attributeValues[$definition->secondaryAttribute->code] ?? 0)
            : null;

        $combined = match ($definition->formula) {
            'primary' => $primary,
            'sum' => $primary + ($secondary ?? 0),
            'best' => max($primary, $secondary ?? $primary),
            // 'average' : comportement par défaut.
            default => $secondary === null ? $primary : ($primary + $secondary) / 2,
        };

        $formulas = config('jdr.character.formulas');
        $percentage = (int) floor($combined * $formulas['skill_percentage_per_point']);

        // Coup de pouce des débuts : sous le seuil, on ajoute le bonus. Voir la
        // note dans config/jdr.php — c'est une addition, pas un plancher.
        if ($percentage < $formulas['skill_low_threshold']) {
            $percentage += $formulas['skill_low_bonus'];
        }

        return $this->clampPercentage($percentage);
    }

    private function clampPercentage(int $value): int
    {
        $formulas = config('jdr.character.formulas');

        return max($formulas['skill_percentage_min'], min($formulas['skill_percentage_max'], $value));
    }

    /**
     * Mana maximum.
     *
     * Dérivé du potentiel magique (MAN). Une valeur forcée par le MJ sur le
     * personnage (mana_max) prend le pas sur la formule.
     */
    public function manaMax(Character $character, ?int $manaAttributeValue = null): int
    {
        if ($character->mana_max !== null) {
            return $character->mana_max;
        }

        $manaAttributeValue ??= $this->attributeValue($character, 'man');
        $formulas = config('jdr.character.formulas');

        return max(0, $manaAttributeValue * $formulas['mana_max_per_man'] + $formulas['mana_max_flat_bonus']);
    }

    /** Valeur effective d'une caractéristique par son code (ex. 'man'). */
    public function attributeValue(Character $character, string $code): int
    {
        $attribute = $character->attributes
            ->first(fn (CharacterAttribute $item) => $item->definition?->code === $code);

        return $attribute?->effectiveValue() ?? 0;
    }

    /**
     * Toutes les valeurs effectives indexées par code, telles que consommées
     * par le calcul des compétences.
     *
     * @return array<string, int>
     */
    public function attributeValues(Character $character): array
    {
        return $character->attributes
            ->filter(fn (CharacterAttribute $attribute) => $attribute->definition !== null)
            ->mapWithKeys(fn (CharacterAttribute $attribute) => [
                $attribute->definition->code => $attribute->effectiveValue(),
            ])
            ->all();
    }

    /**
     * Modificateurs cumulés apportés par les états actifs, par code de
     * caractéristique. Un personnage Blessé peut voir sa FOR chuter.
     *
     * @return array<string, int>
     */
    public function stateModifiers(Character $character): array
    {
        return $character->states
            ->where('is_active', true)
            ->reduce(function (Collection $carry, $state) {
                foreach ($state->modifiers ?? [] as $code => $value) {
                    $carry[$code] = ($carry[$code] ?? 0) + (int) $value;
                }

                return $carry;
            }, collect())
            ->all();
    }
}
