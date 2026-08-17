<?php

namespace App\Models\Concerns;

use App\Enums\RevealState;
use App\Models\Character;

/**
 * Implémentation commune de RevealableSheetEntry.
 *
 * Les cinq éléments révélables d'une fiche partagent la même colonne
 * `reveal_state` et la même relation `character` : seuls les libellés
 * changent, et ceux-là restent définis dans chaque modèle.
 */
trait Revealable
{
    public function getCharacter(): Character
    {
        return $this->character;
    }

    public function getRevealState(): RevealState
    {
        return $this->reveal_state;
    }

    public function setRevealState(RevealState $state): static
    {
        $this->reveal_state = $state;

        return $this;
    }
}
