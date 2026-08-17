<?php

namespace App\Http\Controllers\Player;

use App\Events\HouseTaken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ChooseHouseRequest;
use App\Http\Requests\Player\CreateCharacterRequest;
use App\Models\Character;
use App\Models\House;
use App\Services\Campaign\CharacterCreationService;
use App\Services\Campaign\HouseAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Naissance d'un personnage joueur.
 *
 * Deux temps : une identité minimale, puis le choix de la grande maison. Ce
 * choix est présenté dans une fenêtre que le joueur ne peut pas fermer — tant
 * qu'il n'a pas d'origine, il n'a rien à faire dans le reste du site.
 *
 * Le compte à origine réservée ne voit jamais cet écran : sa maison lui est
 * attribuée dès la création, côté serveur, et les autres maisons ne lui sont
 * même pas transmises.
 */
class CharacterCreationController extends Controller
{
    public function show(Request $request, HouseAssignmentService $houses): View|RedirectResponse
    {
        $user = $request->user();
        $character = $user->character()->first();

        // Personnage déjà complet : plus rien à créer.
        if ($character?->house_id !== null) {
            return redirect()->route('player.character');
        }

        return view('player.creation.show', [
            'character' => $character,
            'step' => $character === null ? 'identity' : 'origin',
            'hasReservedOrigin' => $houses->hasReservedOrigin($user),
            'houses' => $houses->choosableHouses(),
        ]);
    }

    public function store(CreateCharacterRequest $request, CharacterCreationService $creation): RedirectResponse
    {
        $creation->create($request->user(), $request->validated());

        return redirect()->route('player.creation.show');
    }

    /**
     * Le joueur choisit sa grande maison.
     *
     * Le grisage affiché à l'écran n'est qu'un confort : c'est la revendication
     * verrouillée en base qui tranche. Si deux joueurs cliquent sur la même
     * maison au même instant, le second repart avec une erreur et rejoue.
     */
    public function chooseOrigin(ChooseHouseRequest $request, CharacterCreationService $creation): RedirectResponse
    {
        $user = $request->user();
        $character = $user->character()->first();

        if (! $character instanceof Character) {
            return redirect()->route('player.creation.show');
        }

        // Une origine déjà posée ne se rejoue pas : sans ce garde-fou, un
        // joueur pourrait changer de maison en rappelant la route.
        if ($character->house_id !== null) {
            return redirect()->route('player.character');
        }

        $house = $creation->chooseOrigin($character, $user, $request->string('house')->value());

        if (! $house instanceof House) {
            return back()->withErrors([
                'house' => 'Cette maison vient d’être choisie par quelqu’un d’autre.',
            ]);
        }

        HouseTaken::dispatch($house, $user->name);

        return redirect()->route('player.creation.origin');
    }

    /** Mise en scène de l'origine obtenue, une fois la maison acquise. */
    public function showOrigin(Request $request): View|RedirectResponse
    {
        $character = $request->user()->character()->with('house')->first();

        if (! $character instanceof Character || $character->house_id === null) {
            return redirect()->route('player.creation.show');
        }

        return view('player.creation.origin', [
            'house' => $character->house->publicPayload(),
            'parents' => $character->relatives()
                ->get()
                ->map(fn ($parent) => [
                    'name' => $parent->fullName(),
                    'title' => $parent->title,
                    'relation' => $parent->pivot->relation === 'pere' ? 'Ton père' : 'Ta mère',
                    'description' => $parent->description,
                    'initials' => $parent->initials(),
                ])
                ->all(),
        ]);
    }
}
