<?php

namespace App\Http\Controllers\Gm;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gm\MapUploadRequest;
use App\Models\GameMap;
use App\Models\MapCellReveal;
use App\Models\User;
use App\Services\World\MapTileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Cartes quadrillées, côté MJ.
 *
 * Le MJ importe une image, choisit la finesse du quadrillage, puis ouvre les
 * cases au fil de l'exploration. L'ouverture d'une case est un appel AJAX :
 * en partie, on clique sur la grille, on ne recharge pas la page.
 */
class MapGridController extends Controller
{
    public function store(MapUploadRequest $request, MapTileService $tiles): RedirectResponse
    {
        $map = GameMap::create([
            'title' => $request->string('title')->value(),
            'slug' => Str::slug($request->string('title')->value()).'-'.Str::lower(Str::random(4)),
            'description' => $request->input('description'),
            'grid_columns' => $request->integer('grid_columns'),
            'grid_rows' => $request->integer('grid_rows'),
            'is_active' => true,
            'sort_order' => (GameMap::max('sort_order') ?? 0) + 1,
        ]);

        $map->update($tiles->slice(
            $map,
            $request->file('image'),
            $request->integer('grid_columns'),
            $request->integer('grid_rows'),
        ));

        return redirect()
            ->route('gm.maps.grid', $map)
            ->with('success', 'Carte importée et découpée. Toutes les cases sont dans le noir.');
    }

    /** Écran de pilotage d'une carte : quadrillage, cases, points. */
    public function show(GameMap $map, Request $request)
    {
        $map->load('cellReveals', 'points.author', 'discoveredBy');

        return view('gm.world.grid', [
            'map' => $map,
            'revealed' => $map->revealedCellKeys(),
            'players' => User::query()
                ->where('role', UserRole::Player->value)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Change la finesse du quadrillage.
     *
     * Les cases déjà ouvertes ne veulent plus rien dire sur une grille
     * différente : on repart donc du noir, plutôt que de laisser le MJ avec
     * des ouvertures décalées qu'il devrait corriger une par une.
     */
    public function updateGrid(Request $request, GameMap $map, MapTileService $tiles): RedirectResponse
    {
        $data = $request->validate([
            'grid_columns' => ['required', 'integer', 'min:1', 'max:'.MapTileService::MAX_COLUMNS],
            'grid_rows' => ['required', 'integer', 'min:1', 'max:'.MapTileService::MAX_ROWS],
        ]);

        $tiles->reslice($map, $data['grid_columns'], $data['grid_rows']);
        $map->cellReveals()->delete();
        $map->update($data);

        return back()->with('success', 'Quadrillage refait. Les cases sont de nouveau fermées.');
    }

    /** Ouvre ou referme une case. Appelé en AJAX depuis la grille. */
    public function toggleCell(Request $request, GameMap $map): JsonResponse
    {
        $data = $request->validate([
            'column' => ['required', 'integer', 'min:0', 'max:'.($map->grid_columns - 1)],
            'row' => ['required', 'integer', 'min:0', 'max:'.($map->grid_rows - 1)],
        ]);

        $existing = MapCellReveal::query()
            ->where('map_id', $map->getKey())
            ->where('column', $data['column'])
            ->where('row', $data['row'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['revealed' => false]);
        }

        MapCellReveal::create([...$data, 'map_id' => $map->getKey()]);

        return response()->json(['revealed' => true]);
    }

    /**
     * Décide qui possède la carte.
     *
     * `sync` est volontaire : décocher un joueur lui retire la carte, ce qui
     * permet de refermer une région après coup. Les cases ouvertes ne sont pas
     * touchées — un joueur à qui on redonne la carte la retrouve telle quelle.
     */
    public function updateAccess(Request $request, GameMap $map): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        // On refiltre sur le rôle : un identifiant bricolé dans le formulaire
        // ne doit pas pouvoir viser le compte MJ.
        $players = User::query()
            ->whereIn('id', $data['user_ids'] ?? [])
            ->where('role', UserRole::Player->value)
            ->pluck('id');

        $map->discoveredBy()->sync(
            $players->mapWithKeys(fn (int $id) => [$id => ['discovered_at' => now()]])->all()
        );

        return back()->with('success', 'Accès à la carte mis à jour.');
    }

    /** Ouvre ou referme toute la carte d'un coup. */
    public function toggleAllCells(GameMap $map, Request $request): RedirectResponse
    {
        if ($request->boolean('reveal')) {
            $rows = [];

            for ($row = 0; $row < $map->grid_rows; $row++) {
                for ($column = 0; $column < $map->grid_columns; $column++) {
                    $rows[] = [
                        'map_id' => $map->getKey(),
                        'column' => $column,
                        'row' => $row,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // insertOrIgnore s'appuie sur la contrainte d'unicité : rejouer
            // l'action ne crée pas de doublon.
            MapCellReveal::query()->insertOrIgnore($rows);

            return back()->with('success', 'Carte entièrement révélée.');
        }

        $map->cellReveals()->delete();

        return back()->with('success', 'Carte replongée dans le noir.');
    }

    public function destroy(GameMap $map, MapTileService $tiles): RedirectResponse
    {
        $tiles->forget($map);
        $map->delete();

        return redirect()->route('gm.world.index')->with('success', 'Carte supprimée.');
    }
}
