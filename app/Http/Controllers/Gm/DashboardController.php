<?php

namespace App\Http\Controllers\Gm;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SecretMessage;
use App\Models\User;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(CharacterSheetPresenter $presenter): View
    {
        $players = User::query()
            ->where('role', UserRole::Player->value)
            ->with(['character.attributes.definition', 'character.states'])
            ->orderBy('name')
            ->get();
        $messages = SecretMessage::query()->with('recipient')->latest()->limit(12)->get();
        $playerCards = $players->map(fn (User $player) => [
            'player' => $player,
            'character' => $player->character,
            'resources' => $presenter->resources($player->character),
        ]);

        return view('gm.dashboard', compact('players', 'playerCards', 'messages'));
    }
}
