<?php

namespace App\Services\CharacterSheet;

use App\Models\Character;
use App\Models\CharacterState;

/**
 * Gestion des états temporaires ou permanents (Blessé, Béni, Maudit...).
 *
 * Pensé pour la table de jeu : poser ou retirer un état doit être l'affaire
 * d'un clic pendant la partie.
 */
class CharacterStateService
{
    public function add(Character $character, array $data): CharacterState
    {
        return $character->states()->create([
            'visible_to_player' => true,
            'is_active' => true,
            ...$data,
        ]);
    }

    public function update(CharacterState $state, array $data): CharacterState
    {
        $state->update($data);

        return $state->refresh();
    }

    /** Désactive un état sans effacer l'historique de la fiche. */
    public function deactivate(CharacterState $state): CharacterState
    {
        $state->update(['is_active' => false]);

        return $state->refresh();
    }

    public function remove(CharacterState $state): void
    {
        $state->delete();
    }

    /**
     * Raccourcis proposés au MJ, issus de la configuration.
     *
     * @return list<array<string, mixed>>
     */
    public function presets(): array
    {
        return config('jdr.character.state_presets', []);
    }
}
