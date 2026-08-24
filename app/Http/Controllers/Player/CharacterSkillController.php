<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\CharacterSkill;
use App\Services\CharacterSheet\SkillBonusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterSkillController extends Controller
{
    public function update(Request $request, CharacterSkill $skill, SkillBonusService $service): JsonResponse
    {
        $character = $request->user()->character;
        abort_unless(
            $character && $skill->character_id === $character->getKey() && $skill->reveal_state->isDiscovered(),
            404,
        );

        $data = $request->validate(['player_bonus' => ['required', 'integer', 'min:-50', 'max:50']]);

        return response()->json($service->update($skill, $data));
    }
}
