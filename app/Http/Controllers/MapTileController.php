<?php

namespace App\Http\Controllers;

use App\Models\GameMap;
use App\Models\MapCellReveal;
use App\Services\World\MapTileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert une tuile de carte, et elle seule.
 *
 * ── Point de sécurité ─────────────────────────────────────────────────────
 * C'est ici que se joue le brouillard. Les tuiles sont sur le disque privé :
 * la seule façon d'en obtenir une est de passer par cette route, qui vérifie
 * que la case est ouverte et que la carte est révélée au demandeur. Un joueur
 * qui devine l'URL d'une case fermée reçoit 404, jamais l'image.
 */
class MapTileController extends Controller
{
    public function __invoke(Request $request, GameMap $map, int $row, int $column, MapTileService $tiles): Response
    {
        $user = $request->user();
        $isGameMaster = $user->isGameMaster();

        // Le MJ voit tout ; un joueur doit d'abord avoir la carte.
        if (! $isGameMaster) {
            abort_unless($map->discoveredBy()->whereKey($user->getKey())->exists(), 404);

            abort_unless(
                MapCellReveal::query()
                    ->where('map_id', $map->getKey())
                    ->where('column', $column)
                    ->where('row', $row)
                    ->exists(),
                404,
            );
        }

        $contents = $tiles->tileContents($map, $column, $row);

        abort_if($contents === null, 404);

        return response($contents, 200, [
            'Content-Type' => 'image/webp',
            // Privé : une tuile révélée à un joueur ne doit pas être mise en
            // cache par un intermédiaire partagé.
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
