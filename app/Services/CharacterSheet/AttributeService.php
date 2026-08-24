<?php

namespace App\Services\CharacterSheet;

use App\Events\CharacterSheetUpdated;
use App\Models\CharacterAttribute;

class AttributeService
{
    public function __construct(private readonly CharacterSheetPresenter $presenter) {}

    /** @return array<string, mixed> */
    public function update(CharacterAttribute $attribute, array $changes): array
    {
        $attribute->update($changes);
        $character = $attribute->character;
        $character->load(CharacterSheetPresenter::RELATIONS);
        $sheet = $this->presenter->forGameMaster($character);
        $playerSheet = $this->presenter->forPlayer($character);
        $attributePayload = $sheet['attributes']->firstWhere('id', $attribute->getKey());
        $compactSkills = static fn ($skills) => $skills->flatten(1)->map(fn (array $skill) => [
            'id' => $skill['id'],
            'base_value' => $skill['base_value'],
            'gm_bonus' => $skill['gm_bonus'],
            'player_bonus' => $skill['player_bonus'],
            'bonus' => $skill['bonus'],
            'value' => $skill['value'],
            'display' => $skill['display'],
        ])->values()->all();
        $skillsPayload = $compactSkills($sheet['skills']);
        $payload = [
            'character_id' => $character->getKey(),
            'attributes' => [$attributePayload],
            'skills' => $skillsPayload,
            'resources' => $sheet['resources'],
        ];

        event(new CharacterSheetUpdated($character->getKey(), [
            ...$payload,
            'skills' => $compactSkills($playerSheet['skills']),
        ]));

        return $payload;
    }
}
