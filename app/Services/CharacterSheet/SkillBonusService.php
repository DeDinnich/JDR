<?php

namespace App\Services\CharacterSheet;

use App\Events\CharacterSkillUpdated;
use App\Models\CharacterSkill;

class SkillBonusService
{
    public function __construct(private readonly CharacterSheetPresenter $presenter) {}

    /** @return array<string, mixed> */
    public function update(CharacterSkill $skill, array $changes, bool $forGameMaster = false): array
    {
        $skill->update($changes);
        $character = $skill->character;
        $character->load(CharacterSheetPresenter::RELATIONS);

        $gameMasterPayload = $this->presenter->forGameMaster($character)['skills']
            ->flatten(1)
            ->firstWhere('id', $skill->getKey());
        $playerPayload = $this->presenter->forPlayer($character)['skills']
            ->flatten(1)
            ->firstWhere('id', $skill->getKey());

        if ($playerPayload !== null) {
            event(new CharacterSkillUpdated($character->getKey(), $playerPayload));
        }

        return $forGameMaster ? $gameMasterPayload : $playerPayload;
    }
}
