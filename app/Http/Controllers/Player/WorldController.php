<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\GameMap;
use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cartes côté joueur.
 *
 * Le joueur bascule librement d'une carte révélée à l'autre et pose ses
 * propres repères. Il ne reçoit que les cases ouvertes : la vue construit sa
 * grille à partir de $revealed, et les tuiles fermées ne sont même pas
 * demandées au serveur.
 */
class WorldController extends Controller
{
    public function index(Request $request): View
    {
        $maps = $request->user()->discoveredMaps()->orderBy('sort_order')->get();

        return view('player.world.index', compact('maps'));
    }

    public function show(Request $request, GameMap $map): View
    {
        $user = $request->user();

        abort_unless($user->discoveredMaps()->whereKey($map->id)->exists(), 404);

        $map->load('cellReveals');

        return view('player.world.show', [
            'map' => $map,
            // Sélecteur de carte : le joueur change de carte sans repasser par l'index.
            'maps' => $user->discoveredMaps()->orderBy('sort_order')->get(),
            'revealed' => $map->revealedCellKeys(),
            'points' => $this->visiblePoints($map, $user->getKey()),
        ]);
    }

    /**
     * Repères visibles du joueur : les siens, plus ceux que le MJ a ouverts à
     * la table. Ceux des autres joueurs ne le regardent pas.
     *
     * @return Collection<int, MapPoint>
     */
    private function visiblePoints(GameMap $map, int $userId)
    {
        return $map->points()
            ->with('author')
            ->where(fn ($query) => $query
                ->where('user_id', $userId)
                ->orWhere('is_visible_to_players', true))
            ->get();
    }
}
