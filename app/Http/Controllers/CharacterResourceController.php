<?php

namespace App\Http\Controllers;

use App\Events\CharacterResourcesUpdated;
use App\Models\Character;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use App\Services\CharacterSheet\StatFormulaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterResourceController extends Controller
{
    public function update(
        Request $request,
        Character $character,
        CharacterSheetPresenter $presenter,
        StatFormulaService $formulas,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user->isGameMaster() || $character->user_id === $user->getKey(), 403);

        $data = $request->validate([
            'resource' => ['required', 'in:health,mana_current'],
            'value' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $maximum = $data['resource'] === 'health'
            ? $character->max_health
            : $formulas->manaMax($character);

        $character->update([$data['resource'] => min($data['value'], $maximum)]);
        $character->refresh();
        $event = CharacterResourcesUpdated::from($character, $presenter);
        broadcast($event)->toOthers();

        return response()->json($event->broadcastWith());
    }
}
