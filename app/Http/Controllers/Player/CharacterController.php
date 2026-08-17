<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Services\CharacterSheet\CharacterSheetPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fiche personnage côté joueur.
 *
 * La vue ne reçoit QUE le tableau produit par forPlayer() : le modèle complet
 * n'est jamais passé au template, ce qui rend structurellement impossible
 * l'affichage — ou la fuite dans le HTML — d'une donnée non révélée.
 */
class CharacterController extends Controller
{
    public function __invoke(Request $request, CharacterSheetPresenter $presenter): View|RedirectResponse
    {
        $character = $request->user()->character()->first();

        // Le joueur n'a pas encore de personnage : on l'envoie naître. Un
        // personnage sans maison reste consultable — l'origine se découvre
        // depuis le parcours de création, sans bloquer l'accès à la fiche.
        if ($character === null) {
            return redirect()->route('player.creation.show');
        }

        $character->load(CharacterSheetPresenter::RELATIONS);

        return view('player.character', [
            'sheet' => $presenter->forPlayer($character),
        ]);
    }
}
