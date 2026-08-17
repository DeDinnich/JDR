<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tant qu'un joueur n'a pas d'origine, il n'a rien à faire ailleurs.
 *
 * Le blocage vit ici plutôt que dans un contrôleur : le choix de la maison est
 * censé être incontournable, or il existe plusieurs façons d'obtenir un
 * personnage (inscription directe ou parcours de création). Un garde-fou posé
 * sur un seul écran laisserait les autres pages accessibles.
 */
class EnsureOriginChosen
{
    /** Routes qui doivent rester joignables : celles qui servent à choisir. */
    private const ALLOWED = [
        'player.creation.show',
        'player.creation.store',
        'player.creation.choose',
        'player.creation.origin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $request->routeIs(self::ALLOWED)) {
            return $next($request);
        }

        $character = $user->character()->first();

        // Pas encore de personnage, ou un personnage sans origine : dans les
        // deux cas le parcours de naissance reprend la main.
        if ($character === null || $character->house_id === null) {
            return redirect()->route('player.creation.show');
        }

        return $next($request);
    }
}
