<?php

namespace App\Http\Controllers;

use App\Models\GameMap;
use App\Services\World\MapPreviewService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MapPreviewController extends Controller
{
    public function __invoke(Request $request, GameMap $map, MapPreviewService $previews): Response
    {
        $user = $request->user();
        $isGameMaster = $user->isGameMaster();

        if (! $isGameMaster) {
            abort_unless($map->discoveredBy()->whereKey($user->getKey())->exists(), 404);
        }

        $map->load('cellReveals');
        $etag = '"'.sha1(implode('|', [
            $map->getKey(),
            $map->updated_at?->getTimestamp(),
            $isGameMaster ? 'gm' : 'player',
            $map->cellReveals->pluck('updated_at')->map->getTimestamp()->max(),
            $map->cellReveals->count(),
        ])).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response($previews->render($map, withFog: ! $isGameMaster), 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=300',
            'ETag' => $etag,
        ]);
    }
}
