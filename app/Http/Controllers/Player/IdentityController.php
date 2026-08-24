<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdateIdentityRequest;
use App\Http\Requests\Player\UpdatePortraitRequest;
use App\Models\Character;
use App\Services\CharacterPortraitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Identité et portrait, tenus par le joueur lui-même.
 *
 * Rien ici ne touche aux chiffres de jeu : le joueur décrit son personnage, il
 * ne le renforce pas. Les caractéristiques restent modifiables ailleurs, et
 * l'origine n'est jamais rejouable depuis cet écran.
 */
class IdentityController extends Controller
{
    public function update(UpdateIdentityRequest $request): RedirectResponse
    {
        $this->character($request)->update($request->payload());

        return back()->with('success', 'Ta fiche a été mise à jour.');
    }

    /**
     * Remplace le portrait du personnage.
     *
     * Le fichier va sur le disque public — un portrait n'a rien de secret — et
     * l'ancien est supprimé au passage pour ne pas accumuler d'orphelins.
     */
    public function updatePortrait(UpdatePortraitRequest $request, CharacterPortraitService $portraits): RedirectResponse
    {
        $character = $this->character($request);

        $portraits->replace($character, $request->file('portrait'));

        return back()->with('success', 'Ton portrait a été mis à jour.');
    }

    public function destroyPortrait(Request $request, CharacterPortraitService $portraits): RedirectResponse
    {
        $character = $this->character($request);

        $portraits->remove($character);

        return back()->with('success', 'Portrait retiré.');
    }

    private function character(Request $request): Character
    {
        return $request->user()->character()->firstOrFail();
    }
}
