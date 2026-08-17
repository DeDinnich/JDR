<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\UpdateIdentityRequest;
use App\Http\Requests\Player\UpdatePortraitRequest;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    public function updatePortrait(UpdatePortraitRequest $request): RedirectResponse
    {
        $character = $this->character($request);

        $path = $request->file('portrait')->store('portraits', 'public');

        $this->forgetPreviousPortrait($character);

        $character->update(['portrait_path' => Storage::disk('public')->url($path)]);

        return back()->with('success', 'Ton portrait a été mis à jour.');
    }

    public function destroyPortrait(Request $request): RedirectResponse
    {
        $character = $this->character($request);

        $this->forgetPreviousPortrait($character);
        $character->update(['portrait_path' => null]);

        return back()->with('success', 'Portrait retiré.');
    }

    /**
     * Supprime le fichier du portrait précédent, s'il nous appartient.
     *
     * Le champ peut contenir une URL externe saisie par le MJ : on ne tente
     * alors évidemment pas de supprimer quoi que ce soit sur le disque.
     */
    private function forgetPreviousPortrait(Character $character): void
    {
        $previous = $character->portrait_path;

        if ($previous === null || ! str_contains($previous, '/storage/portraits/')) {
            return;
        }

        $relative = 'portraits/'.basename($previous);

        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function character(Request $request): Character
    {
        return $request->user()->character()->firstOrFail();
    }
}
