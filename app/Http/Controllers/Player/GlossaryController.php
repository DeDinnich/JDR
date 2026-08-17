<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdateNpcNotesRequest;
use App\Models\Npc;
use App\Services\Campaign\NpcPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Glossaire des personnages rencontrés.
 *
 * La vue ne reçoit que le tableau produit par NpcPresenter : ni les notes MJ,
 * ni les secrets, ni les informations non révélées à CE joueur n'atteignent le
 * template. Un PNJ jamais rencontré n'existe tout simplement pas ici.
 */
class GlossaryController extends Controller
{
    public function index(Request $request, NpcPresenter $presenter): View
    {
        return view('player.glossary.index', [
            'npcs' => $presenter->glossaryFor($request->user()),
        ]);
    }

    public function show(Request $request, Npc $npc, NpcPresenter $presenter): View
    {
        $entry = $presenter->forPlayer($npc, $request->user());

        // Un PNJ non révélé se comporte comme s'il n'existait pas : pas de 403
        // qui confirmerait son existence, un simple 404.
        abort_if($entry === null, 404);

        return view('player.glossary.show', ['npc' => $entry]);
    }

    /** Notes personnelles : propres au joueur, sans effet sur la fiche officielle. */
    public function updateNotes(UpdateNpcNotesRequest $request, Npc $npc): RedirectResponse
    {
        abort_unless($request->user()->discoveredNpcs()->whereKey($npc->id)->exists(), 404);

        $request->user()->discoveredNpcs()->updateExistingPivot($npc->id, $request->validated());

        return back()->with('success', 'Tes notes ont été enregistrées.');
    }
}
