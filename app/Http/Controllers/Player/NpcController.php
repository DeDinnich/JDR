<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdateNpcNotesRequest;
use App\Models\Npc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NpcController extends Controller
{
    public function show(Request $request, Npc $npc): View
    {
        $npc = $request->user()->discoveredNpcs()->with('location.map')->whereKey($npc->id)->firstOrFail();

        return view('player.npcs.show', compact('npc'));
    }

    public function update(UpdateNpcNotesRequest $request, Npc $npc): RedirectResponse
    {
        $request->user()->discoveredNpcs()->updateExistingPivot($npc->id, $request->validated());

        return back()->with('success', 'Votre perception de ce personnage a été mise à jour.');
    }
}
