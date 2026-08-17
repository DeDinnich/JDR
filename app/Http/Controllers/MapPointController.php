<?php

namespace App\Http\Controllers;

use App\Models\GameMap;
use App\Models\MapPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Repères posés sur une carte, par le MJ comme par les joueurs.
 *
 * Chacun est maître des siens : on ne peut modifier ou supprimer que ses
 * propres points. Le MJ décide en plus si les siens sont visibles de la table.
 */
class MapPointController extends Controller
{
    public function store(Request $request, GameMap $map): JsonResponse
    {
        $user = $request->user();

        // Un joueur ne pose de point que sur une carte qu'il possède.
        abort_unless(
            $user->isGameMaster() || $map->discoveredBy()->whereKey($user->getKey())->exists(),
            404,
        );

        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'x_position' => ['required', 'numeric', 'min:0', 'max:100'],
            'y_position' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_visible_to_players' => ['nullable', 'boolean'],
        ]);

        $point = $map->points()->create([
            ...$data,
            'user_id' => $user->getKey(),
            'color' => $data['color'] ?? ($user->isGameMaster() ? '#c0392b' : '#c9a227'),
            // Un joueur ne peut pas publier un point à toute la table : ses
            // repères restent les siens.
            'is_visible_to_players' => $user->isGameMaster() && $request->boolean('is_visible_to_players'),
        ]);

        return response()->json([
            'id' => $point->id,
            'label' => $point->label,
            'color' => $point->color,
            'x' => $point->x_position,
            'y' => $point->y_position,
        ], 201);
    }

    public function destroy(Request $request, GameMap $map, MapPoint $point): JsonResponse
    {
        abort_unless($point->map_id === $map->getKey(), 404);
        // Chacun ne retire que ses propres repères, MJ compris.
        abort_unless($point->user_id === $request->user()->getKey(), 403);

        $point->delete();

        return response()->json(['deleted' => true]);
    }
}
