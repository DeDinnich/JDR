<?php

namespace App\Contracts;

use App\Enums\RevealState;
use App\Models\Character;

/**
 * Élément de fiche personnage dont la connaissance par le joueur peut être
 * ouverte progressivement par le MJ (caractéristique, compétence, maîtrise,
 * affinité, capacité).
 *
 * Permet au CharacterRevealService de traiter uniformément les cinq types
 * révélables sans multiplier les branches conditionnelles.
 */
interface RevealableSheetEntry
{
    public function getCharacter(): Character;

    public function getRevealState(): RevealState;

    public function setRevealState(RevealState $state): static;

    /** Titre affiché dans la notification de révélation, ex. « Dextérité ». */
    public function revealHeadline(): string;

    /** Texte affiché sous le titre, ex. « Ta dextérité est de 11 ». */
    public function revealDescription(): string;

    /** Famille d'élément, utilisée pour le libellé et le journal. */
    public function revealKind(): string;
}
