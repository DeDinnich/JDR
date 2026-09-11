<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdateMonsterKnowledgeRequest;
use App\Models\Monster;
use App\Services\Bestiary\MonsterPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BestiaryController extends Controller
{
    public function index(Request $request, MonsterPresenter $presenter): View
    {
        return view('player.bestiary.index', [
            'monsters' => $presenter->bestiaryFor($request->user()),
        ]);
    }

    public function show(Request $request, Monster $monster, MonsterPresenter $presenter): View
    {
        $entry = $presenter->forPlayer($monster, $request->user());
        abort_if($entry === null, 404);

        return view('player.bestiary.show', ['monster' => $entry]);
    }

    public function update(UpdateMonsterKnowledgeRequest $request, Monster $monster): RedirectResponse
    {
        $request->user()->discoveredMonsters()->updateExistingPivot($monster->id, $request->validated());

        return back()->with('success', 'Tes déductions ont été enregistrées.');
    }
}
