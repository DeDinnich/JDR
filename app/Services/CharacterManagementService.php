<?php

namespace App\Services;

use App\Models\Character;
use App\Models\InventoryItem;

/**
 * Opérations générales sur un personnage (identité, ressources, inventaire).
 *
 * Tout ce qui concerne la mécanique de fiche — caractéristiques, compétences,
 * maîtrises, affinités, capacités, révélations — vit dans
 * App\Services\CharacterSheet.
 */
class CharacterManagementService
{
    public function updateCharacter(Character $character, array $data): Character
    {
        $character->update($data);

        return $character->refresh();
    }

    public function addInventoryItem(Character $character, array $data): InventoryItem
    {
        return $character->inventoryItems()->create($data);
    }

    public function updateInventoryItem(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);

        return $item->refresh();
    }
}
