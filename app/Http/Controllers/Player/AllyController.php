<?php

namespace App\Http\Controllers\Player;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AllyController extends Controller
{
    public function show(Request $request, Character $character, CharacterSheetPresenter $presenter): View
    {
        abort_if($character->user_id === $request->user()->getKey(), 404);
        abort_unless($character->user?->role === UserRole::Player, 404);

        $character->load(CharacterSheetPresenter::RELATIONS);

        return view('player.allies.show', ['sheet' => $presenter->forAlly($character)]);
    }
}
